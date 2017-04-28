<?php

namespace Vipa\WorkflowBundle\Service;

use Doctrine\ORM\EntityManager;
use Vipa\UserBundle\Entity\User;
use Vipa\JournalBundle\Entity\Article;
use Vipa\JournalBundle\Entity\Journal;
use Vipa\JournalBundle\Entity\ArticleFile;
use Vipa\CoreBundle\Params\ArticleStatuses;
use Vipa\JournalBundle\Service\JournalService;
use Symfony\Component\HttpFoundation\Response;
use Vipa\WorkflowBundle\Entity\DialogPost;
use Vipa\WorkflowBundle\Params\StepStatus;
use Vipa\WorkflowBundle\Entity\StepDialog;
use Vipa\WorkflowBundle\Event\WorkflowEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Vipa\WorkflowBundle\Event\WorkflowEvents;
use Vipa\WorkflowBundle\Entity\StepReviewForm;
use Vipa\JournalBundle\Entity\ArticleSubmissionFile;
use Vipa\WorkflowBundle\Params\StepActionTypes;
use Vipa\WorkflowBundle\Params\DialogPostTypes;
use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Vipa\WorkflowBundle\Params\StepDialogStatus;
use Symfony\Component\Translation\TranslatorInterface;
use Vipa\WorkflowBundle\Entity\JournalReviewForm;
use Vipa\WorkflowBundle\Entity\WorkflowHistoryLog;
use Vipa\WorkflowBundle\Entity\ArticleWorkflowStep;
use Vipa\WorkflowBundle\Entity\JournalWorkflowStep;
use Vipa\WorkflowBundle\Params\JournalWorkflowSteps;
use Vipa\WorkflowBundle\Params\ArticleWorkflowStatus;
use Vipa\WorkflowBundle\Entity\ArticleWorkflowSetting;
use Vipa\WorkflowBundle\Entity\JournalWorkflowSetting;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @var WorkflowPermissionService
     */
    public $wfPermissionCheck;

    /**
     * @var \Twig_Environment
     */
    public $twig;

    /**
     * @var TranslatorInterface
     */
    public $translator;

    /**
     * @var EventDispatcherInterface
     */
    public $dispatcher;

    /**
     * WorkflowService constructor.
     *
     * @param EntityManager $em
     * @param JournalService $journalService
     * @param TokenStorageInterface $tokenStorage
     * @param WorkflowLoggerService $wfLogger
     * @param WorkflowPermissionService $wfPermissionCheck
     * @param TranslatorInterface $translator
     * @param \Twig_Environment $twig
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EntityManager $em,
        JournalService $journalService,
        TokenStorageInterface $tokenStorage,
        WorkflowLoggerService $wfLogger,
        WorkflowPermissionService $wfPermissionCheck,
        TranslatorInterface $translator,
        \Twig_Environment $twig,
        EventDispatcherInterface $dispatcher
    ) {
        $this->em                   = $em;
        $this->journalService       = $journalService;
        $this->tokenStorage         = $tokenStorage;
        $this->wfLogger             = $wfLogger;
        $this->twig                 = $twig;
        $this->wfPermissionCheck    = $wfPermissionCheck;
        $this->translator           = $translator;
        $this->dispatcher           = $dispatcher;
    }

    /**
     * @param Article $article
     *
     * @return ArticleWorkflow
     */
    public function prepareArticleWorkflow(Article $article)
    {
        $user = $this->getUser();
        //create new article workflow object
        $articleWorkflow = new ArticleWorkflow();
        $articleWorkflow
            ->setArticle($article)
            ->setJournal($this->journalService->getSelectedJournal())
            ->setStartDate(new \DateTime())
        ;
        //clone workflow settings
        $journalWorkflowSetting = $this->em->getRepository(JournalWorkflowSetting::class)->findOneBy([]);
        $articleWorkflowSetting = new ArticleWorkflowSetting();
        $articleWorkflowSetting
            ->setDoubleBlind($journalWorkflowSetting->isDoubleBlind())
            ->setReviewerWaitDay($journalWorkflowSetting->getReviewerWaitDay())
            ->setWorkflow($articleWorkflow)
        ;
        $this->em->persist($articleWorkflowSetting);

        //fetch each journal workflow step
        foreach ($this->currentJournalWorkflowSteps() as $journalWorkflowStep) {
            //clone journal workflow steps to article workflow
            $articleWorkflowStep = new ArticleWorkflowStep();
            $articleWorkflowStep
                ->setOrder($journalWorkflowStep->getOrder())
                ->setGrantedUsers($journalWorkflowStep->getGrantedUsers())
                ->setArticleWorkflow($articleWorkflow)
            ;
            //add each granted user to workflow related users
            foreach ($journalWorkflowStep->getGrantedUsers() as $stepUser) {
                $articleWorkflow->addRelatedUser($stepUser);
            }
            //if current step order is pre control then set current step to this step
            if ($journalWorkflowStep->getOrder() == JournalWorkflowSteps::PRE_CONTROL_ORDER) {
                $articleWorkflow->setCurrentStep($articleWorkflowStep);
            } else {
                //if not pre control then set step status as not opened
                $articleWorkflowStep->setStatus(StepStatus::NOT_OPENED);
            }
            $this->em->persist($articleWorkflowStep);

            $journalWorkflowStepReviewForms = $this->getJournalStepReviewForms($journalWorkflowStep, true);

            //clone all journal workflow step review forms to article workflow step
            foreach($journalWorkflowStepReviewForms as $journalReviewForm) {
                $articleStepReviewForm = new StepReviewForm();
                $articleStepReviewForm
                    ->setContent($journalReviewForm->getContent())
                    ->setName($journalReviewForm->getName())
                    ->setStep($articleWorkflowStep)
                    ->setUpdatedAt(new \DateTime())
                ;
                $this->em->persist($articleStepReviewForm);
            }
        }

        //if submitter user not exists set current user as submitter user
        if($article->getSubmitterUser() == null){
            $article->setSubmitterUser($user);
        }
        //add submitter user to workflow related user too
        $articleWorkflow->addRelatedUser($article->getSubmitterUser());

        $this->em->persist($articleWorkflow);

        //set submission article status as inreview
        $article->setStatus(ArticleStatuses::STATUS_INREVIEW);
        $this->em->persist($article);

        //log workflow events
        $this->wfLogger->setArticleWorkflow($articleWorkflow);
        $this->wfLogger->log('article.submitted.by', ['%user%' => '@'.$user->getUsername()]);
        $this->wfLogger->log('article.workflow.started');
        $this->wfLogger->log('setted.up.all.workflow.steps');
        $this->wfLogger->log('give.permission.for.journal.specified.users');

        $this->em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setWorkflow($articleWorkflow);
        $this->dispatcher->dispatch(WorkflowEvents::WORKFLOW_STARTED, $workflowEvent);

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
     * @param JournalWorkflowStep $journalWorkflowStep
     * @param bool $isActive
     *
     * @return array|JournalReviewForm[]
     */
    public function getJournalStepReviewForms(JournalWorkflowStep $journalWorkflowStep, $isActive = true)
    {
        $forms = $this->em->getRepository(JournalReviewForm::class)->findBy([
            'step' => $journalWorkflowStep,
            'active' => $isActive,
        ]);

        return $forms;
    }

    public function getUserRelatedWorkflowsContainer($status = ArticleWorkflowStatus::ACTIVE)
    {
        $wfContainers = [];
        $workflows = $this->getUserRelatedWorkflows(null, null, $status);
        foreach ($workflows as $workflow) {
            $wfContainer = [];
            $wfContainer['workflow'] = $workflow;
            $wfContainer['article'] = $workflow->getArticle();
            if (!$this->hasReviewerRoleOnWorkflow($workflow)) {
                $wfContainer['author'] = $workflow->getArticle()->getSubmitterUser();
            }
            $wfContainer['active_dialog'] = $this->getUserBasedDialogCount($workflow, StepDialogStatus::ACTIVE);
            $wfContainer['closed_dialog'] = $this->getUserBasedDialogCount($workflow, StepDialogStatus::CLOSED);
            if($workflow->getCurrentStep()->getOrder() == JournalWorkflowSteps::REVIEW_ORDER
                && $status == ArticleWorkflowStatus::ACTIVE
                && $this->wfPermissionCheck->isGrantedForStep($workflow->getCurrentStep())){
                $wfContainer['reviewer_stats'] = $this->getArticleWorkflowReviewerStats($workflow);
            }
            $wfContainers[] = $wfContainer;
        }

        return $wfContainers;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param $status
     *
     * @return int
     */
    private function getUserBasedDialogCount(ArticleWorkflow $workflow, $status)
    {
        $journal = $this->journalService->getSelectedJournal();
        $fetchAll = false;
        $userJournalRoles = $this->getUser()->getJournalRolesBag($journal);
        $specialRoles = ['ROLE_EDITOR', 'ROLE_CO_EDITOR'];
        if (count(array_intersect($specialRoles, $userJournalRoles)) > 0 || $this->getUser()->isAdmin()) {
            $fetchAll = true;
        }
        $dialogRepo = $this->em->getRepository(StepDialog::class);
        $dialogQuery = $dialogRepo
            ->createQueryBuilder('stepDialog')
            ->join('stepDialog.step', 'dialogStep')
            ->andWhere('stepDialog.status = :status')
            ->setParameter('status', $status)
            ->andWhere('dialogStep.articleWorkflow = :workflow')
            ->setParameter('workflow', $workflow)
        ;
        if($status == StepDialogStatus::ACTIVE){
            $dialogQuery
                ->andWhere('stepDialog.rejected != true');
        }
        if (!$fetchAll) {
            $dialogQuery
                ->andWhere(':user MEMBER OF stepDialog.users OR stepDialog.createdDialogBy = :user')
                ->setParameter('user', $this->getUser())
            ;
        }
        $dialogs = $dialogQuery->getQuery()->getResult();

        return count($dialogs);
    }

    /**
     * @param ArticleWorkflow $workflow
     *
     * @return array
     */
    private function getArticleWorkflowReviewerStats(ArticleWorkflow $workflow)
    {
        $stats = [];
        $dialogRepo = $this->em->getRepository(StepDialog::class);
        $dialogQuery = $dialogRepo
            ->createQueryBuilder('stepDialog')
            ->andWhere('stepDialog.step = :dialogStep')
            ->setParameter('dialogStep', $workflow->getCurrentStep())
            ->andWhere('stepDialog.dialogType = :dialogType')
            ->setParameter('dialogType', StepActionTypes::ASSIGN_REVIEWER)
        ;
        $dialogs = $dialogQuery->getQuery()->getResult();

        $stats['rejected.review.dialog.count'] = 0;
        $stats['accepted.review.dialog.count'] = 0;
        $stats['active.waiting.reviewer.count'] = 0;

        /** @var StepDialog $dialog */
        foreach($dialogs as $dialog){

            if($dialog->isRejected()){
                $stats['rejected.review.dialog.count']++;
            }
            if($dialog->isAccepted()){
                $stats['accepted.review.dialog.count']++;
            }
            if(($dialog->getInviteTime() !== null || $dialog->getRemindingTime() !== null)
                && ($dialog->isRejected() == false && $dialog->isAccepted() == false)){
                $stats['active.waiting.reviewer.count']++;
            }
        }

        return $stats;
    }

    private function hasReviewerRoleOnWorkflow(ArticleWorkflow $workflow)
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
     * @param User|null $user
     * @param Journal|null $journal
     * @param int $status
     *
     * @return array|ArticleWorkflow[]
     */
    public function getUserRelatedWorkflows(User $user = null, Journal $journal = null, $status = ArticleWorkflowStatus::ACTIVE)
    {
        if (!$user) {
            $user = $this->getUser();
        }
        if (!$journal) {
            $journal = $this->journalService->getSelectedJournal();
            if (!$journal) {
                throw new \LogicException('i can not find current journal');
            }
        }

        $fetchAll = false;
        $userJournalRoles = $user->getJournalRolesBag($journal);
        $specialRoles = ['ROLE_EDITOR', 'ROLE_CO_EDITOR'];
        if (count(array_intersect($specialRoles, $userJournalRoles)) > 0 || $user->isAdmin()) {
            $fetchAll = true;
        }

        if ($fetchAll) {
            $userRelatedWorkflows = $this->em->getRepository(ArticleWorkflow::class)->findBy([
                'journal' => $journal,
                'status' => $status
            ], ['id' => 'DESC']);
        } else {
            $userRelatedWorkflows = $this->em->getRepository(ArticleWorkflow::class)
                ->createQueryBuilder('aw')
                ->andWhere('aw.status = '.$status)
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
     *
     * @return ArticleWorkflow
     */
    public function getArticleWorkflow($articleWorkflowId)
    {
        return $this->em->getRepository(ArticleWorkflow::class)->findOneBy([
            'journal' => $this->journalService->getSelectedJournal(),
            'id' => $articleWorkflowId,
        ]);
    }

    /**
     * @param ArticleWorkflow $articleWorkflow
     *
     * @return array
     */
    public function getWorkflowTimeline(ArticleWorkflow $articleWorkflow)
    {
        $timeline = [];
        $timeline['workflow'] = $articleWorkflow;
        $timeline['journal'] = $articleWorkflow->getJournal();
        $timeline['article'] = $articleWorkflow->getArticle();
        $timeline['steps'] = $this->em->getRepository(ArticleWorkflowStep::class)->findBy([
            'articleWorkflow' => $articleWorkflow,
        ], ['order' => 'ASC']);

        return $timeline;
    }

    /**
     * @param ArticleWorkflow $articleWorkflow
     *
     * @return array
     */
    public function getWorkflowLogs(ArticleWorkflow $articleWorkflow)
    {
        return $this->em->getRepository(WorkflowHistoryLog::class)->findBy([
            'articleWorkflow' => $articleWorkflow,
        ]);
    }

    /**
     * @param ArticleWorkflow $workflow
     *
     * @return array
     */
    public function getPermissionsContainer(ArticleWorkflow $workflow)
    {
        $permissions = [];

        //set admins full permission
        $permission[0] = $this->translator->trans('have.system.admin.role');
        //pre control step
        $permission[1] = true;
        //review step
        $permission[2] = true;
        //arrangement step
        $permission[3] = true;
        //full permission
        $permission[4] = true;
        $permissions[] = $permission;

        //set role editor and role co-editor full permission
        $permission[0] = $this->translator->trans('have.role.editor.or.co.editor');
        //pre control step
        $permission[1] = true;
        //review step
        $permission[2] = true;
        //arrangement step
        $permission[3] = true;
        //full permission
        $permission[4] = true;
        $permissions[] = $permission;

        foreach ($workflow->getGrantedUsers() as $user) {
            $permission[0] = $user->getUsername().'['.$this->translator->trans('article.editor').']';
            $permission[1] = true;
            $permission[2] = true;
            $permission[3] = true;
            $permission[4] = false;
            $permissions[] = $permission;
        }

        $preControlStep = $this->em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => JournalWorkflowSteps::PRE_CONTROL_ORDER,
        ]);
        foreach ($preControlStep->getGrantedUsers() as $user) {
            if ($this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($workflow->getJournal()))) {
                continue;
            }
            $permission[0] = $user->getUsername();
            $permission[1] = true;
            $permission[2] = false;
            $permission[3] = false;
            $permission[4] = false;
            $permissions[] = $permission;
        }

        $reviewStep = $this->em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => JournalWorkflowSteps::REVIEW_ORDER,
        ]);
        foreach ($reviewStep->getGrantedUsers() as $user) {
            if ($this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($workflow->getJournal()))) {
                continue;
            }
            $permission[0] = $user->getUsername();
            $permission[1] = false;
            $permission[2] = true;
            $permission[3] = false;
            $permission[4] = false;
            $permissions[] = $permission;
        }

        $arrangementStep = $this->em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => JournalWorkflowSteps::ARRANGEMENT_ORDER,
        ]);
        foreach ($arrangementStep->getGrantedUsers() as $user) {
            if ($this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($workflow->getJournal()))) {
                continue;
            }
            $permission[0] = $user->getUsername();
            $permission[1] = false;
            $permission[2] = false;
            $permission[3] = true;
            $permission[4] = false;
            $permissions[] = $permission;
        }

        return $permissions;
    }

    /**
     * @param $block
     *
     * @return Response
     */
    public function getMessageBlock($block, $params = [])
    {
        $template = $this->twig->loadTemplate('VipaWorkflowBundle:ArticleWorkflow:_message_blocks.html.twig');

        return new Response($template->renderBlock($block, $params));
    }

    /**
     * @param $block
     * @param array $params
     *
     * @return Response
     */
    public function getFormBlock($block, $params = [])
    {
        $template = $this->twig->loadTemplate('VipaWorkflowBundle:ArticleWorkflow:_action_forms.html.twig');

        return new Response($template->renderBlock($block, $params));
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param bool            $flush
     *
     * @return bool
     */
    public function declineSubmission(ArticleWorkflow $workflow, $flush = false)
    {
        $article = $workflow->getArticle();
        $steps = $this->em->getRepository(ArticleWorkflowStep::class)->findBy([
            'articleWorkflow' => $workflow,
            'status' => StepStatus::ACTIVE,
        ]);

        foreach ($steps as $step) {
            $step->setStatus(StepStatus::CLOSED);
            //close all dialogs
            $this->closeStepDialogs($step);
            $this->em->persist($step);
        }
        $workflow->setStatus(ArticleWorkflowStatus::HISTORY);
        $article->setStatus(ArticleStatuses::STATUS_REJECTED);
        $this->em->persist($workflow);
        $this->em->persist($article);

        if ($flush) {
            $this->em->flush();
        }

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param bool            $flush
     *
     * @return bool
     */
    public function acceptSubmission(ArticleWorkflow $workflow, $flush = false)
    {
        $article = $workflow->getArticle();
        $steps = $this->em->getRepository(ArticleWorkflowStep::class)->findBy([
            'articleWorkflow' => $workflow,
            'status' => StepStatus::ACTIVE,
        ]);

        foreach ($steps as $step) {
            $step->setStatus(StepStatus::CLOSED);
            //close all step dialogs
            $this->closeStepDialogs($step);
            $this->em->persist($step);
        }
        $workflow->setStatus(ArticleWorkflowStatus::HISTORY);
        $article->setStatus(ArticleStatuses::STATUS_PUBLISH_READY);
        $article->setAcceptanceDate(new \DateTime());
        $this->em->persist($workflow);
        $this->em->persist($article);

        if ($flush) {
            $this->em->flush();
        }

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param bool            $flush
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException
     */
    public function gotoReview(ArticleWorkflow $workflow, $flush = false)
    {
        $currentStep = $workflow->getCurrentStep();
        if ($currentStep->getOrder() !== JournalWorkflowSteps::PRE_CONTROL_ORDER) {
            throw new \LogicException('current step must be pre control');
        }
        // deactive current step
        $currentStep->setStatus(StepStatus::CLOSED);
        $this->closeStepDialogs($currentStep);
        $this->em->persist($currentStep);

        $reviewStep = $this->em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => JournalWorkflowSteps::REVIEW_ORDER,
        ]);

        $workflow->setCurrentStep($reviewStep);
        $this->em->persist($workflow);

        $reviewStep->setStatus(StepStatus::ACTIVE);
        $this->em->persist($reviewStep);

        if ($flush) {
            $this->flush();
        }

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param bool            $flush
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException
     */
    public function gotoArrangement(ArticleWorkflow $workflow, $flush = false)
    {
        $currentStep = $workflow->getCurrentStep();
        if ($currentStep->getOrder() !== JournalWorkflowSteps::REVIEW_ORDER) {
            throw new \LogicException('current step must be aggrangement');
        }
        // deactive current step
        $currentStep->setStatus(StepStatus::CLOSED);
        $this->closeStepDialogs($currentStep);
        $this->em->persist($currentStep);

        $arrangementStep = $this->em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => JournalWorkflowSteps::ARRANGEMENT_ORDER,
        ]);

        $workflow->setCurrentStep($arrangementStep);
        $this->em->persist($workflow);

        $arrangementStep->setStatus(StepStatus::ACTIVE);
        $this->em->persist($arrangementStep);

        if ($flush) {
            $this->flush();
        }

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param bool            $flush
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException
     */
    public function finishWorkflow(ArticleWorkflow $workflow, $flush = false)
    {
        $article = $workflow->getArticle();
        $currentStep = $workflow->getCurrentStep();
        if ($currentStep->getOrder() !== JournalWorkflowSteps::ARRANGEMENT_ORDER) {
            throw new \LogicException('current step must be arrangement');
        }
        // deactive current step
        $currentStep->setStatus(StepStatus::CLOSED);
        //close step dialogs
        $this->closeStepDialogs($currentStep);
        $this->em->persist($currentStep);

        //close workflow
        $workflow->setStatus(ArticleWorkflowStatus::HISTORY);
        $this->em->persist($workflow);

        //publish article
        $article->setStatus(ArticleStatuses::STATUS_PUBLISH_READY);
        $this->em->persist($article);

        if ($flush) {
            $this->flush();
        }

        return true;
    }

    /**
     * @param Article         $article
     * @param bool            $flush
     *
     * @return JsonResponse
     *
     * @throws AccessDeniedException
     */
    public function closeOldWorklfows(Article $article, $flush = false)
    {
        $workflows = $this->em->getRepository(ArticleWorkflow::class)->findBy([
            'article' => $article,
            'status' => ArticleWorkflowStatus::ACTIVE,
        ]);

        if(empty($workflows)){
            return true;
        }

        foreach($workflows as $workflow){

            $currentStep = $workflow->getCurrentStep();

            // deactive current step
            $currentStep->setStatus(StepStatus::CLOSED);
            //close step dialogs
            $this->closeStepDialogs($currentStep);
            $this->em->persist($currentStep);

            //close workflow
            $workflow->setStatus(ArticleWorkflowStatus::HISTORY);
            $this->em->persist($workflow);

            //publish article
            $article->setStatus(ArticleStatuses::STATUS_PUBLISHED);
            $this->em->persist($article);

            if ($flush) {
                $this->em->flush();
            }
        }

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @param StepDialog $dialog
     *
     * @return array
     */
    public function getUserRelatedFiles(ArticleWorkflow $workflow, StepDialog $dialog)
    {
        $collectFiles = [];

        if($this->wfPermissionCheck->isGrantedForStep($workflow->getCurrentStep())){
            //collect article files
            $articleFiles = $workflow->getArticle()->getArticleFiles()->toArray();
            $collectFiles = array_merge($collectFiles, $articleFiles);

            //collect article submission files
            $articleSubmissionFiles = $workflow->getArticle()->getArticleSubmissionFiles()->toArray();
            $collectFiles = array_merge($collectFiles, $articleSubmissionFiles);

            if(!empty($workflow->getReviewVersionFile())){
                //collect workflow review version file
                $collectFiles[] = [
                    'review_version' => $workflow->getReviewVersionFile()
                ];
            }
        }

        //collect post files
        $workflowPostFiles = $this->em->getRepository(DialogPost::class)
            ->createQueryBuilder('dialogPost')
            ->join('dialogPost.dialog', 'stepDialog')
            ->join('stepDialog.step', 'articleWorkflowStep')
            ->andWhere('articleWorkflowStep.articleWorkflow = :articleWorkflow')
            ->andWhere('stepDialog.id != :dialog')
            ->setParameter('articleWorkflow', $workflow)
            ->setParameter('dialog', $dialog)
            ->andWhere('dialogPost.type = :fileType')
            ->setParameter('fileType', DialogPostTypes::TYPE_FILE)
        ;
        if(!$this->wfPermissionCheck->isGrantedForStep($workflow->getCurrentStep())){
            $workflowPostFiles
                ->andWhere(':user MEMBER OF stepDialog.users')
                ->setParameter('user', $this->getUser())
            ;
        }
        $workflowPostFiles = $workflowPostFiles->getQuery()->getResult();

        $collectFiles = array_merge($collectFiles, $workflowPostFiles);
        $files = $this->normalizeBrowseFiles($collectFiles);

        return $files;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    private function normalizeBrowseFiles($files = array())
    {
        $normalizeFile = [];
        foreach ($files as $file) {
            if ($file instanceof ArticleFile) {
                $normalizeFile[] = [
                    'originalname' => $file->getTitle(),
                    'filename' => $file->getFile(),
                    'filepath' => '/uploads/articlefiles/'.$file->getFile(),
                    'collected.from' => $this->translator->trans('article.files'),
                ];
            }
            if ($file instanceof ArticleSubmissionFile) {
                $normalizeFile[] = [
                    'originalname' => $file->getTitle(),
                    'filename' => $file->getFile(),
                    'filepath' => '/uploads/submissionfiles/'.$file->getFile(),
                    'collected.from' => $this->translator->trans('submission.files'),
                ];
            }
            if ($file instanceof DialogPost) {
                $normalizeFile[] = [
                    'originalname' => $file->getFileOriginalName(),
                    'filename' => $file->getFileName(),
                    'filepath' => '/uploads/articlefiles/'.$file->getFileName(),
                    'collected.from' => $this->translator->trans('action.dialog').'['.date('m.d/H:i', $file->getSendedAt()->getTimestamp()).']',
                ];
            }
            if(is_array($file) && array_key_exists('review_version', $file)){
                $normalizeFile[] = [
                    'originalname' => $this->translator->trans('title.workflow.review.version.file'),
                    'filename' => $file['review_version'],
                    'filepath' => '/uploads/articlefiles/'.$file['review_version'],
                    'collected.from' => $this->translator->trans('title.workflow.review.version.file'),
                ];
            }
        }

        return $normalizeFile;
    }

    /**
     * @param ArticleWorkflowStep $step
     *
     * @return array
     */
    public function getStepFormResponses(ArticleWorkflowStep $step)
    {
        return $this->em->getRepository(DialogPost::class)
            ->createQueryBuilder('dialogPost')
            ->join('dialogPost.dialog', 'stepDialog')
            ->andWhere('stepDialog.step = :step')
            ->setParameter('step', $step)
            ->andWhere('dialogPost.type = :formResponseType')
            ->setParameter('formResponseType', DialogPostTypes::TYPE_FORM_RESPONSE)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param ArticleWorkflow     $workflow
     * @param ArticleWorkflowStep $step
     *
     * @return array|StepDialog[]
     */
    public function getUserRelatedStepDialogs(ArticleWorkflow $workflow, ArticleWorkflowStep $step)
    {
        $user = $this->getUser();
        $dialogRepo = $this->em->getRepository(StepDialog::class);
        $fetchAll = false;
        //if user have admin role or related roles
        if ($this->wfPermissionCheck->isGrantedForStep($step)) {
            $fetchAll = true;
        }
        //if user in step granted users
        if ($step->grantedUsers->contains($user)) {
            $fetchAll = true;
        }
        if ($fetchAll) {
            $dialogs = $dialogRepo->findBy(['step' => $step], ['openedAt' => 'ASC']);
        } else {
            $dialogs = $dialogRepo
                ->createQueryBuilder('sd')
                ->andWhere(':user MEMBER OF sd.users')
                ->setParameter('user', $user)
                ->andWhere('sd.step = :step')
                ->setParameter('step', $step)
                ->orderBy('sd.openedAt', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        }

        return $dialogs;
    }

    /**
     * @param array $searchRoles
     * @param array $roleBag
     *
     * @return bool
     */
    public function haveLeastRole($searchRoles = [], $roleBag = [])
    {
        if (count(array_intersect($searchRoles, $roleBag)) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param ArticleWorkflow $workflow
     *
     * @return Response
     */
    public function getArticleDetail(ArticleWorkflow $workflow)
    {
        $article = $workflow->getArticle();
        $template = $this->twig->render('VipaWorkflowBundle:ArticleWorkflow/article_detail:_article_detail.html.twig', [
            'article' => $article,
            'workflow' => $workflow,
            'workflowSettings' => $this->workflowSettings($workflow),
        ]);

        return new Response($template);
    }

    /**
     * @param ArticleWorkflowStep $step
     * @param bool $flush
     */
    public function syncStepReviewForms(ArticleWorkflowStep $step, $flush = true)
    {
        $journalStep = $this->em->getRepository(JournalWorkflowStep::class)->findOneBy([
            'order' => $step->getOrder(),
        ]);
        $journalStepReviewForms = $this->em->getRepository(JournalReviewForm::class)->findBy([
            'step' => $journalStep,
        ]);
        $articleReviewForms = $this->em->getRepository(StepReviewForm::class)->findBy([
            'step' => $step,
        ]);
        foreach($journalStepReviewForms as $journalReviewForm){
            $formMatch = false;
            foreach($articleReviewForms as $articleReviewForm){
                if($journalReviewForm->getContent() == $articleReviewForm->getContent()){
                    $formMatch = true;
                }
            }
            if(!$formMatch){
                $newArticleReviewForm = new StepReviewForm();
                $newArticleReviewForm
                    ->setContent($journalReviewForm->getContent())
                    ->setName($journalReviewForm->getName())
                    ->setUpdatedAt(new \DateTime())
                    ->setStep($step)
                ;
                $this->em->persist($newArticleReviewForm);
            }
        }

        if($flush){
            $this->em->flush();
        }
    }

    /**
     * @param ArticleWorkflowStep $step
     *
     * @return bool
     */
    public function closeStepDialogs(ArticleWorkflowStep $step)
    {
        $dialogs = $this->em->getRepository(StepDialog::class)->findBy([
            'step' => $step,
        ]);
        foreach ($dialogs as $dialog) {
            $dialog->setStatus(StepDialogStatus::CLOSED);
            $this->em->persist($dialog);
        }

        return true;
    }

    /**
     * @param ArticleWorkflow $workflow
     *
     * @return ArticleWorkflowSetting
     */
    public function workflowSettings(ArticleWorkflow $workflow)
    {
        return $this->em->getRepository(ArticleWorkflowSetting::class)->findOneBy([
            'workflow' => $workflow,
        ]);
    }

    /**
     * @return User
     */
    public function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            throw new \LogicException('i can not find current user token :/');
        }

        return $token->getUser();
    }

    /**
     * @param ArticleWorkflow $workflow
     */
    public function cleanWorkflow(ArticleWorkflow $workflow)
    {
        $workflow->setArticle(null);
        $workflow->setJournal(null);
        $workflow->setStatus(ArticleWorkflowStatus::REMOVED);
        $this->em->persist($workflow);
    }
}
