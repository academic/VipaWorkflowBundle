<?php

namespace Dergipark\WorkflowBundle\EventListener;

use Dergipark\WorkflowBundle\Event\WorkflowEvent;
use Dergipark\WorkflowBundle\Event\WorkflowEvents;
use Doctrine\ORM\EntityManager;
use Ojs\CoreBundle\Service\OjsMailer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

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
     * @param RouterInterface $router
     * @param EntityManager $em
     * @param OjsMailer $ojsMailer
     *
     */
    public function __construct(
        RouterInterface $router,
        EntityManager $em,
        OjsMailer $ojsMailer
    ) {
        $this->router = $router;
        $this->em = $em;
        $this->ojsMailer = $ojsMailer;
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

    }

    /**
     * @param WorkflowEvent $event
     */
    public function onReviewFormResponse(WorkflowEvent $event)
    {

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
}
