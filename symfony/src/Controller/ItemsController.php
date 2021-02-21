<?php

namespace App\Controller;

use App\Entity\Item;
use App\Form\AddItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ItemsController extends AbstractController
{
    /**
     * @Route("/items", name="items")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {

        $item = new Item();
        $addItemForm = $this->createForm(AddItem::class, $item);
        $addItemForm->handleRequest($request);

        if ($addItemForm->isSubmitted() && $addItemForm->isValid()) {
            // encode the plain password
            $item->setName($addItemForm->get('name')->getData());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();
            return $this->redirect($request->getUri());
        }

        return $this->render('page/items.html.twig', [
            'createItemForm' => $addItemForm->createView()
        ]);
    }
}
