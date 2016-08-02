<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Form\Type\DialogType;
use Dergipark\WorkflowBundle\Params\StepActionTypes;
use Dergipark\WorkflowBundle\Params\StepDialogStatus;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StepDialogController extends Controller
{
    /**
     * @param Request $request
     * @param $workflowId
     * @param $stepOrder
     * @return JsonResponse|Response
     */
    public function createSpecificDialogAction(Request $request, $workflowId, $stepOrder)
    {
        $actionType = $request->get('actionType');
        $actionAlias = StepActionTypes::$typeAlias[$actionType];
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);

        $dialog = new StepDialog();
        $dialog
            ->setDialogType($actionType)
            ->setOpenedAt(new \DateTime())
            ->setStatus(StepDialogStatus::ACTIVE)
            ->setStep($step)
            ;

        $form = $this->createForm(new DialogType(), $dialog, [
            'action' => $request->getUri(),
            'action_alias' => $actionAlias,
        ]);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            $em->persist($dialog);
            $em->flush();

            return $workflowService->getMessageBlock('successful_create'.$actionAlias);
        }

        return $this->render('DergiparkWorkflowBundle:ArticleWorkflow/actions:_specific_dialog.html.twig', [
            'form' => $form->createView(),
            'actionAlias' => $actionAlias
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function createBasicDialogAction($workflowId, $stepOrder)
    {
        return new Response('createBasicDialogAction -> '. $workflowId. '---> '.$stepOrder);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function acceptGotoArrangementAction($workflowId, $stepOrder)
    {
        return new Response('acceptGotoArrangementAction -> '. $workflowId. '---> '.$stepOrder);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function gotoReviewingAction($workflowId, $stepOrder)
    {
        return new Response('gotoReviewingAction -> '. $workflowId. '---> '.$stepOrder);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function acceptSubmissionAction($workflowId, $stepOrder)
    {
        return new Response('acceptSubmissionAction -> '. $workflowId. '---> '.$stepOrder);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function declineSubmissionAction($workflowId, $stepOrder)
    {
        return new Response('declineSubmissionAction -> '. $workflowId. '---> '.$stepOrder);
    }
}
