<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Traits;

use App\Entity\ConstructionSite;
use App\Entity\Issue;
use App\Service\Interfaces\ImageServiceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ImageDownloadTrait
{
    /**
     * @param Issue $issue
     * @param string $expectedImageFilename
     * @param string $size
     * @param ImageServiceInterface $imageService
     *
     * @return string
     */
    protected function getImagePathForIssue(Issue $issue, $expectedImageFilename, $size, ImageServiceInterface $imageService)
    {
        if ($issue->getImageFilename() !== $expectedImageFilename) {
            throw new NotFoundHttpException();
        }

        $filePath = $imageService->getSizeForIssue($issue, $imageService->ensureValidSize($size));
        if ($filePath === null) {
            throw new NotFoundHttpException();
        }

        return $filePath;
    }

    /**
     * @param ConstructionSite $constructionSite
     * @param string $expectedImageFilename
     * @param string $size
     * @param ImageServiceInterface $imageService
     *
     * @return string
     */
    protected function getImagePathForConstructionSite(ConstructionSite $constructionSite, $expectedImageFilename, $size, ImageServiceInterface $imageService)
    {
        if ($constructionSite->getImageFilename() !== $expectedImageFilename) {
            throw new NotFoundHttpException();
        }

        $filePath = $imageService->getSizeForConstructionSite($constructionSite, $imageService->ensureValidSize($size));
        if ($filePath === null) {
            throw new NotFoundHttpException();
        }

        return $filePath;
    }
}
