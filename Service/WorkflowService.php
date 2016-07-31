<?php

namespace Dergipark\WorkflowBundle\Service;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\JournalWorkflowStep;
use Doctrine\ORM\EntityManager;
use Ojs\CoreBundle\Params\ArticleStatuses;
use Ojs\JournalBundle\Service\JournalService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Ojs\JournalBundle\Entity\Article;

class WorkflowService
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
     * @param RequestStack              $requestStack
     * @param EventDispatcherInterface  $eventDispatcher
     */
    public function __construct(
        EntityManager $em = null,
        RouterInterface $router = null,
        TranslatorInterface $translator = null,
        JournalService $journalService = null,
        TokenStorageInterface $tokenStorage = null,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->router = $router;
        $this->journalService = $journalService;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function prepareArticleWorkflow(Article $article)
    {
        $articleWorkflow = new ArticleWorkflow();
        $articleWorkflow
            ->setArticle($article)
            ->setJournal($this->journalService->getSelectedJournal())
            ->setStartDate(new \DateTime())
            ;

        foreach($this->currentJournalWorkflowSteps() as $step){
            $articleWorkflowStep = new ArticleWorkflowStep();
            $articleWorkflowStep
                ->setOrder($step->getOrder())
                ->setGrantedUsers($step->getGrantedUsers())
                ->setArticleWorkflow($articleWorkflow)
                ;
            foreach($step->getGrantedUsers() as $user){
                $articleWorkflow->addRelatedUser($user);
            }
            $this->em->persist($articleWorkflowStep);
        }
        $articleWorkflow->addRelatedUser($article->getSubmitterUser());

        $this->em->persist($articleWorkflow);

        $article->setStatus(ArticleStatuses::STATUS_INREVIEW);

        $this->em->flush();

        return $articleWorkflow;
    }

    public function currentJournalWorkflowSteps()
    {
        return $this->em->getRepository(JournalWorkflowStep::class)->findAll();
    }
}
