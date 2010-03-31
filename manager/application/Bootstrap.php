<?php
require_once('plugins/plugin.charsetheader.php');
require_once('plugins/plugin.forceauth.php');
require_once('plugins/plugin.loginlogout.php');
require_once('plugins/plugin.buildmenu.php');
require_once('config/Config.php');
require_once('log/Log.php');
require_once('l10n/L10n.php');
require_once('i18n/I18n.php');

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
	protected function _initAutoload() {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Default_',
            'basePath'  => dirname(__FILE__)
        ));
        return $autoloader;
    }

	protected function _initPlugins() {
		$this->bootstrap('session');

		$fc = Zend_Controller_Front::getInstance();

		$charset_header = new CharsetHeader();
		$fc->registerPlugin($charset_header);

		$force_auth = new ForceAuth();
		$fc->registerPlugin($force_auth);

		$buildmenu = new BuildMenu();
		$fc->registerPlugin($buildmenu);

		$loginlogout = new LoginLogout();
		$fc->registerPlugin($loginlogout);
	}

	protected function _initDoctype() {
		$this->bootstrap('view');
		$this->bootstrap('log');
		$this->bootstrap('I18n');
		$this->bootstrap('session');

		$view = $this->getResource('view');
		Zend_Registry::set('view', $view);
		$view->doctype('XHTML1_STRICT');
		$view->addHelperPath(APPLICATION_PATH . '/views/helpers/');
		$view->headTitle = I18n::_('CACert Test Manager');
	}

	/**
	 * @todo expireSessionCookie()
	 * @todo rememberMe(xx)
	 * @todo forgetMe()
	 * @see Zend_Registry::get('session');
	 * @return Zend_Session_Namespace
	 */
	protected function _initSession() {
		$options = $this->getOption('ca_mgr');

		$db = Zend_Db::factory($options['db']['session']['pdo'], $options['db']['session']);

		/**
		 * automatically clean up expired session entries from session cache
		 * use the modified and lifetime stamps to calculate expire time
		 */
		if ($options['db']['session']['autocleanup'] == '1') {
			$stmt = $db->query('delete from front_session where (modified + lifetime * 2) < unix_timestamp()');
			# $stmt->execute();
		}

		//you can either set the Zend_Db_Table default adapter
		//or you can pass the db connection straight to the save handler $config
		// @see lifetimeColumn / lifetime / overrideLifetime, lifetime defaults to php.ini: session.gc_maxlifetime
		Zend_Db_Table_Abstract::setDefaultAdapter($db);
		$config = array(
		    'name'           => 'front_session',
		    'primary'        => 'id',
		    'modifiedColumn' => 'modified',
		    'dataColumn'     => 'data',
		    'lifetimeColumn' => 'lifetime'
		);

		//create your Zend_Session_SaveHandler_DbTable and
		//set the save handler for Zend_Session
		Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($config));

		// Zend_Session::rememberMe(7200);

		//start your session!
		Zend_Session::start();

		$session = new Zend_Session_Namespace();
		if (!isset($session->started))
			$session->started = time();
		if (!isset($session->authdata))
			$session->authdata = array('authed' => false);

		Zend_Registry::set('session', $session);
		return $session;
	}

	/**
	 * get the basic system config from database, store the config object in the bootstrap registry
	 * @see Zend_Registry::get('config');
	 * @return Config
	 */
	protected function _initConfig() {
		$options = $this->getOption('ca_mgr');
		$db = Zend_Db::factory($options['db']['config']['pdo'], $options['db']['config']);
		$config = Config::getInstance(SYSTEM_CONFIG, $db);

		Zend_Registry::set('config', $config);
		Zend_Registry::set('config_dbc', $db);

		return $config;
	}

	/**
	 * make singleton system logger
	 * @see Zend_Registry::get('log');
	 * @return Log
	 */
	public function _initLog() {
		$this->bootstrap('Config');

		$op = $this->getOption('log');
		$log = Log::getInstance(SYSTEM_LOG, $op['application']);

		Zend_Registry::set('log', $log);
		return $log;
	}

	/**
	 * make singleton I18n (internationalization) object (translation)
	 */
	public function _initI18n() {
		$this->bootstrap('Config');
		// need existing L10n object for initialization
		$this->bootstrap('L10n');

		$I18n = I18n::getInstance(L10n::getInstance()->getLanguage());
	}

	/**
	 * make singleton L10n (localization) object (set locale, convert date and
	 * number formats)
	 */
	public function _initL10n() {
		$this->bootstrap('Config');

		$L10n = L10n::getInstance();
	}
}
