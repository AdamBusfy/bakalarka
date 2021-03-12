<?php

namespace App\Form;

use App\Entity\Location;
use App\Entity\User;
use App\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddLocation extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $location = $options['presetLocation'];
//        var_dump($location);exit;
        //exit;

        $builder
            ->add('name', TextType::class)
            ->add('parent', EntityTreeType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'data' => $location,
                'required' => false,
                'query_builder' => function (LocationRepository $repository) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('l')
                        ->from(Location::class, 'l')
                        ->andWhere('l.isActive = 1');
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
            'presetLocation' => null,
        ]);
        $resolver->addAllowedTypes('presetLocation', [Location::class, 'null']);

//        $resolver->setAllowedTypes('presetLocation', Location::class);

    }
}