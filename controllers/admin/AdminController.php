<?php
/**
* DotBoost Technologies Inc.
* DotKernel v1.0
*
* @category   DotKernel
* @package    Admin
* @copyright  Copyright (c) 2009 DotBoost  Technologies (http://www.dotboost.com)
* @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version    $Id$
*/

/**
* Admin Controller
* @author     DotKernel Team <team@dotkernel.com>
*/

$adminView = new Admin_View($tpl);
$adminModel = new Admin();
// switch based on the action, NO default action here
$pageTitle = $scope->pageTitle->action->{$requestAction};
switch ($requestAction)
{
	case 'login':
		// Show the Login form
		$adminView->loginForm('login');
	break;	
	case 'logout':
		Dot_Auth::clearIdentity('admin');
		header('location: '.$config->website->params->url.'/' . $requestModule);
		exit;
	break;	
	case 'authorize':	
		// validate the authorization request parameters 
		$validate = $adminModel->validateLogin($_POST['username'], $_POST['password'], $_POST['send']);
		if(!empty($validate['login']) && empty($validate['error']))
		{
			// login info are VALID, we can see if is a valid user now 
			$user = $adminModel->checkLogin($validate['login']);
			if(!empty($user))
			{
				$session->admin = $user[0];
				header('Location: '.$config->website->params->url.'/' . $requestModule );
				exit;
			}
			else
			{
				unset($session->admin);
				$session->message['txt'] = $scope->errorMessage->wrongCredentials;
				$session->message['type'] = 'error';
				header('Location: '.$config->website->params->url. '/' . $requestModule . '/' . $requestController. '/login');
				exit;				
			}
		}
		else
		{
			// login info are NOT VALID
			$session->message['txt'] = array($validate['error']['username'], $validate['error']['password']);
			$session->message['type'] = 'error';
			header('Location: '.$config->website->params->url. '/' . $requestModule . '/' . $requestController. '/login');
			exit;
		}			
	break;
	case 'account':
		$data = $adminModel->getAdminInfo($session->admin['id']);
		$adminView->details('update',$data);	
	break;
	case 'list':
		$page = (isset($request['page'])) ? $request['page'] : 1;
		$users = $adminModel->getUserList($page);		
		$adminView->listUser('list', $users,$page);	
	break;	
	case 'add':
		$data = array();
		$error = array();
		if(array_key_exists('send', $_POST) && 'on' == $_POST['send'])
		{						
			$values = array('username' => 
								array('username' => $_POST['username']
									 ),
							'details' => 
								array('firstName' => $_POST['firstName'],
									  'lastName' => $_POST['lastName']
									 ),
							'email' => array('email' => $_POST['email']),
							'password' => array('password' => $_POST['password'],
												'password2' =>  $_POST['password2']
											   )
						  );
			$valid = $adminModel->validateUser($values);
			$data = $valid['data'];
			$error = $valid['error'];
			if(empty($error))
			{	
				//check if admin already exists by $field ('username','email')
				$checkBy = array('username','email');
				foreach ($checkBy as $field)
				{					
				   	$adminExists = $adminModel->getAdminBy($field, $data[$field]);
					if(!empty($adminExists))
					{
						$session->message['txt'] = $data[$field].$scope->errorMessage->adminExists;
						$session->message['type'] = 'error';
					}
				}	
			}
			if(empty($error))
			{
				//add admin user
				$adminModel->addUser($data);				
				$session->message['txt'] = $scope->infoMessage->accountAdd;
				$session->message['type'] = 'info';
				header('Location: '.$config->website->params->url. '/' . $requestModule . '/' . $requestController. '/list/');
				exit;	
				
			}
			else
			{				
				$session->message['txt'] = $error;
				$session->message['type'] = 'error';
			}
		}
		$adminView->details('add',$data);		
	break;
	case 'update':
		$error = array();
		if(array_key_exists('send', $_POST) && 'on' == $_POST['send'])
		{						
			$values = array('details' => 
								array('firstName'=>$_POST['firstName'],
									  'lastName'=>$_POST['lastName']
									 ),
							'email' => array('email' => $_POST['email']),
							'password' => array('password' => $_POST['password'],
												'password2' =>  $_POST['password2']
											   )
						  );
			$valid = $adminModel->validateUser($values);
			$data = $valid['data'];
			$error = $valid['error'];			
			if(empty($valid['error']))
			{
				$data['id'] = $request['id'];
				//add admin user
				$adminModel->updateUser($data);
				$session->message['txt'] = $scope->infoMessage->accountUpdate;
				$session->message['type'] = 'info';
				header('Location: '.$config->website->params->url. '/' . $requestModule . '/' . $requestController. '/list/');
				exit;				
			}
			else
			{
				$session->message['txt'] = $error;
				$session->message['type'] = 'error';
			}
		}
		$data = $adminModel->getAdminInfo($request['id']);
		$adminView->details('update',$data);	
	break;
}

