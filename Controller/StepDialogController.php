<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Form\Type\DialogType;
use Dergipark\WorkflowBundle\Params\StepActionTypes;
use Dergipark\WorkflowBundle\Params\StepDialogStatus;
use Dergipark\WorkflowBundle\Params\StepStatus;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Form\Type\JournalUsersFieldType;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StepDialogController extends Controller
{
    public function getDialogsAction($workflowId, $stepOrder)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        //#permissioncheck
        if(!$permissionService->isInWorkflowRelatedUsers($workflow)){
            throw new AccessDeniedException;
        }
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        $dialogs = $workflowService->getUserRelatedStepDialogs($workflow, $step);

        return $this->render('DergiparkWorkflowBundle:StepDialog:_step_dialogs.html.twig', [
            'dialogs' => $dialogs,
            'step' => $step,
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
        $wfLogger = $this->get('dp.wf_logger_service');
        $logUsers = [];
        $workflowService = $this->get('dp.workflow_service');
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger->setArticleWorkflow($workflow);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        $this->throw404IfNotFound($step);
        //#permissioncheck
        if(!$permissionService->isGrantedForStep($step)){
            throw new AccessDeniedException;
        }

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
            foreach($dialog->getUsers() as $dialogUser){
                $logUsers[] = $dialogUser->getUsername();
                $workflow->addRelatedUser($dialogUser);
                //if action like section editor add to step granted users
                if($actionType == StepActionTypes::ASSIGN_SECTION_EDITOR){
                    $step->addGrantedUser($dialogUser);
                }
            }
            //if action type is assin review, then persist seperate dialog for each of them
            if($actionType == StepActionTypes::ASSIGN_REVIEWER && $dialog->users->count()>1){
                foreach($dialog->users as $reviewerUser){
                    $reviewDialog = new StepDialog();
                    $reviewDialog
                        ->setDialogType(StepActionTypes::ASSIGN_REVIEWER)
                        ->setOpenedAt(new \DateTime())
                        ->setStatus(StepDialogStatus::ACTIVE)
                        ->setStep($step)
                        ->setCreatedDialogBy($user)
                        ->addUser($reviewerUser)
                    ;
                    $em->persist($reviewDialog);
                }
            }else{
                $em->persist($dialog);
            }
            $em->persist($step);
            $em->persist($workflow);

            //log action
            $wfLogger->log($actionAlias.'_log.action', [
                '%users%' => implode(',', $logUsers),
                '%by_user%' => $user->getUsername(),
            ]);
            $em->flush();

            return $workflowService->getMessageBlock('successful_create'.$actionAlias);
        }

        return $workflowService->getFormBlock('_specific_form', [
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
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        $this->throw404IfNotFound($step);
        //#permissioncheck
        if(!$permissionService->isGrantedForStep($step)){
            throw new AccessDeniedException;
        }
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

        //log action
        $wfLogger->log('assign.issue.to.author_log', [
            '%author%' => $articleSubmitter->getUsername(),
            '%by_user%' => $user->getUsername(),
        ]);
        $em->flush();

        return $workflowService->getMessageBlock('successful_create'.$actionAlias);
    }

    /**
     * @param Request $request
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
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        $this->throw404IfNotFound($step);
        //#permissioncheck
        if(!$permissionService->isGrantedForStep($step)){
            throw new AccessDeniedException;
        }

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
            foreach($dialog->getUsers() as $issueUser){
                $issueUsers[] = $issueUser->getUsername();
                $workflow->addRelatedUser($issueUser);
            }
            $em->persist($workflow);
            $em->persist($dialog);

            //log action
            $wfLogger->log('create.issue_log', [
                '%issue_users%' => implode(',', $issueUsers),
                '%by_user%' => $user->getUsername(),
            ]);

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
     * @return JsonResponse
     */
    public function acceptGotoArrangementAction($workflowId, $stepOrder)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();

        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        //#permissioncheck
        if(!$permissionService->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        //do action
        $workflowService->gotoArrangement($workflow);

        //log action
        $wfLogger->log('accept.article.and.goto.arrangement', [
            '%by_user%' => $this->getUser()->getUsername(),
        ]);
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function gotoReviewingAction($workflowId, $stepOrder)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        //#permissioncheck
        if(!$permissionService->isGrantedForStep($step)){
            throw new AccessDeniedException;
        }
        $workflowService->gotoReview($workflow);
        //log action
        $wfLogger->log('goto.review_log', [
            '%by_user%' => $this->getUser()->getUsername(),
        ]);
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param $workflowId
     * @return JsonResponse
     */
    public function acceptSubmissionAction($workflowId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $permissionService = $this->get('dp.workflow_permission_service');
        //#permissioncheck
        if(!$permissionService->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        $workflowService->acceptSubmission($workflow);

        //log action
        $wfLogger->log('accept.submission_log', ['%by_user%' => $this->getUser()->getUsername()]);

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'data' => [
                'redirectUrl' => $this->generateUrl('ojs_journal_article_show', [
                    'journalId' => $workflow->getArticle()->getJournal()->getId(),
                    'id' => $workflow->getArticle()->getId(),
                ])
            ]
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return JsonResponse
     */
    public function declineSubmissionAction($workflowId, $stepOrder)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service');
        //fetch step
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        //#permissioncheck
        if(!$permissionService->isGrantedForStep($step)){
            throw new AccessDeniedException;
        }

        //decline submission
        $workflowService->declineSubmission($workflow);

        //log action
        $wfLogger->log('decline.submission_log', ['%by_user%' => $this->getUser()->getUsername()]);

        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return JsonResponse
     */
    public function finishDialogAction($workflowId, $stepOrder, $dialogId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        //fetch dialog
        /** @var StepDialog $dialog */
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $this->throw404IfNotFound($dialog);
        //#permissioncheck
        if(!$permissionService->isGrantedForDialogPost($dialog)){
            throw new AccessDeniedException;
        }
        $dialog->setStatus(StepDialogStatus::CLOSED);
        $em->persist($dialog);

        //log action
        $wfLogger->log('finish.dialog_log', ['%by_user%' => $this->getUser()->getUsername()]);
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
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
