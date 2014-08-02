<?php

namespace ContaoCommunityAlliance\UsageStatistic\Client;

use ContaoCommunityAlliance\Contao\Events\Cron\CronEvents;
use Guzzle\Http\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Collector
	implements EventSubscriberInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents()
	{
		return array(
			CronEvents::DAILY             => 'run',
			CollectorEvents::COLLECT_DATA => array(
				array('collectContaoVersion'),
				array('collectExtensionRepository2Packages'),
				array('collectComposerPackages'),
			),
		);
	}

	/**
	 * Check if the installation is complete and ready to run.
	 *
	 * @return bool
	 */
	protected function checkInstallation()
	{
		return !empty($GLOBALS['TL_CONFIG']['encryptionKey']);
	}

	/**
	 * Check if the server address is public and non local.
	 *
	 * @return bool
	 */
	protected function checkServerAddress()
	{
		// Address is missing (e.g. cli mode)
		if (empty($_SERVER['SERVER_ADDR'])) {
			return false;
		}

		// IPv4
		if (preg_match('~^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$~', $_SERVER['SERVER_ADDR'], $matches)) {
			$addr = hexdec(str_pad(dechex($matches[1]), 2, '0', STR_PAD_LEFT) . str_pad(dechex($matches[2]), 2, '0', STR_PAD_LEFT));

			if (
				// localhost
				($addr & 0xff00) === 0x7f00 ||
				// class A private networks
				($addr & 0xff00) === 0x0a00 ||
				// class B private network
				($addr & 0xfff0) === 0xac10 ||
				// class C private network
				($addr & 0xffff) === 0xc0a8
			) {
				return false;
			}

			return true;
		}

		// IPv6
		if (preg_match('~^([0-9a-f]{1,4})(:[0-9a-f]{0,4}){1,7}$~', $_SERVER['SERVER_ADDR'], $matches)) {
			$addr = hexdec($matches[1]);

			if (
				// link local
				($addr & 0xffc0) === 0xfe80 ||
				// site local unicast
				($addr & 0xffc0) === 0xfec0 ||
				// unique local unicast
				($addr & 0xfe00) === 0xfc00
			) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Generate a unique installation ID.
	 *
	 * @return string
	 */
	public function getInstallationId()
	{
		// Parameters used in the installation ID
		$parameters = array(
			gethostname(),
			TL_ROOT
		);

		// return an ID hash
		return hash('sha512', implode(PHP_EOL, $parameters));
	}

	/**
	 * Collect the usage statistics data.
	 *
	 * @return array
	 */
	protected function collect()
	{
		global $container;

		/** @var EventDispatcherInterface $eventDispatcher */
		$eventDispatcher = $container['event-dispatcher'];

		$event = new CollectDataEvent();
		$eventDispatcher->dispatch(CollectorEvents::COLLECT_DATA, $event);

		return $event->getData();
	}

	/**
	 * Send the usage statistics data to the server.
	 *
	 * @param array $data The collected usage data.
	 */
	protected function send($id, array $data)
	{
		try {
			// build the request body
			$body       = new \stdClass();
			$body->id   = $id;
			$body->data = $data;

			// encode the request body
			$body = json_encode($body);

			// send the data
			$client  = new Client();
			$request = $client->put(
				'http://statistic.c-c-a.org/collect',
				null,
				$body
			);
			$request->send();
		}
		catch (\Exception $e) {
			log_message(
				'Could not send usage statistics: [' . get_class($e) . '] ' . $e->getMessage(
				) . PHP_EOL . $e->getTraceAsString()
			);
		}
	}

	/**
	 * Collect the usage statistics.
	 */
	public function run()
	{
		// Skip incomplete and non public installations, e.g. installations run on a CI server or on localhost.
		if (
			!$this->checkInstallation() ||
			!$this->checkServerAddress()
		) {
			return;
		}

		$id   = $this->getInstallationId();
		$data = $this->collect();
		$this->send($id, $data);
	}

	/**
	 * Collect the contao version.
	 *
	 * @param CollectDataEvent $event
	 */
	public function collectContaoVersion(CollectDataEvent $event)
	{
		$event->set('contao.version', VERSION . '.' . BUILD);
	}

	/**
	 * If extension repository client is active, collect extension installation statistics.
	 *
	 * @param CollectDataEvent $event
	 */
	public function collectExtensionRepository2Packages(CollectDataEvent $event)
	{
		$database = \Database::getInstance();

		if (
			$database->tableExists('tl_repository_installs') && (
				in_array('rep_client', \Config::getInstance()->getActiveModules()) ||
				in_array('repository', \Config::getInstance()->getActiveModules())
			)
		) {
			$resultSet = $database->query('SELECT * FROM tl_repository_installs');

			while ($resultSet->next()) {
				$version = $resultSet->version;
				$status  = $version % 10;
				$version = (int) ($version / 10);
				$micro   = $version % 1000;
				$version = (int) ($version / 1000);
				$minor   = $version % 1000;
				$major   = (int) ($version / 1000);

				switch ($status) {
					case 0:
						$status = 'alpha1';
						break;
					case 1:
						$status = 'alpha2';
						break;
					case 2:
						$status = 'alpha3';
						break;
					case 3:
						$status = 'beta1';
						break;
					case 4:
						$status = 'beta2';
						break;
					case 5:
						$status = 'beta3';
						break;
					case 6:
						$status = 'RC1';
						break;
					case 7:
						$status = 'RC2';
						break;
					case 8:
						$status = 'RC3';
						break;
					case 0:
						$status = 'stable';
						break;
					default:
						$status = 'dev';
				}

				$event->set(
					'installed.extension.' . $resultSet->extension . '.version',
					$major . '.' . $minor . '.' . $micro . '.' . $resultSet->build . '-' . $status
				);
			}
		}
	}

	/**
	 * If composer is installed and active, collect package installation statistics.
	 *
	 * @param CollectDataEvent $event
	 */
	public function collectComposerPackages(CollectDataEvent $event)
	{
		if (
			in_array('!composer', \Config::getInstance()->getActiveModules()) &&
			file_exists(TL_ROOT . '/composer/vendor/composer/installed.json')
		) {
			$installed = file_get_contents(TL_ROOT . '/composer/vendor/composer/installed.json');
			$installed = json_decode($installed, true);

			if (is_array($installed)) {
				foreach ($installed as $package) {
					if (is_array($package)) {
						$version = $package['version'];
						$source  = $package['installation-source'];

						if (preg_match('~(^dev-|-dev$)~', $version)) {
							$version .= '#' . $package[$source]['reference'];
						}

						$event->set(
							'installed.package.' . $package['name'] . '.version',
							$version
						);

						if ($package['type'] !== 'metapackage') {
							$event->set(
								'installed.package.' . $package['name'] . '.installation-source',
								$source
							);
						}
					}
				}
			}
		}
	}
}
