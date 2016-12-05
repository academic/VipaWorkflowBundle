<?php

namespace Ojs\WorkflowBundle\EventListener;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Row;
use Ojs\CoreBundle\Params\ArticleStatuses;
use Ojs\JournalBundle\Event\ListEvent;
use Ojs\JournalBundle\Event\Article\ArticleEvents;
use Ojs\JournalBundle\Service\JournalService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ArticleListEventListener implements EventSubscriberInterface
{
    /**
     * @var  JournalService
     */
    private $journalService;

    /**
     * @var  ObjectManager
     */
    private $em;

    /**
     * @var  RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ArticleListEventListener constructor.
     * @param JournalService $journalService
     * @param ObjectManager $em
     * @param RouterInterface $router
     * @param TranslatorInterface $translator
     */
    public function __construct(
        JournalService $journalService,
        ObjectManager $em,
        RouterInterface $router,
        TranslatorInterface $translator
    )
    {
        $this->journalService   = $journalService;
        $this->em               = $em;
        $this->router           = $router;
        $this->translator       = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ArticleEvents::LISTED => 'onListInitialized',
        );
    }

    /**
     * @param ListEvent $event
     */
    public function onListInitialized(ListEvent $event)
    {
        $journal = $this->journalService->getSelectedJournal();
        $grid = $event->getGrid();
        $availableStatus = [
            ArticleStatuses::STATUS_INREVIEW,
            ArticleStatuses::STATUS_REJECTED,
            ArticleStatuses::STATUS_WITHDRAWN,
        ];

        /** @var ActionsColumn $actionColumn */
        $actionColumn = $grid->getColumn("actions");
        $rowActions = $actionColumn->getRowActions();

        $rowAction = new RowAction('<i class="fa fa-random"></i>', 'ojs_workflow_restart_workflow');
        $rowAction->setRouteParameters(['id', 'journalId' => $journal->getId()]);

        $rowAction->manipulateRender(
            function (RowAction $rowAction, Row $row) use ($journal, $availableStatus) {
                if (in_array($row->getField('status'), $availableStatus)) {
                    $rowAction->setAttributes(
                        [
                            'class' => 'btn btn-primary btn-xs',
                            'data-toggle' => 'tooltip',
                            'title' => $this->translator->trans('restart.workflow.process'),
                        ]
                    );
                    return $rowAction;
                }
                return null;
            }
        );

        $rowActions[] = $rowAction;
        $actionColumn->setRowActions($rowActions);
        $event->setGrid($grid);
    }
}
