<?php

namespace Ojs\WorkflowBundle\Controller;

use Ojs\WorkflowBundle\Entity\ArticleWorkflowStep;
use Ojs\WorkflowBundle\Entity\DialogPost;
use Ojs\WorkflowBundle\Entity\StepDialog;
use Ojs\WorkflowBundle\Event\WorkflowEvent;
use Ojs\WorkflowBundle\Event\WorkflowEvents;
use Ojs\WorkflowBundle\Form\Type\DialogType;
use Ojs\WorkflowBundle\Params\StepActionTypes;
use Ojs\WorkflowBundle\Params\StepDialogStatus;
use Ojs\WorkflowBundle\Params\StepStatus;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Entity\Journal;
use Ojs\JournalBundle\Form\Type\JournalUsersFieldType;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

        return $this->render('OjsWorkflowBundle:StepDialog:_step_dialogs.html.twig', [
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
        $dispatcher = $this->get('event_dispatcher');
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

        $roles = [];
        if(isset(StepActionTypes::$dialogRoles[$actionType])){
            $roles = StepActionTypes::$dialogRoles[$actionType];
        }
        $form = $this->createForm(new DialogType(), $dialog, [
            'action' => $request->getUri(),
            'action_alias' => $actionAlias,
            'journalId' => $journal->getId(),
            'roles' => $roles,
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

            //dispatch event
            $workflowEvent = new WorkflowEvent();
            $workflowEvent->setDialog($dialog);
            $dispatcher->dispatch(WorkflowEvents::CREATE_SPESIFIC_DIALOG, $workflowEvent);

            return $workflowService->getMessageBlock('successful_create'.$actionAlias);
        }
        $formBlock = '_specific_form';
        if($actionType == StepActionTypes::ASSIGN_SECTION_EDITOR){
            $formBlock = '_assign_section_editor_form';
        }

        return $workflowService->getFormBlock($formBlock, [
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
        $actionType = StepActionTypes::ASK_AUTHOR_FOR_CORRECTION;
        $actionAlias = StepActionTypes::$typeAlias[$actionType];
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $user = $this->getUser();

        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $wfLogger = $this->get('dp.wf_logger_service');
        $logUsers = [];
        $dispatcher = $this->get('event_dispatcher');
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
            ->addUser($workflow->getArticle()->getSubmitterUser())
        ;

        $form = $this->createForm(new DialogType(), $dialog, [
            'action' => $request->getUri(),
            'action_alias' => $actionAlias,
            'journalId' => $journal->getId(),
        ]);
        $form = $this->reviseFormForAssignAuthor($form);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            //if action type is assign review, then persist seperate dialog for each of them
            $em->persist($dialog);
            $em->persist($step);
            $em->persist($workflow);

            //log action
            $wfLogger->log($actionAlias.'assign.issue.to.author_log', [
                '%author%' => $workflow->getArticle()->getSubmitterUser()->getUsername(),
                '%by_user%' => $user->getUsername(),
            ]);
            $em->flush();

            //dispatch event
            $workflowEvent = new WorkflowEvent();
            $workflowEvent->setDialog($dialog);
            $dispatcher->dispatch(WorkflowEvents::CREATE_DIALOG_WITH_AUTHOR, $workflowEvent);

            return $workflowService->getMessageBlock('successful_create'.$actionAlias);
        }

        return $workflowService->getFormBlock('_ask_author_for_correction', [
            'form' => $form->createView(),
            'actionAlias' => $actionAlias,
        ]);
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
        $dispatcher = $this->get('event_dispatcher');
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

            //dispatch event
            $workflowEvent = new WorkflowEvent();
            $workflowEvent->setDialog($dialog);
            $dispatcher->dispatch(WorkflowEvents::CREATE_BASIC_DIALOG, $workflowEvent);

            return $workflowService->getMessageBlock('successful_create'.$actionAlias);
        }

        return $workflowService->getFormBlock('_basic_form', [
            'form' => $form->createView(),
            'actionAlias' => $actionAlias,
        ]);
    }

    /**
     * @param Request $request
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function createAssignReviewerDialogAction(Request $request, $workflowId, $stepOrder)
    {
        //set vars
        $actionType = StepActionTypes::ASSIGN_REVIEWER;
        $actionAlias = StepActionTypes::$typeAlias[$actionType];
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $user = $this->getUser();

        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $wfLogger = $this->get('dp.wf_logger_service');
        $logUsers = [];
        $dispatcher = $this->get('event_dispatcher');
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
        ]);
        $form = $this->reviseFormForAssignReviewer($form, $journal);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            //if action type is assin review, then persist seperate dialog for each of them
            foreach($dialog->getUsers() as $reviewerUser){
                $logUsers[] = $reviewerUser->getUsername();
                $workflow->addRelatedUser($reviewerUser);

                $reviewDialog = new StepDialog();
                $reviewDialog
                    ->setDialogType(StepActionTypes::ASSIGN_REVIEWER)
                    ->setOpenedAt(new \DateTime())
                    ->setStatus(StepDialogStatus::ACTIVE)
                    ->setStep($step)
                    ->setTitle($dialog->getTitle())
                    ->setCreatedDialogBy($user)
                    ->addUser($reviewerUser)
                ;
                $em->persist($reviewDialog);
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

        return $workflowService->getFormBlock('_assign_reviewer', [
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
        $dispatcher = $this->get('event_dispatcher');
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

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setWorkflow($workflow);
        $dispatcher->dispatch(WorkflowEvents::STEP_GOTO_ARRANGEMET, $workflowEvent);

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
        $dispatcher = $this->get('event_dispatcher');
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

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setWorkflow($workflow);
        $dispatcher->dispatch(WorkflowEvents::STEP_GOTO_REVIEWING, $workflowEvent);

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
        $dispatcher = $this->get('event_dispatcher');
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        $workflowService->acceptSubmission($workflow);

        //log action
        $wfLogger->log('accept.submission_log', ['%by_user%' => $this->getUser()->getUsername()]);

        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setWorkflow($workflow);
        $dispatcher->dispatch(WorkflowEvents::ACCEPT_SUBMISSION_DIRECTLY, $workflowEvent);

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
    public function finishWorkflowAction($workflowId, $stepOrder)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $dispatcher = $this->get('event_dispatcher');
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
        $workflowService->finishWorkflow($workflow);
        //log action
        $wfLogger->log('finish_workflow_log', [
            '%by_user%' => $this->getUser()->getUsername(),
        ]);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setWorkflow($workflow);
        $dispatcher->dispatch(WorkflowEvents::WORKFLOW_FINISH_ACTION, $workflowEvent);

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
        $dispatcher = $this->get('event_dispatcher');
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
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

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setWorkflow($workflow);
        $dispatcher->dispatch(WorkflowEvents::DECLINE_SUBMISSION, $workflowEvent);

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
        $dispatcher = $this->get('event_dispatcher');
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

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setDialog($dialog);
        $dispatcher->dispatch(WorkflowEvents::CLOSE_DIALOG, $workflowEvent);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return JsonResponse
     */
    public function reopenDialogAction($workflowId, $stepOrder, $dialogId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $dispatcher = $this->get('event_dispatcher');
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
        $dialog->setStatus(StepDialogStatus::ACTIVE);
        $em->persist($dialog);

        //log action
        $wfLogger->log('reopen.dialog_log', ['%by_user%' => $this->getUser()->getUsername()]);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setDialog($dialog);
        $dispatcher->dispatch(WorkflowEvents::REOPEN_DIALOG, $workflowEvent);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return JsonResponse
     */
    public function removeDialogAction($workflowId, $stepOrder, $dialogId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $permissionService = $this->get('dp.workflow_permission_service');
        $workflowService = $this->get('dp.workflow_service');
        $dispatcher = $this->get('event_dispatcher');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $wfLogger = $this->get('dp.wf_logger_service')->setArticleWorkflow($workflow);
        //fetch dialog
        /** @var StepDialog $dialog */
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $dialogPosts = $em->getRepository(DialogPost::class)->findBy([
            'dialog' => $dialog,
        ]);
        $this->throw404IfNotFound($dialog);
        //#permissioncheck
        if(!$permissionService->isGrantedForDialogPost($dialog)){
            throw new AccessDeniedException;
        }

        foreach($dialogPosts as $post){
            $em->remove($post);
        }
        /**
         * remove dialog
         * and we can remove dialog users from workflow related users
         * but maybe user have another dialog or role on workflow
         * we can check this stitution but it will take much time
         * we can do apply this logic some time later
         */
        $em->remove($dialog);

        //log action
        $wfLogger->log('remove.dialog_log', ['%by_user%' => $this->getUser()->getUsername()]);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setDialog($dialog);
        $dispatcher->dispatch(WorkflowEvents::CLOSE_DIALOG, $workflowEvent);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @param $dialogId
     * @return JsonResponse
     */
    public function inviteReviewerAction($workflowId, $stepOrder, $dialogId)
    {
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $dialog->setInviteTime(new \DateTime());
        $em->persist($dialog);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setDialog($dialog);
        $dispatcher->dispatch(WorkflowEvents::REVIEWER_INVITE, $workflowEvent);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @param $dialogId
     *
     * @return JsonResponse
     */
    public function remindReviewerAction($workflowId, $stepOrder, $dialogId)
    {
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $dialog->setRemindingTime(new \DateTime());
        $em->persist($dialog);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setDialog($dialog);
        $dispatcher->dispatch(WorkflowEvents::REVIEWER_REMIND, $workflowEvent);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param Request $request
     * @param $workflowId
     * @param $stepOrder
     * @param $dialogId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function acceptReviewAction(Request $request, $workflowId, $stepOrder, $dialogId)
    {
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $workflow = $dialog->getStep()->getArticleWorkflow();
        $dialog->setAccepted(true);
        $dialog->setAcceptTime(new \DateTime());
        $em->persist($dialog);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setDialog($dialog);
        $dispatcher->dispatch(WorkflowEvents::ACCEPT_REVIEW, $workflowEvent);

        if($request->isXmlHttpRequest()){
            return new JsonResponse([
                'success' => true,
            ]);
        }else{
            return $this->redirectToRoute('ojs_workflow_article_workflow', [
                'workflowId' => $workflow->getId(),
                'journalId' => $workflow->getJournal()->getId(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param $workflowId
     * @param $stepOrder
     * @param $dialogId
     *
     * @return JsonResponse|RedirectResponse
     */
    public function rejectReviewAction(Request $request, $workflowId, $stepOrder, $dialogId)
    {
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);
        $journal = $dialog->getStep()->getArticleWorkflow()->getJournal();
        $dialog->setRejected(true);
        $dialog->setStatus(StepDialogStatus::CLOSED);
        $em->persist($dialog);
        $em->flush();

        //dispatch event
        $workflowEvent = new WorkflowEvent();
        $workflowEvent->setDialog($dialog);
        $dispatcher->dispatch(WorkflowEvents::REJECT_REVIEW, $workflowEvent);

        if($request->isXmlHttpRequest()){
            return new JsonResponse([
                'success' => true,
                'redirectUrl' => $this->generateUrl('ojs_workflow_flow_active', [
                    'journalId' => $journal->getId(),
                ])
            ]);
        }else{
            return $this->redirectToRoute('ojs_workflow_flow_active', [
                'journalId' => $journal->getId(),
            ]);
        }
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

    /**
     * @param FormInterface $form
     * @param Journal $journal
     * @return FormInterface
     */
    private function reviseFormForAssignReviewer(FormInterface $form, Journal $journal)
    {
        $roles = StepActionTypes::$dialogRoles[StepActionTypes::ASSIGN_REVIEWER];
        $form
            ->remove('users')
            ->add('title')
            ->add('users', JournalUsersFieldType::class,[
                'attr' => [
                    'style' => 'width: 100%;',
                ],
                'label' =>'_assign_reviewer.users',
                'remote_params' => [
                    'journalId' => $journal->getId(),
                    'roles' => implode(',', $roles),
                ]
            ]);

        return $form;
    }

    /**
     * @param FormInterface $form
     * @return FormInterface
     */
    private function reviseFormForAssignAuthor(FormInterface $form)
    {
        $form
            ->remove('users')
            ->add('title')
        ;

        return $form;
    }
}
