<?php
/**
 * @author markus
 * $Id: LoginController.php 75 2010-02-25 14:40:10Z markus $
 */

require_once('helpers/GetEnv.php');
require_once('config/Config.php');

class LoginController extends Zend_Controller_Action
{

    public function init() {
        /* Initialize action controller here */
    	$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

    	$db = Zend_Db::factory($config->ca_mgr->db->auth->pdo, $config->ca_mgr->db->auth);
		Zend_Registry::set('auth_dbc', $db);
    	$db2 = Zend_Db::factory($config->ca_mgr->db->auth2->pdo, $config->ca_mgr->db->auth2);
		Zend_Registry::set('auth2_dbc', $db2);
    }

    public function indexAction() {
		$this->view->form = $this->getForm();
		$this->render('index');
    }

    public function loginAction() {
    	$form = $this->getForm();
    	if ($form->isValid($_POST)) {
	    	$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

			$db = Zend_Registry::get('auth_dbc');
			$db2 = Zend_Registry::get('auth2_dbc');

	    	$auth = new Zend_Auth_Adapter_DbTable($db);

	    	$auth->setTableName($config->ca_mgr->db->auth->tablename)
	    		 ->setIdentityColumn('email')
	    		 ->setCredentialColumn('password');

	    	$auth->setIdentity( $this->getRequest()->getParam('login_name'))
	    	     ->setCredential( sha1($this->getRequest()->getParam('login_password')))
	    	     ->setCredentialTreatment('?');

	        $result = $auth->authenticate();

	        $code = $result->getCode();
	    	switch ($code) {
	    		case Zend_Auth_Result::FAILURE:
    				Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE) to log in ' . $this->getRequest()->getParam('login_name'));
	    			throw new Exception(__METHOD__ . ': unknown error');
	    		case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
    				Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND) to log in ' . $this->getRequest()->getParam('login_name'));
	    			throw new Exception(__METHOD__ . ': ID unknown');
	    		case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
    				Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS) to log in ' . $this->getRequest()->getParam('login_name'));
	    			throw new Exception(__METHOD__ . ': ID not unique');
	    		case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
    				Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID) to log in ' . $this->getRequest()->getParam('login_name'));
	    			throw new Exception(__METHOD__ . ': ID unknown');	// to prevent brute force password attachs
	    		case Zend_Auth_Result::FAILURE_UNCATEGORIZED:
    				Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_UNCATEGORIZED) to log in ' . $this->getRequest()->getParam('login_name'));
	    			throw new Exception(__METHOD__ . ': unknown error');
	    	}

			$this->getAuthDetailsIntoSession($auth, false);

			Log::Log()->info(__METHOD__ . ' user logged in ' . $this->view->session->authdata['authed_username'] .
				' (' . $this->getRequest()->getParam('login_name') . ')');

	    	#$this->_forward('index', 'index');  // only "soft" forward, we need to change the url in browser
			$this->_redirect($this->view->url(array('controller' => 'index', 'action' => 'index'), 'default', true));

	    	/*
	    	$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
	    	$viewRenderer->setRender('loginresult');
	    	$this->view->request = $this->getRequest();
	    	*/
    	}
	   	else {
			$this->view->form = $form;
            return $this->render('index');
    	}
    }

    public function crtAction() {
    	$ssl_client_s_dn = GetEnv::getEnvVar('SSL_CLIENT_S_DN');
		$ssl_client_i_dn = GetEnv::getEnvVar('SSL_CLIENT_I_DN');

    	$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

		$db = Zend_Registry::get('auth_dbc');
		$db2 = Zend_Registry::get('auth2_dbc');

    	$auth = new Zend_Auth_Adapter_DbTable($db2);

    	$auth->setTableName($config->ca_mgr->db->auth2->tablename)
    		 ->setIdentityColumn('user_client_crt_s_dn_i_dn')
    		 ->setCredentialColumn('user_client_crt_s_dn_i_dn');

    	$auth->setIdentity( $ssl_client_s_dn . '//' . $ssl_client_i_dn)
    	     ->setCredential($ssl_client_s_dn . '//' . $ssl_client_i_dn)
    	     ->setCredentialTreatment('?');

    	$result = $auth->authenticate();

    	$code = $result->getCode();
    	switch ($code) {
    		case Zend_Auth_Result::FAILURE:
    			Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE) to log in ' . $ssl_client_s_dn . '//' . $ssl_client_i_dn);
    			throw new Exception(__METHOD__ . ': unknown error');
    		case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
    			Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND) to log in ' . $ssl_client_s_dn . '//' . $ssl_client_i_dn);
    			throw new Exception(__METHOD__ . ': ID unknown');
    		case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
    			Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS) to log in ' . $ssl_client_s_dn . '//' . $ssl_client_i_dn);
    			throw new Exception(__METHOD__ . ': ID not unique');
    		case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
    			Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID) to log in ' . $ssl_client_s_dn . '//' . $ssl_client_i_dn);
    			throw new Exception(__METHOD__ . ': ID unknown');	// to prevent brute force password attachs
    		case Zend_Auth_Result::FAILURE_UNCATEGORIZED:
    			Log::Log()->info(__METHOD__ . ' user failed (Zend_Auth_Result::FAILURE_UNCATEGORIZED) to log in ' . $ssl_client_s_dn . '//' . $ssl_client_i_dn);
    			throw new Exception(__METHOD__ . ': unknown error');
    	}

		$this->getAuthDetailsIntoSession($auth, true);

    	/*
    	$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
	    $viewRenderer->setRender('loginresult');
		*/

		Log::Log()->info(__METHOD__ . ' user logged in ' . $this->view->session->authdata['authed_username'] .
			' (' . $ssl_client_s_dn . '//' . $ssl_client_i_dn . ')');

    	#$this->_forward('index', 'index'); // only "soft" forward, we need to change the url in browser
    	$this->_redirect($this->view->url(array('controller' => 'index', 'action' => 'index'), 'default', true));
    }

    /**
     * get user data from Zend_Auth result and store data in session
     * @param Zend_Auth_Result $auth
     */
   	protected function getAuthDetailsIntoSession($auth, $crt) {
   		$session = Zend_Registry::get('session');

   		$db  = Zend_Registry::get('auth_dbc');
		$db2 = Zend_Registry::get('auth2_dbc');

   		/**
   		 * non existent in our case, look up a 2nd table (ca_mgr.system_user by login name (email)) and
   		 * get id from there, defaulting to User (1) when no db entry exists
   		 */
    	$auth_res = $auth->getResultRowObject();

    	if (!isset($auth_res->system_role_id) || $auth_res->system_role_id == 0) {
	    	$res = $db2->query('select * from system_user where login=?', array($auth_res->email));
			if ($res->rowCount() > 0) {
	    		$res_ar = $res->fetch();
	    		$system_roles_id = $res_ar['system_role_id'];
			}
	    	else {
	    		// no extra user info in manager database, assume standard user
	    		$system_roles_id = 1;
	    	}
    	}
		else
			$system_roles_id = $auth_res->system_role_id;

   		$session->authdata['authed'] = true;
    	$session->authdata['authed_id'] = $auth_res->id;
		if (!isset($auth_res->fname) || !isset($auth_res->lname)) {
			$res = $db->query('select * from users where email=?', array($auth_res->login));
			$res_ar = $res->fetch();
			$session->authdata['authed_username'] = 'crt' . $res_ar['login'];
			$session->authdata['authed_fname'] = $res_ar['fname'];
			$session->authdata['authed_lname'] = $res_ar['lname'];
		}
		else  {
		    $session->authdata['authed_username'] = $auth_res->email;
		    $session->authdata['authed_fname'] = $auth_res->fname;
		    $session->authdata['authed_lname'] = $auth_res->lname;
		}
		$session->authdata['authed_by_crt'] = $crt;
		$session->authdata['authed_by_cli'] = true;

		$res = $db2->query('select * from system_role where id=?', array($system_roles_id));
		$res_ar = $res->fetch();
    	$session->authdata['authed_role'] = $res_ar['role'];

    	$acl = $this->makeAcl($db2);

    	$session->authdata['authed_permissions'] = $acl;

    	/* test cases
    	Log::Log()->debug(($acl->isAllowed('User', 'Administration', 'view') == true)?'true':'false');
    	Log::Log()->debug(($acl->isAllowed('User', 'Administration', 'edit') == true)?'true':'false');
    	Log::Log()->debug(($acl->isAllowed('User', 'Account', 'view') == true)?'true':'false');
    	Log::Log()->debug(($acl->isAllowed('User', 'Account', 'edit') == true)?'true':'false');
		Log::Log()->debug(($acl->isAllowed('Admin', 'Administration', 'view') == true)?'true':'false');
		Log::Log()->debug(($acl->isAllowed('Admin', 'Account', 'view') == true)?'true':'false');
		*/

    	$this->view->session = $session;
   	}

    /**
     * build login form and return to requesting method
     * @return Zend_Form
     */
    protected function getForm() {
    	$form = new Zend_Form();
    	$form->setAction('/login/login')
			 ->setMethod('post');
		#$form->setAttrib('id', 'loginform');
		$username = new Zend_Form_Element_Text('login_name');
		$username->setRequired(true)
				 ->setLabel(I18n::_('User Name'))
				 ->addFilter(new Zend_Filter_StringTrim())
				 ->addFilter(new Zend_Filter_StripTags());
		$password = new Zend_Form_Element_Password('login_password');
		$password->setRequired(true)
				 ->setLabel(I18n::_('Password'))
				 ->addFilter(new Zend_Filter_StringTrim());
		$submit = new Zend_Form_Element_Submit('submit');
		$submit->setLabel(I18n::_('Login'));
		$form->addElement($username)
			 ->addElement($password)
			 ->addElement($submit);

		return $form;
    }

    /**
     * get roles and resources from db, build Zend_Acl structure and add permissions
     * @param Zend_Db $db
     */
    protected function makeAcl($db) {
		$acl = new Zend_Acl();

    	$res = $db->fetchAll('select * from system_role');
		foreach ($res as $obj) {
			if ($obj['inherit_role'] != '') {
				if ($acl->hasRole($obj['inherit_role'])) {
					$acl->addRole(new Zend_Acl_Role($obj['role']), $obj['inherit_role']);
				}
				else {
					/**
					 * @todo very simply system to order roles, add role before inherited role
					 */
					$res[] = $obj;
					continue;
				}
			}
			else {
				$acl->addRole(new Zend_Acl_Role($obj['role']));
			}
		}

		$res = $db->fetchAll('select * from system_resource');
		foreach ($res as $obj) {
			$acl->addResource(new Zend_Acl_Resource($obj['resource']));
		}

		$res = $db->fetchAll('select r.role as role, rs.resource as resource, permission, privilege '.
			'from system_role as r join system_role_has_system_resource as m on ' .
			'(r.id = m.system_role_id) join system_resource as rs on (m.system_resource_id = rs.id)');

		foreach ($res as $obj) {
			$privilege = explode(',', $obj['privilege']);
			if ($obj['permission'] == 'allow') {
				$acl->allow($obj['role'], $obj['resource'], $privilege);
			}
			else {
				$acl->deny($obj['role'], $obj['resource'], $privilege);
			}
		}

		return $acl;
    }
}
