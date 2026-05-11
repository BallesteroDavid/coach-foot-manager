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
     * ROLE_ADMIN / ROLE_ADMIN_CLUB :
     * - voit les équipes de son club
     *
     * ROLE_COACH :
     * - voit toutes les équipes de son club en lecture seule
     *
     * @return Team[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createBaseQueryBuilder();

        if ($this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
            return $queryBuilder
                ->getQuery()
                ->getResult();
        }

        if (
            $this->hasRole($user, 'ROLE_ADMIN')
            || $this->hasRole($user, 'ROLE_ADMIN_CLUB')
            || $this->hasRole($user, 'ROLE_COACH')
        ) {
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

        return [];
    }

    /**
     * Retourne les équipes que l'utilisateur peut gérer.
     *
     * ROLE_SUPER_ADMIN :
     * - peut gérer toutes les équipes
     *
     * ROLE_ADMIN / ROLE_ADMIN_CLUB :
     * - peut gérer les équipes de son club
     *
     * ROLE_COACH :
     * - peut gérer uniquement les équipes auxquelles il est affilié
     *
     * @return Team[]
     */
    public function findManageableForUser(AppUser $user): array
    {
        $queryBuilder = $this->createBaseQueryBuilder();

        if ($this->hasRole($user, 'ROLE_SUPER_ADMIN')) {
            return $queryBuilder
                ->getQuery()
                ->getResult();
        }

        if (
            $this->hasRole($user, 'ROLE_ADMIN')
            || $this->hasRole($user, 'ROLE_ADMIN_CLUB')
        ) {
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

        if ($this->hasRole($user, 'ROLE_COACH')) {
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

    private function createBaseQueryBuilder()
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.club', 'club')
            ->addSelect('club')
            ->leftJoin('t.category', 'category')
            ->addSelect('category')
            ->leftJoin('t.season', 'season')
            ->addSelect('season')
            ->leftJoin('t.coaches', 'coach')
            ->addSelect('coach')
            ->orderBy('t.name', 'ASC');
    }

    private function hasRole(AppUser $user, string $role): bool
    {
        return in_array($role, $user->getRoles(), true);
    }
}