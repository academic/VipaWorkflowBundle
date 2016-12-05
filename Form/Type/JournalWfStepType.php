<?php

namespace Ojs\WorkflowBundle\Form\Type;

use Ojs\WorkflowBundle\Entity\JournalWorkflowStep;
use Ojs\JournalBundle\Form\Type\JournalUsersFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalWfStepType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('grantedUsers', JournalUsersFieldType::class,[
                'attr' => [
                    'style' => 'width: 100%;',
                ],
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
                'data_class' => JournalWorkflowStep::class,
                'cascade_validation' => true,
            )
        );
    }
}
