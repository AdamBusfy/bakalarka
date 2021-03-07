<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use App\Entity\User;
use App\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddItemToLocation extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $builder
            ->add('id', HiddenType::class, [
                'mapped' => false
            ])
            ->add('location', EntityTreeType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
                'required' => false,
                'query_builder' => function (LocationRepository $repository) use ($user, $isAdmin) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('l')
                        ->from(Location::class, 'l');

                    if (!$isAdmin) {
                        $usersLocationsIds = array_map(function (Location $location) {
                            return $location->getId();
                        }, $user->getLocations()->toArray());
                        $queryBuilder->andWhere($queryBuilder->expr()->in('l', $usersLocationsIds));
                    }

                    return $queryBuilder;
                }
            ])
            ->add('submitButton', SubmitType::class, [
                'label'=>'Assign',
                'attr'=> ['class' =>'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', User::class);

    }
}