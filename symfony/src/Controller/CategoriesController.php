<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\AddCategory;
use App\Form\DeleteForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoriesController extends AbstractController
{
    /**
     * @Route("/categories", name="categories")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $category = new Category();
        $addCategoryForm = $this->createForm(AddCategory::class, $category);
        $addCategoryForm->handleRequest($request);

        if ($addCategoryForm->isSubmitted() && $addCategoryForm->isValid()) {
            // encode the plain password
            $category->setName($addCategoryForm->get('name')->getData());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();
            return $this->redirect($request->getUri());
        }


        $deleteCategoryForm = $this->createForm(DeleteForm::class);
        $deleteCategoryForm->handleRequest($request);

        if ($deleteCategoryForm->isSubmitted() && $deleteCategoryForm->isValid()) {
            $categoryRepository = $this->getDoctrine()
                ->getRepository(Category::class);

            $category = $categoryRepository->find($deleteCategoryForm->get('id')->getData());

            if (!empty($category)) {
                $this->deleteCategory($category);
            }
        }

        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();

        return $this->render('page/categories.html.twig', [
            'categories' => $categories,
            'createCategoryForm' => $addCategoryForm->createView(),
            'deleteCategoryForm' => $deleteCategoryForm

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
