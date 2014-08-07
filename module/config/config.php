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

/**
 * Event subscriber
 */
$GLOBALS['TL_EVENT_SUBSCRIBERS'][] = 'ContaoCommunityAlliance\UsageStatistic\Client\Collector';
