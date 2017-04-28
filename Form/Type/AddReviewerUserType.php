<?php

namespace Vipa\WorkflowBundle\Form\Type;

use Vipa\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AddReviewerUserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
            $builder->add(
                'reviewerUser',
                'tetranz_select2entity',
                [
                    'required' => true,
                    'label' => 'user',
                    'placeholder' => 'user',
                    'class' => 'Vipa\UserBundle\Entity\User',
                    'remote_route' => 'vipa_journal_user_search'
                ]
            );
    }
}
