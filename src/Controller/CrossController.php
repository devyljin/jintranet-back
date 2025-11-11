<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CrossController extends AbstractController
{
    #[Route('/cross', name: 'app_cross')]
    public function index(): Response
    {
        return $this->render('cross/index.html.twig', [
            'controller_name' => 'CrossController',
        ]);
    }


}
