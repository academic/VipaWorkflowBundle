<?php

namespace Vipa\WorkflowBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vipa\JournalBundle\Entity\Journal;

trait WorkflowJournalTrait
{
    /**
     * @var Journal
     */
    protected $journal;

    /**
     * @return Journal
     */
    public function getJournal()
    {
        return $this->journal;
    }

    /**
     * @param Journal $journal
     * @return $this
     */
    public function setJournal(Journal $journal = null)
    {
        $this->journal = $journal;

        return $this;
    }
}
