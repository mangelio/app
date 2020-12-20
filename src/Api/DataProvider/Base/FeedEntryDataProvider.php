<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Api\DataProvider\Base;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Api\DataProvider\FeedEntryDataProvider\FeedEntryAggregator;
use App\Entity\Issue;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

abstract class FeedEntryDataProvider extends NoPaginationDataProvider
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    public function __construct(SerializerInterface $serializer, IriConverterInterface $iriConverter, ManagerRegistry $managerRegistry, iterable $collectionExtensions = [])
    {
        parent::__construct($managerRegistry, $collectionExtensions);
        $this->serializer = $serializer;
        $this->iriConverter = $iriConverter;
    }

    abstract protected function getResourceClass(): string;

    abstract protected function registerEvents(array $resources, FeedEntryAggregator $aggregator);

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->getResourceClass() === $resourceClass && 'get_feed' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $queryBuilder = $this->getCollectionQueryBuilerWithoutPagination($resourceClass, $operationName, $context);
        /** @var Issue[] $issues */
        $issues = $queryBuilder->getQuery()->getResult();

        $feedEntryAggregator = new FeedEntryAggregator($this->iriConverter);
        $this->registerEvents($issues, $feedEntryAggregator);

        $feedEntries = $feedEntryAggregator->createFeedEntries();
        $json = $this->serializer->serialize($feedEntries, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
