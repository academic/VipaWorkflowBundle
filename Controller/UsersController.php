<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Event\WorkflowEvent;
use Dergipark\WorkflowBundle\Event\WorkflowEvents;
use Dergipark\WorkflowBundle\Form\Type\ReviewerUserType;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Entity\JournalUser;
use Ojs\UserBundle\Entity\Role;
use Ojs\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $this->throw404IfNotFound($workflow);
        if($journal->getId() !== $workflow->getJournal()->getId()){
            throw new AccessDeniedException;
        }

        $reviewerUsers = $em->getRepository('OjsUserBundle:User')->findUsersByJournalRole(
            ['ROLE_REVIEWER']
        );

        return $this->render('DergiparkWorkflowBundle:Users:_reviewers_browse.html.twig', [
            'reviewerUsers' => $reviewerUsers,
            'reviewerStats' => $this->collectReviewerStats(),
        ]);
    }

    private function collectReviewerStats()
    {
        $em = $this->getDoctrine()->getManager();
        $journalId = $this->get('ojs.journal_service')->getSelectedJournal()->getId();

        $sql = "SELECT DISTINCT
    users.id,
    COUNT(closed_dialogs) AS closed_issues_count,
    MAX(closed_dialogs.openedat) as newest_closed_dialog,
    COUNT(open_dialogs) AS open_issues_count,
    COUNT(review_accept_dialogs) AS review_accept_count,
    COUNT(review_decline_dialogs) AS review_decline_count,
    COUNT(review_remind_dialogs) AS review_remind_count

FROM   users
    INNER JOIN journal_user
        ON users.id = journal_user.user_id
            AND ( journal_user.journal_id = {$journalId} )

    LEFT OUTER JOIN bc_wf_step_dialog_users
        ON users.id = bc_wf_step_dialog_users.user_id

    -- closed dialogs related joins
    LEFT JOIN bc_wf_step_dialog as closed_dialogs
        ON (bc_wf_step_dialog_users.dialog_id = closed_dialogs.id AND closed_dialogs.status = 0 AND closed_dialogs.dialog_type = '8')
    LEFT JOIN bc_article_wf_step as closed_dialogs_steps
        ON (closed_dialogs.workflow_step_id = closed_dialogs_steps.id)
    LEFT JOIN bc_wf_article_workflow as closed_dialogs_workflow
        ON (closed_dialogs_steps.article_workflow_id = closed_dialogs_workflow.id)

    -- open dialogs related joins
    LEFT JOIN bc_wf_step_dialog as open_dialogs
        ON (bc_wf_step_dialog_users.dialog_id = open_dialogs.id AND open_dialogs.status = 1 AND open_dialogs.dialog_type = '8')
    LEFT JOIN bc_article_wf_step as open_dialogs_steps
        ON (open_dialogs.workflow_step_id = open_dialogs_steps.id)
    LEFT JOIN bc_wf_article_workflow as open_dialogs_workflow
        ON (open_dialogs_steps.article_workflow_id = open_dialogs_workflow.id)

    -- review accept related joins
    LEFT JOIN bc_wf_step_dialog as review_accept_dialogs
        ON (bc_wf_step_dialog_users.dialog_id = review_accept_dialogs.id AND review_accept_dialogs.dialog_type = '8' AND review_accept_dialogs.accepted = 'TRUE')
    LEFT JOIN bc_article_wf_step as review_accept_dialogs_steps
        ON (review_accept_dialogs.workflow_step_id = review_accept_dialogs_steps.id)
    LEFT JOIN bc_wf_article_workflow as review_accept_dialogs_workflow
        ON (review_accept_dialogs_steps.article_workflow_id = review_accept_dialogs_workflow.id)

    -- review decline related joins
    LEFT JOIN bc_wf_step_dialog as review_decline_dialogs
        ON (bc_wf_step_dialog_users.dialog_id = review_decline_dialogs.id AND review_decline_dialogs.dialog_type = '8' AND review_decline_dialogs.rejected = 'TRUE')
    LEFT JOIN bc_article_wf_step as review_decline_dialogs_steps
        ON (review_decline_dialogs.workflow_step_id = review_decline_dialogs_steps.id)
    LEFT JOIN bc_wf_article_workflow as review_decline_dialogs_workflow
        ON (review_decline_dialogs_steps.article_workflow_id = review_decline_dialogs_workflow.id)

    -- review invite related joins
    LEFT JOIN bc_wf_step_dialog as review_remind_dialogs
        ON (bc_wf_step_dialog_users.dialog_id = review_remind_dialogs.id AND review_remind_dialogs.dialog_type = '8' AND review_remind_dialogs.remindingtime IS NOT NULL)
    LEFT JOIN bc_article_wf_step as review_remind_dialogs_steps
        ON (review_remind_dialogs.workflow_step_id = review_remind_dialogs_steps.id)
    LEFT JOIN bc_wf_article_workflow as review_remind_dialogs_workflow
        ON (review_remind_dialogs_steps.article_workflow_id = review_remind_dialogs_workflow.id)


    LEFT JOIN journal_user_role
        ON journal_user.id = journal_user_role.journal_user_id
    INNER JOIN role
        ON role.id = journal_user_role.role_id

WHERE  ( role.role IN ( 'ROLE_REVIEWER' ) )
       AND ( users.deletedat IS NULL )
       AND (closed_dialogs_workflow.journal_id = {$journalId} OR closed_dialogs_workflow.journal_id IS NULL) -- closed dialogs
       AND (open_dialogs_workflow.journal_id = {$journalId} OR open_dialogs_workflow.journal_id IS NULL) -- open dialogs
       AND (review_accept_dialogs_workflow.journal_id = {$journalId} OR review_accept_dialogs_workflow.journal_id IS NULL) -- accepted dialogs
       AND (review_decline_dialogs_workflow.journal_id = {$journalId} OR review_decline_dialogs_workflow.journal_id IS NULL) -- declined dialogs
       AND (review_remind_dialogs_workflow.journal_id = {$journalId} OR review_remind_dialogs_workflow.journal_id IS NULL) -- remind dialogs

GROUP BY users.id;";

        $connection = $em->getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();

        $resultMap = [];
        foreach($results as $result){
            $resultMap[$result['id']] = $result;
        }

        return $resultMap;
    }

    /**
     * @param Request $request
     * @param $workflowId
     * @return Response
     */
    public function createReviewerUserAction(Request $request, $workflowId)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $dispatcher = $this->get('event_dispatcher');
        $em = $this->getDoctrine()->getManager();

        $reviewerRole = $em->getRepository(Role::class)->findOneBy([
            'role' => 'ROLE_REVIEWER',
        ]);

        $reviewerUser = new User();
        $reviewerUser->setPassword($this->generateRandomString(10));
        $reviewerUser->setEnabled(true);

        $form = $this->createForm(new ReviewerUserType(), $reviewerUser, [
            'action' => $this->generateUrl('dergipark_workflow_create_reviewer_user', [
                'journalId' => $journal->getId(),
                'workflowId' => $workflow->getId(),
            ])
        ]);
        $form->handleRequest($request);

        if($request->getMethod() == 'POST' && $form->isValid()){
            $em->persist($reviewerUser);

            $journalReviewerUser = new JournalUser();
            $journalReviewerUser
                ->setJournal($journal)
                ->setUser($reviewerUser)
                ->addRole($reviewerRole)
            ;
            $em->persist($journalReviewerUser);

            $em->flush();

            //dispatch event
            $event = new WorkflowEvent($reviewerUser);
            $event->setWorkflow($workflow);
            $dispatcher->dispatch(WorkflowEvents::REVIEWER_USER_CREATED, $event);

            return $workflowService->getMessageBlock('successful_create_reviewer_user');
        }

        return $this->render('DergiparkWorkflowBundle:Users:_create_reviewer.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
