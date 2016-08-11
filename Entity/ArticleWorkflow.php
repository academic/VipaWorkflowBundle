<?php

namespace Dergipark\WorkflowBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Ojs\JournalBundle\Entity\ArticleTrait;
use Ojs\JournalBundle\Entity\JournalTrait;
use Ojs\UserBundle\Entity\User;
use Dergipark\WorkflowBundle\Params\ArticleWorkflowStatus;

/**
 * Class ArticleWorkflow
 * @package Dergipark\WorkflowBundle\Entity
 */
class ArticleWorkflow
{
    use JournalTrait;
    use ArticleTrait;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var ArrayCollection|User[]
     */
    public $relatedUsers;

    /**
     * @var ArticleWorkflowStep
     */
    public $currentStep;

    /**
     * @var ArrayCollection|User[]
     */
    public $grantedUsers;

    /**
     * @var int
     */
    protected $status = ArticleWorkflowStatus::ACTIVE;

    /**
     * Step constructor.
     *
     */
    public function __construct()
    {
        $this->relatedUsers = new ArrayCollection();
        $this->grantedUsers = new ArrayCollection();
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
    public function getGrantedUsers()
    {
        return $this->grantedUsers;
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function setGrantedUsers($users)
    {
        $this->grantedUsers = $users;

        return $this;
    }

    /**
     * @param  User $user
     * @return $this
     */
    public function addGrantedUser(User $user)
    {
        if (!$this->grantedUsers->contains($user)) {
            $this->grantedUsers->add($user);
        }

        return $this;
    }

    /**
     * @param  User $user
     * @return $this
     */
    public function removeGrantedUser(User $user)
    {
        if ($this->grantedUsers->contains($user)) {
            $this->grantedUsers->removeElement($user);
        }

        return $this;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasGrantedUser(User $user)
    {
        if ($this->grantedUsers->contains($user)) {
            return true;
        }

        return false;
    }

    /**
     * @return ArrayCollection|User[]
     */
    public function getRelatedUsers()
    {
        return $this->relatedUsers;
    }

    /**
     * @param  User $user
     * @return $this
     */
    public function addRelatedUser(User $user)
    {
        if (!$this->relatedUsers->contains($user)) {
            $this->relatedUsers->add($user);
        }

        return $this;
    }

    /**
     * @param  User $user
     * @return $this
     */
    public function removeRelatedUser(User $user)
    {
        if ($this->relatedUsers->contains($user)) {
            $this->relatedUsers->removeElement($user);
        }

        return $this;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function hasRelatedUser(User $user)
    {
        if ($this->relatedUsers->contains($user)) {
            return true;
        }

        return false;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     *
     * @return $this
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
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
     * @return ArticleWorkflowStep
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * @param ArticleWorkflowStep $currentStep
     *
     * @return $this
     */
    public function setCurrentStep($currentStep)
    {
        $this->currentStep = $currentStep;

        return $this;
    }
}
