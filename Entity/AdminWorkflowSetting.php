<?php

namespace Dergipark\WorkflowBundle\Entity;

/**
 * AdminWorkflowSetting
 */
class AdminWorkflowSetting
{
    /**
     * @var integer
     */
    private $id;

    public function __construct()
    {
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}

