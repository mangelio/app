<?php

/*
 * This file is part of the baupen project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Api\DataProvider;

use App\Api\DataProvider\Base\NoPaginationDataProvider;
use App\Api\Entity\IssueSummary;
use App\Entity\Issue;
use App\Service\Interfaces\AnalysisServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class IssueSummaryDataProvider extends NoPaginationDataProvider
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var AnalysisServiceInterface
     */
    private $analysisService;

    public function __construct(SerializerInterface $serializer, ManagerRegistry $managerRegistry, AnalysisServiceInterface $analysisService, iterable $collectionExtensions = [])
    {
        parent::__construct($managerRegistry, $collectionExtensions);
        $this->serializer = $serializer;
        $this->analysisService = $analysisService;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Issue::class === $resourceClass && 'get_summary' === $operationName;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $queryBuilder = $this->getCollectionQueryBuilerWithoutPagination($resourceClass, $operationName, $context);
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $issueAnalysis = $this->analysisService->createIssueAnalysis($rootAlias, $queryBuilder);
        $summary = IssueSummary::createFromIssueAnalysis($issueAnalysis);
        $json = $this->serializer->serialize($summary, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
