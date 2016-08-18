<?php

namespace Dergipark\WorkflowBundle\EventListener;

use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Event\WorkflowEvent;
use Dergipark\WorkflowBundle\Event\WorkflowEvents;
use Dergipark\WorkflowBundle\Params\StepActionTypes;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Ojs\CoreBundle\Service\OjsMailer;
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
     * @var OjsMailer
     */
    private $ojsMailer;

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
     *
     * @param RouterInterface       $router
     * @param EntityManager         $em
     * @param OjsMailer             $ojsMailer
     * @param TranslatorInterface   $translator
     */
    public function __construct(
        RouterInterface $router,
        EntityManager $em,
        OjsMailer $ojsMailer,
        TranslatorInterface $translator
    ) {
        $this->router       = $router;
        $this->em           = $em;
        $this->ojsMailer    = $ojsMailer;
        $this->accessor     = PropertyAccess::createPropertyAccessor();
        $this->translator   = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            WorkflowEvents::WORKFLOW_STARTED            => 'onWorkflowStarted',
            WorkflowEvents::REVIEW_FORM_RESPONSE        => 'onReviewFormResponse',
            WorkflowEvents::REVIEW_FORM_REQUEST         => [['dialogAcceptedCheck'], ['onReviewFormRequest']],
            WorkflowEvents::WORKFLOW_GRANT_USER         => 'onWorkflowGrantUser',
            WorkflowEvents::DIALOG_POST_COMMENT         => [['dialogAcceptedCheck'], ['onDialogPostComment']],
            WorkflowEvents::DIALOG_POST_FILE            => [['dialogAcceptedCheck'], ['onDialogPostFile']],
            WorkflowEvents::CREATE_SPESIFIC_DIALOG      => 'onCreateSpecificDialog',
            WorkflowEvents::CREATE_DIALOG_WITH_AUTHOR   => 'onCreateDialogWithAuthor',
            WorkflowEvents::CREATE_BASIC_DIALOG         => 'onCreateBasicDialog',
            WorkflowEvents::STEP_GOTO_ARRANGEMET        => 'onStepGotoArrangement',
            WorkflowEvents::STEP_GOTO_REVIEWING         => 'onStepGotoReviewing',
            WorkflowEvents::ACCEPT_SUBMISSION_DIRECTLY  => 'onAcceptSubmissionDirectly',
            WorkflowEvents::WORKFLOW_FINISH_ACTION      => 'onWorkflowFinishAction',
            WorkflowEvents::DECLINE_SUBMISSION          => 'onDeclineSubmission',
            WorkflowEvents::CLOSE_DIALOG                => 'onCloseDialog',
            WorkflowEvents::REOPEN_DIALOG               => 'onReopenDialog',
            WorkflowEvents::REMOVE_DIALOG               => 'onRemoveDialog',
            WorkflowEvents::REVIEWER_INVITE             => 'onReviewerInvite',
            WorkflowEvents::REVIEWER_REMIND             => 'onReviewerRemind',
            WorkflowEvents::ACCEPT_REVIEW               => 'onAcceptReview',
            WorkflowEvents::REJECT_REVIEW               => 'onRejectReview',
        );
    }

    /**
     * @param WorkflowEvent $event
     *
     * @return WorkflowEvent|null
     */
    public function dialogAcceptedCheck(WorkflowEvent $event)
    {
        if($event->dialog === null) {
            return null;
        }
        if($event->dialog->getDialogType() == StepActionTypes::ASSIGN_REVIEWER && !$event->dialog->isAccepted()) {
            $event->stopPropagation();
        }

        return $event;
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowStarted(WorkflowEvent $event)
    {
        $accessor = $this->accessor;
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::WORKFLOW_STARTED);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags($event->workflow->relatedUsers, $this->getJournalEditors());
        foreach ($mailUsers as $user) {
            $transformParams = [
                'article.author'    => $accessor->getValue($event, 'article.submitterUser.username'),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewFormResponse(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REVIEW_FORM_RESPONSE);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags($event->dialog->getUsers(), [$event->dialog->getCreatedDialogBy()]);
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'     => $this->getDialogTitle($event->dialog),
                'form.name'     => $event->post->getReviewForm()->getName(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewFormRequest(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REVIEW_FORM_REQUEST);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags($event->dialog->getUsers(), [$event->dialog->getCreatedDialogBy()]);
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'     => $this->getDialogTitle($event->dialog),
                'form.name'     => $event->post->getReviewForm()->getName(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowGrantUser(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::WORKFLOW_GRANT_USER);
        if(!$getMailEvent){
            return;
        }
        $transformParams = [
            'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
            'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'journal'           => $event->journal->getTitle(),
            'receiver.username' => $event->user->getUsername(),
            'receiver.fullName' => $event->user->getFullName(),
            'article.title'     => $event->article->getTitle(),
        ];
        $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
        $this->ojsMailer->sendToUser(
            $event->user,
            $getMailEvent->getSubject(),
            $template
        );
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDialogPostComment(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::DIALOG_POST_COMMENT);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->dialog->getUsers(),
            [$event->dialog->getCreatedDialogBy()],
            $event->step->grantedUsers,
            $event->workflow->grantedUsers
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'     => $this->getDialogTitle($event->dialog),
                'post.content'     => $event->post->getText(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDialogPostFile(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::DIALOG_POST_FILE);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->dialog->getUsers(),
            [$event->dialog->getCreatedDialogBy()],
            $event->step->grantedUsers,
            $event->workflow->grantedUsers
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'     => $this->getDialogTitle($event->dialog),
                'file.name'     => $event->post->getFileOriginalName(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateSpecificDialog(WorkflowEvent $event)
    {
        if($event->dialog->getDialogType() == StepActionTypes::ASSIGN_REVIEWER){
            return;
        }
        $eventName = WorkflowEvents::CREATE_SPESIFIC_DIALOG;
        if($event->dialog->getDialogType() == StepActionTypes::ASSIGN_SECTION_EDITOR){
            $eventName = WorkflowEvents::CREATE_SPESIFIC_DIALOG.'.assign.section.editor';
        }
        $getMailEvent = $this->ojsMailer->getEventByName($eventName);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->dialog->getUsers(),
            [$event->dialog->getCreatedDialogBy()],
            $event->step->grantedUsers,
            $event->workflow->grantedUsers
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'     => $this->getDialogTitle($event->dialog),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateDialogWithAuthor(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::CREATE_DIALOG_WITH_AUTHOR);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->dialog->getUsers(),
            [$event->dialog->getCreatedDialogBy()],
            $event->step->grantedUsers,
            $event->workflow->grantedUsers
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateBasicDialog(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::CREATE_BASIC_DIALOG);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->dialog->getUsers(),
            [$event->dialog->getCreatedDialogBy()],
            $event->step->grantedUsers,
            $event->workflow->grantedUsers
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'      => $event->dialog->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onStepGotoArrangement(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::STEP_GOTO_ARRANGEMET);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->workflow->relatedUsers,
            $this->getJournalEditors()
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onStepGotoReviewing(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::STEP_GOTO_REVIEWING);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->workflow->relatedUsers,
            $this->getJournalEditors()
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onAcceptSubmissionDirectly(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::ACCEPT_SUBMISSION_DIRECTLY);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->workflow->relatedUsers,
            $this->getJournalEditors()
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowFinishAction(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::WORKFLOW_FINISH_ACTION);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->workflow->relatedUsers,
            $this->getJournalEditors()
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                //@todo generate history link here
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDeclineSubmission(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::DECLINE_SUBMISSION);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->workflow->relatedUsers
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCloseDialog(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::CLOSE_DIALOG);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->workflow->grantedUsers,
            $event->step->grantedUsers,
            $event->dialog->users,
            [$event->dialog->createdDialogBy]
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'      => $this->getDialogTitle($event->dialog)
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReopenDialog(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REOPEN_DIALOG);
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->workflow->grantedUsers,
            $event->step->grantedUsers,
            $event->dialog->users,
            [$event->dialog->createdDialogBy]
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'dialog.title'      => $this->getDialogTitle($event->dialog)
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewerInvite(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REVIEWER_INVITE.'.to.reviewer');
        if(!$getMailEvent){
            goto sendmailtoeditors;
        }
        $reviewerUser = $event->dialog->users->first();
        $transformParams = [
            'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
            'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'journal'           => $event->journal->getTitle(),
            'receiver.username' => $reviewerUser->getUsername(),
            'receiver.fullName' => $reviewerUser->getFullName(),
            'article.title'     => $event->article->getTitle(),
            'accept.link'     => $this->router->generate('dp_workflow_dialog_accept_review' ,[
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
                'stepOrder' => $event->step->getId(),
                'dialogId' => $event->dialog->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'reject.link'     => $this->router->generate('dp_workflow_dialog_reject_review' ,[
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
                'stepOrder' => $event->step->getId(),
                'dialogId' => $event->dialog->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
        $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
        $this->ojsMailer->sendToUser(
            $reviewerUser,
            $getMailEvent->getSubject(),
            $template
        );

        sendmailtoeditors:

        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REVIEWER_INVITE.'.to.editor');
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->step->grantedUsers,
            [$event->dialog->createdDialogBy]
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewerRemind(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REVIEWER_REMIND.'.to.reviewer');
        if(!$getMailEvent){
            goto sendmailtoeditors;
        }
        $reviewerUser = $event->dialog->users->first();
        $transformParams = [
            'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
            'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'journal'           => $event->journal->getTitle(),
            'receiver.username' => $reviewerUser->getUsername(),
            'receiver.fullName' => $reviewerUser->getFullName(),
            'article.title'     => $event->article->getTitle(),
            'accept.link'     => $this->router->generate('dp_workflow_dialog_accept_review' ,[
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
                'stepOrder' => $event->step->getId(),
                'dialogId' => $event->dialog->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'reject.link'     => $this->router->generate('dp_workflow_dialog_reject_review' ,[
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
                'stepOrder' => $event->step->getId(),
                'dialogId' => $event->dialog->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ];
        $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
        $this->ojsMailer->sendToUser(
            $reviewerUser,
            $getMailEvent->getSubject(),
            $template
        );

        sendmailtoeditors:

        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REVIEWER_REMIND.'.to.editor');
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->step->grantedUsers,
            [$event->dialog->createdDialogBy]
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onAcceptReview(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::ACCEPT_REVIEW.'.to.reviewer');
        /** @var User $reviewerUser */
        $reviewerUser = $event->dialog->users->first();
        if(!$getMailEvent){
            goto sendmailtoeditors;
        }
        $transformParams = [
            'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
            'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'journal'           => $event->journal->getTitle(),
            'receiver.username' => $reviewerUser->getUsername(),
            'receiver.fullName' => $reviewerUser->getFullName(),
            'article.title'     => $event->article->getTitle(),
        ];
        $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
        $this->ojsMailer->sendToUser(
            $reviewerUser,
            $getMailEvent->getSubject(),
            $template
        );

        sendmailtoeditors:

        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::ACCEPT_REVIEW.'.to.editor');
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->step->grantedUsers,
            [$event->dialog->createdDialogBy]
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'reviewer.username' => $reviewerUser->getUsername(),
                'reviewer.fullName' => $reviewerUser->getFullName(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
    }

    /**
     * @param WorkflowEvent $event
     */
    public function onRejectReview(WorkflowEvent $event)
    {
        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REJECT_REVIEW.'.to.reviewer');
        /** @var User $reviewerUser */
        $reviewerUser = $event->dialog->users->first();
        if(!$getMailEvent){
            goto sendmailtoeditors;
        }
        $transformParams = [
            'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
            'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                'journalId' => $event->journal->getId(),
                'workflowId' => $event->workflow->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'journal'           => $event->journal->getTitle(),
            'receiver.username' => $reviewerUser->getUsername(),
            'receiver.fullName' => $reviewerUser->getFullName(),
            'article.title'     => $event->article->getTitle(),
        ];
        $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
        $this->ojsMailer->sendToUser(
            $reviewerUser,
            $getMailEvent->getSubject(),
            $template
        );

        sendmailtoeditors:

        $getMailEvent = $this->ojsMailer->getEventByName(WorkflowEvents::REJECT_REVIEW.'.to.editor');
        if(!$getMailEvent){
            return;
        }
        $mailUsers = $this->mergeUserBags(
            $event->step->grantedUsers,
            [$event->dialog->createdDialogBy]
        );
        foreach ($mailUsers as $user) {
            $transformParams = [
                'done.by'    => $this->ojsMailer->currentUser()->getUsername(),
                'related.link'      => $this->router->generate('dergipark_workflow_article_workflow', [
                    'journalId' => $event->journal->getId(),
                    'workflowId' => $event->workflow->getId(),
                ], UrlGeneratorInterface::ABSOLUTE_URL),
                'journal'           => $event->journal->getTitle(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getTitle(),
                'reviewer.username' => $reviewerUser->getUsername(),
                'reviewer.fullName' => $reviewerUser->getFullName(),
            ];
            $template = $this->ojsMailer->transformTemplate($getMailEvent->getTemplate(), $transformParams);
            $this->ojsMailer->sendToUser(
                $user,
                $getMailEvent->getSubject(),
                $template
            );
        }
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
}
