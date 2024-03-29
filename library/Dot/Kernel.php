<?php
/**
 * DotBoost Technologies Inc.
 * DotKernel Application Framework
 *
 * @category   DotKernel
 * @copyright  Copyright (c) 2009-2015 DotBoost Technologies Inc. (http://www.dotboost.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version    $Id$
 */

/**
 * Bunch of miscelaneous  functions, used in all DotKernel Applications
 * @category   DotKernel
 * @package    DotLibrary
 * @author     DotKernel Team <team@dotkernel.com>
 */

class Dot_Kernel
{
	/**
	 * Constant
	 * Dot Kernel version identification
	 * @var string
	 */
	const VERSION = '1.8.1';
	
	/**
 	 * Start DotKernel
	 * Pass controll to the Front Controller if it exists,
	 * otherwise throw a 404 error
	 */
	public static function gallop()
	{
		$registry = Zend_Registry::getInstance();

		$frontControllerPath = CONTROLLERS_PATH . '/' . $registry->requestModule . '/' . 'IndexController.php';
		if (file_exists($frontControllerPath))
		{
			require($frontControllerPath);
		}
		else
		{
			Dot_Route::pageNotFound();
		} 
	}
	/**
	 * Initializes the Zend_Registry
	 * 
	 * Was Part of main initialize method
	 * 
	 * @access private
	 * @static
	 * @return boolean
	 */
	private static function _initializeRegistry()
	{
		$registry = new Zend_Registry(array(), ArrayObject::ARRAY_AS_PROPS);
		Zend_Registry::setInstance($registry);
		return $registry;
	}
	
	/**
	 * Load Router from Cache
	 * 
	 * Was Part of main initialize method
	 * Returns Router Configuration from Cache
	 * If cache is not available, it will be loaded from file
	 * 
	 * @access private
	 * @static
	 * @return Zend_Config_Xml
	 */
	private static function _loadRouter()
	{
		$router = Dot_Cache::load('router');
		if($router != false)
		{
			return $router;
		}
		$router = new Zend_Config_Xml(CONFIGURATION_PATH.'/router.xml');
		Dot_Cache::save($router, 'router');
		return $router;
	}
	
	/**
	 * Load Plugin Configuration from Cache
	 *
	 * Was Part of main initialize method
	 * Returns Plugin Configuration from Cache
	 * If cache is not available, it will be loaded from file
	 *
	 * @access private
	 * @static
	 * @return Zend_Config_Xml
	 */
	private static function _loadPluginConfiguration()
	{
		$pluginConfig = Dot_Cache::load('plugin_configuration');
		if($pluginConfig != false)
		{
			// no time to waste 
			return $pluginConfig;
		}
		// config needed
		$config = Zend_Registry::get('configuration');
		
		$pluginConfig = new Zend_Config_Ini(CONFIGURATION_PATH.'/plugins.ini', APPLICATION_ENV);
		
		// only save the settings if plugin_config caching is enabled
		if($config->cache->cache_plugin_config)
		{
			Dot_Cache::save($pluginConfig, 'plugin_configuration');
		}
		return $pluginConfig;
	}
	
