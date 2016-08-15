<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Params\StepActionTypes;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Ojs\JournalBundle\Service\JournalService;
use Ojs\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WorkflowPermissionService
{
    /**
     * @var JournalService
     */
    private $journalService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * WorkflowPermissionService constructor.
     * @param JournalService $journalService
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $em
     */
    public function __construct(
        JournalService $journalService,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em
    ) {
        $this->journalService   = $journalService;
        $this->tokenStorage     = $tokenStorage;
        $this->em               = $em;
    }

    /**
     * @return bool
     */
    public function isHaveEditorRole()
    {
        $user = $this->getUser();
        $journal = $this->journalService->getSelectedJournal();

        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            return true;
        }

        return false;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @return bool
     */
    public function isInWorkflowRelatedUsers(ArticleWorkflow $workflow)
    {
        $user = $this->getUser();
        $journal = $this->journalService->getSelectedJournal();

        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            return true;
        }
        if($workflow->relatedUsers->contains($user)){
            return true;
        }

        return false;
    }

    /**
     * @param ArticleWorkflowStep $step
     * @return bool
     */
    public function isGrantedForStep(ArticleWorkflowStep $step)
    {
        $user = $this->getUser();
        $journal = $this->journalService->getSelectedJournal();

        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            return true;
        }
        if($step->getArticleWorkflow()->getGrantedUsers()->contains($user)){
            return true;
        }
        if($step->grantedUsers->contains($user)){
            return true;
        }

        return false;
    }

    /**
     * @param StepDialog $dialog
     * @return bool
     */
    public function isGrantedForDialogPost(StepDialog $dialog)
    {
        $user = $this->getUser();
        $journal = $this->journalService->getSelectedJournal();

        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            return true;
        }
        if($dialog->getStep()->getArticleWorkflow()->getGrantedUsers()->contains($user)){
            return true;
        }
        if($dialog->getStep()->grantedUsers->contains($user)){
            return true;
        }
        if($dialog->users->contains($user)){
            return true;
        }

        return false;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @return bool
     */
    public function isReviewerOnWorkflow(ArticleWorkflow $workflow)
    {
        $dialogRepo = $this->em->getRepository(StepDialog::class);
        $dialogs = $dialogRepo
            ->createQueryBuilder('stepDialog')
            ->join('stepDialog.step', 'dialogStep')
            ->andWhere(':user MEMBER OF stepDialog.users')
            ->setParameter('user', $this->getUser())
            ->andWhere('stepDialog.dialogType = :dialogType')
            ->setParameter('dialogType', StepActionTypes::ASSIGN_REVIEWER)
            ->andWhere('dialogStep.articleWorkflow = :workflow')
            ->setParameter('workflow', $workflow)
            ->getQuery()
            ->getResult()
        ;

        return count($dialogs) > 0;
    }

    /**
     * @param array $searchRoles
     * @param array $roleBag
     * @return bool
     */
    public function haveLeastRole($searchRoles = [], $roleBag = [])
    {
        if(count(array_intersect($searchRoles, $roleBag)) > 0){
            return true;
        }

        return false;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if(!$token){
            throw new \LogicException('i can not find current user token :/');
        }
        return $token->getUser();
    }
}
