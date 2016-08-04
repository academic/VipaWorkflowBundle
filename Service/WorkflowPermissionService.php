<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;

class WorkflowPermissionService
{
    /**
     * @var WorkflowService
     */
    private $workflowService;

    /**
     * WorkflowPermissionService constructor.
     * @param WorkflowService $workflowService
     */
    public function __construct(
        WorkflowService $workflowService
    ) {
        $this->workflowService  = $workflowService;
    }

    /**
     * checks permission for workflow settings page
     *
     * @return bool
     */
    public function isGrantedForWorkflowSetting()
    {
        $user = $this->workflowService->getUser();
        $journal = $this->workflowService->journalService->getSelectedJournal();

        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isGrantedForWorkflowHistory()
    {
        $user = $this->workflowService->getUser();
        $journal = $this->workflowService->journalService->getSelectedJournal();

        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            return true;
        }

        return false;
    }

    /**
     * @param ArticleWorkflow $workflow
     * @return bool
     */
    public function isGrantedForTimeline(ArticleWorkflow $workflow)
    {
        $user = $this->workflowService->getUser();
        $journal = $this->workflowService->journalService->getSelectedJournal();
        if($user->isAdmin()
            || $this->haveLeastRole(['ROLE_EDITOR', 'ROLE_CO_EDITOR'], $user->getJournalRolesBag($journal))){
            return true;
        }
        if($workflow->relatedUsers->contains($user)){
            return true;
        }

        return false;
    }

    /**
     * @param array $searchRoles
     * @param array $roleBag
     * @return bool
     */
    public function haveLeastRole($searchRoles = [], $roleBag = [])
    {
        if(count(array_intersect($searchRoles, $roleBag)) > 0){
            return true;
        }

        return false;
    }
}
