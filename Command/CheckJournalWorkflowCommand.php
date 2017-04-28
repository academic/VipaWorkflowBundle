<?php

namespace Vipa\WorkflowBundle\Command;

use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Vipa\WorkflowBundle\Entity\ArticleWorkflowStep;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Vipa\WorkflowBundle\Params\StepStatus;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CheckJournalWorkflowCommand
 * @package Vipa\WorkflowBundle\Command
 */
class CheckJournalWorkflowCommand extends ContainerAwareCommand
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

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('vipa:check:journal:workflow')
            ->setDescription('Checking journal workflows.')
            ->addArgument('journalId', InputArgument::REQUIRED, 'Journal ID?')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io               = new SymfonyStyle($input, $output);
        $this->container        = $this->getContainer();
        $this->em               = $this->container->get('doctrine')->getManager();
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
         * @var ArticleWorkflow[] $allWorkflows
         */
        $allWorkflows = $this->em->getRepository(ArticleWorkflow::class)
            ->createQueryBuilder('articleWorkflow')
            ->where('articleWorkflow.journal = :journalId')
            ->setParameter('journalId', $input->getArgument('journalId'))
            ->getQuery()
            ->getResult();

        $this->io->title($this->getDescription());
        $this->io->progressStart(count($allWorkflows));
        $counter = 1;


        foreach($allWorkflows as $articleWorkflow){
            if($articleWorkflow->getCurrentStep() == null){
                /** @var ArticleWorkflowStep $step */
                $step = $this->em->getRepository(ArticleWorkflowStep::class)->findOneBy(['articleWorkflow' => $articleWorkflow, 'status' => StepStatus::ACTIVE]);
                if($step){
                    $articleWorkflow->setCurrentStep($step);
                    $this->em->persist($articleWorkflow);
                    $this->io->progressAdvance(1);
                    $counter = $counter+1;
                }
            }
        }
        $this->em->flush();
    }
}
