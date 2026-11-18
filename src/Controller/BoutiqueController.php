<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\RayonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BoutiqueController extends AbstractController
{
    #[Route('/', name: 'app_accueil', methods: ['GET'])]
    #[Route('/catalogue', name: 'app_catalogue', methods: ['GET'])]
    public function catalogue(Request $request, ArticleRepository $articles, RayonRepository $rayons): Response
    {
        $recherche = mb_substr(trim($request->query->getString('q')), 0, 150);
        $rayonId = $request->query->getInt('rayon');

        return $this->render('boutique/catalogue.html.twig', [
            'articles' => $articles->rechercher($recherche, $rayonId),
            'rayons' => $rayons->findBy([], ['id' => 'ASC']),
            'recherche' => $recherche,
            'rayonId' => $rayonId,
        ]);
    }

    #[Route('/panier', name: 'app_panier', methods: ['GET'])]
    public function panier(ArticleRepository $articles): Response
    {
        return $this->render('boutique/panier.html.twig', [
            'articles' => $articles->rechercher(),
        ]);
    }
}
