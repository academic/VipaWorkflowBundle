<?php

namespace Dergipark\WorkflowBundle\Entity;

use Dergipark\WorkflowBundle\Params\StepDialogStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Ojs\JournalBundle\Entity\ArticleTrait;
use Ojs\JournalBundle\Entity\JournalTrait;
use Ojs\UserBundle\Entity\User;
use Dergipark\WorkflowBundle\Params\ArticleWorkflowStatus;

/**
 * Class StepDialog
 * @package Dergipark\WorkflowBundle\Entity
 */
class StepDialog
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \DateTime
     */
    private $openedAt;

    /**
     * @var string
     */
    private $dialogType;

    /**
     * @var ArrayCollection|User[]
     */
    public $users;

    /**
     * @var ArticleWorkflowStep
     */
    public $step;

    /**
     * @var int
     */
    protected $status = StepDialogStatus::ACTIVE;

    /**
     * StepDialog constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
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
     * @return ArrayCollection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param  User $user
     * @return $this
     */
    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    /**
     * @param  User $user
     * @return $this
     */
    public function removeUser(User $user)
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }

        return $this;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasUser(User $user)
    {
        if ($this->users->contains($user)) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOpenedAt()
    {
        return $this->openedAt;
    }

    /**
     * @param \DateTime $openedAt
     *
     * @return $this
     */
    public function setOpenedAt($openedAt)
    {
        $this->openedAt = $openedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getDialogType()
    {
        return $this->dialogType;
    }

    /**
     * @param string $dialogType
     *
     * @return $this
     */
    public function setDialogType($dialogType)
    {
        $this->dialogType = $dialogType;

        return $this;
    }

    /**
     * @return ArticleWorkflowStep
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param ArticleWorkflowStep $step
     *
     * @return $this
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }
}
