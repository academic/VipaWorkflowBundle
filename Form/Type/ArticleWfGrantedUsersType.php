<?php

namespace Dergipark\WorkflowBundle\Form\Type;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflow;
use Ojs\JournalBundle\Form\Type\JournalUsersFieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleWfGrantedUsersType extends AbstractType
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
                'data_class' => ArticleWorkflow::class,
                'cascade_validation' => true,
                'journalId' => null,
                'roles' => [],
            )
        );
    }
}
