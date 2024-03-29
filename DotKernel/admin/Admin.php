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
 * Admin Model
 * Here are all the actions related to the admin users
 * @category   DotKernel
 * @package    Admin 
 * @author     DotKernel Team <team@dotkernel.com>
*/

class Admin extends Dot_Model
{
	private $_userAgent;
	private $_httpReferer;
	
	/**
	 * Constructor
	 * @access public
	 */
	public function __construct($userAgent = null, $httpReferer=null)
	{
		parent::__construct();
		// if no userAgent is given on function call mark it as empty - if the userAgent is empty keep it empty
		// if the userAgent stays empty it can be used for robot detecting or devices with blank UA (usually bots)
		// HTTP Reffer is optional so mark it empty if there is no HTTP Reffer
		$this->_userAgent = (!is_null($userAgent)) ? $userAgent : '';
		$this->_httpReferer = (!is_null($httpReferer)) ? $httpReferer : '';
		
		$this->seo = Zend_Registry::get('seo');
	}

	/**
	 * Get admin by field
	 * @access public
	 * @param string $field
	 * @param string $value
	 * @return array
	 */
	public function getUserBy($field = '', $value = '')
	{
		$select = $this->db->select()
							->from('admin')
							->where($field . ' = ?', $value)
							->limit(1);
		$result = $this->db->fetchRow($select);
		return $result;
	}

	/**
	 * Get user list
	 * @access public 
	 * @param int $page [optional]
	 * @return array
	 */
	public function getUserList($page = 1)
	{
		$select = $this->db->select()->from('admin');
		$dotPaginator = new Dot_Paginator($select, $page, $this->settings->resultsPerPage);
		return $dotPaginator->getData();
	}

	/**
	 * Add new user
	 * @access public
	 * @param array $data
	 * @return void
	 */
	public function addUser($data)
	{
		// if you want to add an inactive user, un-comment the below line, default: isActive = 1
		// $data['isActive'] = 0;
		

		// start the Password API object
		$passwordApi = new Dot_Password();
		$data['password'] = $passwordApi->hashPassword($data['password'], PASSWORD_DEFAULT);
		$this->db->insert('admin', $data);
	}

	/**
	 * Update user
	 * @access public
	 * @param array $data
	 * @return void
	 */
	public function updateUser($data)
	{
		$id = $data['id'];
		unset($data['id']);
		if(array_key_exists('password', $data))
		{
			$user = $this->getUserBy('id', $id);
			// start the Password API object
			$passwordApi = new Dot_Password();
			$data['password'] = $passwordApi->hashPassword($data['password'], PASSWORD_DEFAULT);
		}
		$this->db->update('admin', $data, 'id = ' . $id);
	}

	/**
	 * Delete admin user
	 * @access public
	 * @param int $id
	 * @return void
	 */
	public function deleteUser($id)
	{
		$this->db->delete('admin', 'id = ' . $id);
	}

	/**
	 * Update active field for admin user
	 * @access public 
	 * @param int $id
	 * @param int $isActive
	 * @return void
	 */
	public function activateUser($id, $isActive)
	{
		$this->db->update('admin', array('isActive' => $isActive), 'id = ' . $id);
	}

	/**
	 * Register logins data
	 * @access public 
	 * @param array $data
	 * @return void
	 */
	public function registerLogin($data)
	{
		$this->db->insert('adminLogin', $data);
	}

	/**
	 * Get admin users logins archive list
	 * @access public
	 * @param int $id 
	 * @param int $page [optional]
	 * @return array
	 */
	public function getLogins($id, $page = 1)
	{
		$select = $this->db->select()
						->from('adminLogin')
						->joinLeft(
							'admin',
							'adminLogin.adminId=admin.id',
							'username'
						);
		if($id > 0)
		{
			$select->where('adminId = ?', $id);
		}
		$select->order('dateLogin DESC');
		$dotPaginator = new Dot_Paginator($select, $page, $this->settings->resultsPerPage);
		return $dotPaginator->getData();
	}

