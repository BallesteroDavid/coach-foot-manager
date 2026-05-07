<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * Retourne les équipes visibles selon l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit toutes les équipes
     *
     * ROLE_ADMIN :
     * - voit les équipes de son club
     *
     * ROLE_COACH :
     * - voit uniquement les équipes qu'il encadre
     *
     * @return Team[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->leftJoin('t.club', 'club')
            ->addSelect('club')
            ->leftJoin('t.category', 'category')
            ->addSelect('category')
            ->leftJoin('t.season', 'season')
            ->addSelect('season')
            ->orderBy('t.name', 'ASC');

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
                ->andWhere('t.club = :club')
                ->setParameter('club', $club)
                ->getQuery()
                ->getResult();
        }

        if (in_array('ROLE_COACH', $roles, true)) {
            $coachedTeams = $user->getCoachedTeams()->toArray();

            if (count($coachedTeams) === 0) {
                return [];
            }

            return $queryBuilder
                ->andWhere('t IN (:teams)')
                ->setParameter('teams', $coachedTeams)
                ->getQuery()
                ->getResult();
        }

        return [];
    }
}