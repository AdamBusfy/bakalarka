<?php

namespace App\Form\Item;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class FilterDeletedItems extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('name', TextType::class, [
                'required' => false
            ])
            ->add('startDateTime', TextType::class, [
                'attr' => [
                    'class' => 'form-control datepicker',
                    'type' => 'text',
                    'placeholder' => 'from',
                    'data-date-format' => 'dd/mm/yy',
                    'autocomplete' => 'off'
                ],
                'label' => false,
                'required' => false,
            ])
            ->add('endDateTime', TextType::class, [
                'attr' => [
                    'class' => 'form-control datepicker',
                    'type' => 'text',
                    'placeholder' => 'to',
                    'data-date-format' => 'dd/mm/yy',
                    'autocomplete' => 'off'
                ],
                'label' => false,
                'required' => false,
            ])
            ->add('submit', SubmitType::class);

    }
}