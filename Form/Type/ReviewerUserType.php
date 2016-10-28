<?php

namespace Dergipark\WorkflowBundle\Form\Type;

use Ojs\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewerUserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                    'label' => 'username',
                ]
            )
            ->add('email', EmailType::class, [
                    'label' => 'email',
                ]
            )
            ->add('title', null, [
                'required' => false,
                'label' => 'user.title'
            ])
            ->add('firstName',  TextType::class, [
                    'label' => 'firstname',
                ]
            )
            ->add('lastName', TextType::class, [
                    'label' => 'lastname',
                ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => User::class,
                'cascade_validation' => true,
            )
        );
    }
}
