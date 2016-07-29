<?php

namespace Dergipark\WorkflowBundle\Controller;


use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Entity\Article;
use Ojs\JournalBundle\Entity\Journal;
use Ojs\UserBundle\Entity\Role;
use Ojs\UserBundle\Entity\User;
use BulutYazilim\WorkflowBundle\Entity\Flow;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
