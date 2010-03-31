<?php

/**
 * this plugin tries to add modules to the top navigation depending on the user
 * which is logged in and the required permissions needed (provided by the action modules)
 *
 * @author markus
 * $Id: plugin.buildmenu.php 95 2010-03-19 14:14:39Z markus $
 */
class BuildMenu extends Zend_Controller_Plugin_Abstract {
	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$session = Zend_Registry::get('session');
    	if (!isset($session->authdata) || !isset($session->authdata['authed']) || $session->authdata['authed'] === false)
    		return;

		$cur_ctrl = $request->getControllerName();
		$cur_action = $request->getActionName();

    	$view = Zend_Registry::get('view');

		if (is_dir(FWACTIONS_PATH)) {
			$dir = opendir(FWACTIONS_PATH);

			while (($file = readdir($dir)) !== false) {
				if ($file == '.' || $file == '..')
					continue;
				if (preg_match('/^Action([a-zA-Z0-9_]*)\.php/', $file, $match)) {
					$path = FWACTIONS_PATH . '/' . $file;
					require_once($path);

					$r = new ReflectionClass($match[1]);

					if ($r->isSubclassOf('FWAction')) {
						/**
						 * match Actions permission with the permissions of the currently logged in user,
						 * add to menu if user has access to that action
						 */

						$required = $r->getMethod('getRequiredPermissions')->invoke(null);
						$menuprio = $r->getMethod('getTopNavPrio')->invoke(null);
						$ctrl = $r->getMethod('getController')->invoke(null);
						$action = $r->getMethod('getAction')->invoke(null);
						$text = $r->getMethod('getMenutext')->invoke(null);
						$role = $session->authdata['authed_role'];

						if ($cur_ctrl == $ctrl) # && $cur_action == $action)
							$aclass = ' class="active"';
						else
							$aclass = '';

						$acl = $session->authdata['authed_permissions'];
						if (is_array($required) && count($required) == 0) {
							$view->topNav('<a href="' .
					    		$view->url(array('controller' => $ctrl, 'action' => $action), 'default', true) .
					   		'"' . $aclass . '>' . I18n::_($text) . '</a>', Zend_View_Helper_Placeholder_Container_Abstract::SET, $menuprio);
						}
						else {
							foreach ($required as $rperm) {
								if ($acl->has($rperm) && $acl->isAllowed($role, $rperm, 'view')) {
							    	$view->topNav('<a href="' .
							    		$view->url(array('controller' => $ctrl, 'action' => $action), 'default', true) .
							   		'"' . $aclass . '>' . I18n::_($text) . '</a>', Zend_View_Helper_Placeholder_Container_Abstract::SET, $menuprio);
					    		break;	// exit on first match
								}
							}
						}
					}
				}
			}

			closedir($dir);
		}
	}
}