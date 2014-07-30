<?php

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

		// check IPv4
		for (
			$ip = 0x00000000;
			$ip < 0xff000000;
			$ip += 0x10000
		) {
			$addr = array(
				(($ip >> 24) % 0xff),
				(($ip >> 16) % 0xff),
				(($ip >> 8) % 0xff),
				($ip % 0xff),
			);

			$local =
				// local address
				$addr[0] === 127 ||
				// class A network
				$addr[0] === 10 &&
				($addr[1] >= 0 && $addr[1] <= 255) ||
				// class B network
				$addr[0] === 172 &&
				($addr[1] >= 16 && $addr[1] <= 31) ||
				// class C network
				$addr[0] === 192 &&
				$addr[1] === 168;

			$_SERVER['SERVER_ADDR'] = implode('.', $addr);
			$this->assertEquals(
				!$local,
				$method->invoke($collector),
				'IP ' . $_SERVER['SERVER_ADDR'] . ' was' . ($local ? ' not' : '') . ' guessed as local!'
			);
		}
		// check IPv6
		for (
			$ip = 0x0000;
			$ip < 0xffff;
			$ip += 0x0001
		) {
			$local =
				// link local
				$ip >= 0xfe80 && $ip <= 0xfebf ||
				// site local unicast
				$ip >= 0xfec0 && $ip <= 0xfeff ||
				// unique local unicast
				$ip >= 0xfc00 && $ip <= 0xfdff;

			$_SERVER['SERVER_ADDR'] = dechex($ip) . ':1:2:3:4:5:6:7';
			$this->assertEquals(
				!$local,
				$method->invoke($collector),
				'IP ' . $_SERVER['SERVER_ADDR'] . ' was' . ($local ? ' not' : '') . ' guessed as local!'
			);
		}
	}

	/**
	 * @covers ContaoCommunityAlliance\UsageStatistic\Client\Collector::generateInstallationId
	 */
	public function generateInstallationId()
	{

	}
}
