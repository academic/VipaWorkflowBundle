<?php

namespace Dergipark\WorkflowBundle\Controller;

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
        return new Response('hello wf actives page');
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
