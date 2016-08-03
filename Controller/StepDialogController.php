<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Form\Type\DialogType;
use Dergipark\WorkflowBundle\Params\StepActionTypes;
use Dergipark\WorkflowBundle\Params\StepDialogStatus;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Form\Type\JournalUsersFieldType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StepDialogController extends Controller
{
    public function getDialogsAction(Request $request, $workflowId, $stepOrder)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        $dialogs = $em->getRepository(StepDialog::class)->findBy([
            'step' => $step,
        ]);

        return $this->render('DergiparkWorkflowBundle:StepDialog:_step_dialogs.html.twig', [
            'dialogs' => $dialogs,
        ]);
    }

    /**
     * @param Request $request
     * @param $workflowId
     * @param $stepOrder
     * @return JsonResponse|Response
     */
    public function createSpecificDialogAction(Request $request, $workflowId, $stepOrder)
    {
        //set vars
        $actionType = $request->get('actionType');
        $actionAlias = StepActionTypes::$typeAlias[$actionType];
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $user = $this->getUser();

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
            ->setCreatedDialogBy($user)
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

        return $workflowService->getFormBlock('_spesific_form', [
            'form' => $form->createView(),
            'actionAlias' => $actionAlias,
        ]);
    }

    /**
     * @param Request $request
     * @param $workflowId
     * @param $stepOrder
     * @return JsonResponse|Response
     */
    public function createDialogWithAuthorAction(Request $request, $workflowId, $stepOrder)
    {
        //set vars
        $actionType = $request->get('actionType');
        $actionAlias = StepActionTypes::$typeAlias[$actionType];
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $user = $this->getUser();

        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        $articleSubmitter = $workflow->getArticle()->getSubmitterUser();

        $dialog = new StepDialog();
        $dialog
            ->setDialogType($actionType)
            ->setOpenedAt(new \DateTime())
            ->setStatus(StepDialogStatus::ACTIVE)
            ->setStep($step)
            ->setCreatedDialogBy($user)
            ->addUser($articleSubmitter)
        ;
        $em->persist($dialog);
        $em->flush();

        return $workflowService->getMessageBlock('successful_create'.$actionAlias);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function createBasicDialogAction(Request $request, $workflowId, $stepOrder)
    {
        //set vars
        $actionType = $request->get('actionType');
        $actionAlias = StepActionTypes::$typeAlias[$actionType];
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $user = $this->getUser();

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
            ->setCreatedDialogBy($user)
        ;

        $form = $this->createForm(new DialogType(), $dialog, [
            'action' => $request->getUri(),
            'action_alias' => $actionAlias,
        ]);
        $form = $this->reviseFormForBasicUse($form);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            $em->persist($dialog);
            $em->flush();

            return $workflowService->getMessageBlock('successful_create'.$actionAlias);
        }

        return $workflowService->getFormBlock('_basic_form', [
            'form' => $form->createView(),
            'actionAlias' => $actionAlias,
        ]);
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

    /**
     * @param FormInterface $form
     * @return FormInterface
     */
    private function reviseFormForBasicUse(FormInterface $form)
    {
        $form
            ->remove('users')
            ->add('title')
            ->add('users', JournalUsersFieldType::class,[
                'attr' => [
                'style' => 'width: 100%;',
            ],
            'label' => '_create_issue.users',
        ]);

        return $form;
    }
}
