<?php

/**
 * Usage statistic client for Contao Open Source CMS
 *
 * PHP version 5
 *
 * @copyright  2014 Contao Community Alliance <http://c-c-a.org>
 * @author     Tristan Lins <t.lins@c-c-a.org>
 * @package    contao-community-alliance/usage-statistic-client
 * @license    LGPL-3.0+ <http://opensource.org/licenses/LGPL-3.0>
 * @filesource
 */

namespace ContaoCommunityAlliance\UsageStatistic\Client;

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