	/**
	 * Authorize user login
	 * @access public
	 * @param array $validData
	 * @return void
	 */
	public function authorizeLogin($validData)
	{
		$session = Zend_Registry::get('session');
		unset($session->admin);
		// login info are VALID, we can see if is a valid user now	
		$dotAuth = Dot_Auth::getInstance();
		$validAuth = $dotAuth->process('admin', $validData);
		if($validAuth)
		{
			//prepare data for register the login
			$dataLogin = array('ip' => Dot_Kernel::getUserIp(), 
								'adminId' => $session->admin->id,
								'referer' => $this->_httpReferer,
								'userAgent' => $this->_userAgent);
			$this->registerLogin($dataLogin);
			header('Location: ' . $this->config->website->params->url . '/' . Zend_Registry::get('requestModule'));
			exit();
		}
		else
		{ // failed admin login - send email to valid admin account
			$this->sendEmailFailedLogin($validData);
			// check if account is inactive
			$adminTmp = $this->getUserBy('username', $validData['username']);
			(1 == $adminTmp['isActive']) ?
				$session->message['txt'] = $this->option->errorMessage->wrongCredentials:
				$session->message['txt'] = $this->option->errorMessage->inactiveAcount;
			$session->message['type'] = 'error';
		}
	}
	
	/**
	 * Failed admin login - send email notice to valid admin account
	 * @access private
	 * @param arry $values
	 * @return void
	 */
	private function sendEmailFailedLogin($values)
	{
		// get all  admin list
		$emailAdminList = explode(',', $this->settings->devEmails);
		
		$dotEmail = new Dot_Email();
		
		// Add each admin 
		foreach($emailAdminList as $emailAdmin)
		{
			$dotEmail->addTo($emailAdmin);
		}
		$dotEmail->setSubject($this->seo->siteName . ' - ' . $this->option->failedLogin->subject);
		$dotGeoip = new Dot_Geoip();
		$country = $dotGeoip->getCountryByIp(Dot_Kernel::getUserIp());
		$msg = str_replace(array('%LINK%', '%USERNAME%', '%PASSWORD%', '%DATE%', '%COUNTRY%', '%IP%', '%USERAGENT%'), 
							array($this->config->website->params->url.'/' .  Zend_Registry::get('requestModule'), 
								 $values['username'], 
								 $values['password'], 
								 Dot_Kernel::timeFormat('now', 'long'), 
								 $country[1], 
								 Dot_Kernel::getUserIp(), 
								 $this->_userAgent),
								$this->option->failedLogin->message);
		$dotEmail->setBodyText($msg);
		$success = $dotEmail->send();
		return $success ;
	}

	/**
	 * Get last months admin's logins
	 * @access public
	 * @return array
	 */
	public function getAdminsTimeActivity($monthsBefore)
	{
		$fromDate = mktime(0, 0, 0, date('n') - ($monthsBefore - 1), date('j'), date('Y'));
		$fromDate = date('Y-m-01', $fromDate);
		$select = $this->db->select()
			->from(array('a' => 'adminLogin'), array('dateLogin'))
			->where('dateLogin >= ?', $fromDate);
		$resultLogins = $this->db->fetchAll($select);
		
		$daySec = 86400; // Day in seconds
		$fTime = strtotime($fromDate);
		$nTime = strtotime(date('Y-m-d'));
		$result = array();
		$series = array();
		
		for($i = $fTime; $i <= $nTime; $i += $daySec)
		{
			$month = date('F', $i);
			$day = date('j', $i);
			$result[$month][$day] = 0;
		}
		
		foreach($resultLogins as $login)
		{
			$login = explode(' ', $login['dateLogin']);
			$login = strtotime($login[0]);
			
			$month = date('F', $login);
			$day = date('j', $login);
			if(! isset($result[$month][$day]))
			{
				$result[$month][$day] = 1;
			}
			else
			{
				$result[$month][$day] ++;
			}
		}
		$i = 0;
		foreach($result as $month => $days)
		{
			$series[$i] = new stdClass();
			$series[$i]->name = $month;
			$series[$i]->data = array_merge($days, array());
			$i ++;
		}
		return $series;
	}
}