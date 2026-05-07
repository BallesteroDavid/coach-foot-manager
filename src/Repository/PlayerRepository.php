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
     * Retourne les joueurs visibles selon le rôle de l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit tous les joueurs
     *
     * ROLE_ADMIN :
     * - voit les joueurs des équipes de son club
     *
     * ROLE_COACH :
     * - voit les joueurs des équipes qu'il encadre
     *
     * @return Player[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.team', 't')
            ->addSelect('t')
            ->leftJoin('t.club', 'c')
            ->addSelect('c')
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC');

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
                ->andWhere('p.team IN (:teams)')
                ->setParameter('teams', $coachedTeams)
                ->getQuery()
                ->getResult();
        }

        return [];
    }
}