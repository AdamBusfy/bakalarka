<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use App\Repository\CategoryRepository;
use App\Repository\ItemRepository;
use App\Repository\LocationRepository;
use Omines\DataTablesBundle\Column\NumberColumn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
                'required' => false,
                'query_builder' => function (ItemRepository $repository) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('i')
                        ->from(Item::class, 'i');
                    $queryBuilder->andWhere('i.isActive = 1');
                    return $queryBuilder;
                }
            ])
            ->add('category', EntityTreeType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => true,
                'query_builder' => function (CategoryRepository $repository) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('c')
                        ->from(Category::class, 'c');
                    $queryBuilder->andWhere('c.isActive = 1');
                    return $queryBuilder;
                }
            ])
            ->add('location', EntityTreeType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'required' => false,
                'query_builder' => function (LocationRepository $repository) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('l')
                        ->from(Location::class, 'l');
                    $queryBuilder->andWhere('l.isActive = 1');
                    return $queryBuilder;
                }
            ])
            ->add('price', NumberType::class, [
                'data' => 0.00
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