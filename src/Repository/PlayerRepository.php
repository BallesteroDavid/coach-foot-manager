<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /**
     * Retourne les joueurs visibles selon l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit tous les joueurs
     *
     * ROLE_ADMIN / ROLE_ADMIN_CLUB :
     * - voit les joueurs de son club
     *
     * ROLE_COACH :
     * - voit tous les joueurs de son club en lecture seule
     *
     * @return Player[]
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
     * Retourne les joueurs que l'utilisateur peut gérer.
     *
     * ROLE_SUPER_ADMIN :
     * - peut gérer tous les joueurs
     *
     * ROLE_ADMIN / ROLE_ADMIN_CLUB :
     * - peut gérer les joueurs de son club
     *
     * ROLE_COACH :
     * - peut gérer uniquement les joueurs des équipes qu'il encadre
     *
     * @return Player[]
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
                ->andWhere('p.team IN (:teams)')
                ->setParameter('teams', $coachedTeams)
                ->getQuery()
                ->getResult();
        }

        return [];
    }

    private function createBaseQueryBuilder()
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.team', 't')
            ->addSelect('t')
            ->leftJoin('t.club', 'c')
            ->addSelect('c')
            ->leftJoin('t.category', 'category')
            ->addSelect('category')
            ->leftJoin('t.season', 'season')
            ->addSelect('season')
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC');
    }

    private function hasRole(AppUser $user, string $role): bool
    {
        return in_array($role, $user->getRoles(), true);
    }
}