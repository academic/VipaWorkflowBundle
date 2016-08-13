<?php

namespace Dergipark\WorkflowBundle\Event;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\DialogPost;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Ojs\UserBundle\Entity\User;

final class WorkflowEvent
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var array|User[]
     */
    public $userBag;

    /**
     * @var ArticleWorkflow
     */
    public $workflow;

    /**
     * @var ArticleWorkflowStep
     */
    public $step;

    /**
     * @var StepDialog
     */
    public $dialog;

    /**
     * @var DialogPost
     */
    public $post;

    /**
     * WorkflowEvent constructor.
     *
     * @param User|null $user
     * @param array $userBag
     * @param ArticleWorkflow|null $workflow
     * @param ArticleWorkflowStep|null $step
     * @param ArticleWorkflowStep|null $dialog
     * @param DialogPost|null $post
     */
    public function __construct(
        User $user = null,
        $userBag = [],
        ArticleWorkflow $workflow = null,
        ArticleWorkflowStep $step = null,
        ArticleWorkflowStep $dialog = null,
        DialogPost $post = null
    )
    {
        $this->user = $user;
        $this->userBag = $userBag;
        $this->workflow = $workflow;
        $this->step = $step;
        $this->dialog = $dialog;
        $this->post = $post;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param array|User[] $userBag
     *
     * @return $this
     */
    public function setUserBag($userBag)
    {
        $this->userBag = $userBag;

        return $this;
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
     * @param StepDialog $dialog
     *
     * @return $this
     */
    public function setDialog($dialog)
    {
        $this->dialog = $dialog;

        return $this;
    }

    /**
     * @param DialogPost $post
     *
     * @return $this
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }
}
