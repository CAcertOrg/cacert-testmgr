<?php
/**
 * @author markus
 * $Id: plugin.forceauth.php 40 2009-12-21 09:40:43Z markus $
 */
class ForceAuth extends Zend_Controller_Plugin_Abstract {
	public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request) {
		$session = Zend_Registry::get('session');

		if (in_array($request->getControllerName(), array('login', 'error', 'js', 'img', 'css')))
			return;

		if (!isset($session->authdata) || !isset($session->authdata['authed']) || $session->authdata['authed'] === false) {
			$fc = Zend_Controller_Front::getInstance();

			$response = $fc->getResponse();
			$response->canSendHeaders(true);

			$response->setHeader('Location', 'login', true);
			$response->setHeader('Status', '301', true);
			Log::Log()->debug('redirected to login');

			$request->setModuleName('default')
        		->setControllerName('login')
        		->setActionName('index')
        		->setDispatched(false);
		}
	}
}
