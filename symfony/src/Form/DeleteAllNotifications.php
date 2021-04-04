<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class DeleteAllNotifications extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('submitButton', SubmitType::class, [
                'label' => '<a><span class="c-icon fas fa-check-double"></span> Clear notifications </a>',
                'label_html' => true,
                'attr' => ['class' => 'btn btn-primary btn-lg', 'style=> display:inline-block'],
            ]);
    }

}