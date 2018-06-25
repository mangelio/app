<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Api\Transformer;

use App\Api\Entity\Address;
use App\Api\Entity\Building;
use App\Api\Transformer\Base\BatchTransformer;
use App\Entity\ConstructionSite;

class BuildingTransformer extends BatchTransformer
{
    /**
     * @var ObjectMetaTransformer
     */
    private $objectMetaTransformer;

    public function __construct(ObjectMetaTransformer $objectMetaTransformer)
    {
        $this->objectMetaTransformer = $objectMetaTransformer;
    }

    /**
     * @param ConstructionSite $entity
     *
     * @return Building
     */
    public function toApi($entity)
    {
        $building = new Building();
        $building->setName($entity->getName());
        $building->setImageFilename($entity->getImageFilePath());

        $childrenIds = [];
        foreach ($entity->getMaps() as $child) {
            $childrenIds[] = $child->getId();
        }
        $building->setMaps($childrenIds);

        $childrenIds = [];
        foreach ($entity->getCraftsmen() as $child) {
            $childrenIds[] = $child->getId();
        }
        $building->setCraftsmen($childrenIds);

        $address = new Address();
        $address->setStreetAddress($entity->getStreetAddress());
        $address->setLocality($entity->getLocality());
        $address->setPostalCode($entity->getPostalCode());
        $address->setCountry($entity->getCountry());
        $building->setAddress($address);

        $building->setMeta($this->objectMetaTransformer->toApi($entity));

        return $building;
    }
}
