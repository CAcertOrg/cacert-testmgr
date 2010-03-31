<?php

/**
 * this plugin just monitors the authdata section in the current session and adds an login / logout link to the
 * top navigation bar depending on the value that was found
 * @author markus
 * $Id: plugin.loginlogout.php 95 2010-03-19 14:14:39Z markus $
 */
class LoginLogout extends Zend_Controller_Plugin_Abstract {
	public function postDispatch(Zend_Controller_Request_Abstract $request) {
		$session = Zend_Registry::get('session');
    	if (!isset($session->authdata) || !isset($session->authdata['authed']) || $session->authdata['authed'] === false) {
    		$controller		= 'login';
    		$text			= 'Login';
    	}
    	else {
    		$controller		= 'logout';
    		$text			= 'Logout';
    	}
    	$view = Zend_Registry::get('view');
    	$view->topNav('<a href="' .
    		$view->url(array('controller' => $controller), 'default', true) .
    		'">' . I18n::_($text) . '</a>', Zend_View_Helper_Placeholder_Container_Abstract::SET, 1000);
	}
}
