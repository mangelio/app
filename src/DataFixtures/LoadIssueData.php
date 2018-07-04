<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\DataFixtures\Base\BaseFixture;
use App\Entity\ConstructionSite;
use App\Entity\Issue;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Serializer\SerializerInterface;

class LoadIssueData extends BaseFixture
{
    const ORDER = LoadConstructionSiteData::ORDER + LoadConstructionManagerData::ORDER + LoadCraftsmanData::ORDER + ClearPublicUploadDir::ORDER + 1;
    const MULTIPLICATION_FACTOR = 10;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    const REGISTRATION_SET = 1;
    const RESPONSE_SET = 2;
    const REVIEW_SET = 4;

    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     *
     * @throws \BadMethodCallException
     */
    public function load(ObjectManager $manager)
    {
        $json = file_get_contents(__DIR__ . '/Resources/issues.json', 'r');

        $getFreshIssueSet = function () use ($json) {
            /** @var Issue[] $issues */
            $issues = $this->serializer->deserialize($json, Issue::class . '[]', 'json');

            return $issues;
        };

        $issueNumber = 1;
        $constructionSites = $manager->getRepository(ConstructionSite::class)->findAll();
        foreach ($constructionSites as $constructionSite) {
            for ($i = 0; $i < self::MULTIPLICATION_FACTOR; ++$i) {
                $this->add($constructionSite, $manager, $getFreshIssueSet(), $issueNumber, 0);
                $this->add($constructionSite, $manager, $getFreshIssueSet(), $issueNumber, self::REGISTRATION_SET);
                $this->add($constructionSite, $manager, $getFreshIssueSet(), $issueNumber, self::REGISTRATION_SET | self::RESPONSE_SET);
                $this->add($constructionSite, $manager, $getFreshIssueSet(), $issueNumber, self::REGISTRATION_SET | self::RESPONSE_SET | self::REVIEW_SET);
                $this->add($constructionSite, $manager, $getFreshIssueSet(), $issueNumber, self::REGISTRATION_SET | self::REVIEW_SET);
            }
        }
        $manager->flush();
    }

    /**
     * @param $index
     * @param Collection $collection
     *
     * @return mixed
     */
    private function getRandomEntry(&$index, Collection $collection)
    {
        $index = ($index + 1) % $collection->count();

        return $collection->get($index);
    }

    /**
     * @param int $generator
     * @param int $group
     *
     * @return int
     */
    private function getRandomNumber($generator, $group)
    {
        return ($generator ** ($this->currentExponent++ % $group)) % $group;
    }

    private $currentExponent = 7;

    private $randomMapCounter = 0;
    private $randomCraftsmanCounter = 0;
    private $randomConstructionManagerCounter = 0;

    /**
     * @param ConstructionSite $constructionSite
     * @param ObjectManager $manager
     * @param Issue[] $issues
     * @param int $setStatus
     * @param int $issueNumber
     */
    private function add(ConstructionSite $constructionSite, ObjectManager $manager, array $issues, int &$issueNumber, int $setStatus = 0)
    {
        //use global counters so result of randomization is always the same
        $randomMapCounter = $this->randomMapCounter;
        $randomCraftsmanCounter = $this->randomCraftsmanCounter;
        $randomConstructionManagerCounter = $this->randomConstructionManagerCounter;

        foreach ($issues as $issue) {
            $issue->setMap($this->getRandomEntry($randomMapCounter, $constructionSite->getMaps()));

            if ($setStatus !== 0 || $this->getRandomNumber(7, 11) > 7) {
                //if no status is set leave craftsman null sometime
                $issue->setCraftsman($this->getRandomEntry($randomCraftsmanCounter, $constructionSite->getCraftsmen()));
            } else {
                assert($issue->getCraftsman() === null);
            }

            $issue->setUploadBy($this->getRandomEntry($randomConstructionManagerCounter, $constructionSite->getConstructionManagers()));
            $issue->setUploadedAt(new \DateTime('-' . ($this->getRandomNumber(7, 11) + 50) . ' hours'));

            if ($setStatus & self::REGISTRATION_SET) {
                $issue->setNumber($issueNumber++);
                $issue->setRegistrationBy($this->getRandomEntry($randomConstructionManagerCounter, $constructionSite->getConstructionManagers()));
                $issue->setRegisteredAt(new \DateTime('-' . ($this->getRandomNumber(7, 11) + 35) . ' hours'));

                if ($setStatus & self::RESPONSE_SET) {
                    $issue->setResponseBy($issue->getCraftsman());
                    $issue->setRespondedAt(new \DateTime('-' . ($this->getRandomNumber(7, 11) + 20) . ' hours'));
                }
                if ($setStatus & self::REVIEW_SET) {
                    $issue->setReviewBy($this->getRandomEntry($randomConstructionManagerCounter, $constructionSite->getConstructionManagers()));
                    $issue->setReviewedAt(new \DateTime('-' . ($this->getRandomNumber(7, 11)) . ' hours'));
                }
            }

            if ($issue->getImageFilename() !== null) {
                $issue->setImageFilename($this->safeCopyToPublic($issue->getImageFilePath(), 'issue_images'));
            }

            $manager->persist($issue);
        }

        //write back values
        $this->randomMapCounter = $randomMapCounter;
        $this->randomCraftsmanCounter = $randomCraftsmanCounter;
        $this->randomConstructionManagerCounter = $randomConstructionManagerCounter;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return static::ORDER;
    }
}
