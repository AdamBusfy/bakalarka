<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{

    public function load(ObjectManager $manager) {

        for ($i = 1; $i <= 500; $i++) {

            $auta = new Category();
            $auta->setName("Kategoria $i");

            $manager->persist($auta);

        }
        $manager->flush();

    }
}
