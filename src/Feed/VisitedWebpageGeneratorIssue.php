<?php

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Feed;

use App\Entity\Craftsman;
use App\Feed\Base\CraftsmanFeedEntryGenerator;
use App\Feed\Entity\FeedEntry;

class VisitedWebpageGeneratorIssue implements CraftsmanFeedEntryGenerator
{
    /**
     * @param Craftsman[] $craftsmen
     *
     * @return FeedEntry[]
     */
    public function getFeedEntries($craftsmen)
    {
        //create feed entries
        $res = [];
        foreach ($craftsmen as $craftsman) {
            if ($craftsman->getLastOnlineVisit() !== null) {
                $feedEntry = new FeedEntry();
                $feedEntry->setTimestamp($craftsman->getLastOnlineVisit());
                $feedEntry->setCraftsman($craftsman);
                $res[] = $feedEntry;
            }
        }

        return $res;
    }
}