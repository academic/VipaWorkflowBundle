<?php

namespace Ojs\WorkflowBundle\Command;

use Ojs\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\WorkflowBundle\Entity\ArticleWorkflowStep;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Ojs\WorkflowBundle\Params\StepStatus;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CheckJournalWorkflowCommand
 * @package Ojs\WorkflowBundle\Command
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
     * @var Collection|ArticleWorkflow[]
     */
    private $allWorkflows;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ojs:check:journal:workflow')
            ->setDescription('Checking journal workflows.')
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
        $this->allWorkflows      = $this->em->getRepository(ArticleWorkflow::class)->findAll();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title($this->getDescription());
        $this->io->progressStart(count($this->allWorkflows));
        $counter = 1;
        foreach($this->allWorkflows as $articleWorkflow){
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
