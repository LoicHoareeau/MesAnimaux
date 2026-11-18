<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MaquetteController extends AbstractController
{
    private const ECRANS = [
        'accueil' => 'animalia_dition_botanique_solaire',
        'catalogue' => 'boutique_catalogue_produits',
        'animaux' => 'adoption_faune_dition_botanique_solaire',
        'accessoires' => 'fashion_accessoires_dition_botanique_solaire',
        'panier' => 'mon_panier_commande_dition_botanique_solaire',
        'animalerie' => 'animalia_animalerie_noble_engag_e',
        'adoption-1' => 'animaux_de_compagnie_adoption_1',
        'adoption-2' => 'animaux_de_compagnie_adoption_2',
        'boutique-wildlife' => 'boutique_animalia_dition_nature_wildlife',
        'vestiaire' => 'fashion_accessoires',
        'panier-classique' => 'mon_panier_commande',
    ];

    #[Route('/maquette', name: 'app_maquette_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('boutique/maquettes.html.twig', ['ecrans' => self::ECRANS]);
    }

    #[Route('/', name: 'app_accueil', defaults: ['ecran' => 'accueil'], methods: ['GET'])]
    #[Route('/catalogue', name: 'app_catalogue', defaults: ['ecran' => 'catalogue'], methods: ['GET'])]
    #[Route('/boutique-soins', name: 'app_boutique_soins', defaults: ['ecran' => 'catalogue'], methods: ['GET'])]
    #[Route('/animaux', name: 'app_animaux', defaults: ['ecran' => 'animaux'], methods: ['GET'])]
    #[Route('/accessoires', name: 'app_accessoires', defaults: ['ecran' => 'accessoires'], methods: ['GET'])]
    #[Route('/panier', name: 'app_panier', defaults: ['ecran' => 'panier'], methods: ['GET'])]
    #[Route('/maquette/{ecran}', name: 'app_maquette', methods: ['GET'])]
    public function afficher(
        string $ecran,
        Request $request,
        ArticleRepository $articles,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): Response {
        $dossier = self::ECRANS[$ecran] ?? throw $this->createNotFoundException();
        $fichier = $projectDir.'/Maquette/stitch_animalerie_e_commerce_shop/'.$dossier.'/code.html';
        if (!is_readable($fichier)) {
            throw $this->createNotFoundException('Écran de la maquette introuvable.');
        }

        $html = file_get_contents($fichier);
        $html = $this->rendreVitrine($html, $ecran, $request, $articles);
        $catalogue = $this->generateUrl('app_catalogue');
        $animaux = $this->generateUrl('app_animaux');
        $accessoires = $this->generateUrl('app_accessoires');
        $panier = $this->generateUrl('app_panier');
        $accueil = $this->generateUrl('app_accueil');
        $liens = [
            'accueil' => $accueil,
            'boutique' => $catalogue,
            'tous-les-produits' => $catalogue,
            'boutique-&-soins' => $this->generateUrl('app_boutique_soins'),
            'alimentation-et-friandises' => $catalogue.'?rayon=Alimentation',
            'loisirs-et-jouets' => $catalogue.'?rayon=Loisirs',
            'adoption-responsable' => $animaux,
            'animaux-de-compagnie' => $animaux,
            'loisirs-&-accessoires' => $accessoires,
            'fashion-et-accessoires' => $accessoires,
            'panier' => $panier,
            'nos-engagements' => $accueil.'#charte',
        ];
        $html = preg_replace_callback('/data-path="([^"]+)" href="#"/', static function (array $match) use ($liens): string {
            $path = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!isset($liens[$path])) {
                return $match[0].' data-demo-link';
            }

            return 'data-path="'.$match[1].'" href="'.htmlspecialchars($liens[$path], ENT_QUOTES).'" data-site-link';
        }, $html);

        $route = $request->attributes->getString('_route');
        if (in_array($route, ['app_accueil', 'app_catalogue', 'app_boutique_soins', 'app_animaux', 'app_accessoires', 'app_panier'], true)) {
            $menu = $this->renderView('boutique/_menu_vitrine.html.twig', ['route' => $route]);
            $html = preg_replace_callback(
                '/<header\\b.*?<\\/header>/s',
                static fn (array $entete): string => preg_replace_callback(
                    '/<nav\\b([^>]*)>.*?<\\/nav>/s',
                    static fn (array $nav): string => '<nav'.$nav[1].' aria-label="Navigation principale" data-vitrine-menu>'.$menu.'</nav>',
                    $entete[0],
                    1,
                ),
                $html,
                1,
            );
        }


        if ($ecran === 'animaux') {
            $html = str_replace('whitespace-nowrap shrink-0 flex items-center gap-2" href="#"', 'whitespace-nowrap shrink-0 flex items-center gap-2" href="#" data-demo-link', $html);
        }

        // Tous les scripts d’origine lisent le même panier de démonstration.
        $html = str_replace(
            ["'animalia_cart'", "'animalia_cart_state_v1'", "'animalia_cart_items_v1'"],
            "'mesanimaux.maquette.cart.v1'",
            $html,
        );
        $html = str_replace('<html', '<html data-maquette="'.htmlspecialchars($ecran, ENT_QUOTES).'"', $html);
        if (in_array($ecran, ['catalogue', 'boutique-wildlife', 'vestiaire'], true)) {
            $html = str_replace('prod-', $ecran.'-prod-', $html);
        }
        $html = str_replace('cart.push(item);', 'item.quantity = 1; cart.push(item);', $html);
        $html = str_replace('return [...DEFAULT_CART];', 'return [];', $html);
        $html = str_replace('cart = [...DEFAULT_SAMPLE_ITEMS];'.PHP_EOL.'        saveCart();', 'cart = [];', $html);
        $html = str_replace('let promoApplied = true;', 'let promoApplied = false;', $html);
        $html = str_replace('cart = [...DEFAULT_SAMPLE_ITEMS];', 'cart = [];', $html);
        if ($ecran === 'panier-classique') {
            $html = str_replace('cart = JSON.parse(stored);', 'cart = readMaquetteClassicCart(stored);', $html);
            $html = str_replace('src="placeholder" data-alt="${item.imageAlt}"', 'src="${item.image}" alt="${item.imageAlt}"', $html);
            $html = str_replace('Redirection vers la passerelle bancaire sécurisée SSL 256 bits.', 'La commande est simulée dans cette maquette.', $html);
        }
        if ($ecran === 'panier') {
            $html = str_replace('        // Update items DOM visually', '        renderMaquetteRows(itemsListEl, cart);', $html);
        }
        if ($ecran === 'vestiaire') {
            $html = str_replace('existing.qty += 1;', 'existing.quantity = (existing.quantity || 1) + 1; existing.qty = existing.quantity;', $html);
            $html = str_replace('qty: 1,', 'qty: 1, quantity: 1,', $html);
        }

        $script = htmlspecialchars($request->getBasePath().'/maquette-ui.js', ENT_QUOTES);
        $html = str_ireplace('</head>', '<style>#sfToolbarMainContent, .sf-toolbar { display: none !important; }
@media (max-width: 639px) {
    html[data-maquette="animaux"] main a.whitespace-nowrap.shrink-0 { max-width: 100%; white-space: normal; }
    html[data-maquette] header nav[data-active-classes] { justify-content: flex-start; }
    html[data-maquette="animaux"] header .shrink-0,
    html[data-maquette="panier-classique"] header .flex-shrink-0 { gap: .375rem; }
    html[data-maquette="animaux"] header a[data-path="favoris"],
    html[data-maquette="animaux"] header a[data-path="panier"] { padding-inline: .5rem; }
    html[data-maquette="animaux"] header a[data-path="panier"],
    html[data-maquette="panier-classique"] header [aria-label="Mon panier"] { min-height: 44px; }
    html[data-maquette="panier-classique"] #cart-items-list > div,
    html[data-maquette="panier-classique"] #cart-items-list .min-w-0 { max-width: 100%; }
    html[data-maquette="panier-classique"] #cart-items-list h3 { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
}
@media (prefers-reduced-motion: reduce) {
    html[data-maquette] { scroll-behavior: auto !important; }
    html[data-maquette] *, html[data-maquette] *::before, html[data-maquette] *::after {
        scroll-behavior: auto !important;
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
    }
}#vitrine-filtres { scroll-margin-top: 10rem; } html[data-maquette] header nav[data-vitrine-menu] { justify-content: flex-start; gap: .25rem; overflow-x: auto; } html[data-maquette] header nav a[aria-current="page"] { color: #133322; background: #edf0e6; font-weight: 700; border-bottom: 2px solid #9e7200; border-radius: 0; }</style><script src="'.$script.'"></script></head>', $html);

        return new Response($html);
    }

    private function rendreVitrine(string $html, string $ecran, Request $request, ArticleRepository $repository): string
    {
        $route = $request->attributes->getString('_route');
        if (!in_array($route, ['app_accueil', 'app_catalogue', 'app_boutique_soins', 'app_animaux', 'app_accessoires'], true)) {
            return $html;
        }

        $rayons = match ($route) {
            'app_animaux' => ['Animaux'],
            'app_accessoires' => ['Loisirs', 'Mode'],
            default => ['Animaux', 'Alimentation', 'Loisirs', 'Mode'],
        };
        $rayon = $request->query->getString('rayon', $route === 'app_boutique_soins' ? 'Alimentation' : '');
        $rayon = in_array($rayon, $rayons, true) ? $rayon : '';
        $recherche = mb_substr(trim($request->query->getString('q')), 0, 150);
        $articles = array_values(array_filter(
            $repository->rechercher($recherche),
            static fn (Article $article): bool => in_array($article->getRayon()->getNom(), $rayons, true)
                && ($rayon === '' || $article->getRayon()->getNom() === $rayon),
        ));

        $cartes = $this->renderView('boutique/_vitrine.html.twig', ['articles' => $articles, 'ecran' => $ecran, 'recherche' => $recherche]);
        $grille = match ($ecran) {
            'accueil' => '/(<div\\b[^>]*id="products-grid"[^>]*>).*?(?=<\\/div>\\s*<\\/div>\\s*<\\/section>\\s*<!-- 4\\.)/s',
            'catalogue' => '/(<div\\b[^>]*id="productGrid"[^>]*>).*?(?=<\\/div>\\s*<!-- Empty search)/s',
            'animaux' => '/(<!-- Grille 6 cartes -->\\s*<div[^>]*>).*?(?=<\\/div>\\s*<\\/div>\\s*<\\/section>\\s*<!-- Section 4 :)/s',
            'accessoires' => '/(<div\\b[^>]*id="productsGrid"[^>]*>).*?(?=<\\/div>\\s*<\\/section>\\s*<!-- SECTION 4:)/s',
        };
        $html = preg_replace_callback($grille, static fn (array $match): string => $match[1].$cartes, $html, 1);

        if ($ecran === 'accueil') {
            return str_replace(
                ['data-category="soins"', 'Soins &amp; Herboristerie', 'Nutrition pressée à froid', 'Mobilier &amp; Griffoirs scandinaves', 'Accessoires de sortie &amp; Éveil'],
                ['data-category="animaux"', 'Animaux', 'Alimentation', 'Loisirs', 'Mode &amp; Accessoires'],
                $html,
            );
        }

        $filtres = $this->renderView('boutique/_navigation_vitrine.html.twig', [
            'route' => $route,
            'rayons' => $rayons,
            'rayon' => $rayon,
            'recherche' => $recherche,
            'nombre' => count($articles),
        ]);

        if ($ecran === 'catalogue') {
            $html = preg_replace('/<button\\b[^>]*data-specie="aquatique"[^>]*>.*?<\\/button>/s', '', $html);
            $titre = $route === 'app_boutique_soins' ? 'Boutique &amp; Soins' : 'Tous les produits';
            $html = str_replace('Nouveautés &amp; Incontournables', $titre, $html);
            $html = str_replace("(cardSpecie === activeSpecie)", "(cardSpecie === activeSpecie || cardSpecie === 'all')", $html);
            return preg_replace_callback('/<div\\b[^>]*id="categoryFilterBar"[^>]*>.*?<\\/div>/s', static fn (): string => $filtres, $html, 1);
        }

        if ($ecran === 'animaux') {
            $html = preg_replace('/\\s\\((42|18|14|6|4)\\)(?=\\s*<\\/button>)/', '', $html);
            return preg_replace_callback('/<!-- Sélecteurs secondaires -->.*?(?=<\\/section>)/s', static fn (): string => $filtres, $html, 1);
        }

        return preg_replace_callback(
            '/(<section\\b[^>]*id="catalogue-vestiaire"[^>]*>).*?(?=<\\/section>)/s',
            static fn (array $match): string => $match[1].'<div class="max-w-[1440px] mx-auto px-margin lg:px-margin-desktop">'.$filtres.'</div>',
            $html,
            1,
        );
    }
}
