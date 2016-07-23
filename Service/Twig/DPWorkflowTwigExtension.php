<?php

namespace Dergipark\WorkflowBundle\Service\Twig;

use Doctrine\ORM\EntityManager;
use Ojs\JournalBundle\Service\JournalService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DPWorkflowTwigExtension extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var JournalService
     */
    private $journalService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EntityManager             $em
     * @param RouterInterface           $router
     * @param TranslatorInterface       $translator
     * @param JournalService            $journalService
     * @param TokenStorageInterface     $tokenStorage
     * @param Session                   $session
     * @param RequestStack              $requestStack
     * @param EventDispatcherInterface  $eventDispatcher
     */
    public function __construct(
        EntityManager $em = null,
        RouterInterface $router = null,
        TranslatorInterface $translator = null,
        JournalService $journalService = null,
        TokenStorageInterface $tokenStorage = null,
        Session $session = null,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->journalService = $journalService;
        $this->tokenStorage = $tokenStorage;
        $this->session = $session;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getFilters()
    {
        return array();
    }

    public function getFunctions()
    {
        return array();
    }

    public function getName()
    {
        return 'dergipark_workflow_extension';
    }
}
