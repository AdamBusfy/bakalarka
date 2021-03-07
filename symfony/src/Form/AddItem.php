<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddItem extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('parent', EntityTreeType::class, [
                'class' => Item::class,
                'choice_label' => 'name',
                'required' => false
            ])
            ->add('category', EntityTreeType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => true,
            ])
            ->add('location', EntityTreeType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'required' => false,
            ])
            ->add('submitButton', SubmitType::class, [
                'label'=>'Add',
                'attr'=> ['class' =>'btn btn-success btn-xs']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
        ]);
    }
}