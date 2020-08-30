<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\ConstructionSite;
use App\Entity\Issue;
use App\Entity\Map;
use App\Helper\ImageHelper;
use App\Service\Interfaces\ImageServiceInterface;
use App\Service\Interfaces\PathServiceInterface;
use const DIRECTORY_SEPARATOR;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ImageService implements ImageServiceInterface
{
    /**
     * the name of the image rendered from the map pdf.
     */
    private const MAP_RENDER_NAME = 'render.jpg';
    // TODO: unit test to detect if all enums in here
    /**
     * @var array
     */
    private $validSizes = [ImageServiceInterface::SIZE_FULL, ImageServiceInterface::SIZE_REPORT_ISSUE, ImageServiceInterface::SIZE_REPORT_MAP, ImageServiceInterface::SIZE_SHARE_VIEW, ImageServiceInterface::SIZE_THUMBNAIL, ImageServiceInterface::SIZE_MEDIUM];

    /**
     * @var PathServiceInterface
     */
    private $pathService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int the bubble size as an abstract unit
     *          the higher the number the smaller the resulting bubble
     */
    private $bubbleScale = 800;

    /**
     * @var bool if the cache should be disabled
     */
    private $disableCache = false;

    /**
     * @var bool prevents calls to warmup cache from archiving something
     */
    private $preventCacheWarmUp;

    /**
     * ImageService constructor.
     */
    public function __construct(PathServiceInterface $pathService, KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->pathService = $pathService;
        $this->logger = $logger;

        // improves performance when generating fixtures (done extensively in dev / test environment)
        $this->preventCacheWarmUp = $kernel->getEnvironment() !== 'prod';
    }

    /**
     * @param Issue[] $issues
     * @param string  $size
     *
     * @return string|null
     */
    public function generateMapImage(Map $map, array $issues, $size = self::SIZE_THUMBNAIL)
    {
        if ($map->getFile() === null) {
            return null;
        }

        //setup paths
        $sourceFilePath = $this->pathService->getFolderForMapFile($map->getConstructionSite()) . DIRECTORY_SEPARATOR . $map->getFile()->getFilename();
        $generationTargetFolder = $this->pathService->getTransientFolderForMapFile($map);
        $this->ensureFolderExists($generationTargetFolder);

        return $this->generateMapImageInternal($issues, $sourceFilePath, $generationTargetFolder, false, $size);
    }

    /**
     * @param string $size
     *
     * @return string|null
     */
    public function generateMapImageForReport(Map $map, array $issues, $size = self::SIZE_THUMBNAIL)
    {
        if ($map->getFile() === null) {
            return null;
        }

        //setup paths
        $sourceFilePath = $this->pathService->getFolderForMapFile($map->getConstructionSite()) . DIRECTORY_SEPARATOR . $map->getFile()->getFilename();
        $generationTargetFolder = $this->pathService->getTransientFolderForMapFile($map);
        $this->ensureFolderExists($generationTargetFolder);

        return $this->generateMapImageInternal($issues, $sourceFilePath, $generationTargetFolder, true, $size);
    }

    /**
     * generates all sizes so the getSize call goes faster once it is really needed.
     */
    public function warmUpCacheForIssue(Issue $issue)
    {
        if ($issue->getImage() === null || $this->preventCacheWarmUp) {
            return;
        }

        //setup paths
        $sourceFolder = $this->pathService->getFolderForIssueImage($issue->getMap()->getConstructionSite());
        $targetFolder = $this->pathService->getTransientFolderForIssueImage($issue);
        $this->ensureFolderExists($targetFolder);

        foreach ($this->validSizes as $validSize) {
            $this->renderSizeFor($issue->getImage()->getFilename(), $sourceFolder, $targetFolder, $validSize);
        }
    }

    /**
     * generates all sizes so the getSize call goes faster once it is really needed.
     */
    public function warmUpCacheForConstructionSite(ConstructionSite $constructionSite)
    {
        if ($constructionSite->getImage() === null || $this->preventCacheWarmUp) {
            return;
        }

        //setup paths
        $sourceFolder = $this->pathService->getFolderForConstructionSiteImage($constructionSite);
        $targetFolder = $this->pathService->getTransientFolderForConstructionSiteImage($constructionSite);
        $this->ensureFolderExists($targetFolder);

        foreach ($this->validSizes as $validSize) {
            $this->renderSizeFor($constructionSite->getImage()->getFilename(), $sourceFolder, $targetFolder, $validSize);
        }
    }

    /**
     * generates all sizes so the getSize call goes faster once it is really needed.
     */
    public function warmUpCacheForMap(Map $map)
    {
        if ($map->getFile() === null || $this->preventCacheWarmUp) {
            return;
        }

        //setup paths
        $sourceFilePath = $this->pathService->getFolderForMapFile($map->getConstructionSite()) . DIRECTORY_SEPARATOR . $map->getFile()->getFilename();
        $generationTargetFolder = $this->pathService->getTransientFolderForMapFile($map);
        $this->ensureFolderExists($generationTargetFolder);

        //pre-render all sizes
        foreach ($this->validSizes as $validSize) {
            $this->generateMapImageInternal([], $sourceFilePath, $generationTargetFolder, false, $validSize);
        }
    }

    /**
     * @param string $uncheckedSize
     *
     * @return string
     */
    public function ensureValidSize($uncheckedSize)
    {
        return \in_array($uncheckedSize, $this->validSizes, true) ? $uncheckedSize : ImageServiceInterface::SIZE_THUMBNAIL;
    }

    /**
     * @param string $size
     *
     * @return string|null
     */
    public function getSizeForIssue(Issue $issue, $size = self::SIZE_THUMBNAIL)
    {
        if ($issue->getImage() === null) {
            return null;
        }

        //setup paths
        $sourceFolder = $this->pathService->getFolderForIssueImage($issue->getMap()->getConstructionSite());
        $targetFolder = $this->pathService->getTransientFolderForIssueImage($issue);
        $this->ensureFolderExists($targetFolder);

        return $this->renderSizeFor($issue->getImage()->getFilename(), $sourceFolder, $targetFolder, $size);
    }

    /**
     * @param string $size
     *
     * @return string|null
     */
    public function getSizeForConstructionSite(ConstructionSite $constructionSite, $size = self::SIZE_THUMBNAIL)
    {
        if ($constructionSite->getImage() === null) {
            return null;
        }

        //setup paths
        $sourceFolder = $this->pathService->getFolderForConstructionSiteImage($constructionSite);
        $targetFolder = $this->pathService->getTransientFolderForConstructionSiteImage($constructionSite);
        $this->ensureFolderExists($targetFolder);

        return $this->renderSizeFor($constructionSite->getImage()->getFilename(), $sourceFolder, $targetFolder, $size);
    }

    /**
     * @param $size
     *
     * @return string
     */
    private function renderSizeFor(?string $sourceFileName, string $sourceFolder, string $targetFolder, $size)
    {
        if ($sourceFileName === null) {
            return null;
        }

        //setup paths
        $sourceFilePath = $sourceFolder . DIRECTORY_SEPARATOR . $sourceFileName;
        $targetFileName = $this->getSizeFilename($sourceFileName, $size);
        $targetFilePath = $targetFolder . DIRECTORY_SEPARATOR . $targetFileName;

        if (!file_exists($targetFilePath) || $this->disableCache) {
            $this->renderSizeOfImage($sourceFilePath, $targetFilePath, $size);

            //abort if generation failed
            if (!file_exists($targetFilePath)) {
                return null;
            }
        }

        return $targetFilePath;
    }

    /**
     * @param string $size
     */
    private function renderSizeOfImage(string $sourceFilePath, string $targetFilePath, $size = ImageServiceInterface::SIZE_THUMBNAIL)
    {
        //generate variant if possible
        switch ($size) {
            case ImageServiceInterface::SIZE_THUMBNAIL:
                $this->resizeImage($sourceFilePath, $targetFilePath, 100, 50);
                break;
            case ImageServiceInterface::SIZE_SHARE_VIEW:
                $this->resizeImage($sourceFilePath, $targetFilePath, 450, 600);
                break;
            case ImageServiceInterface::SIZE_REPORT_ISSUE:
            case ImageServiceInterface::SIZE_MEDIUM:
                $this->resizeImage($sourceFilePath, $targetFilePath, 600, 600);
                break;
            case ImageServiceInterface::SIZE_FULL:
                $this->resizeImage($sourceFilePath, $targetFilePath, 1920, 1080);
                break;
            case ImageServiceInterface::SIZE_REPORT_MAP:
                $this->resizeImage($sourceFilePath, $targetFilePath, 2480, 2480);
                break;
        }
    }

    private function renderPdfToImage(string $sourcePdfPath, string $targetFilepath)
    {
        //do first low quality render to get artboxsize
        $command = 'gs -sDEVICE=jpeg -dDEVICEWIDTHPOINTS=1920 -dDEVICEHEIGHTPOINTS=1080 -dJPEGQ=10 -dUseCropBox -sPageList=1 -o "' . $targetFilepath . '" "' . $sourcePdfPath . '"';
        exec($command);
        if (!is_file($targetFilepath)) {
            return;
        }

        //second render with correct image dimensions
        list($width, $height) = ImageHelper::getWidthHeightArguments($targetFilepath, 3840, 2160);
        $command = 'gs -sDEVICE=jpeg -dDEVICEWIDTHPOINTS=' . $width . ' -dDEVICEHEIGHTPOINTS=' . $height . ' -dJPEGQ=80 -dUseCropBox -dFitPage -sPageList=1 -o "' . $targetFilepath . '" "' . $sourcePdfPath . '"';
        exec($command);
    }

    /**
     * @param $image
     */
    private function drawIssue(Issue $issue, bool $rotated, &$image)
    {
        //get sizes
        $xSize = imagesx($image);
        $ySize = imagesy($image);

        //target location
        $position = $issue->getPosition();
        if ($rotated) {
            $yCoordinate = $position->getPositionX();
            $xCoordinate = $position->getPositionY();
        } else {
            $yCoordinate = $position->getPositionY();
            $xCoordinate = $position->getPositionX();
        }
        $yCoordinate *= $ySize;
        $xCoordinate *= $xSize;

        //colors sometime do not work and show up as black. just choose another color as close as possible to workaround
        if ($issue->getReviewedAt() !== null) {
            //green
            $circleColor = $this->createColor($image, 18, 140, 45);
        } else {
            //orange
            $circleColor = $this->createColor($image, 201, 151, 0);
        }

        $this->drawRectangleWithText($yCoordinate, $xCoordinate, $circleColor, (string) $issue->getNumber(), $image);
    }

    /**
     * @param float $yPosition
     * @param float $xPosition
     * @param $circleColor
     * @param $text
     * @param $image
     */
    private function drawRectangleWithText($yPosition, $xPosition, $circleColor, $text, &$image)
    {
        $textFactor = mb_strlen($text) / 2.6;

        //get sizes
        $xSize = imagesx($image);
        $ySize = imagesy($image);
        $imageSize = $xSize * $ySize;
        $targetTextDimension = sqrt($imageSize / ($this->bubbleScale * M_PI)) * $textFactor;

        //get text dimensions
        $font = __DIR__ . '/../../assets/fonts/OpenSans-Bold.ttf';
        $testFontSize = 30;
        $txtSize = imagettfbbox($testFontSize, 0, $font, $text);
        $testTextWidth = abs($txtSize[4] - $txtSize[0]);
        $testTextHeight = abs($txtSize[5] - $txtSize[1]);

        //calculate appropriate font size
        $maxTextDimension = max($testTextWidth, $testTextHeight * 1.4); //*1.4 to counter single number being too big
        $scalingFactor = $targetTextDimension / $maxTextDimension;
        $fontSize = $scalingFactor * $testFontSize;
        $textWidth = $testTextWidth * $scalingFactor;
        $textHeight = $testTextHeight * $scalingFactor;

        //draw white base ellipse before the colored one
        $white = $this->createColor($image, 255, 255, 255);
        $padding = $textHeight * 0.3;
        $halfHeight = $textHeight / 2;
        $textStart = $xPosition - ($textWidth / 2);
        $textEnd = $xPosition + ($textWidth / 2);
        imagerectangle($image, (int) ($textStart - $padding - 1), (int) ($yPosition - $halfHeight - $padding - 1), (int) ($textEnd + $padding + 1), (int) ($yPosition + $halfHeight + $padding + 1), $white);
        imagefilledrectangle($image, (int) ($textStart - $padding), (int) ($yPosition - $padding - $halfHeight), (int) ($textEnd + $padding), (int) ($yPosition + $halfHeight + $padding), $circleColor);

        //draw text
        imagettftext($image, $fontSize, 0, (int) ($textStart), (int) ($yPosition + $halfHeight), $white, $font, $text);
    }

    /**
     * @param resource $image
     * @param int      $red
     * @param int      $green
     * @param int      $blue
     *
     * @return int
     */
    private function createColor($image, $red, $green, $blue)
    {
        //get color from palette
        $color = imagecolorexact($image, $red, $green, $blue);
        if ($color === -1) {
            //color does not exist...
            //test if we have used up palette
            if (imagecolorstotal($image) >= 255) {
                //palette used up; pick closest assigned color
                $color = imagecolorclosest($image, $red, $green, $blue);
            } else {
                //palette NOT used up; assign new color
                $color = imagecolorallocate($image, $red, $green, $blue);
            }
        }

        return $color;
    }

    /**
     * render issues on image if it does not already exist.
     *
     * @param Issue[] $issues
     * @param string  $pdfRenderPath
     * @param string  $issueImagePath
     * @param string  $landscapeIssueImagePath
     * @param bool    $forceLandscape
     *
     * @return string
     */
    private function renderIssues(array $issues, $pdfRenderPath, $issueImagePath, $landscapeIssueImagePath, $forceLandscape)
    {
        $targetImagePath = null;
        $sourceImageStream = null;
        $rotated = false;
        if ($forceLandscape) {
            if (!is_file($landscapeIssueImagePath) || $this->disableCache) {
                $targetImagePath = $landscapeIssueImagePath;
                $sourceImageStream = imagecreatefromjpeg($pdfRenderPath);
                $width = imagesx($sourceImageStream);
                $height = imagesy($sourceImageStream);

                if ($height > $width) {
                    $sourceImageStream = imagerotate($sourceImageStream, 90, 0);
                    $rotated = true;
                } elseif (file_exists($issueImagePath)) {
                    //simply copy already rendered file
                    copy($issueImagePath, $landscapeIssueImagePath);
                    imagedestroy($sourceImageStream);
                    $sourceImageStream = null;
                }
            }
        } elseif (!is_file($issueImagePath) || $this->disableCache) {
            $targetImagePath = $issueImagePath;
            $sourceImageStream = imagecreatefromjpeg($pdfRenderPath);
        }

        //render if needed
        if ($sourceImageStream !== null) {
            //draw the issues on the map
            foreach ($issues as $issue) {
                if ($issue->getPosition() !== null) {
                    $this->drawIssue($issue, $rotated, $sourceImageStream);
                }
            }

            //write to disk & destroy
            imagejpeg($sourceImageStream, $targetImagePath, 90);
            imagedestroy($sourceImageStream);
        }

        return $forceLandscape ? $landscapeIssueImagePath : $issueImagePath;
    }

    /**
     * @param Issue[] $issues
     * @param bool    $forceLandscape
     * @param string  $size
     *
     * @return string|null
     */
    private function generateMapImageInternal(array $issues, string $sourceFilePath, string $generationTargetFolder, $forceLandscape, $size)
    {
        //render pdf to image
        $pdfRenderPath = $generationTargetFolder . DIRECTORY_SEPARATOR . self::MAP_RENDER_NAME;
        if (!file_exists($pdfRenderPath) || $this->disableCache) {
            $this->renderPdfToImage($sourceFilePath, $pdfRenderPath);

            //abort if creation failed
            if (!file_exists($pdfRenderPath)) {
                return null;
            }
        }

        // shortcut if no issues to be printed
        if (\count($issues) > 0) {
            //prepare filename for exact issue combination
            $issueToString = function ($issue) {
                /* @var Issue $issue */
                return $issue->getId() . $issue->getStatusCode() . $issue->getLastChangedAt()->format('c');
            };
            $issueHash = hash('sha256', 'v1' . implode(',', array_map($issueToString, $issues)) . $this->bubbleScale);

            //render issue image
            $issueImagePath = $generationTargetFolder . DIRECTORY_SEPARATOR . $issueHash . '.jpg';
            $landscapeIssueImagePath = $generationTargetFolder . DIRECTORY_SEPARATOR . $issueHash . '_landscape.jpg';
            $issueRenderPath = $this->renderIssues($issues, $pdfRenderPath, $issueImagePath, $landscapeIssueImagePath, $forceLandscape);
        } else {
            $issueRenderPath = $pdfRenderPath;
        }

        //render size variant
        $fileName = pathinfo($issueRenderPath, PATHINFO_BASENAME);
        $issueImagePathSize = $generationTargetFolder . DIRECTORY_SEPARATOR . $this->getSizeFilename($fileName, $size);
        if (!is_file($issueImagePathSize) || $this->disableCache) {
            $this->renderSizeOfImage($issueRenderPath, $issueImagePathSize, $size);

            //abort if creation failed
            if (!is_file($issueImagePathSize)) {
                return null;
            }
        }

        //return the path of the rendered file
        return $issueImagePathSize;
    }

    private function ensureFolderExists(string $folderName)
    {
        if (!is_dir($folderName)) {
            mkdir($folderName, 0777, true);
        }
    }

    /**
     * adds sizing infos to filename.
     *
     * @param string $size
     *
     * @return string
     */
    private function getSizeFilename(string $fileName, $size)
    {
        $ending = pathinfo($fileName, PATHINFO_EXTENSION);
        $filenameWithoutEnding = mb_substr($fileName, 0, -(mb_strlen($ending) + 1));

        return $filenameWithoutEnding . '_' . $size . '.' . $ending;
    }

    /**
     * @return bool
     */
    private function resizeImage(string $sourcePath, string $targetPath, int $maxWidth, int $maxHeight)
    {
        list($width, $height) = ImageHelper::getWidthHeightArguments($sourcePath, $maxWidth, $maxHeight, false);
        $ending = pathinfo($sourcePath, PATHINFO_EXTENSION);

        //resize & save
        $newImage = imagecreatetruecolor($width, $height);
        if ($ending === 'jpg' || $ending === 'jpeg') {
            $originalImage = imagecreatefromjpeg($sourcePath);
            imagecopyresampled($newImage, $originalImage, 0, 0, 0, 0, $width, $height, imagesx($originalImage), imagesy($originalImage));
            imagejpeg($newImage, $targetPath, 90);
        } elseif ($ending === 'png') {
            $originalImage = imagecreatefrompng($sourcePath);
            imagecopyresampled($newImage, $originalImage, 0, 0, 0, 0, $width, $height, imagesx($originalImage), imagesy($originalImage));
            imagepng($newImage, $targetPath, 9);
        } elseif ($ending === 'gif') {
            $originalImage = imagecreatefromgif($sourcePath);
            imagecopyresampled($newImage, $originalImage, 0, 0, 0, 0, $width, $height, imagesx($originalImage), imagesy($originalImage));
            imagegif($newImage, $targetPath);
        } else {
            $this->logger->warning('cannot resize image with ending ' . $ending);
            // can not resize; but at least create the file
            copy($sourcePath, $targetPath);
        }

        return true;
    }
}