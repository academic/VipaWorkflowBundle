<?php

namespace Ojs\WorkflowBundle\Command;

use Doctrine\ORM\EntityManager;
use Ojs\JournalBundle\Entity\Article;
use Ojs\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\WorkflowBundle\Service\WorkflowService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CleanArticleWorkflowCommand
 * @package Ojs\WorkflowBundle\Command
 */
class RestartArticleWorkflowCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var ContainerInterface
     */
    private $container;

    /** @var  WorkflowService */
    private $workflowService;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ojs:restart:article:workflow')
            ->setDescription('Restart article workflow.')
            ->addArgument('articleId', InputArgument::REQUIRED, 'Article ID?');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->container = $this->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();
        $this->workflowService = $this->container->get('dp.workflow_service');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var Article $article
         */
        $article = $this->em->getRepository(Article::class)->findBy(['id' => $input->getArgument('articleId')]);

        /**
         * @var ArticleWorkflow[] $allWorkflows
         */
        $allWorkflows = $this->em->getRepository(ArticleWorkflow::class)->findBy(['article' => $article]);


        $this->io->title($this->getDescription());
        $this->io->note('Found Articles ' . count($article));
        $this->io->note('Found Workflows ' . count($allWorkflows));
        $this->io->progressStart(count($allWorkflows));
        $counter = 1;

        if ($articleWorkflow) {
            foreach ($allWorkflows as $articleWorkflow) {


                $this->workflowService->cleanWorkflow($articleWorkflow);
                $this->workflowService->closeOldWorklfows($articleWorkflow->getArticle(), true);

                $this->io->progressAdvance(1);
                $counter = $counter + 1;
            }
        }

        $this->workflowService->prepareArticleWorkflow($article);


        $this->em->flush();
    }
}
