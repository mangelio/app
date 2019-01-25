<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Report\IssueReport;

use App\Helper\ImageHelper;
use App\Service\Report\Document\Interfaces\Layout\Base\PrintableLayoutInterface;
use App\Service\Report\IssueReport\Interfaces\BuildingBlocksInterface;
use App\Service\Report\Pdf\Cursor;
use App\Service\Report\Pdf\Design\Interfaces\ColorServiceInterface;
use App\Service\Report\Pdf\Design\Interfaces\TypographyServiceInterface;
use App\Service\Report\Pdf\Design\TypographyService;
use App\Service\Report\Pdf\Interfaces\PdfDocument\PdfDocumentPrintInterface;

class PdfBuildingBlocks implements BuildingBlocksInterface
{
    /**
     * @var TypographyService
     */
    private $typography;

    /**
     * @var ColorServiceInterface
     */
    private $color;

    /**
     * @var PrintableLayoutInterface
     */
    private $layout;

    /**
     * @param PrintableLayoutInterface $customPrinterLayout
     * @param TypographyServiceInterface $typographyService
     * @param ColorServiceInterface $colorService
     */
    public function __construct(TypographyServiceInterface $typographyService, ColorServiceInterface $colorService)
    {
        $this->typography = $typographyService;
        $this->color = $colorService;
    }

    /**
     * @param PrintableLayoutInterface $layout
     */
    public function setLayout(PrintableLayoutInterface $layout)
    {
        $this->layout = $layout;
    }

    /**
     * @param string $paragraph
     */
    public function printParagraph(string $paragraph)
    {
        $this->printText($paragraph, $this->typography->getTextFontSize());
    }

    /**
     * @param string $title
     */
    public function printTitle(string $title)
    {
        $this->printBoldText($title, $this->typography->getTextFontSize());
    }

    /**
     * @param string $header
     */
    public function printRegionHeader(string $header)
    {
        $this->printBoldText($header, $this->typography->getTitleFontSize());
    }

    /**
     * @param string $filePath
     */
    public function printImage(string $filePath)
    {
        $this->layout->registerPrintable(function (PdfDocumentPrintInterface $document, float $defaultWidth) use ($filePath) {
            list($width, $height) = ImageHelper::getWidthHeightArguments($filePath, $defaultWidth);
            $document->printImage($filePath, $width, $height);
        });
    }

    /**
     * @param string[] $keyValues
     */
    public function printKeyValueParagraph(array $keyValues)
    {
        foreach ($keyValues as $key => $value) {
            $this->printBoldText($key, $this->typography->getTextFontSize());
            $this->printText($value, $this->typography->getTextFontSize());
        }
    }

    /**
     * @param string $imagePath
     * @param int $number
     */
    public function printIssueImage(string $imagePath, int $number)
    {
        $this->layout->registerPrintable(function (PdfDocumentPrintInterface $document, float $defaultWidth) use ($imagePath, $number) {
            list($width, $height) = ImageHelper::getWidthHeightArguments($imagePath, $defaultWidth);
            $document->printImage($imagePath, $width, $height);
            $afterImageCursor = $document->getCursor();

            // put cursor to top left corner of image
            $document->setCursor($afterImageCursor->setY($afterImageCursor->getYCoordinate() - $height));

            // print number of issue
            $document->configurePrint(['background' => $this->color->getImageOverlayColor()]);
            $document->printText((string)$number, $this->typography->getTextFontSize());

            // reset cursor to after image
            $document->setCursor(...$afterImageCursor);
        });
    }

    /**
     * @param string $text
     * @param float $fontSize
     */
    private function printText(string $text, float $fontSize)
    {
        $this->layout->registerPrintable(function (PdfDocumentPrintInterface $document, float $defaultWidth) use ($text, $fontSize) {
            $document->configurePrint(['fontSize' => $fontSize]);
            $document->printText($text, $defaultWidth);
        });
    }

    /**
     * @param string $text
     * @param float $fontSize
     */
    private function printBoldText(string $text, float $fontSize)
    {
        $this->layout->registerPrintable(function (PdfDocumentPrintInterface $document, float $defaultWidth) use ($text, $fontSize) {
            $document->configurePrint(['fontSize' => $fontSize, 'bold' => true]);
            $document->printText($text, $defaultWidth);
        });
    }

    /**
     * will call the closure with the printer as the first and the default width as the second argument.
     *
     * @param \Closure $param
     */
    public function printCustom(\Closure $param)
    {
        $this->layout->registerPrintable(function (PdfDocumentPrintInterface $document, float $defaultWidth) use ($param) {
            $param($document, $defaultWidth);
        });
    }

    /**
     * @param Cursor $start
     * @param Cursor $end
     */
    public function drawTableAlternatingBackground(Cursor $start, Cursor $end)
    {
        //TODO: factor out to Drawer or something
    }
}
