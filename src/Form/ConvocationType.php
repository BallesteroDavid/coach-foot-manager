<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Convocation;
use App\Entity\FootballMatch;
use App\Entity\Player;
use App\Repository\FootballMatchRepository;
use App\Repository\PlayerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConvocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['hide_football_match']) {
            $builder->add('footballMatch', EntityType::class, [
                'class' => FootballMatch::class,
                'query_builder' => function (FootballMatchRepository $footballMatchRepository) use ($options) {
                    $queryBuilder = $footballMatchRepository->createQueryBuilder('m')
                        ->leftJoin('m.team', 't')
                        ->addSelect('t')
                        ->leftJoin('t.club', 'club')
                        ->addSelect('club')
                        ->orderBy('m.matchDate', 'DESC')
                        ->addOrderBy('m.startTime', 'DESC');

                    $user = $options['current_user'];

                    if (!$user instanceof AppUser) {
                        return $queryBuilder->andWhere('1 = 0');
                    }

                    $roles = $user->getRoles();

                    if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                        return $queryBuilder;
                    }

                    if (in_array('ROLE_ADMIN', $roles, true)) {
                        if ($user->getClub() === null) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('t.club = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    if (in_array('ROLE_COACH', $roles, true)) {
                        $coachedTeams = $user->getCoachedTeams()->toArray();

                        if (count($coachedTeams) === 0) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('m.team IN (:teams)')
                            ->setParameter('teams', $coachedTeams);
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => function (FootballMatch $match) {
                    $date = $match->getMatchDate()?->format('d/m/Y') ?? 'Date inconnue';
                    $team = $match->getTeam()?->getName() ?? 'Équipe inconnue';
                    $opponent = $match->getOpponent() ?? 'Adversaire inconnu';

                    return $date . ' - ' . $team . ' vs ' . $opponent;
                },
                'label' => 'Match',
                'placeholder' => 'Choisir un match',
            ]);
        }

        $builder
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'query_builder' => function (PlayerRepository $playerRepository) use ($options) {
                    $queryBuilder = $playerRepository->createQueryBuilder('p')
                        ->leftJoin('p.team', 't')
                        ->addSelect('t')
                        ->leftJoin('t.club', 'club')
                        ->addSelect('club')
                        ->orderBy('p.lastName', 'ASC')
                        ->addOrderBy('p.firstName', 'ASC');

                    if (
                        $options['football_match'] instanceof FootballMatch
                        && $options['football_match']->getTeam() !== null
                    ) {
                        return $queryBuilder
                            ->andWhere('p.team = :team')
                            ->setParameter('team', $options['football_match']->getTeam());
                    }

                    $user = $options['current_user'];

                    if (!$user instanceof AppUser) {
                        return $queryBuilder->andWhere('1 = 0');
                    }

                    $roles = $user->getRoles();

                    if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                        return $queryBuilder;
                    }

                    if (in_array('ROLE_ADMIN', $roles, true)) {
                        if ($user->getClub() === null) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('t.club = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    if (in_array('ROLE_COACH', $roles, true)) {
                        $coachedTeams = $user->getCoachedTeams()->toArray();

                        if (count($coachedTeams) === 0) {
                            return $queryBuilder->andWhere('1 = 0');
                        }

                        return $queryBuilder
                            ->andWhere('p.team IN (:teams)')
                            ->setParameter('teams', $coachedTeams);
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => function (Player $player) {
                    $team = $player->getTeam()?->getName() ?? 'Sans équipe';

                    return $player->getFullName() . ' - ' . $team;
                },
                'label' => 'Joueur',
                'placeholder' => 'Choisir un joueur',
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Convoqué' => 'called',
                    'Présent' => 'present',
                    'Absent' => 'absent',
                    'Excusé' => 'excused',
                ],
            ])

            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Convocation::class,
            'hide_football_match' => false,
            'football_match' => null,
            'current_user' => null,
        ]);
    }
}