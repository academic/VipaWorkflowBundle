<?php

namespace Dergipark\WorkflowBundle\Command;

use Dergipark\WorkflowBundle\Entity\JournalWorkflowSetting;
use Dergipark\WorkflowBundle\Entity\JournalWorkflowStep;
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
 * @package Dergipark\DergiparkWorkflowBundle\Command
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
            ->setName('dergipark:normalize:journal:workflow')
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
        if($findWorkflowSetting){
           return;
        }
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
        foreach(range(1,3) as $stepOrder){
            $step = $this->em->getRepository(JournalWorkflowStep::class)->findBy([
                'journal' => $journal,
                'order' => $stepOrder
            ]);
            if($step){
                continue;
            }
            $journalStep = new JournalWorkflowStep();
            $journalStep
                ->setJournal($journal)
                ->setOrder($stepOrder)
            ;
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
        return $this->em->getRepository('OjsUserBundle:User')->findUsersByJournalRole(
            ['ROLE_EDITOR', 'ROLE_CO_EDITOR'],
            $journal
        );
    }
}
