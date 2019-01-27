<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Report\Document\Pdf;

interface LayoutFactoryConfigurationInterface
{
    /**
     * @return float
     */
    public function getContentXSize(): float;

    /**
     * @return float
     */
    public function getColumnGutter(): float;

    /**
     * @return float
     */
    public function getTableColumnGutter(): float;
}
