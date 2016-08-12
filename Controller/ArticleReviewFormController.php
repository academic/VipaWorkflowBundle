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
     * @param $workflowId
     * @param $dialogId
     * @return Response
     */
    public function reviewFormsAction($workflowId, $dialogId)
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
}
