<?php

namespace Ojs\WorkflowBundle\Service;

use Ojs\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\WorkflowBundle\Entity\WorkflowHistoryLog;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Exception\LogicException;
use Ojs\JournalBundle\Service\JournalService;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * WorkflowLoggerService constructor.
     * @param EntityManager $em
     * @param JournalService $journalService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityManager $em,
        JournalService $journalService,
        TranslatorInterface $translator
    )
    {
        $this->em               = $em;
        $this->journalService   = $journalService;
        $this->translator       = $translator;
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
     * @param array $parameters
     * @param ArticleWorkflow|null $articleWorkflow
     * @param bool $flush
     * @return WorkflowHistoryLog
     */
    public function log($message , $parameters = [], ArticleWorkflow $articleWorkflow = null, $flush = false)
    {
        if(!$this->articleWorkflow && !$articleWorkflow){
            throw new LogicException('one of article workflow must be filled. use set or give as arg');
        }
        if(!$articleWorkflow){
            $articleWorkflow = $this->articleWorkflow;
        }
        $message = $this->translator->trans($message, $parameters);
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
