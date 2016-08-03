<?php

namespace Dergipark\WorkflowBundle\Entity;

use Dergipark\WorkflowBundle\Params\DialogPostTypes;
use Ojs\UserBundle\Entity\User;

/**
 * Class DialogPost
 * @package Dergipark\WorkflowBundle\Entity
 */
class DialogPost
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \DateTime
     */
    private $sendedAt;

    /**
     * @var int
     */
    private $type = DialogPostTypes::TYPE_TEXT;

    /**
     * @var string
     */
    private $text;

    /**
     * @var string
     */
    private $file;

    /**
     * @var StepDialog
     */
    private $dialog;

    /**
     * @var User
     */
    private $sendedBy;

    /**
     * DialogPost constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getSendedAt()
    {
        return $this->sendedAt;
    }

    /**
     * @param \DateTime $sendedAt
     *
     * @return $this
     */
    public function setSendedAt($sendedAt)
    {
        $this->sendedAt = $sendedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     *
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return StepDialog
     */
    public function getDialog()
    {
        return $this->dialog;
    }

    /**
     * @param StepDialog $dialog
     *
     * @return $this
     */
    public function setDialog($dialog)
    {
        $this->dialog = $dialog;

        return $this;
    }

    /**
     * @return User
     */
    public function getSendedBy()
    {
        return $this->sendedBy;
    }

    /**
     * @param User $sendedBy
     *
     * @return $this
     */
    public function setSendedBy($sendedBy)
    {
        $this->sendedBy = $sendedBy;

        return $this;
    }
}
