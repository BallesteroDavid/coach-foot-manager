<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Club;
use App\Entity\Season;
use App\Repository\ClubRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom de la saison',
                'help' => 'Exemple : 2025/2026.',
            ])

            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Date de début',
            ])

            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Date de fin',
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Planifiée' => 'planned',
                    'Active' => 'active',
                    'Clôturée' => 'closed',
                ],
            ])

            ->add('club', EntityType::class, [
                'class' => Club::class,
                'query_builder' => function (ClubRepository $clubRepository) use ($options) {
                    $queryBuilder = $clubRepository->createQueryBuilder('club')
                        ->orderBy('club.name', 'ASC');

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
                            ->andWhere('club = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => 'name',
                'placeholder' => 'Choisir un club',
                'label' => 'Club',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Season::class,
            'current_user' => null,
        ]);
    }
}