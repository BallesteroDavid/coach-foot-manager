<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Retourne les catégories visibles selon l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit toutes les catégories
     *
     * ROLE_ADMIN :
     * - voit les catégories de son club
     *
     * @return Category[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->leftJoin('category.club', 'club')
            ->addSelect('club')
            ->orderBy('category.name', 'ASC');

        $roles = $user->getRoles();

        if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
            return $queryBuilder
                ->getQuery()
                ->getResult();
        }

        if (in_array('ROLE_ADMIN', $roles, true)) {
            $club = $user->getClub();

            if ($club === null) {
                return [];
            }

            return $queryBuilder
                ->andWhere('category.club = :club')
                ->setParameter('club', $club)
                ->getQuery()
                ->getResult();
        }

        return [];
    }
}