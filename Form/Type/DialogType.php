<?php

namespace Vipa\WorkflowBundle\Form\Type;

use Vipa\WorkflowBundle\Entity\StepDialog;
use Vipa\JournalBundle\Form\Type\JournalUsersFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DialogType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('users', JournalUsersFieldType::class,[
                'attr' => [
                    'style' => 'width: 100%;',
                ],
                'label' => $options['action_alias'].'.users',
                'remote_params' => [
                    'journalId' => $options['journalId'],
                    'roles' => implode(',', $options['roles']),
                ]
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
                'data_class' => StepDialog::class,
                'cascade_validation' => true,
                'action_alias' => '',
                'roles' => [],
                'journalId' => null,
            )
        );
    }
}
