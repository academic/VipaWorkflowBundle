<?php

namespace Vipa\WorkflowBundle\Service\Twig;

use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Vipa\WorkflowBundle\Entity\ArticleWorkflowSetting;
use Vipa\WorkflowBundle\Entity\StepDialog;
use Vipa\WorkflowBundle\Params\JournalWorkflowSteps;
use Vipa\WorkflowBundle\Params\StepActionTypes;
use Vipa\WorkflowBundle\Service\WorkflowPermissionService;
use Doctrine\ORM\EntityManager;
use Vipa\JournalBundle\Entity\Author;
use Vipa\JournalBundle\Service\JournalService;
use Vipa\UserBundle\Entity\User;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DPWorkflowTwigExtension extends \Twig_Extension
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
     * @var Session
     */
    private $session;

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
     * @var WorkflowPermissionService
     */
    public $wfPermissionService;

    /**
     * @var \Twig_Environment
     */
    public $twig;

    /**
     * DPWorkflowTwigExtension constructor.
     *
     * @param EntityManager|null $em
     * @param RouterInterface|null $router
     * @param TranslatorInterface|null $translator
     * @param JournalService|null $journalService
     * @param TokenStorageInterface|null $tokenStorage
     * @param Session|null $session
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     * @param WorkflowPermissionService $permissionService
     * @param \Twig_Environment $twig
     */
    public function __construct(
        EntityManager $em = null,
        RouterInterface $router = null,
        TranslatorInterface $translator = null,
        JournalService $journalService = null,
        TokenStorageInterface $tokenStorage = null,
        Session $session = null,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        WorkflowPermissionService $permissionService,
        \Twig_Environment $twig
    ) {
        $this->em                   = $em;
        $this->router               = $router;
        $this->journalService       = $journalService;
        $this->tokenStorage         = $tokenStorage;
        $this->session              = $session;
        $this->translator           = $translator;
        $this->requestStack         = $requestStack;
        $this->eventDispatcher      = $eventDispatcher;
        $this->wfPermissionService  = $permissionService;
        $this->twig                 = $twig;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('actionType', array($this, 'getActionType')),
            new \Twig_SimpleFunction('actionAlias', array($this, 'getActionAlias')),
            new \Twig_SimpleFunction('permissionCheck', array($this, 'getPermissionCheck')),
            new \Twig_SimpleFunction('journalStepAlias', array($this, 'getJournalStepAlias')),
            new \Twig_SimpleFunction('dialogStatus', array($this, 'getDialogStatus')),
            new \Twig_SimpleFunction('workflowStatus', array($this, 'getWorkflowStatus')),
            new \Twig_SimpleFunction('stepStatus', array($this, 'getStepStatus')),
            new \Twig_SimpleFunction('postType', array($this, 'getPostType')),
            new \Twig_SimpleFunction('profileLink', array($this, 'getProfileLink'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('authorSearchLink', array($this, 'getAuthorSearchLink'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('passedInviteDate', array($this, 'passedInviteDate')),
            new \Twig_SimpleFunction('passedRemindDate', array($this, 'passedRemindDate')),
            new \Twig_SimpleFunction('articleEditors', array($this, 'getArticleEditors')),
            new \Twig_SimpleFunction('generateReviewboxContent', array($this, 'generateReviewboxContent'), ['is_safe' => ['html']]),
        );
    }

    public function getActionType($const)
    {
        return constant('Vipa\WorkflowBundle\Params\StepActionTypes::'.$const);
    }

    public function getDialogStatus($const)
    {
        return constant('Vipa\WorkflowBundle\Params\StepDialogStatus::'.$const);
    }

    public function getWorkflowStatus($const)
    {
        return constant('Vipa\WorkflowBundle\Params\ArticleWorkflowStatus::'.$const);
    }

    public function getStepStatus($const)
    {
        return constant('Vipa\WorkflowBundle\Params\StepStatus::'.$const);
    }

    public function getPostType($const)
    {
        return constant('Vipa\WorkflowBundle\Params\DialogPostTypes::'.$const);
    }

    public function getJournalStepAlias($stepOrder)
    {
        return JournalWorkflowSteps::$stepAlias[$stepOrder];
    }

    public function getActionAlias($actionType)
    {
        return StepActionTypes::$typeAlias[$actionType];
    }

    public function getName()
    {
        return 'vipa_workflow_extension';
    }

    public function getPermissionCheck()
    {
        return $this->wfPermissionService;
    }

    public function getProfileLink(User $user, $name = false)
    {
        $currentUser = $this->getUser();
        if($currentUser->getUsername() == $user->getUsername()){
            $username = $this->translator->trans('you');
        }else{
            if($name){
                $username = $user->getFullName();
            }else{
                $username = '@'.$user->getUsername();
            }
        }
        $link = $this->router->generate('vipa_user_profile', ['slug' => $user->getUsername()]);
        $tooltip = (string)$user;
        $template = '<a target="_blank" data-toggle="tooltip" title="'.$tooltip.'" href="'.$link.'">'.$username.'</a>';

        return $template;
    }

    public function getAuthorSearchLink(Author $author)
    {
        $link = $this->router->generate('vipa_search_index', [
            'q' => (string)$author,
            'section' => 'author',
        ]);
        $tooltip = (string)$author;
        $template = '<a target="_blank" data-toggle="tooltip" title="'.$tooltip.'" href="'.$link.'">'.(string)$author.'</a>';

        return $template;
    }

    /**
     * @param StepDialog $dialog
     * @return bool
     */
    public function passedInviteDate(StepDialog $dialog)
    {
        $settings = $this->getWorkflowSettings($dialog->getStep()->getArticleWorkflow());
        $reviewerWaitDay = $settings->getReviewerWaitDay();
        $interval = $dialog->getInviteTime()->diff(new \DateTime());
        if($interval->days > $reviewerWaitDay){
            return true;
        }else{
            return (int)$reviewerWaitDay - $interval->days;
        }
    }

    /**
     * @param StepDialog $dialog
     * @return mixed
     */
    public function generateReviewboxContent(StepDialog $dialog)
    {
        $inviteTime = $dialog->getInviteTime();
        $remindTime = $dialog->getRemindingTime();
        $isReviewer = $dialog->users->contains($this->getUser());

        //if dialog is accepted return directly
        if($dialog->isAccepted()){
            return '';
        }

        //if invitation not sended yet
        if($inviteTime === null){
            if($isReviewer) {
                return $this->renderReviewMessage('send_invitation_reviewer');
            }else{
                return $this->renderReviewMessage('send_invitation_editor', [
                    'dialog' => $dialog,
                ]);
            }
        }

        //if invitation is rejected
        if($dialog->isRejected()){
            if($isReviewer) {
                return $this->renderReviewMessage('invitation_rejected_reviewer');
            }else{
                return $this->renderReviewMessage('invitation_rejected_editor');
            }
        }

        //if invitation sended but not passed yet
        if($inviteTime instanceof \DateTime && $this->passedInviteDate($dialog) !== true){
            if($isReviewer) {
                return $this->renderReviewMessage('invitation_sended_waiting_reviewer', [
                    'dialog' => $dialog,
                    'leftDay' => $this->passedInviteDate($dialog),
                    'fromDay' => $this->getWorkflowSettings($dialog->getStep()->getArticleWorkflow())->getReviewerWaitDay(),
                ]);
            }else{
                return $this->renderReviewMessage('invitation_sended_waiting_editor', [
                    'dialog' => $dialog,
                    'leftDay' => $this->passedInviteDate($dialog),
                    'fromDay' => $this->getWorkflowSettings($dialog->getStep()->getArticleWorkflow())->getReviewerWaitDay(),
                ]);
            }
        }

        //if invitation sended and finished time to response
        if($inviteTime instanceof \DateTime
            && $this->passedInviteDate($dialog) === true
            && $remindTime === null){
            if($isReviewer){
                return $this->renderReviewMessage('wait_remind_mail_reviewer');
            }else{
                return $this->renderReviewMessage('send_remind_editor', [
                    'dialog' => $dialog,
                ]);
            }
        }

        //if waiting for remind
        if($remindTime instanceof \DateTime && $this->passedRemindDate($dialog) !== true) {
            if($isReviewer) {
                return $this->renderReviewMessage('remind_sended_waiting_accept_reject_reviewer', [
                    'dialog' => $dialog,
                    'leftDay' => $this->passedRemindDate($dialog),
                    'fromDay' => 7,
                ]);
            }else{
                return $this->renderReviewMessage('remind_sended_waiting_editor', [
                    'dialog' => $dialog,
                    'leftDay' => $this->passedRemindDate($dialog),
                    'fromDay' => 7,
                ]);
            }
        }

        //remind mail sended and time has finished
        if($remindTime instanceof \DateTime && $this->passedRemindDate($dialog) === true) {
            if($isReviewer) {
                return $this->renderReviewMessage('remind_time_finished_reviewer');
            }else{
                return $this->renderReviewMessage('remind_time_finished_editor');
            }
        }

        return '';
    }

    /**
     * @param $block
     * @param array $params
     *
     * @return mixed
     */
    private function renderReviewMessage($block, $params = [])
    {
        $template = $this->twig->loadTemplate('VipaWorkflowBundle:StepDialog/dialog/messages:_reviewer_message_box.html.twig');

        return $template->renderBlock($block, $params);
    }

    /**
     * @param StepDialog $dialog
     * @return bool
     */
    public function passedRemindDate(StepDialog $dialog)
    {
        $remindWaitDay = 7;
        $interval = $dialog->getRemindingTime()->diff(new \DateTime());
        if($interval->days > $remindWaitDay){
            return true;
        }else{
            return (int)$remindWaitDay - $interval->days;
        }
    }

    /**
     * @param ArticleWorkflow $workflow
     *
     * @return array
     */
    public function getArticleEditors(ArticleWorkflow $workflow)
    {
        $articleEditors = [];
        foreach($workflow->grantedUsers as $grantedUser){
            $articleEditors[] = $grantedUser;
        }

        $dialogRepo = $this->em->getRepository(StepDialog::class);
        $dialogs = $dialogRepo
            ->createQueryBuilder('stepDialog')
            ->join('stepDialog.step', 'dialogStep')
            ->andWhere('stepDialog.dialogType = :dialogType')
            ->setParameter('dialogType', StepActionTypes::ASSIGN_SECTION_EDITOR)
            ->andWhere('dialogStep.articleWorkflow = :workflow')
            ->setParameter('workflow', $workflow)
            ->getQuery()
            ->getResult()
        ;
        /** @var StepDialog $dialog */
        foreach($dialogs as $dialog){
            foreach($dialog->getUsers() as $dialogUser){
                $articleEditors[] = $dialogUser;
            }
        }

        return $articleEditors;
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

    private function getWorkflowSettings(ArticleWorkflow $workflow)
    {
        return $this->em->getRepository(ArticleWorkflowSetting::class)->findOneBy([
            'workflow' => $workflow,
        ]);
    }
}
