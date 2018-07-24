<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\External\Image;

use App\Controller\Base\BaseDoctrineController;
use App\Controller\External\Traits\FilterAuthenticationTrait;
use App\Controller\Traits\ImageDownloadTrait;
use App\Entity\Filter;
use App\Entity\Issue;
use App\Entity\Map;
use App\Service\Interfaces\ImageServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/f/{identifier}")
 */
class FilterController extends BaseDoctrineController
{
    use ImageDownloadTrait;
    use FilterAuthenticationTrait;

    /**
     * @return array
     */
    public static function getSubscribedServices()
    {
        return parent::getSubscribedServices() + [ImageServiceInterface::class => ImageServiceInterface::class];
    }

    /**
     * @Route("/map/{map}/{hash}/{size}", name="external_image_filter_map")
     *
     * @param $identifier
     * @param Map $map
     * @param $size
     * @param ImageServiceInterface $imageService
     *
     * @return Response
     */
    public function mapAction($identifier, Map $map, $size, ImageServiceInterface $imageService)
    {
        /** @var Filter $filter */
        if (!$this->parseIdentifierRequest($this->getDoctrine(), $identifier, $filter)) {
            throw new NotFoundHttpException();
        }

        //before filter is shared the unsafe condition is checked
        $filter->setMaps([$map->getId()]);
        $issues = $this->getDoctrine()->getRepository(Issue::class)->filter($filter);
        $imagePath = $imageService->generateMapImage($map, $issues);

        return $this->file($imageService->getSize($imagePath, $size), null, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/issue/{issue}/{imageFilename}/{size}", name="external_image_filter_issue")
     *
     * @param $identifier
     * @param Issue $issue
     * @param string $imageFilename
     * @param string $size
     * @param ImageServiceInterface $imageService
     *
     * @return Response
     */
    public function issueAction($identifier, Issue $issue, $imageFilename, $size, ImageServiceInterface $imageService)
    {
        /** @var Filter $filter */
        if (!$this->parseIdentifierRequest($this->getDoctrine(), $identifier, $filter)) {
            throw new NotFoundHttpException();
        }

        return $this->file($this->getImagePath($this->getParameter('PUBLIC_DIR'), $issue, $imageFilename, $size, $imageService), $imageFilename, ResponseHeaderBag::DISPOSITION_INLINE);
    }
}
