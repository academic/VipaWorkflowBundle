<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\DialogPost;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Entity\StepReviewForm;
use Dergipark\WorkflowBundle\Event\WorkflowEvent;
use Dergipark\WorkflowBundle\Event\WorkflowEvents;
use Dergipark\WorkflowBundle\Params\DialogPostTypes;
use Dergipark\WorkflowBundle\Params\StepStatus;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Pagerfanta\Exception\LogicException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ArticleReviewFormController extends Controller
{
    /**
     * Browse article workflow step review forms
     *
     * @param $dialogId
     * @return Response
     */
    public function reviewFormsAction($dialogId)
    {
        $em = $this->getDoctrine()->getManager();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $step  = $dialog->getStep();
        $forms = $em->getRepository(StepReviewForm::class)->findBy([
            'step' => $step,
        ]);

        return $this->render('DergiparkWorkflowBundle:DialogPost/review_form:_browse_review_forms.html.twig', [
            'forms' => $forms,
            'dialogId' => $dialogId,
        ]);
    }

    /**
     * Sync article workflow review form with journal review forms
     *
     * @param $workflowId
     * @param $stepOrder
     *
     * @return Response
     */
    public function syncReviewFormsAction($workflowId, $stepOrder)
    {
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
            'status' => StepStatus::ACTIVE,
        ]);
        $this->throw404IfNotFound($step);
        // sync via workflow service
        $workflowService->syncStepReviewForms($step);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Show article workflow review form
     *
     * @param StepReviewForm $reviewForm
     * @return Response
     */
    public function showFormAction(StepReviewForm $reviewForm)
    {
        return $this->render('DergiparkWorkflowBundle:DialogPost/review_form:_show.html.twig', [
            'reviewForm' => $reviewForm,
        ]);
    }

    /**
     * Preview action for article workflow review form response
     *
     * @param $postId
     * @return Response
     */
    public function previewReviewFormResponseAction($postId)
    {
        $isArticleAuthor = false;
        $accessor = PropertyAccess::createPropertyAccessor();
        $em = $this->getDoctrine()->getManager();
        /** @var DialogPost $responsePost */
        $responsePost = $em->getRepository(DialogPost::class)->find($postId);
        $submitterUsername = $accessor->getValue($responsePost, 'dialog.step.articleWorkflow.article.submitterUser.username');

        if($submitterUsername === $this->getUser()->getUsername()){
            $isArticleAuthor = true;
        }
        return $this->render('DergiparkWorkflowBundle:DialogPost/review_form:_response_preview.html.twig', [
            'post' => $responsePost,
            'isArticleAuthor' => $isArticleAuthor,
        ]);
    }

    /**
     * Reviewer or another article workflow related person
     * review form request response form and send action
     *
     * @param Request $request
     * @param StepReviewForm $reviewForm
     * @param $dialogId
     * @return JsonResponse|Response
     */
    public function submitFormAction(Request $request, StepReviewForm $reviewForm, $dialogId)
    {
        if($request->getMethod() == 'POST'){
            return $this->persistSubmittedForm($request, $reviewForm, $dialogId);
        }
        return $this->render('DergiparkWorkflowBundle:DialogPost/review_form:_submit_form.html.twig', [
            'reviewForm' => $reviewForm,
            'dialogId' => $dialogId,
        ]);
    }

    /**
     * Persist submitted form to dialog and dispatch event
     *
     * @param Request $request
     * @param StepReviewForm $reviewForm
     * @param $dialogId
     * @return JsonResponse
     */
    private function persistSubmittedForm(Request $request, StepReviewForm $reviewForm, $dialogId)
    {
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $formContent = $request->request->get('formContent');
        $reviewFormResponse = new DialogPost();
        $reviewFormResponse
            ->setReviewForm($reviewForm)
            ->setDialog($dialog)
            ->setSendedAt(new \DateTime())
            ->setSendedBy($this->getUser())
            ->setType(DialogPostTypes::TYPE_FORM_RESPONSE)
            ->setReviewFormResponseContent($formContent)
            ;
        $em->persist($reviewFormResponse);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setPost($reviewFormResponse);
        $dispatcher->dispatch(WorkflowEvents::REVIEW_FORM_RESPONSE, $workflowEvent);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * Send selected article workflow forms to dialog related persons
     * and dispatch related event
     *
     * @param Request $request
     * @param $dialogId
     * @return LogicException|JsonResponse
     */
    public function postReviewFormAction(Request $request, $dialogId)
    {
        $permissionService = $this->get('dp.workflow_permission_service');
        $reviewForms = $request->request->get('reviewForms');
        if(!$reviewForms){
            return new LogicException('reviewForms param must be!');
        }
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        //#permissioncheck
        if(!$permissionService->isGrantedForDialogPost($dialog)){
            throw new AccessDeniedException;
        }
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($dialog->getStep()->getArticleWorkflow());
        foreach($reviewForms as $reviewFormId) {
            $stepReviewForm = $em->getRepository(StepReviewForm::class)->find($reviewFormId);
            $reviewFormPost = new DialogPost();
            $reviewFormPost
                ->setDialog($dialog)
                ->setSendedBy($user)
                ->setSendedAt(new \DateTime())
                ->setReviewForm($stepReviewForm)
                ->setType(DialogPostTypes::TYPE_FORM_REQUEST)
            ;
            $em->persist($reviewFormPost);

            //dispatch event
            $workflowEvent = new WorkflowEvent();
            $workflowEvent->setPost($reviewFormPost);
            $dispatcher->dispatch(WorkflowEvents::REVIEW_FORM_REQUEST, $workflowEvent);
        }
        //log action
        $wfLogger->log('post.review.form.to.dialog', ['%user%' => '@'.$user->getUsername()]);

        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    #########################################################################
    #######  hereafter form response send to author logic begins   #########
    #########################################################################

    /**
     * Browse article workflow review form responses
     *
     * @param $dialogId
     * @return Response
     */
    public function reviewFormResponsesAction($dialogId)
    {
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $this->throw404IfNotFound($dialog);
        $step  = $dialog->getStep();

        return $this->render('DergiparkWorkflowBundle:DialogPost/review_form:_browse_review_form_responses.html.twig', [
            'formResponses' => $workflowService->getStepFormResponses($step),
            'dialogId' => $dialogId,
        ]);
    }

    /**
     * send article workflow review form response preview to another user dialog
     *
     * @param Request $request
     * @param $dialogId
     * @return LogicException|JsonResponse
     */
    public function postReviewFormResponsePreviewAction(Request $request, $dialogId)
    {
        $permissionService = $this->get('dp.workflow_permission_service');
        $reviewFormResponses = $request->request->get('reviewFormResponses');
        if(!$reviewFormResponses){
            return new LogicException('reviewFormResponses param must be exist!');
        }
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        //#permissioncheck
        if(!$permissionService->isGrantedForDialogPost($dialog)){
            throw new AccessDeniedException;
        }
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($dialog->getStep()->getArticleWorkflow());
        foreach($reviewFormResponses as $reviewFormResponseId) {
            $stepReviewFormResponse = $em->getRepository(DialogPost::class)->find($reviewFormResponseId);
            $reviewFormPreviewPost = new DialogPost();
            $reviewFormPreviewPost
                ->setDialog($dialog)
                ->setSendedBy($user)
                ->setSendedAt(new \DateTime())
                ->setRelatedPost($stepReviewFormResponse)
                ->setReviewForm($stepReviewFormResponse->getReviewForm())
                ->setType(DialogPostTypes::TYPE_FORM_RESPONSE_PREVIEW)
            ;
            $em->persist($reviewFormPreviewPost);

            //dispatch event
            $workflowEvent = new WorkflowEvent();
            $workflowEvent->setPost($reviewFormPreviewPost);
            $dispatcher->dispatch(WorkflowEvents::REVIEW_FORM_RESPONSE_PREVIEW, $workflowEvent);
        }
        //log action
        $wfLogger->log('post.review.form.to.dialog', ['%user%' => '@'.$user->getUsername()]);

        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
