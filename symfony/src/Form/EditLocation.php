<?php

namespace App\Form;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditLocation extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
            ])
            ->add('parent', EntityTreeType::class, [
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
            ->add('submitButton', SubmitType::class, [
                'label'=>'Edit',
                'attr'=> ['class' =>'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}