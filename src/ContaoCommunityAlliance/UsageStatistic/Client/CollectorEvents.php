<?php

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
