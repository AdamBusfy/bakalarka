<?php

namespace App\Form\Item;

use App\Entity\Category;
use App\Entity\Location;
use App\Form\EntityTreeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class Filter extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('name', TextType::class, [
                'required' => false
            ])
            ->add('location', EntityTreeType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'required' => false
            ])
            ->add('category', EntityTreeType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false
            ])
//            ->add('startDateTime', DateTimeType::class, [
//                'date_label' => 'Starts On',
////                'input_format' => 'Y-m-d',
//                'format' => 'Y-m-d'
//            ])
            ->add('submit', SubmitType::class);

    }
}