<?php

namespace Ojs\WorkflowBundle\Form\Type;

use Ojs\UserBundle\Entity\User;
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
                    'class' => 'Ojs\UserBundle\Entity\User',
                    'remote_route' => 'ojs_journal_user_search'
                ]
            );
    }
}
