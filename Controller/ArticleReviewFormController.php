<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\DialogPost;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Entity\StepReviewForm;
use Dergipark\WorkflowBundle\Params\DialogPostTypes;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Pagerfanta\Exception\LogicException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleReviewFormController extends Controller
{
    /**
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
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        //#permissioncheck
        if(!$permissionService->isGrantedForDialogPost($dialog)){
            throw new AccessDeniedException;
        }
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($dialog->getStep()->getArticleWorkflow());
        foreach($reviewForms as $reviewFormId){
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
        }
        //log action
        $wfLogger->log('post.review.form.to.dialog', ['%user%' => '@'.$user->getUsername()]);

        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
