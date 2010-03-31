<?php

require_once('config/Config_Db.php');

class Config {
	/**
	 * static pointer to instances
	 * @var array(Config)
	 */
	private static $instances = array();

	/**
	 * can handle several instances, distinct by instance name string
	 * @var string
	 */
	private $instanceName = '';

	/**
	 * config object
	 * @var Config_Db
	 */
	private $config = null;

	/**
	 * make a new Config_Db
	 *
	 * by using the $where statement you can limit the data that is fetched from db, i.e. only get config for zone $id
	 *
	 * @param string $instanceName
	 * @param Zend_Db_Adapter $db
	 * @param string $where
	 */
	protected function __construct($instanceName = null, $db = null, $where = null) {
    	if ($instanceName === null)
    		throw new Exception(__METHOD__ . ': expected an instance name, got none');

		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
		$this->instanceName = $instanceName;

		if ($db === null)
    		$db = Zend_Db::factory($config->dnssecme->db->config->pdo, $config->dnssecme->db->config);

		$this->config = new Config_Db($db, $instanceName, $where, true);
	}

	/**
	 * get already existing instance, make new instance or throw an exception
	 * @param string $instanceName
	 * @param Zend_Db_Adapter $db
	 * @param string $where
	 */
	public static function getInstance($instanceName, $db = null, $where = null) {
    	if ($instanceName === null)
    		throw new Exception(__METHOD__ . ': expected an instance name, got none');

    	// no caching if presumeably volatile data is requested
    	if ($db !== null && $where !== null) {
    		return new Config($instanceName, $db, $where);
    	}

		if (!array_key_exists($instanceName, self::$instances)) {
			self::$instances[$instanceName] = new Config($instanceName, $db, $where);
		}

		return self::$instances[$instanceName];
	}

	/**
	 * magic method that dispatches all unrecognized method calls to the config object
	 *
	 * @param string $param
	 */
	public function __get($param) {
		return $this->config->$param;
	}

	/**
	 * magic method that handles isset inquiries to attributes
	 *
	 * @param string $param
	 */
	public function __isset($param) {
		return isset($this->config->$param);
	}

	/**
	 * magic method that dispatches all unrecognized method calls to the config object
	 *
	 * @param string $param
	 * @param string $value
	 */
	public function __set($param, $value) {
		$this->config->$param = $value;
	}

	/**
	 * get the config object
	 * @return Zend_Config_*
	 */
	public function getConfig() {
		return $this->config;
	}
}
?>