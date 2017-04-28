<?php

namespace Vipa\WorkflowBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Vipa\WorkflowBundle\Entity\WorkflowArticleTrait;
use Vipa\WorkflowBundle\Entity\WorkflowJournalTrait;
use Vipa\UserBundle\Entity\User;
use Vipa\WorkflowBundle\Params\ArticleWorkflowStatus;

/**
 * Class ArticleWorkflow
 * @package Vipa\WorkflowBundle\Entity
 */
class ArticleWorkflow
{
    use WorkflowJournalTrait;
    use WorkflowArticleTrait;

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
     * @var ArrayCollection|ArticleWorkflowStep[]
     */
    public $steps = [];

    /**
     * @var int
     */
    protected $status = ArticleWorkflowStatus::ACTIVE;

    /**
     * @var string
     */
    private $reviewVersionFile;

    /**
     * Step constructor.
     *
     */
    public function __construct()
    {
        $this->relatedUsers = new ArrayCollection();
        $this->grantedUsers = new ArrayCollection();
        $this->steps = new ArrayCollection();
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

    /**
     * @return ArticleWorkflowStep[]|ArrayCollection
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param $steps
     * @return $this
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;

        return $this;
    }

    /**
     * @param $stepOrder
     * @return bool|ArticleWorkflowStep|mixed
     */
    public function getStepByOrder($stepOrder)
    {
        foreach ($this->steps as $step){
            if($step->getOrder() == $stepOrder){
                return $step;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getReviewVersionFile()
    {
        return $this->reviewVersionFile;
    }

    /**
     * @param string $reviewVersionFile
     *
     * @return $this
     */
    public function setReviewVersionFile(string $reviewVersionFile)
    {
        $this->reviewVersionFile = $reviewVersionFile;

        return $this;
    }

    public function __toString()
    {
        return 'article.workflow';
    }
}
