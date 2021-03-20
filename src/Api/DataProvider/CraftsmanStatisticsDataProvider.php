<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Api\DataProvider;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Api\Entity\CraftsmanStatistics;
use App\Entity\Craftsman;
use App\Service\Interfaces\CraftsmanServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class CraftsmanStatisticsDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /**
     * @var ContextAwareCollectionDataProviderInterface
     */
    private $decoratedCollectionDataProvider;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CraftsmanServiceInterface
     */
    private $craftsmanService;

    private const ALREADY_CALLED = 'CRAFTSMAN_STATISTICS_DATA_PROVIDER_ALREADY_CALLED';

    /**
     * CraftsmanStatisticsDataProvider constructor.
     *
     * @param ManagerRegistry $manager
     */
    public function __construct(ContextAwareCollectionDataProviderInterface $decoratedCollectionDataProvider, IriConverterInterface $iriConverter, SerializerInterface $serializer, CraftsmanServiceInterface $craftsmanService)
    {
        $this->decoratedCollectionDataProvider = $decoratedCollectionDataProvider;
        $this->iriConverter = $iriConverter;
        $this->serializer = $serializer;
        $this->craftsmanService = $craftsmanService;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        // Make sure we're not called twice
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return Craftsman::class === $resourceClass && 'get_statistics' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var Craftsman[] $craftsmen */
        $craftsmen = $this->decoratedCollectionDataProvider->getCollection($resourceClass, $operationName, $context);

        $statisticDictionary = $this->craftsmanService->createStatisticLookup($craftsmen);
        $statistics = [];
        foreach ($statisticDictionary as $craftsmanId => $statistic) {
            $craftsmanIri = $this->iriConverter->getItemIriFromResourceClass(Craftsman::class, ['id' => $craftsmanId]);
            $statistics[] = new CraftsmanStatistics($craftsmanIri, $statistic);
        }

        $json = $this->serializer->serialize($statistics, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
