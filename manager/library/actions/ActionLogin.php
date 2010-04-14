<?php

require_once (FWACTIONS_PATH . '/FWAction.php');

class Login extends FWAction {
	/**
	 * get a list of required permissions that are needed to access this action
	 * @return array
	 */
	public static function getRequiredPermissions() {
		return array();
	}

	/**
	 * get a role that is required for accessing that action
	 * @return string
	 */
	public static function getRequiredRole() {
		return 'User';
	}

	/**
	 * sort order for top navigation
	 * @return integer
	 */
	public static function getTopNavPrio() {
		return 1000;
	}

	/**
	 * controller to invoke
	 * @return string
	 */
	public static function getController() {
		return 'login';
	}

	/**
	 * action to invoke
	 * @return string
	 */
	public static function getAction() {
		return 'index';
	}

	/**
	 * get text for menu, caller is responsible for translating
	 * @return string
	 */
	public static function getMenuText() {
		return 'Login';
	}
}
