<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ItemRepository;
use App\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditItem extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $builder
            ->add('name', TextType::class, [
                'required' => false,
            ])
            ->add('parent', EntityTreeType::class, [
                'class' => Item::class,
                'choice_label' => 'name',
                'required' => false,
                'query_builder' => function (ItemRepository $repository) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('i')
                        ->from(Item::class, 'i');
                    $queryBuilder->where('i.isActive = 1');
                    return $queryBuilder;
                }
            ])
            ->add('category', EntityTreeType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'required' => false,
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
                'query_builder' => function (LocationRepository $repository) use ($user, $isAdmin) {
                    $queryBuilder = $repository->createQueryBuilder('qb')
                        ->select('l')
                        ->from(Location::class, 'l');

                    if (!$isAdmin) {
                        $usersLocationsIds = array_map(function (Location $location) {
                            return $location->getId();
                        }, $user->getLocations()->toArray());
                        if (!empty($usersLocationsIds)) {
                            $queryBuilder->andWhere($queryBuilder->expr()->in('l', $usersLocationsIds));
                        }
                    }
                    $queryBuilder->andWhere('l.isActive = 1');
                    return $queryBuilder;
                }
            ])
            ->add('price', NumberType::class)
            ->add('submitButton', SubmitType::class, [
                'label'=>'Edit',
                'attr'=> ['class' =>'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
            'user' => null,
        ]);
        $resolver->setAllowedTypes('user', User::class);

    }
}