<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\ConstructionManager;
use App\Service\Interfaces\AuthorizationServiceInterface;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshAuthorizationCommand extends Command
{
    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var AuthorizationServiceInterface
     */
    private $authorizationService;

    /**
     * ImportLdapUsersCommand constructor.
     *
     * @param RegistryInterface             $registry
     * @param AuthorizationServiceInterface $authorizationService
     */
    public function __construct(RegistryInterface $registry, AuthorizationServiceInterface $authorizationService)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->authorizationService = $authorizationService;
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('app:authorization:refresh')
            ->setDescription('Authorizes construction managers contained in the whitelists and denies the others access.')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws ORMException
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->registry->getEntityManager();

        $managers = $this->registry->getRepository(ConstructionManager::class)->findAll();
        foreach ($managers as $manager) {
            $newIsEnabled = $this->authorizationService->checkIfAuthorized($manager->getEmail());
            if ($newIsEnabled !== $manager->isEnabled()) {
                $manager->setIsEnabled($newIsEnabled);
                $entityManager->persist($manager);
            }
        }

        $entityManager->flush();

        return 0;
    }
}
