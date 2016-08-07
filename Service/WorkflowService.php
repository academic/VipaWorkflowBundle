<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\DialogPost;
use Dergipark\WorkflowBundle\Entity\JournalWorkflowStep;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Entity\WorkflowHistoryLog;
use Dergipark\WorkflowBundle\Params\ArticleWorkflowStatus;
use Dergipark\WorkflowBundle\Params\DialogPostTypes;
use Dergipark\WorkflowBundle\Params\StepStatus;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Exception\LogicException;
use Ojs\CoreBundle\Params\ArticleStatuses;
use Ojs\JournalBundle\Entity\ArticleFile;
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
    public $em;

    /**
     * @var JournalService
     */
    public $journalService;

    /**
     * @var TokenStorageInterface
     */
    public $tokenStorage;

    /**
     * @var WorkflowLoggerService
     */
    public $wfLogger;

    /**
     * @var \Twig_Environment
     */
    public $twig;

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
        $this->wfLogger->log('article.submitted.by', ['%user%' => '@'.$user->getUsername()]);
        $this->wfLogger->log('article.workflow.started');
        $this->wfLogger->log('setted.up.all.workflow.steps');
        $this->wfLogger->log('give.permission.for.journal.specified.users');

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
            ], ['id' => 'DESC']);
        }else{
            $userRelatedWorkflows = $this->em->getRepository(ArticleWorkflow::class)
                ->createQueryBuilder('aw')
                ->andWhere('aw.status = '.ArticleWorkflowStatus::ACTIVE)
                ->andWhere(':user MEMBER OF aw.relatedUsers')
                ->setParameter(':user', $user)
                ->orderBy('aw.id', 'DESC')
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
     * @param ArticleWorkflow $workflow
     * @param bool $flush
     * @return bool
     */
    public function declineSubmission(ArticleWorkflow $workflow, $flush = false)
    {
        $article = $workflow->getArticle();
        $steps = $this->em->getRepository(ArticleWorkflowStep::class)->findBy([
            'articleWorkflow' => $workflow,
            'status' => StepStatus::ACTIVE,
        ]);

        foreach($steps as $step){
            $step->setStatus(StepStatus::CLOSED);
            $this->em->persist($step);
        }
        $workflow->setStatus(ArticleWorkflowStatus::HISTORY);
        $article->setStatus(ArticleStatuses::STATUS_REJECTED);
        $this->em->persist($workflow);
        $this->em->persist($article);

        if(!$flush){
            return true;
        }
        $this->em->flush();

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param bool $flush
     * @return bool
     */
    public function acceptSubmission(ArticleWorkflow $workflow, $flush = false)
    {
        $article = $workflow->getArticle();
        $steps = $this->em->getRepository(ArticleWorkflowStep::class)->findBy([
            'articleWorkflow' => $workflow,
            'status' => StepStatus::ACTIVE,
        ]);

        foreach($steps as $step){
            $step->setStatus(StepStatus::CLOSED);
            $this->em->persist($step);
        }
        $workflow->setStatus(ArticleWorkflowStatus::HISTORY);
        $article->setStatus(ArticleStatuses::STATUS_PUBLISHED);
        $article->setAcceptanceDate(new \DateTime());
        $this->em->persist($workflow);
        $this->em->persist($article);

        if(!$flush){
            return true;
        }
        $this->em->flush();

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @return array
     */
    public function getUserRelatedFiles(ArticleWorkflow $workflow)
    {
        //collect article files
        $articleFiles = $workflow->getArticle()->getArticleFiles()->toArray();

        //collect article submission files
        $articleSubmissionFiles = $workflow->getArticle()->getArticleSubmissionFiles()->toArray();

        //collect post files
        $workflowPostFiles = $this->em->getRepository(DialogPost::class)
            ->createQueryBuilder('dialogPost')
            ->join('dialogPost.dialog', 'stepDialog')
            ->join('stepDialog.step', 'articleWorkflowStep')
            ->andWhere('articleWorkflowStep.articleWorkflow = :articleWorkflow')
            ->setParameter('articleWorkflow', $workflow)
            ->andWhere('dialogPost.type = :fileType')
            ->setParameter('fileType', DialogPostTypes::TYPE_FILE)
            ->getQuery()
            ->getResult();
        $files = array_merge($articleFiles, $articleSubmissionFiles, $workflowPostFiles);
        $files = $this->normalizeBrowseFiles($files);

        return $files;
    }

    /**
     * @param array $files
     * @return array
     */
    private function normalizeBrowseFiles($files = array())
    {
        $normalizeFile = [];
        foreach($files as $file){
            if($file instanceof ArticleFile){
                $normalizeFile[] = [
                    'originalname' => $file->getFile(),
                    'filename' => $file->getFile(),
                    'filepath' => '/uploads/articlefiles/'.$file->getFile(),
                ];
            }
        }

        return $normalizeFile;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param ArticleWorkflowStep $step
     * @return array|StepDialog[]
     */
    public function getUserRelatedStepDialogs(ArticleWorkflow $workflow, ArticleWorkflowStep $step)
    {
        $user = $this->getUser();
        $journal = $workflow->getArticle()->getJournal();
        $dialogRepo = $this->em->getRepository(StepDialog::class);
        $fetchAll = false;
        //if user have admin role or related roles
        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            $fetchAll = true;
        }
        //if user in step granted users
        if($step->grantedUsers->contains($user)){
            $fetchAll = true;
        }
        if($fetchAll){
            $dialogs = $dialogRepo->findBy(['step' => $step]);
        }else{
            $dialogs = $dialogRepo
                ->createQueryBuilder('sd')
                ->andWhere(':user MEMBER OF sd.users')
                ->setParameter('user', $user)
                ->andWhere('sd.step = :step')
                ->setParameter('step', $step)
                ->getQuery()
                ->getResult()
                ;
        }

        return $dialogs;
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
     * @param ArticleWorkflow $workflow
     * @return Response
     */
    public function getArticleDetail(ArticleWorkflow $workflow)
    {
        $article = $workflow->getArticle();
        $template = $this->twig->render('DergiparkWorkflowBundle:ArticleWorkflow:_article_detail.html.twig', [
            'article' => $article,
        ]);

        return new Response($template);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if(!$token){
            throw new LogicException('i can not find current user token :/');
        }
        return $token->getUser();
    }
}
