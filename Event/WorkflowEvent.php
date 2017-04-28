<?php

namespace Vipa\WorkflowBundle\Event;

use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Vipa\WorkflowBundle\Entity\ArticleWorkflowStep;
use Vipa\WorkflowBundle\Entity\DialogPost;
use Vipa\WorkflowBundle\Entity\StepDialog;
use Vipa\JournalBundle\Entity\Article;
use Vipa\JournalBundle\Entity\Journal;
use Vipa\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

final class WorkflowEvent extends Event
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
     * @var Journal
     */
    public $journal;

    /**
     * @var Article
     */
    public $article;

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
        $this->article = $this->workflow->getArticle();
        $this->journal = $this->workflow->getJournal();

        return $this;
    }

    /**
     * @param Journal $journal
     *
     * @return $this
     */
    public function setJournal($journal)
    {
        $this->journal = $journal;

        return $this;
    }

    /**
     * @param Article $article
     *
     * @return $this
     */
    public function setArticle($article)
    {
        $this->article = $article;
        $this->journal = $article->getJournal();

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
        $this->workflow = $this->step->getArticleWorkflow();
        $this->article = $this->workflow->getArticle();
        $this->journal = $this->workflow->getJournal();

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
        $this->step = $dialog->getStep();
        $this->workflow = $this->step->getArticleWorkflow();
        $this->article = $this->workflow->getArticle();
        $this->journal = $this->workflow->getJournal();

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
        $this->dialog = $post->getDialog();
        $this->step = $this->dialog->getStep();
        $this->workflow = $this->step->getArticleWorkflow();
        $this->article = $this->workflow->getArticle();
        $this->journal = $this->workflow->getJournal();

        return $this;
    }
}
