<?php

namespace Ojs\WorkflowBundle\Command;

use Ojs\WorkflowBundle\Entity\JournalWorkflowSetting;
use Ojs\WorkflowBundle\Entity\JournalWorkflowStep;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Ojs\JournalBundle\Entity\Journal;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NormalizeJournalWorkflowCommand
 * @package Ojs\WorkflowBundle\Command
 */
class NormalizeJournalWorkflowCommand extends ContainerAwareCommand
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
     * @var Collection|Journal[]
     */
    private $allJournals;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ojs:normalize:journal:workflow')
            ->setDescription('Normalize journal workflows.')
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
        $this->allJournals      = $this->em->getRepository('OjsJournalBundle:Journal')->findAll();
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
        $this->io->progressStart(count($this->allJournals));
        $counter = 1;
        foreach($this->allJournals as $journal){
            $this->normalizeWorkflowSetting($journal);
            $this->normalizeWorkflowSteps($journal);
            $this->io->progressAdvance(1);
            $counter = $counter+1;
            if($counter%50 == 0){
                $this->em->flush();
            }
        }
        $this->em->flush();
    }

    /**
     * @param Journal $journal
     */
    private function normalizeWorkflowSetting(Journal $journal)
    {
        $findWorkflowSetting = $this->em->getRepository(JournalWorkflowSetting::class)->findOneBy([
            'journal' => $journal,
        ]);
        // if journal workflow settings exists return null
        if($findWorkflowSetting){
           return;
        }
        //if not exists persist new one
        $journalWorkflowSetting = new JournalWorkflowSetting();
        $journalWorkflowSetting
            ->setJournal($journal)
            ;
        $this->em->persist($journalWorkflowSetting);
    }

    /**
     * @param Journal $journal
     */
    private function normalizeWorkflowSteps(Journal $journal)
    {
        // for each step
        foreach(range(1,3) as $stepOrder){
            // find journal workflow step
            $step = $this->em->getRepository(JournalWorkflowStep::class)->findBy([
                'journal' => $journal,
                'order' => $stepOrder
            ]);
            //if step is already exists continue to
            if($step){
                continue;
            }
            // if journal step is not exists setup new one
            $journalStep = new JournalWorkflowStep();
            $journalStep
                ->setJournal($journal)
                ->setOrder($stepOrder)
            ;
            // add all journal granted users to step granted users
            foreach($this->getJournalRelatedUsers($journal) as $user){
                $journalStep->addGrantedUser($user);
            }
            $this->em->persist($journalStep);
        }
    }

    /**
     * @param Journal $journal
     * @return \Ojs\UserBundle\Entity\User[]
     */
    public function getJournalRelatedUsers(Journal $journal)
    {
        // collect editors and co-editors as journal granted users
        return $this->em->getRepository('OjsUserBundle:User')->findUsersByJournalRole(
            ['ROLE_EDITOR', 'ROLE_CO_EDITOR'],
            $journal
        );
    }
}