	/**
	 * Initialize the global variables 
	 * @access public 
	 * @static
	 * @param int $startTime
	 * @return bool $success
	 */
	public static function initialize($startTime)
	{
		// Create registry object, as read-only object to store there config, settings, and database
		$registry = self::_initializeRegistry();

		// mark the start time
		$registry->startTime = $startTime;

		//Load configuration settings from application.ini file and store it in registry
		$config = new Zend_Config_Ini(CONFIGURATION_PATH.'/application.ini', APPLICATION_ENV);
		$registry->configuration = $config;
		
		//start the cache
		Dot_Cache::loadCache();
		
		//Load routes(modules, controllers, actions) settings from router.xml file and store it in registry
		$registry->router = self::_loadRouter();

		// load the plugin configuration
		$registry->pluginConfiguration = self::_loadPluginConfiguration();
		
		//Load configuration settings from application.ini file and store it in registry
		$config = new Zend_Config_Ini(CONFIGURATION_PATH.'/application.ini', APPLICATION_ENV);
		$registry->configuration = $config;
		
		
		// Create  connection to database, as singleton , and store it in registry
		$db = Zend_Db::factory('Pdo_Mysql', $config->database->params->toArray());
		$registry->database = $db;

		//Load specific configuration settings from database, and store it in registry
		$settings = Dot_Settings::getSettings();
		$registry->settings = $settings;
		
		//Set PHP configuration settings from application.ini file
		Dot_Settings::setPhpSettings($config->phpSettings->toArray());

		// Extract the route from the URI
		Dot_Route::setRoute();
		
		// initialize seo options
		$registry->seo = Dot_Route::getOption();

		// initialize default options for dots that may be overwritten
		$option = Dot_Settings::getOptionVariables($registry->requestModule, 'default');
		$registry->option = $option;
		return true;
	}
	
	/**
	 * Check if IP is valid. Return FALSE | 'private' | 'public'
	 * FALSE - $ip is not a valid IP address
	 * private - $ip is a private network IP address, loopback address or an IPv6 IP address
	 * public - $ip is a public network IP address
	 * @access public
	 * @static
	 * @param string $ip
	 * @return mixt [false | 'private' | 'public']
	 */
	public static function validIp($ip)
	{
		// special cases that return private are the loopback address and IPv6 addresses
		if ($ip == '127.0.0.1' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
		{
			return 'private';
		}
		// check if the ip is valid
		if (filter_var($ip, FILTER_VALIDATE_IP))
		{
			// check wether it's private or not
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE))
			{
				return 'public';
			}
			return 'private';
		}
		// not a valid ip
		return false;
	}
	/**
	 * Return the user Ip, even if it is behind a proxy
	 * @access public
	 * @static
	 * @return string
	 */
	public static function getUserIp()
	{
		if (isset($_SERVER))
		{
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && self::validIp($_SERVER['HTTP_X_FORWARDED_FOR']) == 'public')
			{
				// check if HTTP_X_FORWARDED_FOR is public network IP
				$realIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			elseif (isset($_SERVER['HTTP_CLIENT_IP']) && self::validIp($_SERVER['HTTP_CLIENT_IP']) == 'public')
			{
				// check if HTTP_CLIENT_IP is public network IP
				$realIp = $_SERVER['HTTP_CLIENT_IP'];
			}
			else
			{
				$realIp = $_SERVER['REMOTE_ADDR'];
			}
		}
		else
		{
			if (getenv('HTTP_X_FORWARDED_FOR') && self::validIp(getenv('HTTP_X_FORWARDED_FOR')) == 'public')
			{
				// check if HTTP_X_FORWARDED_FOR is public network IP
				$realIp = getenv('HTTP_X_FORWARDED_FOR');
			}
			elseif (getenv('HTTP_CLIENT_IP') && self::validIp(getenv('HTTP_CLIENT_IP')) == 'public')
			{
				// check if HTTP_CLIENT_IP is public network IP
				$realIp = getenv('HTTP_CLIENT_IP');
			}
			else
			{
				$realIp = getenv('REMOTE_ADDR');
			}
		}
		return $realIp;
	}

	/**
	 * Return date formatted fancy
	 * @access public
	 * @static
	 * @param string $date
	 * @param string $format - 'short', 'long'
	 * @return string
	 */
	public static function timeFormat($date, $format='short')
	{
		$settings = Zend_Registry::get('settings');
		$times = strtotime($date);
		switch($format)
		{
			case 'long':
				$times = strftime($settings->timeFormatLong,$times);
			break;
			case 'short':
			default:
				$times = strftime($settings->timeFormatShort,$times);
			break;
		}
		return $times;
	}
}