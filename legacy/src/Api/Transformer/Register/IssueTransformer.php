<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Api\Transformer\Register;

use App\Api\External\Transformer\Base\BatchTransformer;
use App\Entity\Issue;

class IssueTransformer extends BatchTransformer
{
    /**
     * @var \App\Api\Transformer\Foyer\IssueTransformer
     */
    private $issueTransformer;

    /**
     * CraftsmanTransformer constructor.
     */
    public function __construct(\App\Api\Transformer\Foyer\IssueTransformer $issueTransformer)
    {
        $this->issueTransformer = $issueTransformer;
    }

    /**
     * @param Issue $entity
     *
     * @return \App\Api\Entity\Register\Issue
     */
    public function toApi($entity)
    {
        $issue = new \App\Api\Entity\Register\Issue($entity->getId());
        $this->issueTransformer->writeApiProperties($entity, $issue);

        $issue->setWasAddedWithClient($entity->getWasAddedWithClient());
        $issue->setNumber($entity->getNumber());
        $issue->setRegisteredAt($entity->getRegisteredAt());
        $issue->setRegistrationByName($entity->getRegistrationBy()->getName());

        if ($entity->getRespondedAt() !== null) {
            $issue->setRespondedAt($entity->getRespondedAt());
            $issue->setResponseByName($entity->getResponseBy()->getName());
        }

        if ($entity->getReviewedAt() !== null) {
            $issue->setReviewedAt($entity->getReviewedAt());
            $issue->setReviewByName($entity->getReviewBy()->getName());
        }

        $lastVisit = $entity->getCraftsman()->getLastOnlineVisit();
        $issue->setIsRead($lastVisit !== null && $lastVisit > $entity->getRegisteredAt());
        $issue->setMapId($entity->getMap()->getId());

        return $issue;
    }
}