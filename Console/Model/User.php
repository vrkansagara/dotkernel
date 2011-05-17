<?php
/**
* DotBoost Technologies Inc.
* DotKernel Application Framework
*
* @category   DotKernel
* @package    CLI
* @subpackage User
* @copyright  Copyright (c) 2009-2011 DotBoost Technologies Inc. Canada (http://www.dotboost.com)
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version    $Id: Smtp.php 397 2011-01-13 12:56:27Z teo $
* @author     DotKernel Team <team@dotkernel.com>
*/

class Console_Model_User extends Dot_Model_User
{
	/**
	 * Email constructor
	 * @access public 
	 * @return Dot_Model_User
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	public function countUsers()
	{
		$select = $this->db->select()
			->from('user', array('cnt' => 'count(id)'));
		$result = $this->db->fetchRow($select);
		return $result['cnt'];
	}
}