<?php

namespace Vipa\WorkflowBundle\Controller;

use Vipa\WorkflowBundle\Params\ArticleWorkflowStatus;
use Vipa\CoreBundle\Controller\VipaController as Controller;
use Symfony\Component\HttpFoundation\Response;

class WorkflowController extends Controller
{
    /**
     * @return Response
     */
    public function activesAction()
    {
        $workflowService = $this->get('dp.workflow_service');

        return $this->render('VipaWorkflowBundle:Workflow:_article_workflows.html.twig', [
            'dataContainers' => $workflowService->getUserRelatedWorkflowsContainer(ArticleWorkflowStatus::ACTIVE),
        ]);
    }

    /**
     * @return Response
     */
    public function historyAction()
    {
        $workflowService = $this->get('dp.workflow_service');

        return $this->render('VipaWorkflowBundle:Workflow:_article_workflows.html.twig', [
            'dataContainers' => $workflowService->getUserRelatedWorkflowsContainer(ArticleWorkflowStatus::HISTORY),
        ]);
    }
}
