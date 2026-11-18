<?php

namespace App\Command;

use App\Entity\Animalerie;
use App\Entity\Article;
use App\Entity\Catalogue;
use App\Entity\Produit;
use App\Entity\Race;
use App\Entity\Rayon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:catalogue:demo', description: 'Ajoute les articles de démonstration au catalogue MesAnimaux.')]
final class RemplirCatalogueCommand extends Command
{
    private const ANIMAUX = [
        ['Lapin nain', 'Petit lapin au tempérament curieux. Prévoir un espace sécurisé pour ses sorties.', '45.00', 'lapin-nain', 'Lapin', 'Fauve et blanc, poil court'],
        ['Milo — Golden Retriever', 'Chien sociable qui apprécie les promenades et les jeux de rapport.', '260.00', 'milo', 'Chien', 'Doré, poil mi-long'],
        ['Luna — British Shorthair', 'Chat calme qui apprécie les coins douillets et les jeux à son rythme.', '190.00', 'luna', 'Chat', 'Bleu ardoise, poil court et dense'],
        ['Nougat — Cavalier King Charles', 'Petit chien affectueux, à l’aise dans un foyer où il peut partager le quotidien.', '240.00', 'nougat', 'Chien', 'Blenheim, blanc et châtain'],
        ['Plume — Chat européen', 'Chat curieux et vif qui apprécie les perchoirs et les jeux de poursuite.', '170.00', 'plume', 'Chat', 'Tigré gris, poil court'],
        ['Oreo et Pistache — Perruches ondulées', 'Duo de perruches à héberger ensemble dans un espace de vol adapté.', '95.00', 'perruches', 'Oiseau', 'Plumages vert et jaune, bleu et blanc'],
    ];

    private const PRODUITS = [
        'Alimentation' => [
            ['Croquettes pour chien', 'Sac de 2 kg pour chien adulte.', '18.90', 'croquettes-chien'],
            ['Pâtée au saumon pour chat', 'Recette humide au saumon, en barquette de 85 g.', '3.90', 'patee-saumon'],
            ['Foin de fléole pour lapin', 'Foin séché à longues fibres, sachet de 500 g.', '7.50', 'foin-fleole'],
            ['Granulés aux herbes pour lapin', 'Mélange de granulés aux herbes, sachet de 750 g.', '12.90', 'granules-lapin'],
            ['Biscuits au poulet pour chien', 'Petits biscuits croustillants, sachet refermable de 150 g.', '5.90', 'biscuits-chien'],
        ],
        'Loisirs' => [
            ['Jouet en corde', 'Corde nouée pour les jeux de traction.', '8.90', 'jouet-corde'],
            ['Balle Rebond pour chien', 'Balle souple texturée pour les jeux de lancer et de rapport.', '9.90', 'balle-rebond'],
            ['Puzzle en bois pour chien', 'Plateau à cachettes coulissantes pour les jeux de recherche.', '22.00', 'puzzle-bois'],
            ['Canne à plumes pour chat', 'Canne en bois légère pour animer les jeux de poursuite.', '11.90', 'canne-plumes'],
            ['Tunnel en osier pour lapin', 'Petit tunnel tressé pour explorer, se cacher et grignoter.', '18.50', 'tunnel-osier'],
        ],
        'Mode' => [
            ['Harnais Balade', 'Harnais réglable pour les promenades.', '29.90', 'harnais-balade'],
            ['Collier Forêt', 'Collier en sangle tissée avec boucle couleur laiton.', '19.90', 'collier-foret'],
            ['Laisse Traverse', 'Laisse orange de 1,50 m avec mousqueton métallique.', '21.90', 'laisse-traverse'],
            ['Bandana Sauge', 'Bandana en coton à nouer, décliné en trois tailles.', '12.90', 'bandana-sauge'],
            ['Imperméable Averse', 'Manteau déperlant léger à sangles ajustables pour chien.', '39.90', 'impermeable-averse'],
        ],
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->entityManager->wrapInTransaction(function (): void {
            $catalogue = $this->catalogue();
            $rayon = $this->rayon($catalogue, 'Animaux');
            foreach (self::ANIMAUX as [$nom, $description, $prix, $photo, $espece, $pelage]) {
                $race = $this->entityManager->getRepository(Race::class)->findOneBy(['rayon' => $rayon, 'nom' => $nom]) ?? new Race();
                $race->setEspece($espece)->setPelage($pelage);
                $this->enregistrer($race, $rayon, $nom, $description, $prix, $photo);
            }

            foreach (self::PRODUITS as $nomRayon => $produits) {
                $rayon = $this->rayon($catalogue, $nomRayon);
                foreach ($produits as [$nom, $description, $prix, $photo]) {
                    $produit = $this->entityManager->getRepository(Produit::class)->findOneBy(['rayon' => $rayon, 'nom' => $nom]) ?? new Produit();
                    $this->enregistrer($produit, $rayon, $nom, $description, $prix, $photo);
                }
            }
        });

        $output->writeln('<info>Catalogue de démonstration prêt : 6 fiches animales et 15 produits.</info>');

        return self::SUCCESS;
    }

    private function catalogue(): Catalogue
    {
        $animalerie = $this->entityManager->getRepository(Animalerie::class)->findOneBy(['nom' => 'MesAnimaux']);
        if ($animalerie?->getCatalogue() instanceof Catalogue) {
            return $animalerie->getCatalogue();
        }

        $catalogue = new Catalogue();
        $this->entityManager->persist($catalogue);
        $animalerie ??= (new Animalerie())->setNom('MesAnimaux');
        $this->entityManager->persist($animalerie->setCatalogue($catalogue));
        $this->entityManager->flush();

        return $catalogue;
    }

    private function rayon(Catalogue $catalogue, string $nom): Rayon
    {
        $rayon = $this->entityManager->getRepository(Rayon::class)->findOneBy(['catalogue' => $catalogue, 'nom' => $nom]);
        if ($rayon === null) {
            $rayon = (new Rayon())->setNom($nom)->setCatalogue($catalogue);
            $this->entityManager->persist($rayon);
            $this->entityManager->flush();
        }

        return $rayon;
    }

    private function enregistrer(Article $article, Rayon $rayon, string $nom, string $description, string $prix, string $photo): void
    {
        $article->setRayon($rayon)->setNom($nom)->setDescription($description)->setPrix($prix)
            ->setPhoto('images/boutique/'.$photo.'.jpg');
        $this->entityManager->persist($article);
    }
}
