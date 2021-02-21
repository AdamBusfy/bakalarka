<?php

namespace App\Controller;

use App\Entity\Location;
use App\Form\AddLocation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocationsController extends AbstractController
{
    /**
     * @Route("/locations", name="locations")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {

        $location = new Location();
        $addLocationForm = $this->createForm(AddLocation::class, $location);
        $addLocationForm->handleRequest($request);

        if ($addLocationForm->isSubmitted() && $addLocationForm->isValid()) {
            // encode the plain password
            $location->setName($addLocationForm->get('name')->getData());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($location);
            $entityManager->flush();
            return $this->redirect($request->getUri());
        }


        return $this->render('page/locations.html.twig', [
            'createLocationForm' => $addLocationForm->createView()
        ]);
    }
}
