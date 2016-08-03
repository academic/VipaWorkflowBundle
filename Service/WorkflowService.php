<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\JournalWorkflowStep;
use Dergipark\WorkflowBundle\Entity\WorkflowHistoryLog;
use Dergipark\WorkflowBundle\Params\ArticleWorkflowStatus;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Exception\LogicException;
use Ojs\CoreBundle\Params\ArticleStatuses;
use Ojs\JournalBundle\Entity\Journal;
use Ojs\JournalBundle\Service\JournalService;
use Ojs\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Ojs\JournalBundle\Entity\Article;

class WorkflowService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var JournalService
     */
    private $journalService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var WorkflowLoggerService
     */
    private $wfLogger;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * WorkflowService constructor.
     * @param EntityManager $em
     * @param JournalService $journalService
     * @param TokenStorageInterface $tokenStorage
     * @param WorkflowLoggerService $wfLogger
     */
    public function __construct(
        EntityManager $em,
        JournalService $journalService,
        TokenStorageInterface $tokenStorage,
        WorkflowLoggerService $wfLogger,
        \Twig_Environment $twig
    ) {
        $this->em               = $em;
        $this->journalService   = $journalService;
        $this->tokenStorage     = $tokenStorage;
        $this->wfLogger         = $wfLogger;
        $this->twig             = $twig;
    }

    /**
     * @param Article $article
     * @return ArticleWorkflow
     */
    public function prepareArticleWorkflow(Article $article)
    {
        $user = $this->getUser();
        $articleWorkflow = new ArticleWorkflow();
        $articleWorkflow
            ->setArticle($article)
            ->setJournal($this->journalService->getSelectedJournal())
            ->setStartDate(new \DateTime())
            ;

        foreach($this->currentJournalWorkflowSteps() as $step){
            $articleWorkflowStep = new ArticleWorkflowStep();
            $articleWorkflowStep
                ->setOrder($step->getOrder())
                ->setGrantedUsers($step->getGrantedUsers())
                ->setArticleWorkflow($articleWorkflow)
                ;
            foreach($step->getGrantedUsers() as $stepUser){
                $articleWorkflow->addRelatedUser($stepUser);
            }
            $this->em->persist($articleWorkflowStep);

            if($step->getOrder() == 1){
                $articleWorkflow->setCurrentStep($articleWorkflowStep);
            }
        }
        $articleWorkflow->addRelatedUser($article->getSubmitterUser());

        $this->em->persist($articleWorkflow);

        $article->setStatus(ArticleStatuses::STATUS_INREVIEW);

        //log workflow events
        $this->wfLogger->setArticleWorkflow($articleWorkflow);
        $this->wfLogger->log('Article Submitted By -> '. $user->getUsername());
        $this->wfLogger->log('Article Workflow Started');
        $this->wfLogger->log('Setted up all Workflow Steps');
        $this->wfLogger->log('Give permissions for journal specified users');

        $this->em->flush();

        return $articleWorkflow;
    }

    /**
     * @return array|JournalWorkflowStep[]
     */
    public function currentJournalWorkflowSteps()
    {
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
            $user = $this->getUser();
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

    /**
     * @param ArticleWorkflow $articleWorkflow
     * @return array
     */
    public function getWorkflowLogs(ArticleWorkflow $articleWorkflow)
    {
        return $this->em->getRepository(WorkflowHistoryLog::class)->findBy([
            'articleWorkflow' => $articleWorkflow,
        ]);
    }

    /**
     * @param $block
     * @return Response
     */
    public function getMessageBlock($block, $params = [])
    {
        $template = $this->twig->loadTemplate('DergiparkWorkflowBundle:ArticleWorkflow:_message_blocks.html.twig');

        return new Response($template->renderBlock($block, $params));
    }

    /**
     * @param $block
     * @param array $params
     * @return Response
     */
    public function getFormBlock($block, $params = [])
    {
        $template = $this->twig->loadTemplate('DergiparkWorkflowBundle:ArticleWorkflow:_action_forms.html.twig');

        return new Response($template->renderBlock($block, $params));
    }

    /**
     * @return User
     */
    private function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if(!$token){
            throw new LogicException('i can not find current user token :/');
        }
        return $token->getUser();
    }
}
