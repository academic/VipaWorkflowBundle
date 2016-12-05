<?php

namespace Ojs\WorkflowBundle\Entity;

use Ojs\JournalBundle\Entity\JournalTrait;

/**
 * WorkflowHistoryLog
 */
class WorkflowHistoryLog
{
    use JournalTrait;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $logType;

    /**
     * @var string
     */
    private $logText;

    /**
     * @var \DateTime
     */
    private $time;

    /**
     * @var string json
     */
    private $permission;

    /**
     * @var ArticleWorkflow
     */
    private $articleWorkflow;

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
    public function getLogType()
    {
        return $this->logType;
    }

    /**
     * @param string $logType
     *
     * @return $this
     */
    public function setLogType($logType)
    {
        $this->logType = $logType;

        return $this;
    }

    /**
     * @return string
     */
    public function getLogText()
    {
        return $this->logText;
    }

    /**
     * @param string $logText
     *
     * @return $this
     */
    public function setLogText($logText)
    {
        $this->logText = $logText;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param \DateTime $time
     *
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return string
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @param string $permission
     *
     * @return $this
     */
    public function setPermission($permission)
    {
        $this->permission = $permission;

        return $this;
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
