<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use App\Entity\User;
use App\Form\AddUserLocation;
use App\Form\DeleteForm;
use App\Form\Users\Filter;
use App\Form\RemoveUserLocation;
use App\PDF;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\FetchJoinORMAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use function Clue\StreamFilter\fun;

/**
 * Class UsersController
 * @package App\Controller
 * @IsGranted("ROLE_ADMIN")
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
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
                $deleteUser->setIsActive(false);
                $entityManager->persist($deleteUser);
                $entityManager->flush();
                $this->addFlash('success', 'User successfully deleted!');
                return $this->redirectToRoute('users');

            }
        }

        $filterForm = $this->createForm(Filter::class, null, ['csrf_protection' => false]);
        $filterForm->handleRequest($request);

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
                'format' => 'd/m/Y',
                'label' => "Timestamp",
                'globalSearchable' => false

            ])
            ->add
            ('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function ($value, $context) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/attach.html.twig', ['url' => $this->generateUrl('user_add_locations', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value]);
                    $data .= "</div>";
                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => User::class,
                'criteria' => [
                    function (QueryBuilder $builder) {
                        $name = $_GET['filter']['name'] ?? null;
                        $email = $_GET['filter']['email'] ?? null;
                        $startDateTime = $_GET['filter']['startDateTime'] ?? null;
                        $endDateTime = $_GET['filter']['endDateTime'] ?? null;

                        if (!empty(array_filter([$name, $startDateTime, $endDateTime, $email]))) {
                            if (!empty($name)) {
                                $builder
                                    ->andWhere('user.name LIKE :name') // TODO ???
                                    ->setParameter(
                                        'name',
                                        "%" . $name . "%"
                                    );
                            }

                            if (!empty($email)) {
                                $builder
                                    ->andWhere('user.email LIKE :email') // TODO ???
                                    ->setParameter(
                                        'email',
                                        "%" . $email . "%"
                                    );
                            }

                            if (!empty($startDateTime)) {
                                $startDateTimeFormatted = DateTimeImmutable::createFromFormat('d/m/y', $startDateTime);
                                $builder
                                    ->andWhere('user.date_create >= :startDateTime')
                                    ->setParameter(
                                        'startDateTime',
                                        $startDateTimeFormatted->format('Y-m-d')
                                    );
                            }

                            if (!empty($endDateTime)) {
                                $endDateTimeFormatted = DateTimeImmutable::createFromFormat('d/m/y', $endDateTime);
                                $builder
                                    ->andWhere('user.date_create <= :endDateTime')
                                    ->setParameter(
                                        'endDateTime',
                                        $endDateTimeFormatted->format('Y-m-d')
                                    );
                            }
                        }
                        $builder->andWhere('user.isActive = 1');

                    },
                    new SearchCriteriaProvider()
                ]
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('page/users/users.html.twig', [
            'datatable' => $table,
            'filterForm' => $filterForm->createView(),
            'form' => $deleteUserForm->createView()
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

            $this->addFlash('success', 'Location successfully assigned to user!');
            return $this->redirect($request->getUri());
        }

        $filterLeftForm = $this->createForm(\App\Form\Users\FilterAddLocationsLeft::class, null, ['csrf_protection' => false]);
        $filterLeftForm->handleRequest($request);

        $tableAdd = $dataTableFactory->create()
            ->setName('LeftTable')
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
                'render' => function ($value, Location $context) {
                    $links = array_reverse(
                        array_map(
                            function (Location $ancestor) {
                                return sprintf(
                                    '<a> %s</a>'
                                    ,
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
                'render' => function ($value, $context) use ($addLocationForm) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/add.html.twig', ['id' => $value, 'form' => $addLocationForm->createView()]);
                    $data .= "</div>";
                    return $data;
                }
            ])
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
                            return (int)$record['id'];
                        }, $assignedLocationIds);

                        $builder->select('l')
                            ->from(Location::class, 'l');

                        if (!empty($assignedLocationIds)) {
                            $builder
                                ->where($builder->expr()->notIn('l.id', $assignedLocationIds));
                        }

                        $nameLeft = $_GET['filter_add_locations_left']['name'] ?? null;

                        if (!empty(array_filter([$nameLeft]))) {
                            if (!empty($nameLeft)) {
                                $builder
                                    ->andWhere('l.name LIKE :name') // TODO ???
                                    ->setParameter(
                                        'name',
                                        "%" . $nameLeft . "%"
                                    );
                            }
                        }
                        $builder->andWhere('l.isActive = 1');
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
            }

            $this->addFlash('success', 'Location successfully removed from user!');
            return $this->redirectToRoute('user_add_locations', array('id' => $id));
        }

        $filterRightForm = $this->createForm(\App\Form\Users\FilterAddLocationsRight::class, null, ['csrf_protection' => false]);
        $filterRightForm->handleRequest($request);

        $tableRemove = $dataTableFactory->create()
            ->setName('RightTable')
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
                'render' => function ($value, Location $context) {
                    $links = array_reverse(
                        array_map(
                            function (Location $ancestor) {
                                return sprintf(
                                    '<a> %s</a>'
                                    ,
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
                'render' => function ($value, $context) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/removeUsersFromLocation.html.twig', ['id' => $value]);
                    $data .= "</div>";
                    return $data;
                }])
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
                        $builder->andWhere('l.isActive = 1');

                        $nameRight = $_GET['filter_add_locations_right']['name'] ?? null;

                        if (!empty(array_filter([$nameRight]))) {
                            if (!empty($nameRight)) {
                                $builder
                                    ->andWhere('l.name LIKE :name') // TODO ???
                                    ->setParameter(
                                        'name',
                                        "%" . $nameRight . "%"
                                    );
                            }
                        }
                    },
                ],

            ])
            ->handleRequest($request);

        if ($tableRemove->isCallback()) {
            return $tableRemove->getResponse();
        }

        return $this->render('page/users/addLocations.html.twig', [
            'datatableAdd' => $tableAdd,
            'datatableRemove' => $tableRemove,
            'form' => $removeLocationForm->createView(),
            'filterLeftForm' => $filterLeftForm->createView(),
            'filterRightForm' => $filterRightForm->createView(),
        ]);
    }

    /**
     * @Route("/export/users/csv/", name="export_users_csv")
     * @param Request $request
     * @return Response
     */
    public function exportData(Request $request): Response
    {
        $allUsers = $this->getDoctrine()
            ->getRepository(User::class)->findAll();

        $response = new StreamedResponse();

        $pdf = new PDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',9);

        foreach ($allUsers as $user) {
            $pdf->Cell(40,5,'Username: ' . $user->getName() . "   Mail: " . $user->getEmail());
            $pdf->Ln();
            $pdf->Cell(40,5,"Managed locations   " );
            $pdf->Ln();

            $pdf->BasicTable(
                ['id', 'name'],
                array_map(function (Location $location) {
                    return [
                        $location->getId(),
                        $location->getName()
                    ];
                }, $user->getLocations()->toArray())
            );
            $pdf->Cell(40, 20);
            $pdf->Ln();
        }

        $pdf->Output();

        $response->setCallback(
            function () use ($pdf) {
                $handle = fopen('php://output', 'r+');
                    $data = $pdf;
                fclose($handle);
            }
        );

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.pdf"');

        return $response;
    }


    private function addLocations(Location $location, User $user)
    {

        foreach ($location->getChildren() as $child) {
            $this->addLocations($child, $user);
        }

        $location->addUser($user);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($location);
        $entityManager->flush();
    }

    private function removeLocations(Location $location, User $user)
    {

        foreach ($location->getChildren() as $child) {
            $this->removeLocations($child, $user);
        }

        $location->removeUser($user);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($location);
        $entityManager->flush();
    }
}
