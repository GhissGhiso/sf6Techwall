<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin1 = new User();
        $admin1->setEmail('admin@gmail.com');

        $admin2 = new User();
        $admin2->setEmail('admin2@gmail.com');

        // $manager->persist($admin1);

        $manager->flush();
    }
}
