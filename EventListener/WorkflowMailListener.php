<?php

namespace Ojs\WorkflowBundle\EventListener;

use Ojs\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\WorkflowBundle\Entity\ArticleWorkflowSetting;
use Ojs\WorkflowBundle\Entity\StepDialog;
use Ojs\WorkflowBundle\Event\WorkflowEvent;
use Ojs\WorkflowBundle\Event\WorkflowEvents;
use Ojs\WorkflowBundle\Params\JournalWorkflowSteps;
use Ojs\WorkflowBundle\Params\StepActionTypes;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Ojs\CoreBundle\Service\Mailer;
use Ojs\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowMailListener implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var PropertyAccessor
     */
    private $accessor;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * WorkflowMailListener constructor.
     * @param RouterInterface $router
     * @param EntityManager $em
     * @param Mailer $mailer
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RouterInterface $router,
        EntityManager $em,
        Mailer $mailer,
        TranslatorInterface $translator
    ) {
        $this->router       = $router;
        $this->em           = $em;
        $this->mailer    = $mailer;
        $this->accessor     = PropertyAccess::createPropertyAccessor();
        $this->translator   = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::WORKFLOW_STARTED             => 'onWorkflowStarted',
            WorkflowEvents::REVIEW_FORM_RESPONSE         => 'onReviewFormResponse',
            WorkflowEvents::REVIEW_FORM_RESPONSE_PREVIEW => 'onReviewFormResponsePreview',
            WorkflowEvents::REVIEW_FORM_REQUEST          => [['dialogAcceptedCheck'], ['onReviewFormRequest']],
            WorkflowEvents::WORKFLOW_GRANT_USER          => 'onWorkflowGrantUser',
            WorkflowEvents::DIALOG_POST_COMMENT          => [['dialogAcceptedCheck'], ['onDialogPostComment']],
            WorkflowEvents::DIALOG_POST_FILE             => [['dialogAcceptedCheck'], ['onDialogPostFile']],
            WorkflowEvents::CREATE_SPESIFIC_DIALOG       => 'onCreateSpecificDialog',
            WorkflowEvents::CREATE_DIALOG_WITH_AUTHOR    => 'onCreateDialogWithAuthor',
            WorkflowEvents::CREATE_BASIC_DIALOG          => 'onCreateBasicDialog',
            WorkflowEvents::STEP_GOTO_ARRANGEMET         => 'onStepGotoArrangement',
            WorkflowEvents::STEP_GOTO_REVIEWING          => 'onStepGotoReviewing',
            WorkflowEvents::ACCEPT_SUBMISSION_DIRECTLY   => 'onAcceptSubmissionDirectly',
            WorkflowEvents::WORKFLOW_FINISH_ACTION       => 'onWorkflowFinishAction',
            WorkflowEvents::DECLINE_SUBMISSION           => 'onDeclineSubmission',
            WorkflowEvents::CLOSE_DIALOG                 => 'onCloseDialog',
            WorkflowEvents::REOPEN_DIALOG                => 'onReopenDialog',
            WorkflowEvents::REMOVE_DIALOG                => 'onRemoveDialog',
            WorkflowEvents::REVIEWER_INVITE              => 'onReviewerInvite',
            WorkflowEvents::REVIEWER_REMIND              => 'onReviewerRemind',
            WorkflowEvents::ACCEPT_REVIEW                => 'onAcceptReview',
            WorkflowEvents::REJECT_REVIEW                => 'onRejectReview',
            WorkflowEvents::REVIEWER_USER_CREATED        => 'onReviewerCreated',
        ];
    }

    /**
     * @param WorkflowEvent $event
     *
     * @return WorkflowEvent|null
     */
    public function dialogAcceptedCheck(WorkflowEvent $event)
    {
        if ($event->dialog === null) {
            return null;
        }

        if ($event->dialog->getDialogType() == StepActionTypes::ASSIGN_REVIEWER && !$event->dialog->isAccepted()) {
            $event->stopPropagation();
        }

        return $event;
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowStarted(WorkflowEvent $event)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $users = $this->mergeUserBags($event->workflow->relatedUsers);
        $params = ['article.author' => $accessor->getValue($event, 'article.submitterUser.username')];
        $this->sendWorkflowMail($event, WorkflowEvents::WORKFLOW_STARTED, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewFormResponse(WorkflowEvent $event)
    {
        $params = [
            'dialog.title' => $this->getDialogTitle($event->dialog),
            'form.name'    => $event->post->getReviewForm()->getName(),
        ];

        $users = $this->mergeUserBags([$event->dialog->getCreatedDialogBy()]);
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEW_FORM_RESPONSE, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewFormResponsePreview(WorkflowEvent $event)
    {
        $params = [
            'dialog.title' => $this->getDialogTitle($event->dialog),
            'form.name'    => $event->post->getReviewForm()->getName(),
        ];

        $users = $this->mergeUserBags([$event->workflow->getArticle()->getSubmitterUser()]);
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEW_FORM_RESPONSE_PREVIEW, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewFormRequest(WorkflowEvent $event)
    {
        $params = [
            'dialog.title' => $this->getDialogTitle($event->dialog),
            'form.name'    => $event->post->getReviewForm()->getName(),
        ];

        $users = $this->mergeUserBags($event->dialog->getUsers());
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEW_FORM_REQUEST, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowGrantUser(WorkflowEvent $event)
    {
        $this->sendWorkflowMail($event, WorkflowEvents::WORKFLOW_GRANT_USER, $event->user);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDialogPostComment(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags($event->dialog->getUsers(), [$event->dialog->getCreatedDialogBy()]);
        $users = $this->removeUsersFromBag($users, $this->mailer->currentUser());

        $params = [
            'dialog.title' => $this->getDialogTitle($event->dialog),
            'post.content' => $event->post->getText(),
        ];

        $this->sendWorkflowMail($event, WorkflowEvents::DIALOG_POST_COMMENT, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDialogPostFile(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags($event->dialog->getUsers(), [$event->dialog->getCreatedDialogBy()]);
        $users = $this->removeUsersFromBag($users, $this->mailer->currentUser());

        $params = [
            'dialog.title' => $this->getDialogTitle($event->dialog),
            'file.name'    => $event->post->getFileOriginalName(),
        ];

        $this->sendWorkflowMail($event, WorkflowEvents::DIALOG_POST_FILE, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateSpecificDialog(WorkflowEvent $event)
    {
        if ($event->dialog->getDialogType() == StepActionTypes::ASSIGN_REVIEWER) {
            return;
        }

        $name = WorkflowEvents::CREATE_SPESIFIC_DIALOG;

        if ($event->dialog->getDialogType() == StepActionTypes::ASSIGN_SECTION_EDITOR) {
            $name .= '.assign.section.editor';
        }

        $users = $this->mergeUserBags(
            $event->step->grantedUsers,
            $event->dialog->getUsers()
        );

        $params = ['dialog.title' => $this->getDialogTitle($event->dialog)];
        $this->sendWorkflowMail($event, $name, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateDialogWithAuthor(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags(
            $event->step->grantedUsers,
            $event->dialog->getUsers()
        );

        $this->sendWorkflowMail($event, WorkflowEvents::CREATE_DIALOG_WITH_AUTHOR, $users);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateBasicDialog(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags(
            $event->step->grantedUsers,
            $event->dialog->getUsers(),
            [$event->dialog->getCreatedDialogBy()]
        );

        $params = ['dialog.title' => $event->dialog->getTitle()];
        $this->sendWorkflowMail($event, WorkflowEvents::CREATE_BASIC_DIALOG, $users, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onStepGotoArrangement(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags(
            $this->getJournalEditors(),
            [$event->article->getSubmitterUser()],
            $event->workflow->getStepByOrder(JournalWorkflowSteps::ARRANGEMENT_ORDER)->getGrantedUsers()
        );

        $this->sendWorkflowMail($event, WorkflowEvents::STEP_GOTO_ARRANGEMET, $users);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onStepGotoReviewing(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags(
            $this->getJournalEditors(),
            [$event->article->getSubmitterUser()],
            $event->workflow->getStepByOrder(JournalWorkflowSteps::REVIEW_ORDER)->getGrantedUsers()
        );

        $this->sendWorkflowMail($event, WorkflowEvents::STEP_GOTO_REVIEWING, $users);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onAcceptSubmissionDirectly(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags($event->workflow->relatedUsers, $this->getJournalEditors());
        $this->sendWorkflowMail($event, WorkflowEvents::ACCEPT_SUBMISSION_DIRECTLY, $users);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowFinishAction(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags($this->getJournalEditors(), [$event->article->getSubmitterUser()]);
        $this->sendWorkflowMail($event, WorkflowEvents::WORKFLOW_FINISH_ACTION, $users);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDeclineSubmission(WorkflowEvent $event)
    {
        $users = $this->mergeUserBags($this->getJournalEditors(), [$event->article->getSubmitterUser()]);
        $this->sendWorkflowMail($event, WorkflowEvents::DECLINE_SUBMISSION, $users);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCloseDialog(WorkflowEvent $event)
    {
        $mailUsers = $this->mergeUserBags(
            $event->workflow->grantedUsers,
            $event->step->grantedUsers,
            $event->dialog->users
        );

        $params = ['dialog.title' => $this->getDialogTitle($event->dialog)];
        $this->sendWorkflowMail($event, WorkflowEvents::CLOSE_DIALOG, $mailUsers, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReopenDialog(WorkflowEvent $event)
    {
        $mailUsers = $this->mergeUserBags(
            $event->workflow->grantedUsers,
            $event->step->grantedUsers,
            $event->dialog->users
        );

        $params = ['dialog.title' => $this->getDialogTitle($event->dialog)];
        $this->sendWorkflowMail($event, WorkflowEvents::REOPEN_DIALOG, $mailUsers, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewerInvite(WorkflowEvent $event)
    {
        $settings = $this->getWorkflowSettings($event->workflow);
        $reviewerUser = $event->dialog->users->first();

        $linkParams = [
            'workflowId' => $event->workflow->getId(),
            'journalId'  => $event->journal->getId(),
            'dialogId'   => $event->dialog->getId(),
            'stepOrder'  => $event->step->getId(),
        ];

        $acceptLink = $this->router->generate('dp_workflow_dialog_accept_review', $linkParams, UrlGeneratorInterface::ABSOLUTE_URL);
        $rejectLink = $this->router->generate('dp_workflow_dialog_reject_review', $linkParams, UrlGeneratorInterface::ABSOLUTE_URL);

        $params = [
            'accept.link'      => $acceptLink,
            'reject.link'      => $rejectLink,
            'dayLimit'         => $settings->getReviewerWaitDay(),
            'article.abstract' => $event->article->getAbstract(),
            'article.authors'  => implode(', ', $event->article->getArticleAuthors()->toArray()),
        ];

        $editors = $this->mergeUserBags([$event->dialog->createdDialogBy]);
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEWER_INVITE.'.to.reviewer', [$reviewerUser], $params);
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEWER_INVITE.'.to.editor', $editors);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewerRemind(WorkflowEvent $event)
    {
        $linkParams = [
            'stepOrder'  => $event->step->getId(),
            'dialogId'   => $event->dialog->getId(),
            'journalId'  => $event->journal->getId(),
            'workflowId' => $event->workflow->getId(),
        ];

        $acceptLink = $this->router->generate('dp_workflow_dialog_accept_review', $linkParams, UrlGeneratorInterface::ABSOLUTE_URL);
        $rejectLink = $this->router->generate('dp_workflow_dialog_reject_review', $linkParams, UrlGeneratorInterface::ABSOLUTE_URL);
        $reviewerUser = $event->dialog->users->first();

        $reviewerParams = [
            'accept.link'     => $acceptLink,
            'reject.link'     => $rejectLink,
        ];

        $editors = $this->mergeUserBags([$event->dialog->createdDialogBy]);
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEWER_REMIND.'.to.reviewer', [$reviewerUser], $reviewerParams);
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEWER_REMIND.'.to.editor', $editors);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onAcceptReview(WorkflowEvent $event)
    {
        $reviewerUser = $event->dialog->users->first();
        $editors = $this->mergeUserBags([$event->dialog->createdDialogBy]);

        $params = [
            'reviewer.username' => $reviewerUser->getUsername(),
            'reviewer.fullName' => $reviewerUser->getFullName(),
        ];

        $this->sendWorkflowMail($event, WorkflowEvents::ACCEPT_REVIEW.'.to.reviewer', [$reviewerUser]);
        $this->sendWorkflowMail($event, WorkflowEvents::ACCEPT_REVIEW.'.to.editor', $editors, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onRejectReview(WorkflowEvent $event)
    {
        $reviewerUser = $event->dialog->users->first();
        $editors = $this->mergeUserBags([$event->dialog->createdDialogBy]);

        $params = [
            'reviewer.username' => $reviewerUser->getUsername(),
            'reviewer.fullName' => $reviewerUser->getFullName(),
        ];

        $this->sendWorkflowMail($event, WorkflowEvents::REJECT_REVIEW.'.to.reviewer', [$reviewerUser]);
        $this->sendWorkflowMail($event, WorkflowEvents::REJECT_REVIEW.'.to.editor', $editors, $params);
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewerCreated(WorkflowEvent $event)
    {
        $link = $this->router->generate('fos_user_resetting_request', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->sendWorkflowMail($event, WorkflowEvents::REVIEWER_USER_CREATED, [$event->user], ['password.reset.link' => $link]);
    }

    /**
     * we won 't use this function for now because remove dialog action
     * is only valid for invite reviewer action and we don 't want to bother
     * reviewer after decline to invite
     *
     * @param WorkflowEvent $event
     */
    public function onRemoveDialog(WorkflowEvent $event)
    {
        return;
    }

    /**
     * @return ArrayCollection
     */
    private function mergeUserBags()
    {
        $userCollection = new ArrayCollection();
        $userBags = func_get_args();
        foreach($userBags as $userBag){
            foreach($userBag as $user){
                if(!$userCollection->contains($user)){
                    $userCollection->add($user);
                }
            }
        }

        return $userCollection;
    }

    /**
     * @param ArrayCollection $userBag
     * @param array $removeUsers
     * @return ArrayCollection
     */
    private function removeUsersFromBag($userBag, $removeUsers = [])
    {
        foreach($removeUsers as $user){
            if($userBag->contains($user)){
                $userBag->remove($user);
            }
        }

        return $userBag;
    }

    /**
     * @param StepDialog $dialog
     * @return string
     */
    private function getDialogTitle(StepDialog $dialog)
    {
        if($dialog->getDialogType() == StepActionTypes::CREATE_ISSUE){
            return $dialog->getTitle();
        }
        $title = $this->translator->trans(StepActionTypes::$typeAlias[$dialog->getDialogType()].'.dialog.header');
        return $title;
    }

    /**
     * @return mixed
     */
    private function getJournalEditors()
    {
        return $this->em->getRepository('OjsUserBundle:User')->findUsersByJournalRole(
            ['ROLE_EDITOR', 'ROLE_CO_EDITOR']
        );
    }

    private function getWorkflowSettings(ArticleWorkflow $workflow)
    {
        return $this->em->getRepository(ArticleWorkflowSetting::class)->findOneBy([
            'workflow' => $workflow,
        ]);
    }

    private function sendWorkflowMail(
        WorkflowEvent $event,
        string $name,
        $users = [],
        array $extraParams = []
    )
    {
        $linkParams = ['journalId'  => $event->journal->getId(), 'workflowId' => $event->workflow->getId()];
        $link = $this->router->generate('ojs_workflow_article_workflow', $linkParams, UrlGeneratorInterface::ABSOLUTE_URL);

        $params = [
            'related.link'  => $link,
            'article.title' => $event->article->getTitle(),
            'journal'       => $event->journal->getTitle(),
        ];

        if ($users instanceof ArrayCollection) {
            $users = $users->toArray();
        }

        $params = array_merge($params, $extraParams);
        $this->mailer->sendEventMail($name, $users, $params, $event->journal);
    }
}
