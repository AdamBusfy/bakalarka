<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use App\Form\AddItem;
use App\Form\AddItemToLocation;
use App\Form\DeleteForm;
use App\Form\EditItem;
use App\Form\Item\FilterLeft;
use App\Form\Item\FilterRight;
use App\Form\RemoveItemFromLocation;
use DateTimeImmutable;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemsController extends AbstractController
{
    /**
     * @Route("/items", name="items")
     * @param Request $request
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function index(Request $request, DataTableFactory $dataTableFactory): Response
    {

        $addItemToLocationForm = $this->createForm(AddItemToLocation::class, null, ['csrf_protection' => false, 'user' => $this->getUser()]);
        $addItemToLocationForm->handleRequest($request);

        if ($addItemToLocationForm->isSubmitted() && $addItemToLocationForm->isValid()) {
            $itemRepository = $this->getDoctrine()
                ->getRepository(Item::class);

            /** @var Item $itemAdd */
                $itemAdd = $itemRepository->find($addItemToLocationForm->get('id')->getData());


            if (!empty($itemAdd)) {

                $this->editChildren($itemAdd, $addItemToLocationForm->get('location')->getData());
                return $this->redirect($request->getUri());
            }
        }

        $removeItemFromLocationForm = $this->createForm(RemoveItemFromLocation::class);
        $removeItemFromLocationForm->handleRequest($request);

        if ($removeItemFromLocationForm->isSubmitted() && $removeItemFromLocationForm->isValid()) {
            $itemRepository = $this->getDoctrine()
                ->getRepository(Item::class);

            /** @var Item $itemRemove */
                $itemRemove = $itemRepository->find($removeItemFromLocationForm->get('id')->getData());

            if (!empty($itemRemove)) {
                $this->editChildren($itemRemove, null);
                return $this->redirect($request->getUri());
            }
        }


        //DELETE

        $deleteItemForm = $this->createForm(DeleteForm::class);
        $deleteItemForm->handleRequest($request);

        if ($deleteItemForm->isSubmitted() && $deleteItemForm->isValid()) {
            $itemRepository = $this->getDoctrine()
                ->getRepository(Item::class);

            $deleteItem = $itemRepository->find($deleteItemForm->get('id')->getData());

            if (!empty($deleteItem)) {
                $this->deleteItem($deleteItem);
            }
        }

        $usersLocations = $this->getUser()->getLocations()->toArray();

        $filterFormRightTable = $this->createForm(FilterRight::class, null, ['csrf_protection' => false, 'user' => $this->getUser()]);
        $filterFormRightTable->handleRequest($request);

        $filterFormLeftTable = $this->createForm(FilterLeft::class, null, ['csrf_protection' => false]);
        $filterFormLeftTable->handleRequest($request);

        $leftTable = $dataTableFactory->create()
            ->setName('LeftTable')
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
                'render' => function ($value, Item $context) {
                    if (empty($context->getLocation())) {
                        return sprintf(
                            '<a class="text-secondary" style="pointer-events: none;"> %s</a>'
                            ,
                            "empty location"
                        );
                    }
                    return sprintf(
//                        '<a href="../../show/location/%s"> %s</a>'
                        '<a> %s</a>'
                        ,
//                        $context->getLocation()->getId(),
                        $context->getLocation()->getName()
                    );
                }])
            ->add('category', TextColumn::class, [
                'label' => 'Category',
                'render' => function ($value, Item $context) {
                    if (empty($context->getCategory())) {
                        return sprintf(
                            '<a class="text-secondary"> %s</a>'
                            ,
                            "empty category"
                        );
                    }
                    return sprintf(
//                        '<a href="../../show/category/%s"> %s</a>'
                        '<a> %s</a>'
                        ,
//                        $context->getCategory()->getId(),
                        $context->getCategory()->getName()
                    );
                }])
            ->add('ancestors', TextColumn::class, [
                'label' => 'Path',
                'render' => function ($value, Item $context) {
                    $links = array_reverse(
                        array_map(
                            function (Item $ancestor) {
                                return sprintf(
                                    '<a class="text-info" style="text-decoration: none" href="../../show/item/%s"> %s</a>'
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
                'format' => 'd/m/Y',
                'label' => "Timestamp",
                'globalSearchable' => false

            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function ($value, $context) use ($deleteItemForm, $addItemToLocationForm) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/addItemToLocation.html.twig', ['id' => $value]);

                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_item', ['id' => $value])]);

                    if (in_array('ROLE_ADMIN', $this->getUser()->getRoles()) ) {
                        $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_item', ['id' => $value])]);
                        $data .= $this->renderView('layout/table/action/delete.html.twig', ['confirm' => true, 'id' => $value, 'form' => $deleteItemForm->createView()]); // TODO
                    }

                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Item::class,
                'criteria' => [
                    function (QueryBuilder $builder) use ($usersLocations) {
                        $name = $_GET['filter_left']['name'] ?? null;
                        $category = $_GET['filter_left']['category'] ?? null;
                        $startDateTime = $_GET['filter_left']['startDateTime'] ?? null;
                        $endDateTime = $_GET['filter_left']['endDateTime'] ?? null;

                        $usersLocationsIds = array_map(function (Location $location) {
                            return $location->getId();
                        }, $usersLocations);

                        $isAdmin = in_array('ROLE_ADMIN', $this->getUser()->getRoles());

                        if (!empty($name)) {
                            $builder
                                ->andWhere('item.name LIKE :name') // TODO ???
                                ->setParameter(
                                    'name',
                                    "%" . $name . "%"
                                );
                        }

                        if (!empty($category)) {
                            $builder
                                ->andWhere('item.category = :category') // TODO ???
                                ->setParameter(
                                    'category',
                                    $category
                                );
                        }

                        if (!empty($startDateTime)) {
                            $startDateTimeFormatted = DateTimeImmutable::createFromFormat('d/m/y', $startDateTime);
                            $builder
                                ->andWhere('item.date_create >= :startDateTime')
                                ->setParameter(
                                    'startDateTime',
                                    $startDateTimeFormatted->format('Y-m-d')
                                );
                        }

                        if (!empty($endDateTime)) {
                            $endDateTimeFormatted = DateTimeImmutable::createFromFormat('d/m/y', $endDateTime);
                            $builder
                                ->andWhere('item.date_create <= :endDateTime')
                                ->setParameter(
                                    'endDateTime',
                                    $endDateTimeFormatted->format('Y-m-d')
                                );
                        }

                        $builder->andWhere('item.location IS NULL');

//                        if (!$isAdmin) {
//                                $builder->andWhere($builder->expr()->in('item.location', $usersLocationsIds));
//                        }

                    },
                    new SearchCriteriaProvider()
                ]
            ])
            ->handleRequest($request);

        if ($leftTable->isCallback()) {
            return $leftTable->getResponse();
        }



    //// RIGHT TABLE



        $rightTable = $dataTableFactory->create()
            ->setName('RightTable')
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
                'render' => function ($value, Item $context) {
                    if (empty($context->getLocation())) {
                        return sprintf(
                            '<a class="text-secondary" style="pointer-events: none;"> %s</a>'
                            ,
                            "empty location"
                        );
                    }
                    return sprintf(
//                        '<a href="../../show/location/%s"> %s</a>'
                        '<a> %s</a>'
                        ,
//                        $context->getLocation()->getId(),
                        $context->getLocation()->getName()
                    );
                }])
            ->add('category', TextColumn::class, [
                'label' => 'Category',
                'render' => function ($value, Item $context) {
                    if (empty($context->getCategory())) {
                        return sprintf(
                            '<a class="text-secondary"> %s</a>'
                            ,
                            "empty category"
                        );
                    }
                    return sprintf(
//                        '<a href="../../show/category/%s"> %s</a>'
                        '<a> %s</a>'
                        ,
//                        $context->getCategory()->getId(),
                        $context->getCategory()->getName()
                    );
                }])
            ->add('ancestors', TextColumn::class, [
                'label' => 'Path',
                'render' => function ($value, Item $context) {
                    $links = array_reverse(
                        array_map(
                            function (Item $ancestor) {
                                return sprintf(
                                    '<a class="text-info" style="text-decoration: none" href="../../show/item/%s"> %s</a>'
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
                'format' => 'd/m/Y',
                'label' => "Timestamp",
                'globalSearchable' => false

            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function ($value, $context) use ($deleteItemForm) {
                    $data = '<div class="text-center">';

                    $data .= $this->renderView('layout/table/action/removeItemFromLocation.html.twig', ['id' => $value]);
                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_item', ['id' => $value])]);

                    if (in_array('ROLE_ADMIN', $this->getUser()->getRoles()) ) {
                        $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_item', ['id' => $value])]);
                        $data .= $this->renderView('layout/table/action/delete.html.twig', ['confirm' => true, 'id' => $value, 'form' => $deleteItemForm->createView()]); // TODO
                    }

                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Item::class,
                'criteria' => [
                    function (QueryBuilder $builder) use ($usersLocations) {
                        $name = $_GET['filter_right']['name'] ?? null;
                        $category = $_GET['filter_right']['category'] ?? null;
                        $location = $_GET['filter_right']['location'] ?? null;
                        $startDateTime = $_GET['filter_right']['startDateTime'] ?? null;
                        $endDateTime = $_GET['filter_right']['endDateTime'] ?? null;

                        $usersLocationsIds = array_map(function (Location $location) {
                            return $location->getId();
                        }, $usersLocations);

                        $isAdmin = in_array('ROLE_ADMIN', $this->getUser()->getRoles());

                        if (!empty($name)) {
                            $builder
                                ->andWhere('item.name LIKE :name') // TODO ???
                                ->setParameter(
                                    'name',
                                    "%" . $name . "%"
                                );
                        }

                        if (!empty($category)) {
                            $builder
                                ->andWhere('item.category = :category') // TODO ???
                                ->setParameter(
                                    'category',
                                    $category
                                );
                        }

                        if (!empty($location)) {
                            $builder->andWhere('item.location = :locationId')
                                ->setParameter('locationId', $location);
                        }

                        if (!empty($startDateTime)) {
                            $startDateTimeFormatted = DateTimeImmutable::createFromFormat('d/m/y', $startDateTime);
                            $builder
                                ->andWhere('item.date_create >= :startDateTime')
                                ->setParameter(
                                    'startDateTime',
                                    $startDateTimeFormatted->format('Y-m-d')
                                );
                        }

                        if (!empty($endDateTime)) {
                            $endDateTimeFormatted = DateTimeImmutable::createFromFormat('d/m/y', $endDateTime);
                            $builder
                                ->andWhere('item.date_create <= :endDateTime')
                                ->setParameter(
                                    'endDateTime',
                                    $endDateTimeFormatted->format('Y-m-d')
                                );
                        }

                        $builder->andWhere('item.location IS NOT NULL');

                        if (!$isAdmin) {
                            if (!empty($usersLocationsIds)) {
                                $builder->andWhere($builder->expr()->in('item.location', $usersLocationsIds));
                            } else {
                                $builder->andWhere('1 = 2');
                            }
                        }

                    },
                    new SearchCriteriaProvider()
                ]
            ])
            ->handleRequest($request);

        if ($rightTable->isCallback()) {
            return $rightTable->getResponse();
        }

        return $this->render('page/item/items.html.twig', [
            'datatable_left' => $leftTable,
            'datatable_right' => $rightTable,
            'filterForm_right' => $filterFormRightTable->createView(),
            'filterForm_left' => $filterFormLeftTable->createView(),
            'addItemToLocationForm' => $addItemToLocationForm->createView(),
            'removeItemFromLocationForm' => $removeItemFromLocationForm->createView(),
        ]);
    }

    /**
     * @Route("/add/item", name="add_item")
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {
        $item = new Item();
        $addItemForm = $this->createForm(AddItem::class, $item);
        $addItemForm->handleRequest($request);

        if ($addItemForm->isSubmitted() && $addItemForm->isValid()) {
//            $item->setName($addItemForm->get('name')->getData());

            if (!empty($addItemForm->get('parent')->getData())) {
                $item->setLocation($addItemForm->get('parent')->getData()->getLocation());
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();
            return $this->redirect($request->getUri());
        }

        return $this->render('page/item/add.html.twig', [
            'addItemForm' => $addItemForm->createView()
        ]);
    }

    /**
     * @Route("/show/item/{id}", name="show_item", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function show(Request $request, int $id): Response
    {
        $itemRepository = $this->getDoctrine()
            ->getRepository(Item::class);
        $item = $itemRepository->find($id);

        return $this->render('page/item/show.html.twig', [
            'item' => $item,
        ]);
    }

    /**
     * @Route("/edit/item/{id}", name="edit_item", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        $itemRepository = $this->getDoctrine()
            ->getRepository(Item::class);
        $editItem = $itemRepository->find($id);

        $editItemForm = $this->createForm(EditItem::class, $editItem, array(
            'method' => 'PUT',
            'user' => $this->getUser(),
            'csrf_protection' => false
        ));


        $editItemForm->handleRequest($request);

        if ($editItemForm->isSubmitted() && $editItemForm->isValid()) {
            $itemRepository = $this->getDoctrine()
                ->getRepository(Item::class);

            $editItem = $itemRepository->find($id);

            if (!empty($editItem)) {
                if (!empty($editItemForm->get('name')->getData())) {
                    $editItem->setName($editItemForm->get('name')->getData());
                }

                $editItem->setParent($editItemForm->get('parent')->getData());

                $locationToSet = $editItem->getLocation();
                if (!empty($editItemForm->get('parent')->getData())) {
                    $locationToSet = $editItemForm->get('parent')->getData()->getLocation();
                }

                $this->editChildren($editItem, $locationToSet);

                return $this->redirect($request->getUri());
            }
        }

        return $this->render('page/item/edit.html.twig', [
            'editItemForm' => $editItemForm->createView(),
            'editItem' => $editItem
        ]);
    }

    private function editChildren(Item $item, Location $location = null)
    {
        $item->setLocation($location);

        foreach ($item->getChildren() as $child) {
            $this->editChildren($child, $location);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($item);
        $entityManager->flush();
    }

    private function deleteItem(Item $item)
    {
        foreach ($item->getChildren() as $child) {
            $this->deleteItem($child);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($item);
        $entityManager->flush();
    }
}
