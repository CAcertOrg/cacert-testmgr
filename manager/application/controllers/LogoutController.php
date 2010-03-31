<?php
/**
 * @author markus
 * $Id: LogoutController.php 12 2009-11-24 13:35:16Z markus $
 */

require_once('helpers/GetEnv.php');
require_once('config/Config.php');

class LogoutController extends Zend_Controller_Action
{

    public function init() {
        /* Initialize action controller here */
    }

    public function indexAction() {
    	$session = Zend_Registry::get('session');

    	Log::Log()->info(__METHOD__ . ' user logged out ' . $this->view->session->authdata['authed_username']);

    	unset($session->authdata);
    	$session->authdata['authed'] = false;

    	Zend_Session::destroy();
    }
}
