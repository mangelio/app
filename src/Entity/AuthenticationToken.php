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
use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimeTrait;
use App\Helper\HashHelper;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * An Email is a sent email to the specified receivers.
 *
 * @ORM\Entity(repositoryClass="App\Repository\AuthenticationTokenRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AuthenticationToken extends BaseEntity
{
    use IdTrait;
    use TimeTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $token;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $lastUsed;

    /**
     * @var ConstructionManager
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ConstructionManager")
     */
    private $constructionManager;

    /**
     * @param ConstructionManager $constructionManager
     *
     * @throws Exception
     *
     * @return AuthenticationToken
     */
    public static function createFor(ConstructionManager $constructionManager)
    {
        $token = new self();
        $token->constructionManager = $constructionManager;
        $token->token = HashHelper::getHash();
        $token->setLastUsed();

        return $token;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return DateTime
     */
    public function getLastUsed(): DateTime
    {
        return $this->lastUsed;
    }

    /**
     *  refreshes the last used date to the current datetime.
     *
     * @throws Exception
     */
    public function setLastUsed(): void
    {
        $this->lastUsed = new DateTime();
    }

    /**
     * @return ConstructionManager
     */
    public function getConstructionManager(): ConstructionManager
    {
        return $this->constructionManager;
    }
}
