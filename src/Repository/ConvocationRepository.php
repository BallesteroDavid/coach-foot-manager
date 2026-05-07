<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\Convocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Convocation>
 */
class ConvocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Convocation::class);
    }

    /**
     * Retourne les convocations visibles selon l'utilisateur connecté.
     *
     * ROLE_SUPER_ADMIN :
     * - voit toutes les convocations
     *
     * ROLE_ADMIN :
     * - voit les convocations des matchs des équipes de son club
     *
     * ROLE_COACH :
     * - voit les convocations des matchs des équipes qu'il encadre
     *
     * @return Convocation[]
     */
    public function findVisibleForUser(AppUser $user): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->leftJoin('c.footballMatch', 'm')
            ->addSelect('m')
            ->leftJoin('m.team', 't')
            ->addSelect('t')
            ->leftJoin('t.club', 'club')
            ->addSelect('club')
            ->leftJoin('c.player', 'p')
            ->addSelect('p')
            ->orderBy('c.createdAt', 'DESC');

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
                ->andWhere('m.team IN (:teams)')
                ->setParameter('teams', $coachedTeams)
                ->getQuery()
                ->getResult();
        }

        return [];
    }
}