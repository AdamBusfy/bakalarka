<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\AddCategoryForm;
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
        $addCategoryForm = $this->createForm(AddCategoryForm::class, $category);
        $addCategoryForm->handleRequest($request);

        if ($addCategoryForm->isSubmitted() && $addCategoryForm->isValid()) {
            // encode the plain password
            $category->setName($addCategoryForm->get('name')->getData());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($category);
            $entityManager->flush();
            return $this->redirect($request->getUri());
        }

        return $this->render('page/categories.html.twig', [
            'createCategoryForm' => $addCategoryForm->createView()
        ]);
    }
}
