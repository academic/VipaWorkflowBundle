<?php

namespace Vipa\WorkflowBundle\Command;

use Doctrine\ORM\EntityManager;
use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Vipa\WorkflowBundle\Service\WorkflowService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CleanArticleWorkflowCommand
 * @package Vipa\WorkflowBundle\Command
 */
class CleanArticleWorkflowCommand extends ContainerAwareCommand
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
            ->setName('vipa:clean:article:workflow')
            ->setDescription('Clean article workflow.')
            ->addArgument('workflowId', InputArgument::REQUIRED, 'Workflow ID?');
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
         * @var ArticleWorkflow $articleWorkflow
         */
        $articleWorkflow = $this->em->getRepository(ArticleWorkflow::class)->findOneBy([
            'id' => $input->getArgument('workflowId'),
        ]);

        $this->io->title($this->getDescription());
        $this->io->progressStart(count($articleWorkflow));
        $counter = 1;

        if ($articleWorkflow) {

            $this->workflowService->cleanWorkflow($articleWorkflow);

            $this->io->progressAdvance(1);
            $counter = $counter + 1;
        }

        $this->em->flush();
    }
}
