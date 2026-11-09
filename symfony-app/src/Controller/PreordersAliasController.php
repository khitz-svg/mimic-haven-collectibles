<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PreordersAliasController extends AbstractController
{
    // Keep the plural URL working because several existing page links use it.
    #[Route('/preorders', name: 'app_preorders_alias', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('app_preorder');
    }
}
