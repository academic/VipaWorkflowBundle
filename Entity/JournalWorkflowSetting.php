<?php

namespace Dergipark\WorkflowBundle\Entity;

use Ojs\JournalBundle\Entity\JournalTrait;

/**
 * JournalWorkflowSetting
 */
class JournalWorkflowSetting
{
    use JournalTrait;

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
