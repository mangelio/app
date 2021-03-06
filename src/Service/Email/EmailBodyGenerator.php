<?php

/*
 * This file is part of the baupen project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Email;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use App\Helper\DateTimeFormatter;
use App\Service\Report\Email\ConstructionSiteReport;
use App\Service\Report\Email\CraftsmanReport;
use App\Service\Report\Email\IssueCountDeltaTrait;
use App\Service\Report\Email\IssueCountTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailBodyGenerator
{
    private TranslatorInterface $translator;

    private UrlGeneratorInterface $urlGenerator;

    /**
     * EmailBodyGenerator constructor.
     */
    public function __construct(TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator)
    {
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
    }

    public function fromConstructionSiteReports(array $constructionSiteReports)
    {
        $normalizedConstructionSiteReports = [];
        foreach ($constructionSiteReports as $constructionSiteReport) {
            $normalizedConstructionSiteReports[] = $this->fromConstructionSiteReport($constructionSiteReport);
        }

        return [
            'type' => 'construction_site_reports',
            'constructionSiteReports' => $normalizedConstructionSiteReports,
        ];
    }

    public function fromCraftsmanReport(CraftsmanReport $craftsmanReport)
    {
        $normalizedCraftsmanReport = [];
        if ($craftsmanReport->getComparisonTimestamp()) {
            $normalizedCraftsmanReport['comparisonTimeStamp'] = $craftsmanReport->getComparisonTimestamp()->format(DateTimeFormatter::DATE_TIME_FORMAT);
        }

        $issueCountProperties = $this->getIssueCountProperties($craftsmanReport);
        $issueCountDeltaProperties = $this->getIssueCountDeltaProperties($craftsmanReport);

        return array_merge($normalizedCraftsmanReport, $issueCountProperties, $issueCountDeltaProperties);
    }

    private function fromConstructionSiteReport(ConstructionSiteReport $constructionSiteReport)
    {
        $constructionManagers = [];
        $constructionSite = $constructionSiteReport->getConstructionSite();
        foreach ($constructionSite->getConstructionManagers() as $constructionManager) {
            $constructionManagers[] = $constructionManager->getName();
        }

        $dashboardUrl = $this->urlGenerator->generate('construction_site_dashboard', ['constructionSite' => $constructionSite->getId()], UrlGeneratorInterface::ABS_URL);

        $craftsmanReports = [];
        foreach ($constructionSiteReport->getCraftsmanDeltaReports() as $craftsmanDeltaReport) {
            $craftsmanReport = [
                'company' => $craftsmanDeltaReport->getCraftsman()->getCompany(),
                'trade' => $craftsmanDeltaReport->getCraftsman()->getTrade(),
            ];

            $issueCountProperties = $this->getIssueCountProperties($craftsmanDeltaReport);
            $issueCountDeltaProperties = $this->getIssueCountDeltaProperties($craftsmanDeltaReport);
            $craftsmanReports[] = array_merge($craftsmanReport, $issueCountProperties, $issueCountDeltaProperties);
        }

        // sort craftsmen
        usort($craftsmanReports, function ($a, $b) { return strcmp($a['company'], $b['company']); });

        $normalizedConstructionSiteReport = [
            'name' => $constructionSite->getName(),
            'constructionManagers' => $constructionManagers,
            'dashboardUrl' => $dashboardUrl,
            'craftsmanReports' => $craftsmanReports,
        ];
        $issueCountProperties = $this->getIssueCountProperties($constructionSiteReport);
        $issueCountDeltaProperties = $this->getIssueCountDeltaProperties($constructionSiteReport);

        return array_merge($normalizedConstructionSiteReport, $issueCountProperties, $issueCountDeltaProperties);
    }

    /**
     * @param IssueCountTrait $issueCountTrait
     *
     * @return array
     */
    private function getIssueCountProperties($issueCountTrait)
    {
        return [
            'openCount' => $issueCountTrait->getOpenCount(),
            'resolvedCount' => $issueCountTrait->getResolvedCount(),
            'closedCount' => $issueCountTrait->getClosedCount(),
        ];
    }

    /**
     * @param IssueCountDeltaTrait $issueCountDeltaTrait
     *
     * @return array
     */
    private function getIssueCountDeltaProperties($issueCountDeltaTrait)
    {
        return [
            'openCountDelta' => $issueCountDeltaTrait->getOpenCountDelta(),
            'resolvedCountDelta' => $issueCountDeltaTrait->getResolvedCountDelta(),
            'closedCountDelta' => $issueCountDeltaTrait->getClosedCountDelta(),
        ];
    }
}
