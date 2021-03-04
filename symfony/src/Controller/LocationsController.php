<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\Location;
use App\Form\AddLocation;
use App\Form\DeleteForm;
use App\Form\EditLocation;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
                                    '<a> %s</a>'
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
                'render' => function($value, $context) use ($deleteLocationForm) {
                    $data = '<div class="text-center">';
//                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_location', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_location', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['confirm' => true, 'id' => $value, 'form' => $deleteLocationForm->createView()]);
                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Location::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('page/location/locations.html.twig', [
            'datatable' => $table
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
     * @Route("/show/location/{id}", name="show_location", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function show(Request $request, int $id, DataTableFactory $dataTableFactory): Response
    {
        $locationRepository = $this->getDoctrine()
            ->getRepository(Location::class);
        $location = $locationRepository->find($id);

        $table = $dataTableFactory->create()
            ->add('id', TextColumn::class, [
                'propertyPath' => 'id',
                'label' => 'ID',
                'globalSearchable' => false
            ])
            ->add('name', TextColumn::class, [
                'label' => 'Name'
            ])
            ->add('location', TextColumn::class, [
                'label' => 'Location',
                'render' => function($value, Item $context) {
                    if (empty($context->getLocation())) {
                        return sprintf(
                            '<a class="text-secondary" style="pointer-events: none;"> %s</a>'
                            ,
                            "empty location"
                        );
                    }
                    return sprintf(
                        '<a href="../../show/location/%s"> %s</a>'
                        ,
                        $context->getLocation()->getId(),
                        $context->getLocation()->getName()
                    );
                }])
            ->add('category', TextColumn::class, [
                'label' => 'Category',
                'render' => function($value, Item $context) {
                    if (empty($context->getCategory())) {
                        return sprintf(
                            '<a class="text-secondary"> %s</a>'
                            ,
                            "empty category"
                        );
                    }
                    return sprintf(
                        '<a href="../../show/category/%s"> %s</a>'
                        ,
                        $context->getCategory()->getId(),
                        $context->getCategory()->getName()
                    );
                }])
            ->add('ancestors', TextColumn::class, [
                'label' => 'Path',
                'render' => function($value, Item $context) {
                    $links = array_reverse(
                        array_map(
                            function (Item $ancestor) {
                                return sprintf(
                                    '<a href="../../show/item/%s"> %s</a>'
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
            ->add('date_create', DateTimeColumn::class, [
                'format' => 'm-d-Y',
                'label' => "Timestamp"
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function($value, $context) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_item', ['id' => $value])]);
//                    $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_item', ['id' => $value])]);
//                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['confirm' => true, 'id' => $value, 'form' => $deleteItemForm->createView()]);
                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Item::class,
                'query' => function (QueryBuilder $builder) use ($id) {
                    $builder
                        ->select('i')
                        ->from(Item::class, 'i')
                        ->where('i.location = :lid')
                        ->setParameter('lid', $id)
                    ;
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }



        return $this->render('page/location/show.html.twig', [
            'location' => $location,
            'datatable' => $table
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

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($location);
        $entityManager->flush();
    }
}
