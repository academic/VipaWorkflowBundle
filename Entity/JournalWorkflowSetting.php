<?php

namespace Ojs\WorkflowBundle\Entity;

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

    /**
     * @var bool
     */
    private $doubleBlind = true;

    /**
     * @var int
     */
    private $reviewerWaitDay = 15;

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

    /**
     * @return boolean
     */
    public function isDoubleBlind()
    {
        return $this->doubleBlind;
    }

    /**
     * @param boolean $doubleBlind
     *
     * @return $this
     */
    public function setDoubleBlind($doubleBlind)
    {
        $this->doubleBlind = $doubleBlind;

        return $this;
    }

    /**
     * @return int
     */
    public function getReviewerWaitDay()
    {
        return $this->reviewerWaitDay;
    }

    /**
     * @param int $reviewerWaitDay
     *
     * @return $this
     */
    public function setReviewerWaitDay($reviewerWaitDay)
    {
        $this->reviewerWaitDay = $reviewerWaitDay;

        return $this;
    }
}
