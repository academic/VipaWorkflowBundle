<?php

namespace Vipa\WorkflowBundle\Controller;

use Vipa\WorkflowBundle\Entity\JournalWorkflowSetting;
use Vipa\WorkflowBundle\Entity\JournalWorkflowStep;
use Vipa\WorkflowBundle\Form\Type\JournalWfSettingType;
use Vipa\WorkflowBundle\Form\Type\JournalWfStepType;
use Vipa\CoreBundle\Controller\VipaController as Controller;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkflowSettingController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $permissionService = $this->get('dp.workflow_permission_service');
        $em = $this->getDoctrine()->getManager();
        $journal = $this->get('vipa.journal_service')->getSelectedJournal();
        //#permissioncheck
        if(!$permissionService->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        $journalSteps = $em->getRepository(JournalWorkflowStep::class)->findBy([
            'journal' => $journal,
        ], ['order' => 'ASC']);
        $journal = $this->get('vipa.journal_service')->getSelectedJournal();
        return $this->render('VipaWorkflowBundle:WorkflowSetting:_workflow_setting.html.twig',[
            'journal' => $journal,
            'steps' => $journalSteps,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function basicSettingAction(Request $request)
    {
        $permissionService = $this->get('dp.workflow_permission_service');
        //#permissioncheck
        if(!$permissionService->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        $journal = $this->get('vipa.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();

        $journalWorkflowSetting = $em->getRepository(JournalWorkflowSetting::class)->findOneBy([
            'journal' => $journal,
        ]);
        if(!$journalWorkflowSetting){
            $journalWorkflowSetting = new JournalWorkflowSetting();
        }
        $journalWorkflowSetting->setJournal($journal);
        $form = $this->createForm(new JournalWfSettingType(), $journalWorkflowSetting, [
            'action' => $this->generateUrl('vipa_workflow_basic_settings', [
                'journalId' => $journal->getId(),
            ])
        ]);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            $em->persist($journalWorkflowSetting);
            $em->flush();
            $this->successFlashBag('successful.update');
        }

        return $this->render('VipaWorkflowBundle:WorkflowSetting:_basic_journal_wf_setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function stepUsersSetupAction(Request $request, $stepOrder)
    {
        $permissionService = $this->get('dp.workflow_permission_service');
        //#permissioncheck
        if(!$permissionService->isHaveEditorRole()){
            throw new AccessDeniedException;
        }
        $journal = $this->get('vipa.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();

        $journalWorkflowStep = $em->getRepository(JournalWorkflowStep::class)->findOneBy([
            'journal' => $journal,
            'order' => $stepOrder,
        ]);
        if(!$journalWorkflowStep){
            $journalWorkflowStep = new JournalWorkflowStep();
        }
        $journalWorkflowStep->setJournal($journal);
        $journalWorkflowStep->setOrder($stepOrder);

        $form = $this->createForm(new JournalWfStepType(), $journalWorkflowStep, [
            'action' => $this->generateUrl('vipa_workflow_step_users_setup', [
                'journalId' => $journal->getId(),
                'stepOrder' => $stepOrder,
            ])
        ]);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            $em->persist($journalWorkflowStep);
            $em->flush();
            $this->successFlashBag('successful.update');
        }

        return $this->render('VipaWorkflowBundle:WorkflowSetting:_workflow_step_setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
