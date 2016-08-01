<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\JournalWorkflowStep;
use Dergipark\WorkflowBundle\Params\ArticleWorkflowStatus;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Exception\LogicException;
use Ojs\CoreBundle\Params\ArticleStatuses;
use Ojs\JournalBundle\Entity\Journal;
use Ojs\JournalBundle\Service\JournalService;
use Ojs\UserBundle\Entity\User;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Ojs\JournalBundle\Entity\Article;

class WorkflowService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var JournalService
     */
    private $journalService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EntityManager             $em
     * @param RouterInterface           $router
     * @param TranslatorInterface       $translator
     * @param JournalService            $journalService
     * @param TokenStorageInterface     $tokenStorage
     * @param RequestStack              $requestStack
     * @param EventDispatcherInterface  $eventDispatcher
     */
    public function __construct(
        EntityManager $em = null,
        RouterInterface $router = null,
        TranslatorInterface $translator = null,
        JournalService $journalService = null,
        TokenStorageInterface $tokenStorage = null,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->journalService = $journalService;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Article $article
     * @return ArticleWorkflow
     */
    public function prepareArticleWorkflow(Article $article)
    {
        say('prepare stated');
        $articleWorkflow = new ArticleWorkflow();
        $articleWorkflow
            ->setArticle($article)
            ->setJournal($this->journalService->getSelectedJournal())
            ->setStartDate(new \DateTime())
            ;

        foreach($this->currentJournalWorkflowSteps() as $step){
            system('say "journal step '.$step->getOrder().'"');
            $articleWorkflowStep = new ArticleWorkflowStep();
            $articleWorkflowStep
                ->setOrder($step->getOrder())
                ->setGrantedUsers($step->getGrantedUsers())
                ->setArticleWorkflow($articleWorkflow)
                ;
            foreach($step->getGrantedUsers() as $user){
                $articleWorkflow->addRelatedUser($user);
            }
            $this->em->persist($articleWorkflowStep);

            if($step->getOrder() == 1){
                system('say "set current step baby!"');
                $articleWorkflow->setCurrentStep($articleWorkflowStep);
            }
        }
        $articleWorkflow->addRelatedUser($article->getSubmitterUser());

        $this->em->persist($articleWorkflow);

        $article->setStatus(ArticleStatuses::STATUS_INREVIEW);

        $this->em->flush();

        return $articleWorkflow;
    }

    /**
     * @return array|JournalWorkflowStep[]
     */
    public function currentJournalWorkflowSteps()
    {
        say('journal workflow count '.count($this->em->getRepository(JournalWorkflowStep::class)->findAll()));
        return $this->em->getRepository(JournalWorkflowStep::class)->findAll();
    }

    /**
     * @param User|null $user
     * @param Journal|null $journal
     * @return array|ArticleWorkflow[]
     */
    public function getUserRelatedActiveWorkflows(User $user = null, Journal $journal = null)
    {
        if(!$user){
            $token = $this->tokenStorage->getToken();
            if(!$token){
                throw new LogicException('i can not find current user token :/');
            }
            $user = $token->getUser();
        }
        if(!$journal){
            $journal = $this->journalService->getSelectedJournal();
            if(!$journal){
                throw new LogicException('i can not find current journal');
            }
        }

        $fetchAll = false;
        $userJournalRoles = $user->getJournalRolesBag($journal);
        $specialRoles = ['ROLE_EDITOR', 'ROLE_CO_EDITOR'];
        if(count(array_intersect($specialRoles, $userJournalRoles)) > 0 || $user->isAdmin()){
            $fetchAll = true;
        }

        if($fetchAll){
            $userRelatedWorkflows = $this->em->getRepository(ArticleWorkflow::class)->findBy([
                'status'    => ArticleWorkflowStatus::ACTIVE,
                'journal'   => $journal,
            ]);
        }else{
            $userRelatedWorkflows = $this->em->getRepository(ArticleWorkflow::class)
                ->createQueryBuilder('aw')
                ->andWhere('aw.status = '.ArticleWorkflowStatus::ACTIVE)
                ->andWhere(':user MEMBER OF aw.relatedUsers')
                ->setParameter(':user', $user)
                ->getQuery()
                ->getResult()
            ;
        }

        return $userRelatedWorkflows;
    }

    /**
     * @param $articleWorkflowId
     * @param int $status
     * @return ArticleWorkflow
     */
    public function getArticleWorkflow($articleWorkflowId, $status = ArticleWorkflowStatus::ACTIVE)
    {
        return $this->em->getRepository(ArticleWorkflow::class)->findOneBy([
            'journal' => $this->journalService->getSelectedJournal(),
            'id' => $articleWorkflowId,
            'status' => $status,
        ]);
    }

    /**
     * @param ArticleWorkflow $articleWorkflow
     * @return array
     */
    public function getWorkflowTimeline(ArticleWorkflow $articleWorkflow)
    {
        $timeline = [];
        $timeline['workflow'] = $articleWorkflow;
        $timeline['journal'] = $articleWorkflow->getJournal();
        $timeline['article'] = $articleWorkflow->getArticle();

        return $timeline;
    }
}
