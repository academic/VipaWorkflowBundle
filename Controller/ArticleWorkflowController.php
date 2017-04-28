<?php

namespace Vipa\WorkflowBundle\Controller;

use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Vipa\WorkflowBundle\Entity\ArticleWorkflowStep;
use Vipa\WorkflowBundle\Event\WorkflowEvent;
use Vipa\WorkflowBundle\Event\WorkflowEvents;
use Vipa\WorkflowBundle\Form\Type\ArticleWfGrantedUsersType;
use Vipa\WorkflowBundle\Params\StepStatus;
use Vipa\CoreBundle\Controller\VipaController as Controller;
use Vipa\JournalBundle\Entity\Journal;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ArticleWorkflowController extends Controller
{
    /**
     * @param $workflowId
     * @return Response
     */
    public function timelineAction($workflowId)
    {
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $this->throw404IfNotFound($workflow);
        //#permissioncheck
        if(!$this->get('dp.workflow_permission_service')->isInWorkflowRelatedUsers($workflow)){
            throw new AccessDeniedException;
        }
        return $this->render('VipaWorkflowBundle:ArticleWorkflow:_timeline.html.twig', [
            'timeline' => $workflowService->getWorkflowTimeline($workflow),
        ]);
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function stepAction($workflowId, $stepOrder)
    {
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        //#permissioncheck
        if(!$this->get('dp.workflow_permission_service')->isInWorkflowRelatedUsers($workflow)){
            throw new AccessDeniedException;
        }
        if($step->getStatus() == StepStatus::NOT_OPENED){
            return $this->render('VipaWorkflowBundle:ArticleWorkflow/steps:_not_opened.html.twig');
        }

        return $this->render('VipaWorkflowBundle:ArticleWorkflow:steps/_step_'.$stepOrder.'.html.twig', [
            'step' => $step,
        ]);
    }

    /**
     * @param $workflowId
     * @return Response
     */
    public function historyLogAction($workflowId)
    {
        //#permissioncheck
        if(!$this->get('dp.workflow_permission_service')->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);

        return $this->render('VipaWorkflowBundle:ArticleWorkflow:_history_log.html.twig', [
            'logs' => $workflowService->getWorkflowLogs($workflow),
        ]);
    }

    /**
     * @param $workflowId
     * @return Response
     */
    public function permissionTableAction($workflowId)
    {
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);

        //#permissioncheck
        if(!$this->get('dp.workflow_permission_service')->isInWorkflowRelatedUsers($workflow)){
            throw new AccessDeniedException;
        }
        return $this->render('VipaWorkflowBundle:ArticleWorkflow:_permission_table.html.twig', [
            'permissions' => $workflowService->getPermissionsContainer($workflow),
            'workflow' => $workflow,
        ]);
    }

    /**
     * @param $workflowId
     * @return Response
     */
    public function articleDetailAction($workflowId)
    {
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        //#permissioncheck
        if(!$this->get('dp.workflow_permission_service')->isInWorkflowRelatedUsers($workflow)){
            throw new AccessDeniedException;
        }

        return $workflowService->getArticleDetail($workflow);
    }

    /**
     * @param $workflowId
     * @return Response
     */
    public function uploadReviewVersionFileAction(Request $request, $workflowId)
    {
        $file = $request->request->get('file');
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        //#permissioncheck
        if(!$this->get('dp.workflow_permission_service')->isGrantedForStep($workflow->getCurrentStep())){
            throw new AccessDeniedException;
        }
        $workflow->setReviewVersionFile($file['filename']);
        $em->persist($workflow);
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param Request $request
     * @param $workflowId
     * @return Response
     */
    public function grantedUsersSetupAction(Request $request, $workflowId)
    {
        $permissionService = $this->get('dp.workflow_permission_service');
        //#permissioncheck
        if(!$permissionService->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        $journal = $this->get('vipa.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $dispatcher = $this->get('event_dispatcher');

        $workflow = $em->getRepository(ArticleWorkflow::class)->find($workflowId);
        $oldUsersIdBag = [];
        foreach($workflow->grantedUsers as $oldUser){
            $oldUsersIdBag[] = $oldUser->getId();
        }

        $form = $this->createForm(new ArticleWfGrantedUsersType(), $workflow, [
            'action' => $this->generateUrl('vipa_article_workflow_granted_users_setup', [
                'journalId' => $journal->getId(),
                'workflowId' => $workflowId,
            ]),
            'journalId' => $journal->getId(),
            'roles' => [
                'ROLE_SECTION_EDITOR',
                'ROLE_CO_EDITOR',
                'ROLE_EDITOR',
            ],
        ]);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            foreach($workflow->getGrantedUsers() as $grantedUser){
                $workflow->addRelatedUser($grantedUser);
            }
            $em->persist($workflow);
            $em->flush();
            $this->successFlashBag('successful.update');

            //dispatch events
            foreach($workflow->getGrantedUsers() as $user) {
                if(!in_array($user->getId(), $oldUsersIdBag)) {
                    $workflowEvent = new WorkflowEvent();
                    $workflowEvent
                        ->setUser($user)
                        ->setWorkflow($workflow);
                    $dispatcher->dispatch(WorkflowEvents::WORKFLOW_GRANT_USER, $workflowEvent);
                }
            }
        }

        return $this->render('VipaWorkflowBundle:ArticleWorkflow:_article_workflow_granted_users_setup.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
