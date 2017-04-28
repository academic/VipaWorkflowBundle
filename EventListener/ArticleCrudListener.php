<?php

namespace Vipa\WorkflowBundle\EventListener;

use Vipa\JournalBundle\Entity\Article;
use Vipa\JournalBundle\Event\Article\ArticleEvents;
use Vipa\JournalBundle\Event\JournalItemEvent;
use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Doctrine\ORM\EntityManager;
use Vipa\WorkflowBundle\Service\WorkflowService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ArticleCrudListener implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var WorkflowService
     */
    private $workflowService;

    /**
     * ArticleCrudListener constructor.
     *
     * @param EntityManager $em
     * @param WorkflowService $workflowService
     */
    public function __construct(
        EntityManager $em,
        WorkflowService $workflowService
    ) {
        $this->em               = $em;
        $this->workflowService  = $workflowService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ArticleEvents::PRE_DELETE => 'onArticleDelete',
        );
    }

    /**
     * @param JournalItemEvent $event
     * @return null
     */
    public function onArticleDelete(JournalItemEvent $event)
    {
        $article = $event->getItem();
        if(!$article instanceof Article){
            return;
        }
        $workflows = $this->em->getRepository(ArticleWorkflow::class)->findBy([
            'article' => $article,
        ]);
        if(!$workflows){
            return;
        }
        foreach ($workflows as $workflow){
            $this->workflowService->cleanWorkflow($workflow);
        }
        return true;
    }
}
