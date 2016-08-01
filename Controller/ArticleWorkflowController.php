<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Entity\Journal;
use Symfony\Component\HttpFoundation\Response;

class ArticleWorkflowController extends Controller
{
    /**
     * @return Response
     */
    public function timelineAction($workflowId)
    {
        $workflowService = $this->get('dp.workflow_service');
        $articleWorkflow = $workflowService->getArticleWorkflow($workflowId);

        return $this->render('DergiparkWorkflowBundle:ArticleWorkflow:_timeline.html.twig', [
            'timeline' => $workflowService->getWorkflowTimeline($articleWorkflow),
        ]);
    }
}
