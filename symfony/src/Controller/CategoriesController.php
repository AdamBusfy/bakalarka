<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Item;
use App\Form\AddCategory;
use App\Form\DeleteForm;
use App\Form\EditCategory;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Knp\Component\Pager\PaginatorInterface;

class CategoriesController extends AbstractController
{
    /**
     * @Route("/categories", name="categories")
     * @param Request $request
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function categories(Request $request, DataTableFactory $dataTableFactory): Response
    {
        //DELETE

        $deleteCategoryForm = $this->createForm(DeleteForm::class);
        $deleteCategoryForm->handleRequest($request);

        if ($deleteCategoryForm->isSubmitted() && $deleteCategoryForm->isValid()) {
            $categoryRepository = $this->getDoctrine()
                ->getRepository(Category::class);

            $deleteCategory = $categoryRepository->find($deleteCategoryForm->get('id')->getData());

            if (!empty($deleteCategory)) {
                $this->deleteCategory($deleteCategory);
            }
            return $this->redirect($request->getUri());
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
                'render' => function($value,Category $context) {
                    $links = array_reverse(
                        array_map(
                            function (Category $ancestor) {
                                return sprintf(
//                                    '<a href="../../show/category/%s"> %s</a>'
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
                'render' => function($value, $context) use ($deleteCategoryForm) {
                    $data = '<div class="text-center">';
//                    $data .= $this->renderView('layout/table/action/show.html.twig', ['url' => $this->generateUrl('show_category', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/edit.html.twig', ['url' => $this->generateUrl('edit_category', ['id' => $value])]);
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['confirm' => true, 'id' => $value, 'form' => $deleteCategoryForm->createView()]);
                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Category::class,
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('page/category/categories.html.twig', [
            'datatable' => $table
        ]);
    }


    /**
     * @Route("/add/category", name="add_category")
     * @param Request $request
     * @return Response
     */
    public function add(Request $request): Response
    {

        $addCategory = new Category();
        $addCategoryForm = $this->createForm(AddCategory::class, $addCategory);
        $addCategoryForm->handleRequest($request);

        if ($addCategoryForm->isSubmitted() && $addCategoryForm->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($addCategory);
            $entityManager->flush();
            return $this->redirectToRoute('categories');
        }

        return $this->render('page/category/add.html.twig', [
            'addCategoryForm' => $addCategoryForm->createView(),
        ]);
    }

    /**
     * @Route("/show/category/{id}", name="show_category", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @param DataTableFactory $dataTableFactory
     * @return Response
     */
    public function show(Request $request, int $id, DataTableFactory $dataTableFactory): Response
    {
        $categoryRepository = $this->getDoctrine()
            ->getRepository(Category::class);
        $category = $categoryRepository->find($id);

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
                        ->where('i.category = :cid')
                        ->setParameter('cid', $id)
                    ;
                },
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }




        return $this->render('page/category/show.html.twig', [
//            'controller_name' => 'ShowCategoryController',
            'category' => $category,
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/edit/category/{id}", name="edit_category", requirements={"id"="\d+"})
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        $categoryRepository = $this->getDoctrine()
            ->getRepository(Category::class);
        $editCategory = $categoryRepository->find($id);
        $editCategoryForm = $this->createForm(EditCategory::class, $editCategory, array(
            'method' => 'PUT'
        ));

        $editCategoryForm->handleRequest($request);

        if ($editCategoryForm->isSubmitted() && $editCategoryForm->isValid()) {
            $categoryRepository = $this->getDoctrine()
                ->getRepository(Category::class);

            $editCategory = $categoryRepository->find($id);

            if (!empty($editCategory)) {
                if (!empty($editCategoryForm->get('name')->getData())) {
                    $editCategory->setName($editCategoryForm->get('name')->getData());
                }

                $editCategory->setParent($editCategoryForm->get('parent')->getData());

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->flush();
                return $this->redirect($request->getUri());
            }
        }

        return $this->render('page/category/edit.html.twig', [
            'editCategoryForm' => $editCategoryForm->createView(),
        ]);
    }

    private function deleteCategory(Category $category)
    {
        foreach ($category->getChildren() as $child) {
            $this->deleteCategory($child);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($category);
        $entityManager->flush();
    }
}
