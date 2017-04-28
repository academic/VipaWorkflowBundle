<?php

namespace Vipa\WorkflowBundle\Params;

class StepActionTypes
{
    const ASSIGN_GRAMMER_EDITOR = 1;
    const ASSIGN_SPELLING_EDITOR = 2;
    const CREATE_ISSUE = 3;
    const GOTO_REVIEWING = 4;
    const ACCEPT_SUBMISSION = 5;
    const DECLINE_SUBMISSION = 6;
    const ASSIGN_SECTION_EDITOR = 7;
    const ASSIGN_REVIEWER = 8;
    const ASK_AUTHOR_FOR_CORRECTION = 9;
    const ACCEPT_GOTO_ARRANGEMENT = 10;
    const ASSIGN_LAYOUT_EDITOR = 11;
    const ASSIGN_COPY_EDITOR = 12;
    const ASSIGN_PROOF_READER = 13;
    const FINISH_WORKFLOW = 14;

    static $typeAlias = [
        self::ASSIGN_GRAMMER_EDITOR => '_assign_grammer_editor',
        self::ASSIGN_SPELLING_EDITOR => '_assign_spelling_editor',
        self::CREATE_ISSUE => '_create_issue',
        self::GOTO_REVIEWING => '_goto_reviewing',
        self::ACCEPT_SUBMISSION => '_accept_submission',
        self::ASSIGN_SECTION_EDITOR => '_assign_section_editor',
        self::ASSIGN_REVIEWER => '_assign_reviewer',
        self::ASK_AUTHOR_FOR_CORRECTION => '_ask_author_for_correction',
        self::ACCEPT_GOTO_ARRANGEMENT => '_accept_goto_arrangement',
        self::ASSIGN_LAYOUT_EDITOR => '_assign_layout_editor',
        self::ASSIGN_COPY_EDITOR => '_assign_copy_editor',
        self::ASSIGN_PROOF_READER => '_assign_proof_reader',
        self::FINISH_WORKFLOW => '_finish_workflow',
    ];

    static $dialogRoles = [
        self::ASSIGN_REVIEWER => [
            'ROLE_REVIEWER'
        ],
        self::ASSIGN_SECTION_EDITOR => [
            'ROLE_SECTION_EDITOR',
            'ROLE_CO_EDITOR',
            'ROLE_EDITOR',
        ],
    ];
}
