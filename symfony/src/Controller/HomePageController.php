<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Item;
use App\Entity\Location;
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
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function index(Request $request) : Response
    {
        $em = $this->getDoctrine()->getManager();

        $repoItems = $em->getRepository(Item::class);
        $repoLocations = $em->getRepository(Location::class);
        $repoCategories = $em->getRepository(Category::class);
        $repoUsers = $em->getRepository(User::class);

        $assignedItems = $repoItems->createQueryBuilder('i')
            ->where('i.state = 1')
            ->andWhere('i.location IS NOT NULL')
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $unassignedItems = $repoItems->createQueryBuilder('i')
            ->where('i.state = 1')
            ->andWhere('i.location IS NULL')
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $deletedItems = $repoItems->createQueryBuilder('i')
            ->where('i.state = 0')
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $discardedItems = $repoItems->createQueryBuilder('i')
            ->where('i.state = 2')
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalItems = $repoItems->createQueryBuilder('i')
            ->select('count(i.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalLocations = $repoLocations->createQueryBuilder('l')
            ->where('l.isActive = 1')
            ->select('count(l.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalCategories = $repoCategories->createQueryBuilder('c')
            ->where('c.isActive = 1')
            ->select('count(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalUsers = $repoUsers->createQueryBuilder('u')
            ->where('u.isActive = 1')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();


        return $this->render('page/homepage.html.twig', [
            'assignedItems' => $assignedItems,
            'deletedItems' => $deletedItems,
            'discardedItems' => $discardedItems,
            'unassignedItems' => $unassignedItems,
            'totalItems' => $totalItems,
            'totalLocations' => $totalLocations,
            'totalCategories' => $totalCategories,
            'totalUsers' => $totalUsers
        ]);
    }
}