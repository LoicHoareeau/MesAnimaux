<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /** @return list<Article> */
    public function rechercher(string $nom = '', int $rayonId = 0): array
    {
        $query = $this->createQueryBuilder('article')
            ->addSelect('rayon')
            ->join('article.rayon', 'rayon')
            ->orderBy('rayon.id', 'ASC')
            ->addOrderBy('article.nom', 'ASC');

        if ($nom !== '') {
            $query->andWhere("LOWER(article.nom) LIKE :nom ESCAPE '!'")
                ->setParameter('nom', '%'.strtr(mb_strtolower($nom), ['!' => '!!', '%' => '!%', '_' => '!_']).'%');
        }

        if ($rayonId !== 0) {
            $query->andWhere('rayon.id = :rayon')->setParameter('rayon', $rayonId);
        }

        return $query->getQuery()->getResult();
    }
}
