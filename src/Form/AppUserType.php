<?php

namespace App\Form;

use App\Entity\AppUser;
use App\Entity\Club;
use App\Entity\Team;
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
                'choices' => [
                    'Coach' => 'ROLE_COACH',
                    'Admin' => 'ROLE_ADMIN',
                    'Super admin' => 'ROLE_SUPER_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])

            ->add('club', EntityType::class, [
                'class' => Club::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir un club',
                'required' => false,
                'label' => 'Club',
            ])

            ->add('coachedTeams', EntityType::class, [
                'class' => Team::class,
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
        ]);
    }
}