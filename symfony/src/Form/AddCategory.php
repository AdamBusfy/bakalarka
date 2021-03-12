<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Location;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddCategory extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $category = $options['presetCategory'];


        $builder
            ->add('name', TextType::class)
            ->add('parent', EntityTreeType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'data' => $category,
                'required' => false,
                'query_builder' => function (CategoryRepository $repository) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('c')
                        ->from(Category::class, 'c');
                    $queryBuilder->andWhere('c.isActive = 1');
                    return $queryBuilder;
                }
            ])
            ->add('submitButton', SubmitType::class, [
                'label'=>'Add',
                'attr'=> ['class' =>'btn btn-success btn-xs']
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'presetCategory' => null,
        ]);
        $resolver->addAllowedTypes('presetCategory', [Category::class, 'null']);
    }
}