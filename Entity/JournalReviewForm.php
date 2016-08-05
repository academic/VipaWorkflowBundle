<?php

namespace Dergipark\WorkflowBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;

/**
 * Class JournalReviewForm
 * @package Dergipark\WorkflowBundle\Entity
 * @GRID\Source(columns="id,name,active")
 */
class JournalReviewForm
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $active = true;

    /**
     * @var string
     */
    private $content;

    /**
     * @var JournalWorkflowStep
     */
    private $step;

    /**
     * DialogPost constructor.
     */
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return JournalWorkflowStep
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param JournalWorkflowStep $step
     *
     * @return $this
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }
}
