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
            WorkflowEvents::REVIEW_FORM_REQUEST         => 'onReviewFormRequest',
            WorkflowEvents::WORKFLOW_GRANT_USER         => 'onWorkflowGrantUser',
            WorkflowEvents::DIALOG_POST_COMMENT         => 'onDialogPostComment',
            WorkflowEvents::DIALOG_POST_FILE            => 'onDialogPostFile',
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
        );
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
                'journal'           => $event->journal->getId(),
                'receiver.username' => $user->getUsername(),
                'receiver.fullName' => $user->getFullName(),
                'article.title'     => $event->article->getId(),
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
                'form.name'     => $event->article->getTitle(),
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

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowGrantUser(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDialogPostComment(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDialogPostFile(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateSpecificDialog(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateDialogWithAuthor(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCreateBasicDialog(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onStepGotoArrangement(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onStepGotoReviewing(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onAcceptSubmissionDirectly(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onWorkflowFinishAction(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onDeclineSubmission(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onCloseDialog(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReopenDialog(WorkflowEvent $event)
    {

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onRemoveDialog(WorkflowEvent $event)
    {

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
