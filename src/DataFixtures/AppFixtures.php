<?php

namespace App\DataFixtures;

use App\Entity\AppUser;
use App\Entity\Club;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Création du club
        $club = new Club();
        $club->setName('Coach Foot Club');
        $club->setCity('Bordeaux');
        $club->setEmail('contact@coach-foot.test');
        $club->setPhone('0600000000');

        $manager->persist($club);

        // Création du premier admin du club
        $admin = new AppUser();
        $admin->setEmail('admin@coach-foot.test');
        $admin->setFirstname('Admin');
        $admin->setLastname('Club');
        $admin->setRoles(['ROLE_ADMIN_CLUB']);
        $admin->setClub($club);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123'
        );

        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        $manager->flush();
    }
}