<?php

namespace Ojs\WorkflowBundle\Entity;

use Ojs\WorkflowBundle\Params\StepStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Ojs\UserBundle\Entity\User;

/**
 * Class ArticleWorkflowStep
 * @package Ojs\WorkflowBundle\Entity
 */
class ArticleWorkflowStep
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    private $order;

    /**
     * @var integer
     */
    private $status = StepStatus::ACTIVE;

    /**
     * @var ArrayCollection|User[]
     */
    public $grantedUsers;

    /**
     * @var ArticleWorkflow
     */
    protected $articleWorkflow;

    /**
     * Step constructor.
     *
     */
    public function __construct()
    {
        $this->grantedUsers = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param  integer $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

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
     * @return ArticleWorkflow
     */
    public function getArticleWorkflow()
    {
        return $this->articleWorkflow;
    }

    /**
     * @param ArticleWorkflow $articleWorkflow
     *
     * @return $this
     */
    public function setArticleWorkflow($articleWorkflow)
    {
        $this->articleWorkflow = $articleWorkflow;

        return $this;
    }
}
