<?php

namespace Dergipark\WorkflowBundle\Controller;

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

        return $this->render('DergiparkWorkflowBundle:Workflow:_actives.html.twig', [
            'workflows' => $workflowService->getUserRelatedActiveWorkflows(),
        ]);
    }
}
