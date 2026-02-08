<?php

namespace App\Repository;

use App\Entity\SujetsForum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SujetsForum>
 */
class SujetsForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SujetsForum::class);
    }

    /**
     * Recherche, filtre et tri des sujets.
     *
     * @param string|null $search   Recherche dans titre, créé par, catégorie
     * @param string|null $categorie Filtrer par catégorie (exact)
     * @param string      $sort     Champ de tri: titre, date_creation, nb_messages
     * @param string      $order    Ordre: asc ou desc
     * @return SujetsForum[]
     */
    public function searchFilterSort(?string $search, ?string $categorie, string $sort = 'date_creation', string $order = 'desc'): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.messagesForum', 'm')
            ->addSelect('s')
            ->groupBy('s.id');

        if ($search !== null && trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $qb->andWhere('s.titre LIKE :search OR s.cree_par LIKE :search OR s.categorie LIKE :search')
                ->setParameter('search', $term);
        }
        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('s.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }

        $allowedSort = ['titre', 'date_creation', 'cree_par', 'categorie'];
        if ($sort === 'nb_messages') {
            $qb->addOrderBy('COUNT(m.id)', strtoupper($order) === 'ASC' ? 'ASC' : 'DESC');
        } elseif (\in_array($sort, $allowedSort, true)) {
            $qb->addOrderBy('s.' . $sort, strtoupper($order) === 'ASC' ? 'ASC' : 'DESC');
        } else {
            $qb->addOrderBy('s.date_creation', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Liste des catégories distinctes.
     * @return string[]
     */
    public function getDistinctCategories(): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('DISTINCT s.categorie')
            ->orderBy('s.categorie', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
        return array_values(array_filter($result));
    }
}
