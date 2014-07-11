<?php
/**
 * DotBoost Technologies Inc.
 * DotKernel Application Framework
 *
 * @category   DotKernel
 * @package    Api
 * @copyright  Copyright (c) 2009-2014 DotBoost Technologies Inc. (http://www.dotboost.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version    $Id$
 */
 
 /**
 * Command line interface bootstrap
 * example usage:
 *    /var/www/example.com/httpdocs/Api/index.php -e development -a action param1 param2
 * @author     DotKernel Team <team@dotkernel.com>
 */

$startTime = microtime();

// Define PATH's (absolute paths)  to configuration and DotKernel  directories
$rootPath = realpath(dirname(__FILE__) . "/..");
chdir($rootPath);
set_include_path(implode(PATH_SEPARATOR, array($rootPath . '/library', get_include_path())));
defined('CONFIGURATION_PATH') || define('CONFIGURATION_PATH', $rootPath.'/configs');
defined('DOTKERNEL_PATH') || define('DOTKERNEL_PATH', $rootPath.'/DotKernel');

// Load Zend Framework
require_once 'Zend/Loader/Autoloader.php';
$zendLoader = Zend_Loader_Autoloader::getInstance();
$zendLoader->registerNamespace('Dot_');
$zendLoader->registerNamespace('Api_');

// Define application environment
define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Create registry object, as read-only object to store there config, settings, and database
$registry = new Zend_Registry(array(), ArrayObject::ARRAY_AS_PROPS);
Zend_Registry::setInstance($registry);

$registry->startTime = $startTime;

// Load configuration settings from application.ini file and store it in registry
$config = new Zend_Config_Ini(CONFIGURATION_PATH.'/application.ini', APPLICATION_ENV);
$registry->configuration = $config;
// Create  connection to database, as singleton , and store it in registry
$db = Zend_Db::factory('Pdo_Mysql', $config->database->params->toArray());
$registry->database = $db;

// Load specific configuration settings from database, and store it in registry
$settings = Dot_Settings::getSettings();
$registry->settings = $settings;
$registry->option = array();

// Set PHP configuration settings from application.ini file
Dot_Settings::setPhpSettings($config->phpSettings->toArray());

// Get the action and the other arguments
$params = array();
$params = $_GET;
$registry->action = $params['action'];
unset($params['action']);
$registry->arguments = $params;

if (!$registry->configuration->api->params->enable)
{
	header("HTTP/1.0 403 Forbidden");
	exit;
}

include('Controller.php');