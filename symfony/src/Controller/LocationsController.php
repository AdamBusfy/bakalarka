<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Location;
use App\Form\AddLocation;
use App\Form\Location\Filter;
use App\Form\DeleteForm;
use App\Form\EditLocation;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LocationsController
 * @package App\Controller
 * @IsGranted("ROLE_ADMIN")
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class LocationsController extends AbstractController
{
    /**
     * @Route("/locations", name="locations")
     * @param Request $request
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {
        //DELETE

        $deleteLocationForm = $this->createForm(DeleteForm::class);
        $deleteLocationForm->handleRequest($request);

        if ($deleteLocationForm->isSubmitted() && $deleteLocationForm->isValid()) {
            $locationRepository = $this->getDoctrine()
                ->getRepository(Location::class);

            $deleteLocation = $locationRepository->find($deleteLocationForm->get('id')->getData());

            if (!empty($deleteLocation)) {
                $this->deleteLocation($deleteLocation);
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
            ->add('ancestors', TextColumn::class, [
                'label' => 'Path',
                'render' => function($value, Location $context) {
                    $links = array_reverse(
                        array_map(
                            function (Location $ancestor) {
                                return sprintf(
//                                    '<a href="../../show/location/%s"> %s</a>'
                                    '<a class="text-secondary" style="text-decoration: none" > %s</a>'
                                    ,
//                                    $ancestor->getId(),
                                    $ancestor->getName()
                                );
                            },
                            $context->getAncestors()
                        )
                    );

                    for ($i = 0; $i < count($links); $i++) {
                        if ($i === count($links) - 1) {
                            $links[$i] = '<a class="text-secondary">' . $links[$i] . '</a>';
                        } else {
                            $links[$i] = '<a class="text-secondary">' . $links[$i] . '</a>';
                        }
                    }

                    return implode(" > ", $links);
                }
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function($value, $context) {
                    $data = '<div class="text-center">';
//                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_location', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_location', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value]);
                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Location::class,
                'criteria' => [
                    function (QueryBuilder $builder) {
                        $name = $_GET['filter']['name'] ?? null;

                        if (!empty(array_filter([$name]))) {
                            if (!empty($name)) {
                                $builder
                                    ->andWhere('location.name LIKE :name') // TODO ???
                                    ->setParameter(
                                        'name',
                                        "%" . $name . "%"
                                    );
                            }
                        }
                        $builder->andWhere('location.isActive = 1');
                    },
                    new SearchCriteriaProvider()
                ]
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('page/location/locations.html.twig', [
            'datatable' => $table,
            'filterForm' => $filterForm->createView(),
            'form' => $deleteLocationForm->createView()
        ]);
    }

    /**
     * @Route("/add/location", name="add_location")
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {

        $addLocation = new Location();
        $addLocationForm = $this->createForm(AddLocation::class, $addLocation);
        $addLocationForm->handleRequest($request);

        if ($addLocationForm->isSubmitted() && $addLocationForm->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($addLocation);
            $entityManager->flush();
            return $this->redirectToRoute('locations');
        }

        return $this->render('page/location/add.html.twig', [
            'addLocationForm' => $addLocationForm->createView(),
        ]);
    }

    /**
     * @Route("/edit/location/{id}", name="edit_location", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        $locationRepository = $this->getDoctrine()
            ->getRepository(Location::class);
        $editLocation = $locationRepository->find($id);
        $editLocationForm = $this->createForm(EditLocation::class, $editLocation, array(
            'method' => 'PUT'
        ));

        $editLocationForm->handleRequest($request);

        if ($editLocationForm->isSubmitted() && $editLocationForm->isValid()) {
            $locationRepository = $this->getDoctrine()
                ->getRepository(Location::class);

            $editLocation = $locationRepository->find($id);

            if (!empty($editLocation)) {
                if (!empty($editLocationForm->get('name')->getData())) {
                    $editLocation->setName($editLocationForm->get('name')->getData());
                }

                $editLocation->setParent($editLocationForm->get('parent')->getData());

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();
                return $this->redirect($request->getUri());
            }
        }

        return $this->render('page/location/edit.html.twig', [
            'editLocationForm' => $editLocationForm->createView(),
        ]);
    }

    private function deleteLocation(Location $location)
    {
        foreach ($location->getChildren() as $child) {
            $this->deleteLocation($child);
        }

        $location->setIsActive(false);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($location);
        $entityManager->flush();
    }
}
