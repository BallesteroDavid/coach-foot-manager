<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Club;
use App\Entity\Team;
use App\Repository\ClubRepository;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AppUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordConstraints = [];

        if (!$options['is_edit']) {
            $passwordConstraints[] = new Assert\NotBlank([
                'message' => 'Le mot de passe est obligatoire.',
            ]);
        }

        $roleChoices = [
            'Coach' => 'ROLE_COACH',
            'Admin' => 'ROLE_ADMIN',
        ];

        $currentUser = $options['current_user'];

        if (
            $currentUser instanceof AppUser
            && in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)
        ) {
            $roleChoices['Super admin'] = 'ROLE_SUPER_ADMIN';
        }

        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
            ])

            ->add('lastname', TextType::class, [
                'label' => 'Nom',
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])

            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => !$options['is_edit'],
                'help' => $options['is_edit']
                    ? 'Laisse vide pour conserver le mot de passe actuel.'
                    : 'Mot de passe du compte utilisateur.',
                'constraints' => $passwordConstraints,
            ])

            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => $roleChoices,
                'multiple' => true,
                'expanded' => true,
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
                'required' => false,
                'label' => 'Club',
            ])

            ->add('coachedTeams', EntityType::class, [
                'class' => Team::class,
                'query_builder' => function (TeamRepository $teamRepository) use ($options) {
                    $queryBuilder = $teamRepository->createQueryBuilder('t')
                        ->leftJoin('t.club', 'club')
                        ->addSelect('club')
                        ->leftJoin('t.category', 'category')
                        ->addSelect('category')
                        ->leftJoin('t.season', 'season')
                        ->addSelect('season')
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

                    return $queryBuilder->andWhere('1 = 0');
                },
                'choice_label' => function (Team $team) {
                    $club = $team->getClub()?->getName() ?? 'Club inconnu';
                    $category = $team->getCategory()?->getName() ?? 'Sans catégorie';
                    $season = $team->getSeason()?->getName() ?? 'Sans saison';

                    return $team->getName() . ' - ' . $club . ' - ' . $category . ' - ' . $season;
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'by_reference' => false,
                'label' => 'Équipes encadrées',
                'help' => "Sélectionne les équipes que cet utilisateur encadre s'il est coach.",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppUser::class,
            'is_edit' => false,
            'current_user' => null,
        ]);
    }
}