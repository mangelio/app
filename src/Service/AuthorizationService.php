<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\ConstructionManager;
use App\Service\Interfaces\AuthorizationServiceInterface;
use App\Service\Interfaces\PathServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    const AUTHORIZATION_METHOD_NONE = 'none';
    const AUTHORIZATION_METHOD_WHITELIST = 'whitelist';

    /**
     * @var PathServiceInterface
     */
    private $pathService;

    /**
     * @var string[][]|null
     */
    private $userDataCache;

    /**
     * @var string[]|null
     */
    private $emailLookupCache;

    /**
     * @var string
     */
    private $authorizationMethod;

    /**
     * AuthorizationService constructor.
     */
    public function __construct(PathServiceInterface $pathService, string $authorizationMethod)
    {
        $this->pathService = $pathService;
        $this->authorizationMethod = $authorizationMethod;
    }

    /**
     * @throws \Exception
     */
    public function setIsEnabled(ConstructionManager $constructionManager): bool
    {
        if (self::AUTHORIZATION_METHOD_NONE === $this->authorizationMethod) {
            $constructionManager->setIsEnabled(true);

            return true;
        }

        if (self::AUTHORIZATION_METHOD_WHITELIST === $this->authorizationMethod) {
            $onWhitelist = $this->isEmailOnWhitelist($constructionManager->getEmail());
            $otherwiseAuthorized = $constructionManager->getIsExternalAccount() || $constructionManager->getIsTrialAccount();

            if ($onWhitelist) {
                $constructionManager->setIsExternalAccount(false);
                $constructionManager->setIsTrialAccount(false);
                $constructionManager->setIsEnabled(true);

                return true;
            } elseif ($otherwiseAuthorized) {
                $constructionManager->setIsEnabled(true);

                return true;
            } else {
                $constructionManager->setIsEnabled(false);

                return false;
            }
        }

        throw new \Exception('invalid authorization method configured: '.$this->authorizationMethod);
    }

    public function setDefaultValues(ConstructionManager $constructionManager)
    {
        $defaultValues = $this->getDefaultUserData($constructionManager->getEmail());

        if (\array_key_exists('givenName', $defaultValues)) {
            $constructionManager->setGivenName($defaultValues['givenName']);
        }

        if (\array_key_exists('familyName', $defaultValues)) {
            $constructionManager->setFamilyName($defaultValues['familyName']);
        }

        if (\array_key_exists('phone', $defaultValues)) {
            $constructionManager->setPhone($defaultValues['phone']);
        }
    }

    /**
     * @return string[]
     */
    private function getDefaultUserData(string $email): array
    {
        if (null == $this->userDataCache) {
            $this->userDataCache = [];

            $userDataRoot = $this->pathService->getTransientFolderForAuthorization().\DIRECTORY_SEPARATOR.'user_data';
            foreach (glob($userDataRoot.\DIRECTORY_SEPARATOR.'*.json') as $userDataFile) {
                $json = file_get_contents($userDataFile);

                $entries = json_decode($json, true);
                foreach ($entries as $entry) {
                    if (\array_key_exists('email', $entry)) {
                        $this->userDataCache[$entry['email']] = $entry;
                    }
                }
            }
        }

        if (!\array_key_exists($email, $this->userDataCache)) {
            return [];
        }

        return $this->userDataCache[$email];
    }

    private function isEmailOnWhitelist(string $email)
    {
        if (null == $this->emailLookupCache) {
            $this->emailLookupCache = [];

            $whitelistRoot = $this->pathService->getTransientFolderForAuthorization().\DIRECTORY_SEPARATOR.'whitelists';
            foreach (glob($whitelistRoot.\DIRECTORY_SEPARATOR.'*.txt') as $whitelistFile) {
                $whitelist = file_get_contents($whitelistFile);
                $lines = explode("\n", $whitelist);
                foreach ($lines as $line) {
                    $cleanedLine = trim($line);
                    if ('' !== $cleanedLine) {
                        $this->emailLookupCache[$cleanedLine] = true;
                    }
                }
            }
        }

        return \array_key_exists($email, $this->emailLookupCache);
    }
}
