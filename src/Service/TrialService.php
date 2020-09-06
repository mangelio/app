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
use App\Entity\ConstructionSite;
use App\Helper\RandomHelper;
use App\Service\Interfaces\PathServiceInterface;
use App\Service\Interfaces\StorageServiceInterface;
use App\Service\Interfaces\TrialServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class TrialService implements TrialServiceInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PathServiceInterface
     */
    private $pathService;

    /**
     * @var StorageServiceInterface
     */
    private $uploadService;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * TrialService constructor.
     */
    public function __construct(PathServiceInterface $pathService, TranslatorInterface $translator, RequestStack $requestStack, ManagerRegistry $registry, StorageServiceInterface $uploadService)
    {
        $this->pathService = $pathService;
        $this->translator = $translator;
        $this->uploadService = $uploadService;

        $request = $requestStack->getCurrentRequest();
        $this->registry = $registry;
    }

    /**
     * creates a trial account with pre-filled data.
     *
     * @return ConstructionManager
     *
     * @throws Exception
     */
    public function createTrialAccount(?string $proposedGivenName = null, ?string $proposedFamilyName = null)
    {
        $constructionManager = $this->createConstructionManager($proposedGivenName, $proposedFamilyName);
        $constructionSite = $this->createConstructionSite($constructionManager);

        $constructionManager->setActiveConstructionSite($constructionSite);

        $manager = $this->registry->getManager();
        $manager->persist($constructionManager);
        $manager->persist($constructionSite);
        $manager->flush();

        $this->addConstructionSiteContent($constructionSite);

        return $constructionManager;
    }

    /**
     * @return ConstructionManager
     *
     * @throws Exception
     */
    private function createConstructionManager(?string $proposedGivenName, ?string $proposedFamilyName)
    {
        // create manager
        $constructionManager = new ConstructionManager();
        $constructionManager->setIsTrialAccount(true);
        $constructionManager->setGivenName(null !== $proposedGivenName ? $proposedGivenName : $this->faker->firstNameMale);
        $constructionManager->setFamilyName(null !== $proposedFamilyName ? $proposedFamilyName : $this->faker->lastName);

        // generate unused email
        $maxTries = 10;
        $repository = $this->registry->getRepository(ConstructionManager::class);
        do {
            $email = RandomHelper::generateHumanReadableRandom(5, '_').'@test.mangel.io';

            if ($maxTries-- < 0) {
                throw new Exception('unable to create new random email');
            }
        } while (null !== $repository->findOneBy(['email' => $email]));

        // generate login info
        $password = RandomHelper::generateHumanReadableRandom(10, '-');
        $constructionManager->setEmail($email);
        $constructionManager->setPasswordFromPlain($password);

        return $constructionManager;
    }

    /**
     * @return ConstructionSite
     */
    private function createConstructionSite(ConstructionManager $constructionManager)
    {
        $constructionSite = new ConstructionSite();
        $constructionSite->setName($this->translator->trans('example.name', ['%name%' => $constructionManager->getName()], 'entity_construction_site'));
        $constructionSite->setFolderName($constructionManager->getEmail());
        $constructionSite->setStreetAddress($this->translator->trans('example.street_address', [], 'entity_construction_site'));
        $constructionSite->setLocality($this->translator->trans('example.locality', [], 'entity_construction_site'));
        $constructionSite->setPostalCode($this->translator->trans('example.postal_code', [], 'entity_construction_site'));
        $constructionSite->setCountry($this->translator->trans('example.country', [], 'entity_construction_site'));
        $constructionSite->setIsTrialConstructionSite(true);

        $constructionSite->getConstructionManagers()->add($constructionManager);
        $constructionManager->getConstructionSites()->add($constructionSite);

        return $constructionSite;
    }
}
