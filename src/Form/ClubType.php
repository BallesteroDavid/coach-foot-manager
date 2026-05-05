<?php

namespace App\Form;

use App\Entity\Club;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClubType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du club',
            ])

            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])

            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])

            ->add('email', EmailType::class, [
                'label' => 'Email du club',
                'required' => false,
            ])

            ->add('phone', TelType::class, [
                'label' => 'Téléphone du club',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Club::class,
        ]);
    }
}