<?php

namespace Vipa\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vipa\JournalBundle\Entity\Article;

trait WorkflowArticleTrait
{
    /**
     * @var Article
     */
    protected $article;

    /**
     * @return Article
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * @param Article $article
     * @return $this
     */
    public function setArticle(Article $article = null)
    {
        $this->article = $article;

        return $this;
    }
}
