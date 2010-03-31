<?php
/**
 * encapsulate Zend_Locale within an singleton class
 * @author markus
 * $Id: L10n.php 13 2009-11-24 14:52:56Z markus $
 */
class L10n {
	/**
	 * static pointer to instance
	 * @var L10n
	 */
	private static $instance = null;

	/**
	 * config object
	 * @var Zend_Locale
	 */
	private $locale = null;

	/**
	 * make new translate
	 */
	protected function __construct() {
		$this->locale = new Zend_Locale();
	}

	/**
	 * get already existing instance, make new instance or throw an exception
	 * @return L10n
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new L10n();
		}

		return self::$instance;
	}

	/**
	 * magic __call dispatches all unknown methods to Zend_Locale
	 * @param unknown_type $method
	 * @param unknown_type $arguments
	 */
	public function __call($method, $arguments) {
		return call_user_func_array(array($this->locale, $method), $arguments);
	}
}
