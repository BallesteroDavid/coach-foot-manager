<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Category;
use App\Entity\Club;
use App\Entity\Season;
use App\Entity\Team;
use App\Repository\CategoryRepository;
use App\Repository\ClubRepository;
use App\Repository\SeasonRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => "Nom de l'équipe",
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'help' => 'Description optionnelle de l’équipe.',
            ])

            ->add('club', EntityType::class, [
                'class' => Club::class,
                'query_builder' => function (ClubRepository $clubRepository) use ($options) {
                    $queryBuilder = $clubRepository->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');

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
                            ->andWhere('c = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => 'name',
                'placeholder' => 'Choisir un club',
                'label' => 'Club',
            ])

            ->add('category', EntityType::class, [
                'class' => Category::class,
                'query_builder' => function (CategoryRepository $categoryRepository) use ($options) {
                    $queryBuilder = $categoryRepository->createQueryBuilder('category')
                        ->leftJoin('category.club', 'club')
                        ->addSelect('club')
                        ->orderBy('category.name', 'ASC');

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
                            ->andWhere('category.club = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
                'required' => false,
                'label' => 'Catégorie',
                'help' => 'Optionnel : permet de classer l’équipe par catégorie d’âge.',
            ])

            ->add('season', EntityType::class, [
                'class' => Season::class,
                'query_builder' => function (SeasonRepository $seasonRepository) use ($options) {
                    $queryBuilder = $seasonRepository->createQueryBuilder('season')
                        ->leftJoin('season.club', 'club')
                        ->addSelect('club')
                        ->orderBy('season.startDate', 'DESC');

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
                            ->andWhere('season.club = :club')
                            ->setParameter('club', $user->getClub());
                    }

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => 'name',
                'placeholder' => 'Choisir une saison',
                'required' => false,
                'label' => 'Saison',
                'help' => 'Optionnel pour l’instant : permet de rattacher l’équipe à une saison sportive.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Team::class,
            'current_user' => null,
        ]);
    }
}