<?php

namespace Dergipark\WorkflowBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\Common\Collections\ArrayCollection;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\Entity\AbstractTranslatable;
use Ojs\CoreBundle\Entity\GenericEntityTrait;

/**
 * WorkflowStep
 */
class WorkflowStep extends AbstractTranslatable
{
    use GenericEntityTrait;

    /**
     * @Prezent\Translations(targetEntity="Dergipark\WorkflowBundle\Entity\WorkflowStepTranslation")
     */
    protected $translations;

    /**
     * @var integer
     * @GRID\Column(title="id")
     */
    protected $id;

    /**
     * @var string
     * @GRID\Column(title="title")
     */
    private $title;

    /**
     * @var string
     * @GRID\Column(title="description")
     */
    private $description;

    /**
     * @var integer
     */
    private $order;

    /**
     * Step constructor.
     *
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param  integer $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
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
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        if (!is_null($this->translate()->getTitle())) {
            return $this->translate()->getTitle();
        } else {
            return $this->translations->first()->getTitle();
        }
    }

    /**
     * Set title
     *
     * @param  string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->translate()->setTitle($title);

        return $this;
    }

    /**
     * Translation helper method
     * @param null $locale
     * @return mixed|null|\BulutYazilim\WorkflowBundle\Entity\StepTranslation
     */
    public function translate($locale = null)
    {
        if (null === $locale) {
            $locale = $this->currentLocale;
        }
        if (!$locale) {
            throw new \RuntimeException('No locale has been set and currentLocale is empty');
        }
        if ($this->currentTranslation && $this->currentTranslation->getLocale() === $locale) {
            return $this->currentTranslation;
        }
        $defaultTranslation = $this->translations->get($this->getDefaultLocale());
        if (!$translation = $this->translations->get($locale)) {
            $translation = new WorkflowStepTranslation();
            if (!is_null($defaultTranslation)) {
                $translation->setTitle($defaultTranslation->getTitle());
                $translation->setDescription($defaultTranslation->getDescription());
            }
            $translation->setLocale($locale);
            $this->addTranslation($translation);
        }
        $this->currentTranslation = $translation;

        return $translation;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        if (!is_null($this->translate()->getDescription())) {
            return $this->translate()->getDescription();
        } else {
            return $this->translations->first()->getDescription();
        }
    }

    /**
     * Set description
     *
     * @param  string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->translate()->setDescription($description);

        return $this;
    }

    public function __toString()
    {
        return $this->getTitle();
    }
}
