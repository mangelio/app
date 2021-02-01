<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\ConstructionManager;
use App\Tests\DataFixtures\TestConstructionManagerFixtures;
use App\Tests\DataFixtures\TestConstructionSiteFixtures;
use App\Tests\Traits\AssertApiTrait;
use App\Tests\Traits\AuthenticationTrait;
use App\Tests\Traits\TestDataTrait;
use Doctrine\Persistence\ManagerRegistry;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class ConstructionManagerTest extends ApiTestCase
{
    use FixturesTrait;
    use TestDataTrait;
    use AssertApiTrait;
    use AuthenticationTrait;

    public function testInvalidMethods()
    {
        $client = $this->createClient();
        $this->loadFixtures([TestConstructionManagerFixtures::class]);
        $testUser = $this->loginApiConstructionManager($client);

        $this->assertApiOperationUnsupported($client, '/api/construction_managers/'.$testUser->getId(), 'DELETE', 'PUT', 'PATCH');
    }

    public function testValidMethodsNeedAuthentication()
    {
        $client = $this->createClient();
        $this->loadFixtures([TestConstructionManagerFixtures::class]);

        $this->assertApiOperationNotAuthorized($client, '/api/construction_managers', 'GET');

        $userRepository = static::$container->get(ManagerRegistry::class)->getRepository(ConstructionManager::class);
        $testUser = $userRepository->findOneByEmail(TestConstructionManagerFixtures::CONSTRUCTION_MANAGER_EMAIL);
        $this->assertApiOperationNotAuthorized($client, '/api/construction_managers/'.$testUser->getId(), 'GET');
    }

    public function testPost()
    {
        $client = $this->createClient();
        $this->loadFixtures([TestConstructionManagerFixtures::class]);

        // can register
        $this->assertApiPostStatusCodeSame(StatusCode::HTTP_CREATED, $client, '/api/construction_managers', ['email' => 'test@mail.com']);
        $this->assertEmailCount(1);

        // can create other accounts if logged in fully
        $this->loginApiConstructionManager($client);
        $this->assertApiPostPayloadPersisted($client, '/api/construction_managers', ['email' => 'test2@mail.com']);
        $this->assertEmailCount(1);

        // can execute on already created accounts without error / reregistration
        $this->assertApiPostPayloadPersisted($client, '/api/construction_managers', ['email' => TestConstructionManagerFixtures::CONSTRUCTION_MANAGER_EMAIL]);
        $this->assertEmailCount(0);

        // associated construction manager does not get more info
        $this->loginApiAssociatedConstructionManager($client);
        $this->assertApiPostStatusCodeSame(StatusCode::HTTP_BAD_REQUEST, $client, '/api/construction_managers', ['email' => TestConstructionManagerFixtures::CONSTRUCTION_MANAGER_EMAIL]);
        $this->assertEmailCount(0);
    }

    public function testGetAuthenticationToken()
    {
        $client = $this->createClient();
        $this->loadFixtures([TestConstructionManagerFixtures::class]);
        $constructionManager = $this->loginApiConstructionManager($client);

        $otherConstructionManagerFields = ['@id', '@type', 'givenName', 'familyName', 'email', 'phone'];
        $selfConstructionManagerFields = array_merge($otherConstructionManagerFields, ['authenticationToken']);
        sort($otherConstructionManagerFields);
        sort($selfConstructionManagerFields);

        $constructionManagerIri = $this->getIriFromItem($constructionManager);
        $response = $this->assertApiGetOk($client, '/api/construction_managers');
        $constructionManagers = json_decode($response->getContent(), true);
        foreach ($constructionManagers['hydra:member'] as $constructionManager) {
            $actualFields = array_keys($constructionManager);
            sort($actualFields);
            if ($constructionManager['@id'] === $constructionManagerIri) {
                $this->assertArraySubset($actualFields, $selfConstructionManagerFields);
            } else {
                $this->assertArraySubset($actualFields, $otherConstructionManagerFields);
            }
        }
    }

    public function testConstructionSiteFilters()
    {
        $client = $this->createClient();
        $this->loadFixtures([TestConstructionManagerFixtures::class, TestConstructionSiteFixtures::class]);
        $constructionManager = $this->loginApiConstructionManager($client);
        $constructionManagerIri = $this->getIriFromItem($constructionManager);

        $constructionSite = $this->getTestConstructionSite();
        $emptyConstructionSite = $this->getEmptyConstructionSite();

        $this->assertApiCollectionContainsIri($client, '/api/construction_managers?constructionSites.id='.$constructionSite->getId(), $constructionManagerIri);
        $this->assertApiCollectionNotContainsIri($client, '/api/construction_managers?constructionSites.id='.$emptyConstructionSite->getId(), $constructionManagerIri);
    }
}
