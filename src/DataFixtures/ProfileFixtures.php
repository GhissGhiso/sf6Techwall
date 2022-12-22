<?php

namespace App\DataFixtures;

use App\Entity\Profile;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ProfileFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $profile = new Profile();
        $profile->setRs('Facebook');
        $profile->setUrl('https://www.facebook.com/jzagalo');

        $profile1 = new Profile();
        $profile1->setRs('twitter');
        $profile1->setUrl('https://www.twitter.com/ayemensellaouti');

        $profile2 = new Profile();
        $profile2->setRs('LinkedIn');
        $profile2->setUrl('https://www.linkedIn.com/in/aymen-sellaouti-b0427731');

        $profile3 = new Profile();
        $profile3->setRs('Github');
        $profile3->setUrl('https://github.com/GhissGhiso');

        $manager->persist($profile);
        $manager->persist($profile1);
        $manager->persist($profile2);
        $manager->persist($profile3);

        $manager->flush();
    }
}
