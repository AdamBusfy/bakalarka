<?php

namespace App\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class DeleteNotification extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('submitButton', SubmitType::class, [
                'label' => '<i class="c-icon fas fa-check"></i>',
                'label_html' => true,
                'attr'=> ['class' =>'btn btn-primary btn-sm', 'style=> display:inline-block'],
            ]);
    }

}