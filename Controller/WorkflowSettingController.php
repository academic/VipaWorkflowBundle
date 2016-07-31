<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\JournalWorkflowSetting;
use Dergipark\WorkflowBundle\Form\Type\JournalWfSettingType;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkflowSettingController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('DergiparkWorkflowBundle:WorkflowSetting:_workflow_setting.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function basicSettingAction(Request $request)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');

        $journalWorkflowSetting = $em->getRepository(JournalWorkflowSetting::class)->findOneBy([
            'journal' => $journal,
        ]);
        if(!$journalWorkflowSetting){
            $journalWorkflowSetting = new JournalWorkflowSetting();
        }
        $journalWorkflowSetting->setJournal($journal);
        $form = $this->createForm(new JournalWfSettingType(), $journalWorkflowSetting, [
            'action' => $this->generateUrl('dergipark_workflow_basic_settings', [
                'journalId' => $journal->getId(),
            ])
        ]);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            $em->persist($journalWorkflowSetting);
            $em->flush();
            $this->successFlashBag('successful.update');
        }

        return $this->render('DergiparkWorkflowBundle:WorkflowSetting:_basic_journal_wf_setting.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
