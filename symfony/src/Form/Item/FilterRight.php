<?php

namespace App\Form\Item;

use App\Entity\Category;
use App\Entity\Location;
use App\Entity\User;
use App\Form\EntityTreeType;
use App\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;

class FilterRight extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $builder
            ->setMethod('GET')
            ->add('name', TextType::class, [
                'required' => false
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
            ->add('category', EntityTreeType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', User::class);
    }
}