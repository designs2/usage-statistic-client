<?php

/**
 * This file is part of contao-community-alliance/usage-statistic-client.
 *
 * (c) Contao Community Alliance <https://c-c-a.org>
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    contao-community-alliance/usage-statistic-client
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  Contao Community Alliance <https://c-c-a.org>
 * @link       https://github.com/contao-community-alliance/usage-statistic-client
 * @license    http://opensource.org/licenses/LGPL-3.0 LGPL-3.0+
 * @filesource
 */

namespace ContaoCommunityAlliance\UsageStatistic\Client;

/**
 * Usage statistic client collector events.
 */
class CollectorEvents
{
    /**
     * The COLLECT_DATA event occurs when the usage statistic data gets collected.
     *
     * This event allows you to add custom data to the statistics. The event listener
     * method receives a ContaoCommunityAlliance\UsageStatistic\Client\CollectDataEvent instance.
     *
     * @var string
     *
     * @api
     */
    const COLLECT_DATA = 'usage-statistic.collect-data';
}
