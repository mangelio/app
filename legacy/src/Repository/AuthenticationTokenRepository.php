<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Api\External\Request\Base\AuthenticatedRequest;
use App\Entity\AuthenticationToken;
use App\Entity\ConstructionManager;
use App\Helper\HashHelper;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Exception;

class AuthenticationTokenRepository extends EntityRepository
{
    /**
     * get the construction manager assigned to that authentication token
     * returns null if the token is invalid or not found.
     *
     * @throws ORMException
     * @throws Exception
     *
     * @return ConstructionManager|null
     */
    public function getConstructionManager(AuthenticatedRequest $authenticatedRequest)
    {
        if (null === $authenticatedRequest || HashHelper::HASH_LENGTH !== mb_strlen($authenticatedRequest->getAuthenticationToken())) {
            return null;
        }

        /** @var AuthenticationToken $token */
        $token = $this->findOneBy(['token' => $authenticatedRequest->getAuthenticationToken()]);
        if (null !== $token) {
            // remember last used date
            $token->setLastUsed();

            $manager = $this->getEntityManager();
            $manager->persist($token);
            $manager->flush();

            // return user
            return $token->getConstructionManager();
        }

        return null;
    }
}
