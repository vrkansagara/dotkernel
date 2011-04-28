<?php
/**
* DotBoost Technologies Inc.
* DotKernel Application Framework
*
* @category   DotKernel
* @package    Admin
* @copyright  Copyright (c) 2009-2011 DotBoost Technologies Inc. Canada (http://www.dotboost.com)
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version    $Id$
*/

/**
* System Controller
* @author     DotKernel Team <team@dotkernel.com>
*/

$systemView = new System_View($tpl);
$systemModel = new System();
// switch based on the action, NO default action here
$pageTitle = $option->pageTitle->action->{$registry->route['action']};
switch ($registry->route['action'])
{
	case 'dashboard':
		$mysqlVersion = $systemModel->getMysqlVersion();
		$geoIpVersion = $systemModel->getGeoIpVersion();
		$wurflInfo = $systemModel->getWurflInfo();
		$warnings = $systemModel->getWarnings($wurflInfo);
		$apcInfo = $systemModel->getAPCInfo();
		$systemView->dashboard('dashboard', $mysqlVersion, $apcInfo, $geoIpVersion, $wurflInfo, $warnings);
	break;
	case 'settings':
		// list settings values
		$data = $systemModel->getSettings();	
		if(isset($registry->request['update']) && $registry->request['update'] == 'done')
		{			
				$registry->session->message['txt'] = $option->infoMessage->settingsUpdate;
				$registry->session->message['type'] = 'info';
		}
		$systemView->displaySettings('settings', $data);
	break;
	case 'settings-update':
		// update settings value
		$data = array();
		$error = array();
		if(array_key_exists('send', $_POST) && 'on' == $_POST['send'])
		{
			unset($_POST['send']);
			$systemModel->updateSettings($_POST);
			header('Location: '.$registry->configuration->website->params->url. '/' . $registry->route['module'] . '/' . $registry->route['controller']. '/settings/update/done');
			exit;
		}
	break;
	case 'build-wurfl-cache':
		// creates Zend_Http_UserAgent objects with the 15 most common mobile user agents to build the cache
		$userAgents = array(
			'Mozilla/5.0 (Linux; U; Android 1.6; en-us; T-Mobile G1 Build/DMD64) AppleWebKit/528.5+ (KHTML, like Gecko) Version/3.1.2 Mobile Safari/525.20.1',
			'sam-r350 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
			'Mozilla/4.0 (BREW 3.1.5; U; en-us; Sanyo; NetFront/3.5.1/AMB) Boost SCP6760',
			'HUAWEI-M750/001.00 ACS-NetFront/3.2',
			'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_1 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Mobile/8B117',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; T-Mobile_LEO; Windows Phone 6.5)',
			'Mozilla/4.0 (BREW 3.1.5; U; en-us; Sanyo; NetFront/3.5.1/AMB) Boost SCP3810',
			'Mozilla/5.0 (iPod; U; CPU iPhone OS 4_1 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Mobile/8B117',
			'Mozilla/5.0 (iPod; U; CPU iPhone OS 3_1_3 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Mobile/7E18',
			'Mozilla/4.0 (BREW 3.1.5; U; en-us; Sanyo; Polaris/6.0/AMB) Boost SCP-2700',
			'sam-r560 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
			'LGE-MN240/1.0 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
			'Mozilla/5.0 (rv:1.3; BREW 3.1.5; en)/400x240 sam-r860 like Gecko/20080531 NetFront/3.5',
			'sam-r100 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
			'Cricket-A200/1.0 UP.Browser/6.3.0.7 (GUI) MMP/2.0'
		);

		foreach ($userAgents as $ua)
		{
			$device = new Zend_Http_UserAgent($registry->configuration->resources->useragent);
			$device->setUserAgent($ua);
			$d=$device->getDevice();
		}
		$settings = Zend_Registry::get('settings');
		$systemModel->updateSettings(array('wurflCacheBuilt'=>strftime($settings->timeFormatLong)));
		header('Location: '.$registry->configuration->website->params->url. '/admin');
		exit();
	break;
	case 'phpinfo':
		// display phpinfo()
		$systemView->showPHPInfo('phpinfo');
	break;
	case 'apc-info':
		// display apc.php
		$systemView->showAPCInfo();
	break;
	case 'transporter-list':
		$page = (isset($registry->request['page']) && $registry->request['page'] > 0) ? $registry->request['page'] : 1;
		$transporters = $systemModel->getEmailTransporterList($page);		
		$systemView->listEmailTransporter('transporter-list', $transporters, $page);	 
	break;
	case 'transporter-activate':
		$id = (isset($_POST['id'])) ? (int)$_POST['id'] : 0;
		$isActive = (isset($_POST['isActive'])) ? $_POST['isActive'] : 0;
		$page = (isset($_POST['page'])) ? (int)$_POST['page'] : 1;
		$systemModel->activateEmailTransporter($id, $isActive);		

		$transporters = $systemModel->getEmailTransporterList($page);
		$registry->session->useAjaxView = true; 
		$route['action'] = 'transporter-list';
		$registry->route = $route;
		$systemView->listEmailTransporter('transporter-list', $transporters, $page, true);
	break;
	case 'transporter-delete':
		if(array_key_exists('send', $_POST) && 'on' == $_POST['send'])
		{ 
			if ('on' == $_POST['confirm'])
			{
				$systemModel->deleteEmailTransporter($registry->request['id']);
				$registry->session->message['txt'] = $option->infoMessage->transporterDelete;
				$registry->session->message['type'] = 'info';
			}
			else
			{
				$registry->session->message['txt'] = $option->infoMessage->noTransporterDelete;
				$registry->session->message['type'] = 'info';
			}
		 header('Location: '.$registry->configuration->website->params->url. '/' . $registry->route['module'] . '/' . $registry->route['controller']. '/transporter-list/');
			 exit;	 
		}
		$data = $systemModel->getEmailTransporterBy('id', $registry->request['id']);
		// delete page confirmation
		$systemView->details('transporter-delete', $data);	
	break;
	case 'transporter-update':
		// display form and update user
		$error = array();
		if(array_key_exists('send', $_POST) && 'on' == $_POST['send'])
		{
			$data=$_POST;
			unset($data["send"]);
			
			$error=$systemModel->validateEmailTransporter($data);

			if(empty($error))
			{
				// no error - then update
				$data["id"]=$registry->request["id"];
				$systemModel->updateEmailTransporter($data);
				$registry->session->message['txt'] = $option->infoMessage->transporterUpdate;
				$registry->session->message['type'] = 'info';
				header('Location: '.$registry->configuration->website->params->url. '/' . $registry->route['module'] . '/' . $registry->route['controller']. '/transporter-list/');
				exit;
			}
			else
			{
				$registry->session->message['txt'] = $error;
				$registry->session->message['type'] = 'error';
			}
		}
		$data = $systemModel->getEmailTransporterBy('id', $registry->request['id']);
		$systemView->details('transporter-update',$data); 
	break;
	case 'transporter-add':
		$page = $_POST["page"];
		unset($_POST["page"]);
		$data=$_POST;
		unset($data["send"]);

		$error=$systemModel->validateEmailTransporter($data);
		
		if (empty($error))
		{
			$systemModel->addEmailTransporter($data);
			$registry->session->message['txt'] = $option->infoMessage->transporterAdd;
			$registry->session->message['type'] = 'info';
			$data=null;
		}else{
			$registry->session->message['txt'] = $error;
			$registry->session->message['type'] = 'error';
		}

		$transporters = $systemModel->getEmailTransporterList($page);
		$transporters['form']=$data;
		$registry->session->useAjaxView = true; 
		$route['action'] = 'transporter-list';
		$registry->route = $route;
		$systemView->listEmailTransporter('transporter-list', $transporters, $page, true, $error);
	break;
	case 'test':
		$userAgents = array(
		  'sam-r350 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'sam-r360 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'HUAWEI-M750/001.00 ACS-NetFront/3.2',
		  'LGE-MN240/1.0 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'KWC-Torino/ UP.Browser/7.2.7.2.561 (GUI) MMP/2.0',
		  'SCH-U360/1.0 NetFront/3.0.22.2.22 (GUI) MMP/2.0',
		  'sam-r560 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'Cricket-A310/1.0 UP.Browser/6.3.0.7 (GUI) MMP/2.0',
		  'SCH-U350/1.0 NetFront/3.0.22.2.18 (GUI) MMP/2.0',
		  'Mozilla/5.0 (rv:1.3; BREW 3.1.5; en)/400x240 sam-r860 like Gecko/20080531 NetFront/3.5',
		  'KWC-Glide/ UP.Browser/7.2.7.2.541 (GUI) MMP/2.0',
		  'Mozilla/5.0 (Linux; U; Android 2.2; en-us; Sprint APA9292KT Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
		  'Mozilla/4.0 (compatible; Polaris 6.2; Brew 3.1.5; en)/240X320 Samsung sam-r631',
		  'Mozilla/4.1 (compatible; Teleca Q7; BMP 1.0.1; U; en) 240X400 LG-MN510',
		  'HUAWEI-M228/001.00 ACCESS-NetFront/3.2(Mobile) Mozilla/5.0',
		  'Mozilla/5.0 (Linux; U; Android 2.1-update1; en-us; M860 Build/ERE27) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17',
		  'HUAWEI-M735/001.00 Opera/9.70',
		  'sam-r100 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'Mozilla/5.0 (Linux; U; Android 2.1-update1; en-us; HUAWEI-M860 Build/ERE27) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Mobile Safari/530.17',
		  'LGE-VX5600/1.0 UP.Browser/6.2.3.2 (GUI) MMP/2.0',
		  'sam-r570 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'sam-r450 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'Cricket-A300/1.0 UP.Browser/6.3.0.7 (GUI) MMP/2.0',
		  'Cricket-A210/1.0 UP.Browser/6.3.0.7 (GUI) MMP/2.0',
		  'Mozilla/5.0 (Linux; U; Android 2.2.2; en-us; LS670 Build/FRG83G) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
		  'Mozilla/5.0 (compatible; MSIE 6.0; BREW 4.0.3; en )/800x480 Samsung sam-r900',
		  'Mozilla/4.0 (PSP (PlayStation Portable); 2.00)',
		  'NetFront/3.5.1 (BREW 5.0.1.2; U; en-us; LG; NetFront/3.5.1/AMB) Sprint LN510 MMP/2.0 Profile/MIDP-2.1 Configuration/CLDC-1.1',
		  'sam-r420 UP.Browser/6.2.3.8 (GUI) MMP/2.0',
		  'BlackBerry8530/5.0.0.654 Profile/MIDP-2.1 Configuration/CLDC-1.1 VendorID/389',
		  'Mozilla/5.0 (X11; Linux i686) AppleWebKit/534.24 (KHTML, like Gecko) Chrome/11.0.696.50 Safari/534.24'
		);
		foreach($userAgents as $ua)
		{
			echo $ua;
			$os = Dot_Kernel::getOsIcon($ua);
			Zend_Debug::dump($os);
			echo Dot_Kernel::getBrowserIcon($ua)."<br>";
			echo Dot_Kernel::getBrowserIcon($ua, "browser")."<br>";
			echo '<img src="http://adi.dotboost.net/dotkernel/images/browsers/' . Dot_Kernel::getBrowserIcon($ua) . '.png"/>'."<br>";
			echo '<img src="http://adi.dotboost.net/dotkernel/images/os/' . $os['icon'] . '.png"/>'."<br>";
			echo "<hr>";
			//echo $ua." - ".Dot_Kernel::getOsIcon($ua)."<br/>";
		}
		exit();
	break;
}