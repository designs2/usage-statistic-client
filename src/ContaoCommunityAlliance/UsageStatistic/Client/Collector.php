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
		// if encryption key is empty, the installation is not set up yet
		return !empty($GLOBALS['TL_CONFIG']['encryptionKey']);
	}

	/**
	 * Check if the server address is public and non local.
	 *
	 * @return bool
	 */
	protected function checkServerAddress()
	{
		// ip address is missing (e.g. cli mode)
		if (empty($_SERVER['SERVER_ADDR'])) {
			return false;
		}

		return true;
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
			// host name is unique for one machine, even the machine have multiple IPs
			gethostname(),
			// the installation path can only hold ONE installation ;-)
			TL_ROOT,
			// add more uniqueness, the encryption key should be differ in each installation
			$GLOBALS['TL_CONFIG']['encryptionKey']
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
					if (
						is_array($package) &&
						(
							// do not track proprietary packages ...
							!empty($package['license']) && $package['license'] != 'proprietary' ||
							// ... unless the extension allow us to track
							isset($package['extra']['contao']['usage-statistic']) &&
							true === $package['extra']['contao']['usage-statistic']
						)
					) {
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
