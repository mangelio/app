<?php

/*
 * This file is part of the nodika project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\DataFixtures\Base\BaseFixture;
use App\Entity\ConstructionSite;
use App\Entity\ConstructionManager;
use Doctrine\Common\Persistence\ObjectManager;

class LoadBuildingData extends BaseFixture
{
    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \BadMethodCallException
     */
    public function load(ObjectManager $manager)
    {
        $entries = [
            ["Sun Park", "Der Wohnpark in einladender Umgebung", "Parkstrasse", 12, 7270, "Davos"]
        ];

        $appUsers = $manager->getRepository(ConstructionManager::class)->findAll();
        foreach ($entries as $entry) {
            $building = new ConstructionSite();
            $building->setName($entry[0]);
            $building->setDescription($entry[1]);
            $building->setStreetAddress($entry[2]);
            $building->setStreetNr($entry[3]);
            $building->setPostalCode($entry[4]);
            $building->setLocality($entry[5]);
            $building->publish();
            $manager->persist($building);


            foreach ($appUsers as $appUser) {
                $building->getConstructionManagers()->add($appUser);
            }
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return 10;
    }

    /**
     * create an instance with all random values.
     *
     * @return ConstructionSite
     */
    protected function getAllRandomInstance()
    {
        $building = new ConstructionSite();
        $this->fillRandomThing($building);
        $this->fillRandomAddress($building);

        return $building;
    }
}
