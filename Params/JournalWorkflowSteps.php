<?php

namespace Dergipark\WorkflowBundle\Params;

class JournalWorkflowSteps
{
    const PRE_CONTROL_ORDER   = 1;
    const REVIEW_ORDER        = 2;
    const ARRANGEMENT_ORDER   = 3;

    static $stepAlias = [
        self::PRE_CONTROL_ORDER => 'pre.control',
        self::REVIEW_ORDER      => 'review',
        self::ARRANGEMENT_ORDER => 'arrangement',
    ];
}
