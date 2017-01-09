<?php

namespace Ojs\WorkflowBundle\EventListener;

use Ojs\AdminBundle\Events\MergeEvent;
use Ojs\AdminBundle\Events\MergeEvents;
use Ojs\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Ojs\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\WorkflowBundle\Entity\JournalWorkflowStep;
use Ojs\WorkflowBundle\Entity\StepDialog;
use Ojs\WorkflowBundle\Service\WorkflowService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MergeUserListener implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var WorkflowService
     */
    private $workflowService;

    /**
     * ArticleCrudListener constructor.
     *
     * @param EntityManager $em
     * @param WorkflowService $workflowService
     */
    public function __construct(
        EntityManager $em,
        WorkflowService $workflowService
    ) {
        $this->em               = $em;
        $this->workflowService  = $workflowService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            MergeEvents::OJS_ADMIN_USER_MERGE => 'onAdminUserMerge',
        );
    }

    /**
     * @param MergeEvent $event
     * @return null
     */
    public function onAdminUserMerge(MergeEvent $event)
    {
        $primaryUser = $event->getPrimaryUser();
        if(!$primaryUser instanceof User){
            return;
        }
        
        /** @var User[] $slaveUsers */
        $slaveUsers = $event->getPrimaryUser();
        if(!$slaveUsers){
            return;
        }
        foreach ($slaveUsers as $slaveUser) {
            if($primaryUser->getId() == $slaveUser->getId() || $slaveUser->getMerged() !== null){
                continue;
            }
            
            $this->migrateStepDialogs($primaryUser, $slaveUser);
            $this->migrateStepDialogUsers($primaryUser, $slaveUser);
            $this->migrateJournalWorkflowStep($primaryUser, $slaveUser);
            $this->migrateArticleWorkflowGrandted($primaryUser, $slaveUser);
            $this->migrateArticleWorkflowRelated($primaryUser, $slaveUser);
        }
        return true;
    }

    /**
     * @param User $primaryUser
     * @param User $slaveUser
     */
    private function migrateStepDialogs(User $primaryUser, User $slaveUser)
    {
        $stepDialogs = $this->em->getRepository(StepDialog::class)->findBy(['createdDialogBy' => $slaveUser->getId()]);

        if (!$stepDialogs) {
            return;
        }

        foreach ($stepDialogs as $stepDialog) {
            $stepDialog->setCreatedDialogBy($primaryUser);
            $this->em->persist($stepDialog);
        }
    }

    /**
     * @param User $primaryUser
     * @param User $slaveUser
     * @return bool|void
     */
    private function migrateStepDialogUsers(User $primaryUser, User $slaveUser)
    {

        /**  @var StepDialog[] $stepDialogs */
        $stepDialogs = $this->em->getRepository(StepDialog::class)->createQueryBuilder('sd')
            ->where(':slaveUser MEMBER OF sd.users')
            ->setParameter('slaveUser', $slaveUser)
            ->getQuery()
            ->getResult();

        if (!$stepDialogs) {
            return;
        }

        foreach ($stepDialogs as $stepDialog) {
            $stepDialog->addUser($primaryUser);
            $stepDialog->removeUser($slaveUser);
            $this->em->persist($stepDialog);
        }

        return true;
    }

    private function migrateJournalWorkflowStep(User $primaryUser, User $slaveUser)
    {
        /**  @var JournalWorkflowStep[] $journalWorkflowSteps */
        $journalWorkflowSteps = $this->em->getRepository(JournalWorkflowStep::class)->createQueryBuilder('jws')
            ->where(':slaveUser MEMBER OF jws.grantedUsers')
            ->setParameter('slaveUser', $slaveUser)
            ->getQuery()
            ->getResult();

        if (!$journalWorkflowSteps) {
            return;
        }

        foreach ($journalWorkflowSteps as $journalWorkflowStep) {
            $journalWorkflowStep->addGrantedUser($primaryUser);
            $journalWorkflowStep->removeGrantedUser($slaveUser);
            $this->em->persist($journalWorkflowStep);
        }

        return true;
    }

    private function migrateArticleWorkflowGrandted(User $primaryUser, User $slaveUser)
    {
        /**  @var ArticleWorkflow[] $articleWorkflows */
        $articleWorkflows = $this->em->getRepository(ArticleWorkflow::class)->createQueryBuilder('aw')
            ->where(':slaveUser MEMBER OF aw.grantedUsers')
            ->setParameter('slaveUser', $slaveUser)
            ->getQuery()
            ->getResult();

        if (!$articleWorkflows) {
            return;
        }

        foreach ($articleWorkflows as $articleWorkflow) {
            $articleWorkflow->addGrantedUser($primaryUser);
            $articleWorkflow->removeGrantedUser($slaveUser);
            $this->em->persist($articleWorkflow);
        }

        return true;
    }

    private function migrateArticleWorkflowRelated(User $primaryUser, User $slaveUser)
    {
        /**  @var ArticleWorkflow[] $articleWorkflows */
        $articleWorkflows = $this->em->getRepository(ArticleWorkflow::class)->createQueryBuilder('aw')
            ->where(':slaveUser MEMBER OF aw.relatedUsers')
            ->setParameter('slaveUser', $slaveUser)
            ->getQuery()
            ->getResult();

        if (!$articleWorkflows) {
            return;
        }

        foreach ($articleWorkflows as $articleWorkflow) {
            $articleWorkflow->addRelatedUser($primaryUser);
            $articleWorkflow->removeRelatedUser($slaveUser);
            $this->em->persist($articleWorkflow);
        }

        return true;
    }
}
