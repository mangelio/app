<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Report\IssueReport\Pdf\Design\Interfaces;

interface ColorServiceInterface
{
    /**
     * @return int[]
     */
    public function getTextColor();

    /**
     * @return int[]
     */
    public function getImageOverlayColor();

    /**
     * @return int[]
     */
    public function getTableAlternatingBackgroundColor();
}