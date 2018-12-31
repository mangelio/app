<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Api\External\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class IssuePosition
{
    /**
     * @var Point
     *
     * @Assert\NotBlank()
     */
    private $point;

    /**
     * @var float
     *
     * @Assert\NotBlank()
     */
    private $zoomScale;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    private $mapFileID;

    /**
     * @return float
     */
    public function getZoomScale(): float
    {
        return $this->zoomScale;
    }

    /**
     * @param float $zoomScale
     */
    public function setZoomScale(float $zoomScale): void
    {
        $this->zoomScale = $zoomScale;
    }

    /**
     * @return string
     */
    public function getMapFileID(): string
    {
        return $this->mapFileID;
    }

    /**
     * @param string $mapFileID
     */
    public function setMapFileID(string $mapFileID): void
    {
        $this->mapFileID = $mapFileID;
    }

    /**
     * @return Point
     */
    public function getPoint(): Point
    {
        return $this->point;
    }

    /**
     * @param Point $point
     */
    public function setPoint(Point $point): void
    {
        $this->point = $point;
    }
}
