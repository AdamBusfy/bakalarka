<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Location;
use App\Entity\User;
use App\Form\AddUserLocation;
use App\Form\DeleteForm;
use App\Form\RemoveUserLocation;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\FetchJoinORMAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    /**
     * @Route("/users", name="users")
     * @param Request $request
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $deleteUserForm = $this->createForm(DeleteForm::class);
        $deleteUserForm->handleRequest($request);

        if ($deleteUserForm->isSubmitted() && $deleteUserForm->isValid()) {
            $userRepository = $this->getDoctrine()
                ->getRepository(User::class);

            $deleteUser = $userRepository->find($deleteUserForm->get('id')->getData());

            if (!empty($deleteUser)) {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($deleteUser);
                $entityManager->flush();
                return $this->redirectToRoute('users');

            }
        }

        $table = $dataTableFactory->create()
            ->add('id', TextColumn::class, [
                'propertyPath' => 'id',
                'label' => 'ID',
                'globalSearchable' => false
            ])
            ->add('name', TextColumn::class, [
                'label' => 'Name'
            ])
            ->add('email', TextColumn::class, [
                'label' => 'E-mail'
            ])
            ->add('date_create', DateTimeColumn::class, [
                'format' => 'm-d-Y',
                'label' => "Timestamp",
                'globalSearchable' => false

            ])
            ->add
            ('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function($value, $context) use ($deleteUserForm) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/attach.html.twig', ['url' => $this->generateUrl('user_add_locations', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value, 'form' => $deleteUserForm->createView()]);
                    $data .= "</div>";
                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => User::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('page/users/users.html.twig', [
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/user/addLocations/{id}", name="user_add_locations", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function show(Request $request, int $id, DataTableFactory $dataTableFactory): Response
    {
        $addLocationForm = $this->createForm(AddUserLocation::class);
        $addLocationForm->handleRequest($request);

        if ($addLocationForm->isSubmitted() && $addLocationForm->isValid()) {
            $locationRepository = $this->getDoctrine()
                ->getRepository(Location::class);
            $userRepository = $this->getDoctrine()
                ->getRepository(User::class);

            $addLocation = $locationRepository->find($addLocationForm->get('id')->getData());
            $user = $userRepository->find($id);

            if (!empty($addLocation)) {
                $this->addLocations($addLocation, $user);
            }

            return $this->redirect($request->getUri());
        }

        $tableAdd = $dataTableFactory->create()
            ->add('id', TextColumn::class, [
                'propertyPath' => 'id',
                'label' => 'ID',
                'globalSearchable' => false
            ])
            ->add('name', TextColumn::class, [
                'label' => 'Name'
            ])
            ->add('ancestors', TextColumn::class, [
                'label' => 'Path',
                'render' => function($value,Location $context) {
                    $links = array_reverse(
                        array_map(
                            function (Location $ancestor) {
                                return sprintf(
                                    '<a href="../../show/location/%s"> %s</a>'
                                    ,
                                    $ancestor->getId(),
                                    $ancestor->getName()
                                );
                            },
                            $context->getAncestors()
                        )
                    );
                    for ($i = 0; $i < count($links); $i++) {
                        if ($i === count($links) - 1) {
                            $links[$i] = '<a class="text-dark" href="#">' . $links[$i] . '</a>';
                        } else {
                            $links[$i] = '<a class="text-secondary" href="#">' . $links[$i] . '</a>';
                        }
                    }
                    return implode(" > ", $links);
                }
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function($value, $context) use($addLocationForm)  {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/add.html.twig', ['id' => $value, 'form' => $addLocationForm->createView()]);
                    $data .= "</div>";
                    return $data;
                }
            ])
            ->setName('first')
            ->createAdapter(FetchJoinORMAdapter::class, [
                'entity' => Location::class,
                'simple_total_query' => false,
                'query' => [
                    function (QueryBuilder $builder) use ($id) {
                        $assignedLocationIds = $this->getDoctrine()
                            ->getRepository(Location::class)
                            ->createQueryBuilder('qb')
                            ->select('DISTINCT l.id')
                            ->from(Location::class, 'l')
                            ->join('l.users', 'lu')
                            ->where('lu.id = :uid')
                            ->setParameter('uid', $id)
                            ->getQuery()
                            ->getArrayResult();

                        $assignedLocationIds = array_map(function (array $record) {
                            return (int) $record['id'];
                        }, $assignedLocationIds);

                        $builder->select('l')
                            ->from(Location::class, 'l');

                        if (!empty($assignedLocationIds)) {
                            $builder
                                ->where($builder->expr()->notIn('l.id',  $assignedLocationIds));
                        }
                    },

                ],
            ])
            ->handleRequest($request);

        if ($tableAdd->isCallback()) {
            return $tableAdd->getResponse();
        }

        $removeLocationForm = $this->createForm(RemoveUserLocation::class);
        $removeLocationForm->handleRequest($request);

        if ($removeLocationForm->isSubmitted() && $removeLocationForm->isValid()) {
            $locationRepository = $this->getDoctrine()
                ->getRepository(Location::class);
            $userRepository = $this->getDoctrine()
                ->getRepository(User::class);

            $removeLocation = $locationRepository->find($removeLocationForm->get('id')->getData());
            $user = $userRepository->find($id);

            if (!empty($removeLocation)) {
                $this->removeLocations($removeLocation, $user);
//                $removeLocation->removeUser($user);
            }

//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->persist($removeLocation);
//            $entityManager->flush();
            return $this->redirectToRoute('user_add_locations', array('id' => $id));
        }

        $tableRemove = $dataTableFactory->create()
            ->add('id', TextColumn::class, [
                'propertyPath' => 'id',
                'label' => 'ID',
                'globalSearchable' => false
            ])
            ->add('name', TextColumn::class, [
                'label' => 'Name'
            ])
            ->add('ancestors', TextColumn::class, [
                'label' => 'Path',
                'render' => function($value,Location $context) {
                    $links = array_reverse(
                        array_map(
                            function (Location $ancestor) {
                                return sprintf(
                                    '<a href="../../show/location/%s"> %s</a>'
                                    ,
                                    $ancestor->getId(),
                                    $ancestor->getName()
                                );
                            },
                            $context->getAncestors()
                        )
                    );
                    for ($i = 0; $i < count($links); $i++) {
                        if ($i === count($links) - 1) {
                            $links[$i] = '<a class="text-dark" href="#">' . $links[$i] . '</a>';
                        } else {
                            $links[$i] = '<a class="text-secondary" href="#">' . $links[$i] . '</a>';
                        }
                    }
                    return implode(" > ", $links);
                }
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function($value, $context) use($removeLocationForm)  {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value, 'form' => $removeLocationForm->createView()]);
                    $data .= "</div>";
                    return $data;
                }])
            ->setName('second')
            ->createAdapter(FetchJoinORMAdapter::class, [
                'entity' => Location::class,
                'simple_total_query' => false,
                'query' => [
                    function (QueryBuilder $builder) use ($id) {

                        $builder->select('l')
                            ->from(Location::class, 'l')
                            ->join('l.users', 'lu')
                            ->where('lu.id = :uid')
                            ->setParameter('uid', $id);
                    },
                ],
            ])
            ->handleRequest($request);

        if ($tableRemove->isCallback()) {
            return $tableRemove->getResponse();
        }

        return $this->render('page/users/addLocations.html.twig', [
            'datatableAdd' => $tableAdd,
            'datatableRemove' => $tableRemove
        ]);
    }



    private function addLocations(Location $location, User $user) {

        foreach ($location->getChildren() as $child) {
            $this->addLocations($child, $user);
        }

        $location->addUser($user);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($location);
        $entityManager->flush();
    }

    private function removeLocations(Location $location, User $user) {

        foreach ($location->getChildren() as $child) {
            $this->removeLocations($child, $user);
        }

        $location->removeUser($user);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($location);
        $entityManager->flush();
    }
}
