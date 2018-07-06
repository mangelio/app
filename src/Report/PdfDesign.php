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

class PdfDesign
{
    /**
     * @var string
     */
    private $fontFamily = 'opensans';

    /**
     * @var int[]
     */
    private $textColor = [37, 200, 200];

    /**
     * @var int[]
     */
    private $darkBackground = [200, 200, 200];

    /**
     * @var int[]
     */
    private $lightBackground = [230, 230, 230];

    /**
     * @var int[]
     */
    private $lighterBackground = [240, 240, 240];

    /**
     * @return string[]
     */
    public function getDefaultFontFamily(): array
    {
        return [$this->fontFamily];
    }

    /**
     * @return string[]
     */
    public function getEmphasisFontFamily(): array
    {
        return [$this->fontFamily, 'b'];
    }

    /**
     * @return int[]
     */
    public function getTextColor(): array
    {
        return $this->textColor;
    }

    /**
     * @return int[]
     */
    public function getDarkBackground(): array
    {
        return $this->darkBackground;
    }

    /**
     * @return int[]
     */
    public function getLightBackground(): array
    {
        return $this->lightBackground;
    }

    /**
     * @return int[]
     */
    public function getLighterBackground(): array
    {
        return $this->lighterBackground;
    }
}
