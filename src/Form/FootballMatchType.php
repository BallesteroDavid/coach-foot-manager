<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\FootballMatch;
use App\Entity\Team;
use App\Repository\FootballMatchRepository;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FootballMatchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['is_return_creation']) {
            $builder->add('team', EntityType::class, [
                'class' => Team::class,
                'query_builder' => function (TeamRepository $teamRepository) use ($options) {
                    $queryBuilder = $teamRepository->createQueryBuilder('t')
                        ->leftJoin('t.club', 'c')
                        ->addSelect('c')
                        ->leftJoin('t.category', 'cat')
                        ->addSelect('cat')
                        ->leftJoin('t.season', 's')
                        ->addSelect('s')
                        ->orderBy('t.name', 'ASC');

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
                            ->andWhere('t IN (:teams)')
                            ->setParameter('teams', $coachedTeams);
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => function (Team $team) {
                    $category = $team->getCategory()?->getName() ?? 'Sans catégorie';
                    $season = $team->getSeason()?->getName() ?? 'Sans saison';

                    return $team->getName() . ' - ' . $category . ' - ' . $season;
                },
                'placeholder' => 'Choisir une équipe',
                'label' => 'Équipe',
            ]);
        }

        $builder
            ->add('matchDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date du match',
            ])

            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure du match',
            ])

            ->add('location', TextType::class, [
                'label' => 'Lieu',
            ])

            ->add('locationType', ChoiceType::class, [
                'label' => 'Type de lieu',
                'choices' => [
                    'Domicile' => 'home',
                    'Extérieur' => 'away',
                    'Terrain neutre' => 'neutral',
                ],
            ])

            ->add('opponent', TextType::class, [
                'label' => 'Adversaire',
            ])

            ->add('competition', TextType::class, [
                'label' => 'Compétition',
                'required' => false,
            ])

            ->add('homeScore', IntegerType::class, [
                'label' => 'Score domicile',
                'required' => false,
            ])

            ->add('awayScore', IntegerType::class, [
                'label' => 'Score extérieur',
                'required' => false,
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Planifié' => 'planned',
                    'En cours' => 'in_progress',
                    'Terminé' => 'finished',
                    'Annulé' => 'cancelled',
                ],
            ])
        ;

        if (!$options['is_return_creation']) {
            $builder
                ->add('matchType', ChoiceType::class, [
                    'label' => 'Type de match',
                    'choices' => [
                        'Match simple' => 'simple',
                        'Match aller' => 'aller',
                        'Match retour' => 'retour',
                    ],
                ])

                ->add('firstMatch', EntityType::class, [
                    'class' => FootballMatch::class,
                    'query_builder' => function (FootballMatchRepository $footballMatchRepository) use ($options) {
                        $queryBuilder = $footballMatchRepository->createQueryBuilder('f')
                            ->leftJoin('f.team', 't')
                            ->addSelect('t')
                            ->leftJoin('t.club', 'c')
                            ->addSelect('c')
                            ->andWhere('f.matchType = :matchType')
                            ->setParameter('matchType', 'aller')
                            ->orderBy('f.matchDate', 'DESC')
                            ->addOrderBy('f.startTime', 'DESC');

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
                                ->andWhere('f.team IN (:teams)')
                                ->setParameter('teams', $coachedTeams);
                        }

                        return $queryBuilder->andWhere('1 = 0');
                    },
                    'choice_label' => function (FootballMatch $footballMatch) {
                        $team = $footballMatch->getTeam()?->getName() ?? 'Équipe inconnue';
                        $date = $footballMatch->getMatchDate()?->format('d/m/Y') ?? 'Date inconnue';

                        return $date . ' - ' . $team . ' vs ' . $footballMatch->getOpponent();
                    },
                    'placeholder' => 'Aucun match aller associé',
                    'required' => false,
                    'label' => 'Match aller associé',
                    'help' => 'À renseigner seulement si le type de match est "Match retour".',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FootballMatch::class,
            'is_return_creation' => false,
            'current_user' => null,
        ]);
    }
}