<?php

namespace Dergipark\WorkflowBundle\Service\Twig;

use Dergipark\WorkflowBundle\Params\JournalWorkflowSteps;
use Dergipark\WorkflowBundle\Params\StepActionTypes;
use Dergipark\WorkflowBundle\Service\WorkflowPermissionService;
use Doctrine\ORM\EntityManager;
use Ojs\JournalBundle\Service\JournalService;
use Ojs\UserBundle\Entity\User;
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
     * @var WorkflowPermissionService
     */
    public $wfPermissionService;

    /**
     * DPWorkflowTwigExtension constructor.
     * @param EntityManager|null $em
     * @param RouterInterface|null $router
     * @param TranslatorInterface|null $translator
     * @param JournalService|null $journalService
     * @param TokenStorageInterface|null $tokenStorage
     * @param Session|null $session
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     * @param WorkflowPermissionService $permissionService
     */
    public function __construct(
        EntityManager $em = null,
        RouterInterface $router = null,
        TranslatorInterface $translator = null,
        JournalService $journalService = null,
        TokenStorageInterface $tokenStorage = null,
        Session $session = null,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        WorkflowPermissionService $permissionService
    ) {
        $this->em                   = $em;
        $this->router               = $router;
        $this->journalService       = $journalService;
        $this->tokenStorage         = $tokenStorage;
        $this->session              = $session;
        $this->translator           = $translator;
        $this->requestStack         = $requestStack;
        $this->eventDispatcher      = $eventDispatcher;
        $this->wfPermissionService  = $permissionService;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('actionType', array($this, 'getActionType')),
            new \Twig_SimpleFunction('actionAlias', array($this, 'getActionAlias')),
            new \Twig_SimpleFunction('permissionCheck', array($this, 'getPermissionCheck')),
            new \Twig_SimpleFunction('journalStepAlias', array($this, 'getJournalStepAlias')),
            new \Twig_SimpleFunction('dialogStatus', array($this, 'getDialogStatus')),
            new \Twig_SimpleFunction('stepStatus', array($this, 'getStepStatus')),
            new \Twig_SimpleFunction('profileLink', array($this, 'getProfileLink'), ['is_safe' => ['html']]),
        );
    }

    public function getActionType($const)
    {
        return constant('Dergipark\WorkflowBundle\Params\StepActionTypes::'.$const);
    }

    public function getDialogStatus($const)
    {
        return constant('Dergipark\WorkflowBundle\Params\StepDialogStatus::'.$const);
    }

    public function getStepStatus($const)
    {
        return constant('Dergipark\WorkflowBundle\Params\StepStatus::'.$const);
    }

    public function getJournalStepAlias($stepOrder)
    {
        return JournalWorkflowSteps::$stepAlias[$stepOrder];
    }

    public function getActionAlias($actionType)
    {
        return StepActionTypes::$typeAlias[$actionType];
    }

    public function getName()
    {
        return 'dergipark_workflow_extension';
    }

    public function getPermissionCheck()
    {
        return $this->wfPermissionService;
    }

    public function getProfileLink(User $user)
    {
        $currentUser = $this->getUser();
        if($currentUser->getUsername() == $user->getUsername()){
            $username = $this->translator->trans('you');
        }else{
            $username = $user->getUsername();
        }
        $link = $this->router->generate('ojs_user_profile', ['slug' => $user->getUsername()]);
        $tooltip = (string)$user;
        $template = '<a target="_blank" data-toggle="tooltip" title="'.$tooltip.'" href="'.$link.'">@'.$username.'</a>';

        return $template;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if(!$token){
            throw new \LogicException('i can not find current user token :/');
        }
        return $token->getUser();
    }
}
