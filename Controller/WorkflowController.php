<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Params\ArticleWorkflowStatus;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Symfony\Component\HttpFoundation\Response;

class WorkflowController extends Controller
{
    /**
     * @return Response
     */
    public function activesAction()
    {
        $workflowService = $this->get('dp.workflow_service');

        return $this->render('DergiparkWorkflowBundle:Workflow:_article_workflows.html.twig', [
            'dataContainers' => $workflowService->getUserRelatedWorkflowsContainer(ArticleWorkflowStatus::ACTIVE),
        ]);
    }

    /**
     * @return Response
     */
    public function historyAction()
    {
        $workflowService = $this->get('dp.workflow_service');

        return $this->render('DergiparkWorkflowBundle:Workflow:_article_workflows.html.twig', [
            'dataContainers' => $workflowService->getUserRelatedWorkflowsContainer(ArticleWorkflowStatus::HISTORY),
        ]);
    }
}
