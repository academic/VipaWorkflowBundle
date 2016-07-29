<?php

namespace Dergipark\WorkflowBundle\EventListener;

use Ojs\CoreBundle\Acl\AuthorizationChecker;
use Ojs\JournalBundle\Event\MenuEvent;
use Ojs\JournalBundle\Event\MenuEvents;
use Ojs\JournalBundle\Service\JournalService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeftMenuListener implements EventSubscriberInterface
{
    /** @var  AuthorizationChecker */
    private $checker;

    /** @var  JournalService */
    private $journalService;

    /**
     * LeftMenuListener constructor.
     * @param AuthorizationChecker $checker
     * @param JournalService $journalService
     */
    public function __construct(AuthorizationChecker $checker, JournalService $journalService)
    {
        $this->checker = $checker;
        $this->journalService = $journalService;
    }


    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            MenuEvents::LEFT_MENU_INITIALIZED => 'onLeftMenuInitialized',
            MenuEvents::TOP_LEFT_MENU_INITIALIZED => 'onTopLeftMenuInitialized',
        );
    }

    /**
     * @param MenuEvent $menuEvent
     */
    public function onLeftMenuInitialized(MenuEvent $menuEvent)
    {
        $journal = $this->journalService->getSelectedJournal();
        $journalId = $journal->getId();

        $menuItem = $menuEvent->getMenuItem();
        $items = [
            // [field, label, route, icon]
            ['steps', 'workflow.settings', 'dergipark_workflow_step_index', 'random'],
        ];
        foreach ($items as $item) {
            $field = $item[0];
            $label = $item[1];
            $path = $item[2];
            $icon = $item[3];

            if (empty($field) || $this->checker->isGranted('VIEW', $journal, $field)) {
                $menuItem->addChild(
                    $label,
                    [
                        'route' => $path,
                        'routeParameters' => ['journalId' => $journalId],
                        'extras' => ['icon' => $icon]
                    ]
                );
            }
        }
    }

    /**
     * @param MenuEvent $menuEvent
     * @return MenuEvent
     */
    public function onTopLeftMenuInitialized(MenuEvent $menuEvent)
    {
        $journal = $this->journalService->getSelectedJournal();
        if ($journal) {
            $journalId = $journal->getId();
            $menuItem = $menuEvent->getMenuItem();
            $items = [
                // [field, label, route, icon]
                ['articles_in_review', 'dergipark_workflow_flow_active', 'flag'],
                ['wf.flow_history', 'dergipark_workflow_flow_history', 'history'],
            ];
            foreach ($items as $item) {
                $label = $item[0];
                $path = $item[1];
                $icon = $item[2];
                $menuItem->addChild(
                    $label,
                    [
                        'route' => $path,
                        'routeParameters' => ['journalId' => $journalId],
                        'attributes' => array('data-toggle' => 'tooltip', 'data-placement' => 'left'),
                        'extras' => ['icon' => $icon]
                    ]
                );
            }
        }
        return $menuEvent;
    }
}
