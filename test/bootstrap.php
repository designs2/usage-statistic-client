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

error_reporting(E_ALL);

function includeIfExists($file)
{
	return file_exists($file) ? include $file : false;
}

if ((!$loader = includeIfExists(__DIR__.'/../vendor/autoload.php')) && (!$loader = includeIfExists(__DIR__.'/../../../autoload.php'))) {
	echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL.
		'curl -sS https://getcomposer.org/installer | php'.PHP_EOL.
		'php composer.phar install'.PHP_EOL;
	exit(1);
}
