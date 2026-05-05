<?php

namespace App\Form;

use App\Entity\FootballMatch;
use App\Entity\Team;
use App\Repository\FootballMatchRepository;
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
        $builder
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une équipe',
                'label' => 'Équipe',
            ])

            ->add('matchDate', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Date du match',
            ])

            ->add('startTime', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Heure du match',
            ])

            ->add('location', TextType::class, [
                'label' => 'Lieu',
            ])

            ->add('locationType', ChoiceType::class, [
                'label' => 'Type de lieu',
                'placeholder' => 'Choisir le type de lieu',
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
                'help' => 'À renseigner seulement si le match est en cours ou terminé.',
                'attr' => [
                    'min' => 0,
                ],
            ])

            ->add('awayScore', IntegerType::class, [
                'label' => 'Score extérieur',
                'required' => false,
                'help' => 'À renseigner seulement si le match est en cours ou terminé.',
                'attr' => [
                    'min' => 0,
                ],
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

        // Dans le cas d'une création automatique de match retour,
        // on masque ces deux champs car ils sont déjà définis dans le contrôleur :
        // - matchType = retour
        // - firstMatch = match aller courant
        if (!$options['is_return_creation']) {
            $builder
                ->add('matchType', ChoiceType::class, [
                    'label' => 'Type de match',
                    'help' => 'Choisir "Match retour" uniquement si ce match doit être rattaché à un match aller.',
                    'choices' => [
                        'Match simple' => 'simple',
                        'Match aller' => 'aller',
                        'Match retour' => 'retour',
                    ],
                ])

                ->add('firstMatch', EntityType::class, [
                    'class' => FootballMatch::class,
                    'label' => 'Match aller associé',
                    'required' => false,
                    'placeholder' => 'Aucun match aller associé',
                    'help' => 'À renseigner uniquement si le type de match est "Match retour".',

                    // On affiche seulement les matchs de type "aller"
                    // pour éviter de lier un match retour à n'importe quel match.
                    'query_builder' => function (FootballMatchRepository $repository) {
                        return $repository->createQueryBuilder('m')
                            ->andWhere('m.matchType = :matchType')
                            ->setParameter('matchType', 'aller')
                            ->orderBy('m.matchDate', 'DESC');
                    },

                    // Texte affiché dans la liste déroulante.
                    'choice_label' => function (FootballMatch $match) {
                        $date = $match->getMatchDate()?->format('d/m/Y') ?? 'Date inconnue';
                        $team = $match->getTeam()?->getName() ?? 'Équipe inconnue';
                        $opponent = $match->getOpponent() ?? 'Adversaire inconnu';

                        return $date . ' - ' . $team . ' vs ' . $opponent;
                    },
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FootballMatch::class,
            'is_return_creation' => false,
        ]);

        $resolver->setAllowedTypes('is_return_creation', 'bool');
    }
}