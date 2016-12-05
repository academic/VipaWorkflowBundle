<?php

namespace Ojs\WorkflowBundle\Form\Type;

use Ojs\WorkflowBundle\Entity\JournalReviewForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class JournalReviewFormType
 * @package Ojs\WorkflowBundle\Form\Type
 */
class JournalReviewFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('active', CheckboxType::class, [
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'build.review.form'
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => JournalReviewForm::class,
                'cascade_validation' => true,
                'attr' => [
                    'novalidate' => 'novalidate',
                ]
            )
        );
    }
}
