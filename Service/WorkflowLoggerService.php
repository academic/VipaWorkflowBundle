<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\WorkflowHistoryLog;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Exception\LogicException;
use Ojs\JournalBundle\Service\JournalService;

class WorkflowLoggerService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var JournalService
     */
    private $journalService;

    /**
     * @var ArticleWorkflow
     */
    private $articleWorkflow = null;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, JournalService $journalService)
    {
        $this->em               = $em;
        $this->journalService   = $journalService;
    }

    /**
     * @param ArticleWorkflow $articleWorkflow
     * @return $this
     */
    public function setArticleWorkflow(ArticleWorkflow $articleWorkflow)
    {
        $this->articleWorkflow = $articleWorkflow;

        return $this;
    }

    /**
     * @param $message
     * @param ArticleWorkflow $articleWorkflow
     * @param bool $flush
     * @return WorkflowHistoryLog
     */
    public function log($message , ArticleWorkflow $articleWorkflow = null,$flush = false)
    {
        if(!$this->articleWorkflow && !$articleWorkflow){
            throw new LogicException('one of article workflow must be filled. use set or give as arg');
        }
        if(!$articleWorkflow){
            $articleWorkflow = $this->articleWorkflow;
        }
        $log = new WorkflowHistoryLog();
        $log
            ->setLogText($message)
            ->setLogType('info')
            ->setPermission(json_encode([]))
            ->setTime(new \DateTime())
            ->setArticleWorkflow($articleWorkflow)
            ;
        $this->em->persist($log);
        if($flush){
            $this->em->flush();
        }

        return $log;
    }
}
