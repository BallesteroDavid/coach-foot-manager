<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\FootballMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FootballMatch>
 */
class FootballMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FootballMatch::class);
    }

    /**
     * Retourne les matchs visibles selon l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit tous les matchs
     *
     * ROLE_ADMIN :
     * - voit les matchs des équipes de son club
     *
     * ROLE_COACH :
     * - voit les matchs des équipes qu'il encadre
     *
     * @return FootballMatch[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->leftJoin('f.team', 't')
            ->addSelect('t')
            ->leftJoin('t.club', 'c')
            ->addSelect('c')
            ->orderBy('f.matchDate', 'DESC')
            ->addOrderBy('f.startTime', 'DESC');

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
                ->andWhere('f.team IN (:teams)')
                ->setParameter('teams', $coachedTeams)
                ->getQuery()
                ->getResult();
        }

        return [];
    }
}