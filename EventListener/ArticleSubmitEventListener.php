<?php

namespace Vipa\WorkflowBundle\EventListener;

use Vipa\WorkflowBundle\Service\WorkflowService;
use Vipa\JournalBundle\Entity\Article;
use Vipa\JournalBundle\Event\Article\ArticleEvents;
use Vipa\JournalBundle\Event\JournalItemEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ArticleSubmitEventListener implements EventSubscriberInterface
{
    /** @var  WorkflowService */
    private $workflowService;

    /**
     * ArticleSubmitEventListener constructor.
     *
     * @param WorkflowService $workflowService
     */
    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ArticleEvents::POST_SUBMIT => 'onSubmitAfter',
        );
    }

    /**
     * @param JournalItemEvent $event
     */
    public function onSubmitAfter(JournalItemEvent $event)
    {
        /** @var Article $article */
        $article = $event->getItem();
        $this->workflowService->prepareArticleWorkflow($article);
    }
}
