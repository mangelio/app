<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Report;

class PdfSizes
{
    /**
     * @var float the used page size
     */
    private $pageSize = [210, 297];

    /**
     * @var float
     */
    private $marginSide = 10;

    /**
     * @var float
     */
    private $marginVerticalOuter = 6;

    /**
     * @var float
     */
    private $headerSize = 8;

    /**
     * @var float
     */
    private $differentContentMargin = 6;

    /**
     * @var float
     */
    private $footerSize = 4;

    /**
     * @var float
     */
    private $baseFontSizes = 8;

    /**
     * @var float
     */
    private $scalingFactor = 1.6;

    /**
     * @var float
     */
    private $gutterSize = 2;

    /**
     * the total width of the document.
     *
     * @return float
     */
    private function getPageSizeX()
    {
        return $this->pageSize[0];
    }

    /**
     * the total width of the document.
     *
     * @return float
     */
    private function getPageSizeY()
    {
        return $this->pageSize[1];
    }

    /**
     * @return float
     */
    public function getHeaderYStart(): float
    {
        return $this->marginVerticalOuter;
    }

    /**
     * @return float
     */
    public function getHeaderHeight(): float
    {
        return $this->headerSize;
    }

    /**
     * @return float
     */
    public function getContentXStart(): float
    {
        return $this->marginSide;
    }

    /**
     * the width of the document till the right margin.
     *
     * @return float
     */
    public function getContentXEnd(): float
    {
        return $this->getPageSizeX() - $this->marginSide;
    }

    /**
     * @return float
     */
    public function getContentXSize()
    {
        return $this->getContentXEnd() - $this->getContentXStart();
    }

    /**
     * @return float
     */
    public function getContentYStart(): float
    {
        return $this->getHeaderYStart() + $this->getHeaderHeight() + $this->differentContentMargin;
    }

    /**
     * the width of the document till the right margin.
     *
     * @return float
     */
    public function getContentYEnd()
    {
        return $this->getPageSizeY() - $this->marginVerticalOuter - $this->footerSize - $this->differentContentMargin;
    }

    /**
     * the width of the content of the document.
     *
     * @return float
     */
    public function getContentYSize()
    {
        return $this->getContentYEnd() - $this->getContentYStart();
    }

    /**
     * @return float
     */
    public function getFooterYStart(): float
    {
        return $this->getContentYEnd() + $this->differentContentMargin;
    }

    /**
     * @return float
     */
    public function getColumnGutter()
    {
        return $this->gutterSize;
    }

    /**
     * @param $numberOfColumns
     *
     * @return float|float
     */
    public function getColumnContentWidth($numberOfColumns)
    {
        $gutterSpace = ($numberOfColumns - 1) * $this->getColumnGutter();

        return (float)($this->getContentXSize() - $gutterSpace) / $numberOfColumns;
    }

    /**
     * @param $numberOfColumns
     *
     * @return float|float
     */
    public function getColumnWidth($numberOfColumns)
    {
        return $this->getColumnContentWidth($numberOfColumns) + $this->getColumnGutter();
    }

    /**
     * @param $currentColumn
     * @param $numberOfColumns
     *
     * @return float|float
     */
    public function getColumnStart($currentColumn, $numberOfColumns)
    {
        return ($this->getColumnWidth($numberOfColumns)) * $currentColumn + $this->getContentXStart();
    }

    /**
     * for footers/headers.
     *
     * @return float
     */
    public function getSmallFontSize()
    {
        return $this->getRegularFontSize() / $this->scalingFactor;
    }

    /**
     * for text.
     *
     * @return float
     */
    public function getRegularFontSize()
    {
        return $this->baseFontSizes;
    }

    /**
     * for titles.
     *
     * @return float
     */
    public function getBigFontSize()
    {
        return $this->baseFontSizes * $this->scalingFactor;
    }

    /**
     * for big headers.
     *
     * @return float
     */
    public function getLargeFontSize()
    {
        return $this->baseFontSizes * ($this->scalingFactor ** 2);
    }

    /**
     * @return float
     */
    public function getLnHeight()
    {
        return $this->scalingFactor;
    }

    /**
     * @return float
     */
    public function getContentSpacerBig(): float
    {
        return $this->differentContentMargin;
    }

    /**
     * @return float
     */
    public function getContentSpacerSmall(): float
    {
        return (float)$this->differentContentMargin / $this->scalingFactor ** 2;
    }

    /**
     * @return float[]
     */
    public function getDefaultCellPadding(): array
    {
        return [0, 1, 0, 1];
    }

    /**
     * @return float[]
     */
    public function getTableCellPadding(): array
    {
        return [$this->scalingFactor, 1, $this->scalingFactor, 1];
    }
}
