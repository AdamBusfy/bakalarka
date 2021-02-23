<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditCategory extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class, [
                'mapped' => false
            ])
            ->add('name', TextType::class, [
                'required' => false
            ])
            ->add('parent', EntityTreeType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false
            ])
            ->add('submitButton', SubmitType::class, [
                'label'=>'Edit',
                'attr'=> ['class' =>'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
//            'data_class' => Category::class,
        ]);
    }
}