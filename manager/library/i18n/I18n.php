<?php
/**
 * encapsulate Zend_Translate within an singleton class
 * @author markus
 * $Id: I18n.php 33 2009-12-10 15:08:38Z markus $
 */

require_once('l10n/L10n.php');

class I18n {
	/**
	 * static pointer to instance
	 * @var array(I18n)
	 */
	private static $instance = null;

	/**
	 * config object
	 * @var Zend_Translate
	 */
	private $translate = null;

	/**
	 * make new translate
	 */
	protected function __construct() {
    	$options = array(
    		'log'				=> Log::Log(),
    		'logUntranslated'	=> true
    	);

    	$locale = L10n::getInstance();
    	$supported = $locale->getBrowser();
    	arsort($supported, SORT_NUMERIC);

    	$file = '';
    	foreach ($supported as $loc => $val) {
    		if (file_exists(LOCALE_PATH . '/' . $loc . '/locale.php')) {
    			$file = LOCALE_PATH . '/' . $loc . '/locale.php';
    			$locale->setLocale($loc);
    			break;
    		}
    	}

		if ($file == '' && file_exists(LOCALE_PATH . '/en_US/locale.php')) {
			$file = LOCALE_PATH . '/en_US/locale.php';
			$locale->setLocale('en_US');
		}

    	if ($file != '') {
    		$this->translate = new Zend_Translate(Zend_Translate::AN_ARRAY, $file, $locale->getLanguage(), $options);
    		#Log::Log()->debug('locale ' . $locale->getLanguage() . '_' .$locale->getRegion() . ' loaded');
    	}
    	else
    		throw new Exception(__METHOD__ . ': no translation files available');
	}

	/**
	 * get already existing instance, make new instance or throw an exception
	 * @return I18n
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new I18n();
		}

		return self::$instance;
	}

	/**
	 * return the Zend_Translate object
	 * @return Zend_Translate
	 */
	public static function getTranslate() {
		return self::getInstance()->translate;
	}

	/**
	 * map _ to translate
	 * @param unknown_type $text
	 * @param unknown_type $locale
	 */
	public function _($text, $locale = null) {
		return self::getInstance()->translate->_($text, $locale);
	}

	/**
	 * magic __call dispatches all unknown methods to Zend_Translate
	 * @param unknown_type $method
	 * @param unknown_type $arguments
	 */
	public function __call($method, $arguments) {
		return call_user_func_array(array($this->translate, $method), $arguments);
	}
}
