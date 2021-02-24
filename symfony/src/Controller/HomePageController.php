<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    /**
     * @Route("/homepage", name="homepage")
     */
    public function index(Request $request)
    {
        return $this->render('page/homepage.html.twig');
    }
}