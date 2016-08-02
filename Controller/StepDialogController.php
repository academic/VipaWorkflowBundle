<?php

namespace Dergipark\WorkflowBundle\Controller;

use Ojs\CoreBundle\Controller\OjsController as Controller;
use Symfony\Component\HttpFoundation\Response;

class StepDialogController extends Controller
{
    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function createSpecificDialogAction($workflowId, $stepOrder)
    {
        return new Response('createSpecificDialogAction');
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function createBasicDialogAction($workflowId, $stepOrder)
    {
        return new Response('createBasicDialogAction');
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function acceptGotoArrangementAction($workflowId, $stepOrder)
    {
        return new Response('acceptGotoArrangementAction');
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function gotoReviewingAction($workflowId, $stepOrder)
    {
        return new Response('gotoReviewingAction');
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function acceptSubmissionAction($workflowId, $stepOrder)
    {
        return new Response('acceptSubmissionAction');
    }

    /**
     * @param $workflowId
     * @param $stepOrder
     * @return Response
     */
    public function declineSubmissionAction($workflowId, $stepOrder)
    {
        return new Response('declineSubmissionAction');
    }
}
