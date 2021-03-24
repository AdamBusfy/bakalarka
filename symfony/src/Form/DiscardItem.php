<?php

namespace App\Form;


use App\Entity\Item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscardItem extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, [
                'mapped' => false
            ])
            ->add('discardReason', TextareaType::class, [
                'label' => 'Discard reason (optional)'
            ])
            ->add('submitButton', SubmitType::class, [
                'label'=>'Discard',
                'attr'=> ['class' =>'btn btn-danger']
            ]);
    }
}