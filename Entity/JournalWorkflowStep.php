<?php

namespace Vipa\WorkflowBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Vipa\JournalBundle\Entity\JournalTrait;
use Vipa\UserBundle\Entity\User;

/**
 * JournalWorkflowStep
 */
class JournalWorkflowStep
{
    use JournalTrait;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    private $order;

    /**
     * @var ArrayCollection|User[]
     */
    public $grantedUsers;

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
}
