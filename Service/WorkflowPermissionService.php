<?php

namespace Dergipark\WorkflowBundle\Service;

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
    public function grantedForWorkflowSetting()
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
