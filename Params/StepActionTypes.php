<?php

namespace Dergipark\WorkflowBundle\Params;

class StepActionTypes
{
    const ASSIGN_GRAMMER_EDITOR = 1;
    const ASSIGN_SPELLING_EDITOR = 1;
    const CREATE_ISSUE = 1;
    const GOTO_REVIEWING = 1;
    const ACCEPT_SUBMISSION = 1;
    const DECLINE_SUBMISSION = 1;
    const ASSIGN_SECTION_EDITOR = 0;
    const ASSIGN_REVIEWER = 0;
    const ASK_AUTHOR_FOR_CORRECTION = 0;
    const ACCEPT_GOTO_ARRANGEMENT = 0;
    const ASSIGN_LAYOUT_EDITOR = 0;
    const ASSIGN_COPY_EDITOR = 0;
    const ASSIGN_PROOF_READER = 0;
}
