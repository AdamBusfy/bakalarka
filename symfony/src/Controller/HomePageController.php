<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    /**
     * @Route("/homepage", name="homepage")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request) : Response
    {

        $em = $this->getDoctrine()->getManager();

        $repoItems = $em->getRepository(Item::class);

        $totalItems = $repoItems->createQueryBuilder('i')
             ->where('i.isActive = 1')
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();


        return $this->render('page/homepage.html.twig', [
            'totalItems' => $totalItems,
        ]);
    }
}