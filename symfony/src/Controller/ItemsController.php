<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
use App\Form\AddItem;
use App\Form\DeleteForm;
use App\Form\EditItem;
use App\Form\Item\Filter;
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
                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_item', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_item', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['confirm' => true, 'id' => $value, 'form' => $deleteItemForm->createView()]);
                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Item::class,
                'criteria' => [
                    function (QueryBuilder $builder) use ($usersLocations) {
                        $name = $_GET['filter']['name'] ?? null;
                        $category = $_GET['filter']['category'] ?? null;
                        $location = $_GET['filter']['location'] ?? null;
                        $startDateTime = $_GET['filter']['startDateTime'] ?? null;
                        $endDateTime = $_GET['filter']['endDateTime'] ?? null;

                        $usersLocationsIds = array_map(function (Location $location) {
                            return $location->getId();
                        }, $usersLocations);

                        if (!in_array('ROLE_USER', $this->getUser()->getRoles())) {
                            $builder
                                ->andWhere($builder->expr()->in('item.location', $usersLocationsIds));  // TODO ???
                        }

                        if (!empty(array_filter([$name, $category, $location, $startDateTime, $endDateTime]))) {
                            if (!empty($name)) {
                                $builder
                                    ->andWhere('item.name LIKE :name')
                                    ->setParameter(
                                        'name',
                                        "%" . $name . "%"
                                    );
                            }

                            if (!empty($category)) {
                                $builder
                                    ->andWhere('item.category = :category')
                                    ->setParameter(
                                        'category',
                                        $category
                                    );
                            }

                            if (!empty($location)) {
                                $builder
                                    ->andWhere('item.location = :location')
                                    ->setParameter(
                                        'location',
                                        $location
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
                        }
                    },
                    new SearchCriteriaProvider()
                ]
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('page/item/items.html.twig', [
            'datatable' => $table,
            'filterForm' => $filterForm->createView()
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

    private function editChildren(Item $item, Location $location)
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
