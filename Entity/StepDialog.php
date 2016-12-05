<?php

namespace Ojs\WorkflowBundle\Entity;

use Ojs\WorkflowBundle\Params\StepDialogStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Ojs\JournalBundle\Entity\ArticleTrait;
use Ojs\JournalBundle\Entity\JournalTrait;
use Ojs\UserBundle\Entity\User;
use Ojs\WorkflowBundle\Params\ArticleWorkflowStatus;

/**
 * Class StepDialog
 * @package Ojs\WorkflowBundle\Entity
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
     * @var string
     */
    private $title;

    /**
     * @var ArrayCollection|User[]
     */
    public $users;

    /**
     * @var ArticleWorkflowStep
     */
    public $step;

    /**
     * @var User
     */
    public $createdDialogBy;

    /**
     * @var int
     */
    protected $status = StepDialogStatus::ACTIVE;

    /**
     * @var \DateTime
     */
    private $inviteTime = null;

    /**
     * @var \DateTime
     */
    private $acceptTime = null;

    /**
     * @var \DateTime
     */
    private $remindingTime = null;

    /**
     * @var bool
     */
    private $rejected = false;

    /**
     * @var bool
     */
    private $accepted = false;

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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
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

    /**
     * @return User
     */
    public function getCreatedDialogBy()
    {
        return $this->createdDialogBy;
    }

    /**
     * @param User $createdDialogBy
     *
     * @return $this
     */
    public function setCreatedDialogBy($createdDialogBy)
    {
        $this->createdDialogBy = $createdDialogBy;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInviteTime()
    {
        return $this->inviteTime;
    }

    /**
     * @param \DateTime $inviteTime
     *
     * @return $this
     */
    public function setInviteTime($inviteTime)
    {
        $this->inviteTime = $inviteTime;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAcceptTime()
    {
        return $this->acceptTime;
    }

    /**
     * @param \DateTime $acceptTime
     *
     * @return $this
     */
    public function setAcceptTime($acceptTime)
    {
        $this->acceptTime = $acceptTime;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRemindingTime()
    {
        return $this->remindingTime;
    }

    /**
     * @param \DateTime $remindingTime
     *
     * @return $this
     */
    public function setRemindingTime($remindingTime)
    {
        $this->remindingTime = $remindingTime;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRejected()
    {
        return $this->rejected;
    }

    /**
     * @param boolean $rejected
     *
     * @return $this
     */
    public function setRejected($rejected)
    {
        $this->rejected = $rejected;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccepted()
    {
        return $this->accepted;
    }

    /**
     * @param boolean $accepted
     *
     * @return $this
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;

        return $this;
    }
}
