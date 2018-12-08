<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Entity\Base\BaseEntity;
use App\Entity\Traits\FileTrait;
use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimeTrait;
use App\Model\Frame;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class MapFile extends BaseEntity
{
    use IdTrait;
    use TimeTrait;
    use FileTrait;

    /**
     * @var ConstructionSite
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Map", inversedBy="files")
     */
    private $constructionSite;

    /**
     * @var Map|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Map", inversedBy="files")
     */
    private $map;

    /**
     * @var IssuePosition[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\IssuePosition", mappedBy="mapFile")
     */
    private $issuePositions;

    /**
     * @var MapFileSector[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\MapFileSector", mappedBy="mapFile")
     */
    private $mapFileSectors;

    /**
     * @var Frame|null
     *
     * @ORM\Column(type="json", nullable=true)
     */
    private $sectorFrame;

    public function __construct()
    {
        $this->issuePositions = new ArrayCollection();
        $this->mapFileSectors = new ArrayCollection();
    }

    /**
     * @return IssuePosition[]
     */
    public function getIssuePositions(): array
    {
        return $this->issuePositions;
    }

    /**
     * @return ConstructionSite
     */
    public function getConstructionSite(): ConstructionSite
    {
        return $this->constructionSite;
    }

    /**
     * @param ConstructionSite $constructionSite
     */
    public function setConstructionSite(ConstructionSite $constructionSite): void
    {
        $this->constructionSite = $constructionSite;
    }

    /**
     * @return Map|null
     */
    public function getMap(): ?Map
    {
        return $this->map;
    }

    /**
     * @param Map|null $map
     */
    public function setMap(?Map $map): void
    {
        $this->map = $map;
    }

    /**
     * @return Frame|null
     */
    public function getSectorFrame(): ?Frame
    {
        return $this->sectorFrame;
    }

    /**
     * @param Frame|null $sectorFrame
     */
    public function setSectorFrame(?Frame $sectorFrame): void
    {
        $this->sectorFrame = $sectorFrame;
    }
}
