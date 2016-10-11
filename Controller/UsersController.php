<?php

namespace Dergipark\WorkflowBundle\Controller;

use Ojs\CoreBundle\Controller\OjsController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersController extends Controller
{
    /**
     * @param Request $request
     * @param $workflowId
     * @return Response
     */
    public function browseReviewersAction(Request $request, $workflowId)
    {
        $em = $this->getDoctrine()->getManager();
        $reviewerUsers = $em->getRepository('OjsUserBundle:User')->findUsersByJournalRole(
            ['ROLE_REVIEWER']
        );

        return $this->render('DergiparkWorkflowBundle:Users:_reviewers_browse.html.twig', [
            'reviewerUsers' => $reviewerUsers,
        ]);
    }
}
