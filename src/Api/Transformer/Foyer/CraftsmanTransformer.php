<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Api\Transformer\Foyer;

use App\Api\External\Transformer\Base\BatchTransformer;
use App\Entity\Craftsman;

class CraftsmanTransformer extends BatchTransformer
{
    /**
     * @var \App\Api\Transformer\Base\CraftsmanTransformer
     */
    private $craftsmanTransformer;

    /**
     * CraftsmanTransformer constructor.
     *
     * @param \App\Api\Transformer\Base\CraftsmanTransformer $craftsmanTransformer
     */
    public function __construct(\App\Api\Transformer\Base\CraftsmanTransformer $craftsmanTransformer)
    {
        $this->craftsmanTransformer = $craftsmanTransformer;
    }

    public function writeApiProperties(Craftsman $entity, \App\Api\Entity\Foyer\Craftsman $craftsman)
    {
        $this->craftsmanTransformer->writeApiProperties($entity, $craftsman);
    }

    /**
     * @param Craftsman $entity
     * @param null $args
     *
     * @return \App\Api\Entity\Foyer\Craftsman
     */
    public function toApi($entity, $args = null)
    {
        $craftsman = new \App\Api\Entity\Foyer\Craftsman($entity->getId());
        $this->writeApiProperties($entity, $craftsman);

        return $craftsman;
    }
}
