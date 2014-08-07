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

namespace ContaoCommunityAlliance\UsageStatistic\Client\Test;

use ContaoCommunityAlliance\Contao\Events\Cron\CronEvents;
use ContaoCommunityAlliance\UsageStatistic\Client\Collector;
use Guzzle\Http\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CollectorTest
	extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers ContaoCommunityAlliance\UsageStatistic\Client\Collector::checkInstallation
	 */
	public function testCheckInstallation()
	{
		$collector = new Collector();
		$class     = new \ReflectionClass($collector);
		$method    = $class->getMethod('checkInstallation');
		$method->setAccessible(true);

		$GLOBALS['TL_CONFIG'] = array('encryptionKey' => '');
		$this->assertFalse($method->invoke($collector));

		$GLOBALS['TL_CONFIG'] = array('encryptionKey' => 'abcdefg');
		$this->assertTrue($method->invoke($collector));
	}

	/**
	 * @covers ContaoCommunityAlliance\UsageStatistic\Client\Collector::checkServerAddress
	 */
	public function testCheckServerAddress()
	{
		$collector = new Collector();
		$class     = new \ReflectionClass($collector);
		$method    = $class->getMethod('checkServerAddress');
		$method->setAccessible(true);

		// check no addr available
		$_SERVER['SERVER_ADDR'] = '';
		$this->assertFalse($method->invoke($collector));

		// check addr available
		$_SERVER['SERVER_ADDR'] = '123.45.67.89';
		$this->assertTrue($method->invoke($collector));
	}

	/**
	 * @covers ContaoCommunityAlliance\UsageStatistic\Client\Collector::getInstallationId
	 */
	public function testGetInstallationId()
	{

	}
}
