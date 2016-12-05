<?php

namespace Dergipark\WorkflowBundle\Form\Type;

use Dergipark\WorkflowBundle\Entity\JournalWorkflowSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalWfSettingType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('doubleBlind', null, [
                'label' => 'double.blind',
            ])
            ->add('reviewerWaitDay', null, [
                'label' => 'reviewer.wait.day'
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
                'data_class' => JournalWorkflowSetting::class,
                'cascade_validation' => true,
            )
        );
    }
}
