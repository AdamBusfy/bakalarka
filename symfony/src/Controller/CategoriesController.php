<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\AddCategory;
use App\Form\DeleteForm;
use App\Form\EditCategory;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
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
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator, DataTableFactory $dataTableFactory): Response
    {
        $addCategory = new Category();
        $addCategoryForm = $this->createForm(AddCategory::class, $addCategory);
        $addCategoryForm->handleRequest($request);

        if ($addCategoryForm->isSubmitted() && $addCategoryForm->isValid()) {
//            $category->setName($addCategoryForm->get('name')->getData());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($addCategory);
            $entityManager->flush();
            return $this->redirect($request->getUri());
        }

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
        }

        //EDIT

        $editCategoryForm = $this->createForm(EditCategory::class);
        $editCategoryForm->handleRequest($request);

        if ($editCategoryForm->isSubmitted() && $editCategoryForm->isValid()) {
            $categoryRepository = $this->getDoctrine()
                ->getRepository(Category::class);

            $editCategory = $categoryRepository->find($editCategoryForm->get('id')->getData());

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

        $table = $dataTableFactory->create()
            ->add('id', TextColumn::class, [
                'propertyPath' => 'id',
                'label' => 'ID',
                'globalSearchable' => false
            ])
            ->add('name', TextColumn::class, [
                'propertyPath' => 'name',
                'label' => 'Name'
            ])
            ->add('ancestors', TextColumn::class, [
                'label' => 'Path'
            ])
            ->add('actions', TextColumn::class, [
                'label' => 'Actions',
                'propertyPath' => 'id',
                'render' => function($value, $context) use ($deleteCategoryForm) {
                    $data = '<div class="text-center">';
                    $data .= $this->renderView('layout/table/action/show.html.twig', ['id' => $value]);
                    $data .= $this->renderView('layout/table/action/edit.html.twig', ['id' => $value]);
                    $data .= $this->renderView('layout/table/action/delete.html.twig', ['id' => $value, 'form' => $deleteCategoryForm->createView()]);
                    $data .= "</div>";

                    return $data;
                }
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Category::class
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('page/categories.html.twig', [
            'datatable' => $table
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
