<?php
/**
* DotBoost Technologies Inc.
* DotKernel v1.0
*
* @category   DotKernel
* @package    Frontend
* @copyright  Copyright (c) 2009 DotBoost  Technologies (http://www.dotboost.com)
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version    $Id$
*/

/**
* Page Model
* Dummy class, is not doing anything
* @category   DotKernel
* @package    Frontend 
* @author     DotKernel Team <team@dotkernel.com>
*/

class Page
{
	/**
	 * Constructor
	 * @access public
	 * @return Frontend_Page 
	 */
	public function __construct()
	{		
		$this->db = Zend_Registry::get('database');
		$this->config = Zend_Registry::get('configuration');
		$this->settings = Zend_Registry::get('settings');		
	}
	
}