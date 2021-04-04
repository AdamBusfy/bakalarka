<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\History;
use App\Entity\Item;
use App\Entity\Location;
use App\Entity\Notification;
use App\Entity\User;
use App\Form\AddItem;
use App\Form\AddItemToLocation;
use App\Form\DeleteForm;
use App\Form\DiscardItem;
use App\Form\EditItem;
use App\Form\Item\FileUpload;
use App\Form\Item\FilterDeletedItems;
use App\Form\Item\FilterLeft;
use App\Form\Item\FilterRight;
use App\Form\RemoveItemFromLocation;
use App\Service\TreeNode\CycleDetector;
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\FetchJoinORMAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ItemsController
 * @package App\Controller
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
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

        $uploadCsvFileForm = $this->createForm(FileUpload::class);
        $uploadCsvFileForm->handleRequest($request);

        if ($uploadCsvFileForm->isSubmitted() && $uploadCsvFileForm->isValid()) {
            $csvFile = $uploadCsvFileForm->get('file')->getData();
            $this->csvImportItems($csvFile);
            $this->notifyUsers(Notification::TYPE_IMPORT, "New items were imported to system !");
            $this->addFlash('success', 'Items successfully imported!');
            $this->getDoctrine()->getManager()->flush();
        }

        $addItemToLocationForm = $this->createForm(AddItemToLocation::class, null, ['csrf_protection' => false, 'user' => $this->getUser()]);
        $addItemToLocationForm->handleRequest($request);

        if ($addItemToLocationForm->isSubmitted() && $addItemToLocationForm->isValid()) {
            $itemRepository = $this->getDoctrine()
                ->getRepository(Item::class);

            /** @var Item $itemAdd */
            $itemAdd = $itemRepository->find($addItemToLocationForm->get('id')->getData());

            if (!empty($itemAdd)) {

                $this->editChildren($itemAdd, $addItemToLocationForm->get('location')->getData());
                $this->addToHistory($itemAdd);

                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Item successfully attached to location!');
                return $this->redirect($request->getUri());
            }
        }

        $discardItemForm = $this->createForm(DiscardItem::class);
        $discardItemForm->handleRequest($request);

        if ($discardItemForm->isSubmitted() && $discardItemForm->isValid()) {
            $itemRepository = $this->getDoctrine()
                ->getRepository(Item::class);

            /** @var Item $itemDiscard */
            $itemDiscard = $itemRepository->find($discardItemForm->get('id')->getData());

            if (!empty($itemDiscard)) {
                $itemDiscard->setDiscardReason($discardItemForm->get('discardReason')->getData());

                $itemDiscard->setLocation(null);
                $itemDiscard->setState(Item::STATE_DISCARDED);
                $this->addToHistory($itemDiscard);

                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Item successfully discard!');
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
                $this->addToHistory($itemRemove);

                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('success', 'Item successfully removed from location!');
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
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Item successfully deleted!');

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
                        '<a> %s</a>'
                        ,
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
                        '<a> %s</a>'
                        ,
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
            ->add('price', NumberColumn::class, [
                'label' => 'Price',
            ])
            ->add('date_create', DateTimeColumn::class, [
                'format' => 'd/m/Y',
                'label' => "Timestamp",
                'globalSearchable' => false
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function ($value, $context) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/addItemToLocation.html.twig', ['id' => $value]);

                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_item', ['id' => $value])]);

                    if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                        $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_item', ['id' => $value])]);
                        $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value]); // TODO
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
                        $builder->andWhere('item.state = 1 ');
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
                        '<a> %s</a>'
                        ,
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
                        '<a> %s</a>'
                        ,
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
            ->add('price', NumberColumn::class, [
                'label' => 'Price',
            ])
            ->add('date_create', DateTimeColumn::class, [
                'format' => 'd/m/Y',
                'label' => "Timestamp",
                'globalSearchable' => false

            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function ($value, $context) {
                    $data = '<div class="text-center">';

                    $data .= $this->renderView('layout/table/action/removeItemFromLocation.html.twig', ['id' => $value]);
                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_item', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/discardItem.html.twig', ['id' => $value]);

                    if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                        $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_item', ['id' => $value])]);
                        $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value]); // TODO
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
                        $builder->andWhere('item.state = 1 ');

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
            'discardItemForm' => $discardItemForm->createView(),
            'form' => $deleteItemForm->createView(),
            'uploadCsvFileForm' => $uploadCsvFileForm->createView(),
            'selectApiUrlLocations' => $this->generateUrl('api_select_locations'),
            'selectApiUrlCategories' => $this->generateUrl('api_select_categories'),
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

            $cycleDetector = new CycleDetector();

            if ($cycleDetector->containsCycle($item)) {
                $this->addFlash('danger', 'Item not added due to hierarchy cycle');
                return $this->redirectToRoute('items');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);

            $this->addToHistory($item);

//            $entityManager->persist($history);

            $this->addFlash('success', 'Item successfully added!');

            $this->notifyUsers(Notification::TYPE_DEFAULT, "New items in system !");

            $entityManager->flush();
            return $this->redirectToRoute('items');
        }

        return $this->render('page/item/add.html.twig', [
            'addItemForm' => $addItemForm->createView(),
            'selectApiUrlLocations' => $this->generateUrl('api_select_locations'),
            'selectApiUrlCategories' => $this->generateUrl('api_select_categories'),
            'selectApiUrlItems' => $this->generateUrl('api_select_items'),
        ]);
    }

    /**
     * @Route("/show/item/{id}", name="show_item", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function show(Request $request, int $id, DataTableFactory $dataTableFactory): Response
    {
        $itemRepository = $this->getDoctrine()
            ->getRepository(Item::class);
        $item = $itemRepository->find($id);

        $tableHistory = $dataTableFactory->create()
            ->add('user', TextColumn::class, [
                'field' => 'user.name',
                'label' => 'User',
                'render' => function ($value, History $context) {
                    return $context->getUser()->getName();
                },
                'orderable' => false,
            ])
            ->add('location', TextColumn::class, [
                'field' => 'location.name',
                'label' => 'Location',
                'render' => function ($value, History $context) {
                    return $context->getLocation() ? $context->getLocation()->getName() : "empty location";
                },
                'orderable' => false,
            ])
            ->add('category', TextColumn::class, [
                'field' => 'category.name',
                'label' => 'Category',
                'render' => function ($value, History $context) {
                    return $context->getCategory()->getName();
                },
                'orderable' => false,
            ])
            ->add('price', NumberColumn::class, [
                'label' => 'Price',
            ])
            ->add('date_create', DateTimeColumn::class, [
                'format' => 'd/m/Y H:i:s',
                'label' => "Timestamp",
                'globalSearchable' => false
            ])
            ->createAdapter(FetchJoinORMAdapter::class, [
                'entity' => History::class,
                'simple_total_query' => false,
                'query' => function (QueryBuilder $builder) use ($id) {
                    $builder
                        ->select('h')
                        ->from(History::class, 'h')
                        ->join('h.user', 'u')
                        ->join('h.category', 'c')
                        ->leftJoin('h.location', 'l')
                        ->where('h.item = :iid')
                        ->setParameter('iid', $id);
                }])
            ->handleRequest($request);

        if ($tableHistory->isCallback()) {
            return $tableHistory->getResponse();
        }

        $historyRepository = $this->getDoctrine()
            ->getRepository(History::class);

        $historyPrices = $historyRepository->createQueryBuilder('h')
            ->join('h.item', 'i')
            ->where('i = :iid')
            ->setParameter('iid', $id)
            ->select('h.price')
            ->getQuery()
            ->getResult();

        $historyPrices = array_map(function (array $price) {
            return $price['price'];
        }, $historyPrices);

        return $this->render('page/item/show.html.twig', [
            'item' => $item,
            'dataTableHistory' => $tableHistory,
            'historyPrices' => $historyPrices,
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
        $oldEditItemPrice = $itemRepository->find($id)->getPrice();
        $oldEditItemLocation = $itemRepository->find($id)->getLocation();
        $oldEditItemCategory = $itemRepository->find($id)->getCategory();

        $editItemForm = $this->createForm(EditItem::class, $editItem, array(
            'method' => 'PUT',
            'user' => $this->getUser(),
            'csrf_protection' => false
        ));

        $editItemForm->handleRequest($request);

        if ($editItemForm->isSubmitted() && $editItemForm->isValid()) {

            if (!empty($editItem)) {

                if ($editItem->getParent() === null) {
                    if ($editItemForm->get('location')->getData() !== $oldEditItemLocation ||
                        $editItemForm->get('category')->getData() !== $oldEditItemCategory ||
                        $editItemForm->get('price')->getData() !== $oldEditItemPrice) {

                        $this->addToHistory($editItem);
                    }
                } else {
                    if ($editItemForm->get('category')->getData() !== $oldEditItemCategory ||
                        $editItemForm->get('price')->getData() !== $oldEditItemPrice) {

                        $this->addToHistory($editItem);
                    }
                }

                if (!empty($editItemForm->get('name')->getData())) {
                    $editItem->setName($editItemForm->get('name')->getData());
                }

                $editItem->setParent($editItemForm->get('parent')->getData());

                $cycleDetector = new CycleDetector();

                if ($cycleDetector->containsCycle($editItem)) {
                    echo "nemozes"; exit;
                }

                $locationToSet = $editItem->getLocation();
                if (!empty($editItemForm->get('parent')->getData())) {
                    $locationToSet = $editItemForm->get('parent')->getData()->getLocation();
                }

                $this->editChildren($editItem, $locationToSet);

                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', 'Item successfully edited!');
                return $this->redirectToRoute('items');
            }
        }

        return $this->render('page/item/edit.html.twig', [
            'editItemForm' => $editItemForm->createView(),
            'editItem' => $editItem,
            'selectApiUrlLocations' => $this->generateUrl('api_select_locations'),
            'selectApiUrlCategories' => $this->generateUrl('api_select_categories'),
            'selectApiUrlItems' => $this->generateUrl('api_select_items'),
        ]);
    }

    /**
     * @Route("/export/items/csv/", name="export_items_csv")
     * @param Request $request
     * @return Response
     */
    public function exportData(Request $request): Response
    {
        $results = $categoryRepository = $this->getDoctrine()
            ->getRepository(Item::class)->findAll();

        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($results) {
                $handle = fopen('php://output', 'r+');
                foreach ($results as $row) {
                    //array list fields you need to export
                    $data = array(
                        $row->getId(),
                        $row->getName(),
                        $row->getCategory()->getName(),
                        $row->getPrice(),
                        $row->getDateCreate()->format('m/d/Y'),
                    );
                    fputcsv($handle, $data);
                }
                fclose($handle);
            }
        );
        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');

        return $response;
    }

    /**
     * @Route("/show/deletedItems/", name="show_deleted_items")
     * @param Request $request
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function showDeletedItems(Request $request, DataTableFactory $dataTableFactory): Response
    {

        $deleteItemForm = $this->createForm(DeleteForm::class);
        $deleteItemForm->handleRequest($request);

        if ($deleteItemForm->isSubmitted() && $deleteItemForm->isValid()) {
            $itemRepository = $this->getDoctrine()
                ->getRepository(Item::class);

            $deleteItem = $itemRepository->find($deleteItemForm->get('id')->getData());

            if (!empty($deleteItem)) {
                $this->deleteItem($deleteItem);
            }
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Item successfully deleted!');

        }

        $filterFormDeletedItemsTable = $this->createForm(FilterDeletedItems::class, null, ['csrf_protection' => false]);
        $filterFormDeletedItemsTable->handleRequest($request);

        $tableDeletedItems = $dataTableFactory->create()
            ->setName('DeletedItems')
            ->add('id', TextColumn::class, [
                'propertyPath' => 'id',
                'label' => 'ID',
                'globalSearchable' => false
            ])
            ->add('name', TextColumn::class, [
                'label' => 'Name',
            ])
            ->add('date_create', DateTimeColumn::class, [
                'format' => 'd/m/Y',
                'label' => "Timestamp",
                'globalSearchable' => false
            ])
            ->add('state', TextColumn::class, [
                'field' => 'item.state',
                'label' => 'State',
                'render' => function ($value, Item $context) {
                    return $context->getState() === Item::STATE_ACTIVE ? 'Active' : 'Discarded';
                },
                'orderable' => false,
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function ($value, $context) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_item', ['id' => $value])]);
                    if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                        $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value]);
                    }
                    $data .= "</div>";
                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Item::class,
                'criteria' => [
                    function (QueryBuilder $builder)  {
                        $name = $_GET['filter_deleted_items']['name'] ?? null;
                        $startDateTime = $_GET['filter_deleted_items']['startDateTime'] ?? null;
                        $endDateTime = $_GET['filter_deleted_items']['endDateTime'] ?? null;

                        if (!empty($name)) {
                            $builder
                                ->andWhere('item.name LIKE :name') // TODO ???
                                ->setParameter(
                                    'name',
                                    "%" . $name . "%"
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

                        $builder->andWhere('item.state = 2 ');

                    },
                    new SearchCriteriaProvider()
                ]
            ])
            ->handleRequest($request);

        if ($tableDeletedItems->isCallback()) {
            return $tableDeletedItems->getResponse();
        }

        return $this->render('page/item/deletedItems.html.twig', [
            'form' => $deleteItemForm->createView(),
            'dataTableDeletedItems' => $tableDeletedItems,
            'filterDeletedItems' => $filterFormDeletedItemsTable->createView()
        ]);
    }


    private function editChildren(Item $item, Location $location = null)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $item->setLocation($location);

        foreach ($item->getChildren() as $child) {
            if ($child->getLocation() !== $location) {
                $this->editChildren($child, $location);

                $this->addToHistory($child);
            }
        }
        $entityManager->persist($item);
    }

    private function deleteItem(Item $item)
    {
        foreach ($item->getChildren() as $child) {
            $this->deleteItem($child);
        }

        $item->setState(Item::STATE_INACTIVE);

        $this->addToHistory($item);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($item);
//        $entityManager->flush();
    }

    private function addToHistory(Item $item)
    {
        $history = new History();
        $history->setItem($item);
        $history->setCategory($item->getCategory());
        $history->setLocation($item->getLocation());
        $history->setUser($this->getUser());
        $history->setPrice($item->getPrice());

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($history);
    }

    private function csvImportItems(UploadedFile $csvFile)
    {
        $fp = fopen($csvFile, "r");

        $data = array();

        fgetcsv($fp, 0, "|");
        ini_set("auto_detect_line_endings", true);
        while ($line = fgetcsv($fp, 0, "|")){
            $data[] = $line;
        }

        $categoryRepository = $this->getDoctrine()
            ->getRepository(Category::class);
        $locationRepository = $this->getDoctrine()
            ->getRepository(Location::class);

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($data as $row) {

            if (empty($row[0]) || empty($row[1])) {
                continue;
            }
            $importItem = new Item();
            $importItem->setCategory($categoryRepository->find((int)$row[0]));
            $importItem->setName($row[1]);

            if (!empty($row[2])) {
                $importItem->setLocation($locationRepository->find((int)$row[2]));
            }
            $importItem->setPrice((float)$row[3]);
            $entityManager->persist($importItem);
            $this->addToHistory($importItem);
        }
//        $entityManager->flush();
        fclose($fp);
    }

    private function notifyUsers(int $type, string $message) {
        $entityManager = $this->getDoctrine()->getManager();

        $users = $this->getDoctrine()
            ->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $notification = new Notification();
            $notification->setType($type);
            $notification->setUser($user);
            $notification->setMessage($message);
            $entityManager->persist($notification);
        }
    }
}
