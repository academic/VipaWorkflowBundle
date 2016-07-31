<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Entity\Journal;
use Symfony\Component\HttpFoundation\Response;

class WorkflowController extends Controller
{
    /**
     * @return Response
     */
    public function activesAction()
    {
        $workflowService = $this->get('dp.workflow_service');

        return $this->render('DergiparkWorkflowBundle:ArticleWorkflow:_actives.html.twig', [
            'workflows' => $workflowService->getUserRelatedActiveWorkflows(),
        ]);
    }

    /**
     * @param Flow[] $flows
     * @return array
     */
    private function getFlowContainer($flows)
    {
        return true;
    }

    /**
     * @param Journal $journal
     * @return bool
     */
    private function hasReviewerRoleOnJournal(Journal $journal)
    {
        return true;
    }

    /**
     * @return Response
     */
    public function historyAction()
    {
        return new Response('hello wf history page');
    }
}
