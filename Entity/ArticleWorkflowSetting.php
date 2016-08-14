<?php

namespace Dergipark\WorkflowBundle\Entity;

/**
 * Class ArticleWorkflowSetting
 * @package Dergipark\WorkflowBundle\Entity
 */
class ArticleWorkflowSetting
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var bool
     */
    private $doubleBlind = false;

    /**
     * @var int
     */
    private $reviewerWaitDay = 15;

    /**
     * @var ArticleWorkflow
     */
    private $workflow;

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

    /**
     * @return ArticleWorkflow
     */
    public function getWorkflow()
    {
        return $this->workflow;
    }

    /**
     * @param ArticleWorkflow $workflow
     *
     * @return $this
     */
    public function setWorkflow($workflow)
    {
        $this->workflow = $workflow;

        return $this;
    }
}
