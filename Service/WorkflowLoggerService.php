<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\WorkflowHistoryLog;
use Doctrine\ORM\EntityManager;
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
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, JournalService $journalService)
    {
        $this->em               = $em;
        $this->journalService   = $journalService;
    }

    /**
     * @param $message
     * @param ArticleWorkflow $articleWorkflow
     * @param bool $flush
     * @return WorkflowHistoryLog
     */
    public function log($message , ArticleWorkflow $articleWorkflow,$flush = false)
    {
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
