<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\DataFixtures\Base\BaseFixture;
use App\Entity\ConstructionSite;
use App\Entity\Craftsman;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Serializer\SerializerInterface;

class LoadCraftsmanData extends BaseFixture
{
    const ORDER = LoadConstructionSiteData::ORDER + 1;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \BadMethodCallException
     */
    public function load(ObjectManager $manager)
    {
        $json = file_get_contents(__DIR__ . '/Resources/craftsmen.json', 'r');
        /** @var Craftsman[] $craftsmen */
        $craftsmen = $this->serializer->deserialize($json, Craftsman::class . '[]', 'json');

        $constructionSite = $manager->getRepository(ConstructionSite::class)->findOneBy([]);
        foreach ($craftsmen as $craftsman) {
            $craftsman->setConstructionSite($constructionSite);
            $craftsman->setEmail($craftsman->getEmail() . '.example.com');
            $manager->persist($craftsman);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return static::ORDER + 1;
    }
}
