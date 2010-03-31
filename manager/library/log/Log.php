<?php
/**
 * encapsulate Zend_Log with one or several log writers within an singleton class
 * @author markus
 * $Id: Log.php 77 2010-02-26 11:58:34Z markus $
 */
class Log {
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
	 * @var Zend_Log
	 */
	private $log = null;

	/**
	 * make new logger, configuration is taken from system_config, section $instanceName
	 * @param string $instanceName
	 * @param string $application
	 */
	protected function __construct($instanceName, $application = null) {
		if ($instanceName === null)
    		throw new Exception(__METHOD__ . ': expected an instance name, got none');

    	$config = Config::getInstance(SYSTEM_CONFIG);
    	$log_config = $config->$instanceName;

		$this->log = new Zend_Log();
    	if (isset($log_config->file) && intval($log_config->file->enabled) !== 0) {
    		$file_logger = new Zend_Log_Writer_Stream($log_config->file->name);

    		/**
    		 *
    		$format = Zend_Log_Formatter_Simple::DEFAULT_FORMAT;
    		$formatter = new Zend_Log_Formatter_Simple($format);
    		$file_logger->setFormatter($formatter);
    		 */
    		if (isset($application) && $application != '')
    			$this->log->setEventItem('application', $application);
    		$formatter = new Zend_Log_Formatter_Simple('%syslog_time% %application%[%pid%]: %priorityName%: %message%' . PHP_EOL);
    		$file_logger->setFormatter($formatter);
			$this->log->addWriter($file_logger);
    	}
    	if (isset($log_config->syslog) && intval($log_config->syslog->enabled) !== 0) {
    		$param = array('facility' => $log_config->syslog->facility);
    		if (isset($application) && $application != '')
    			$param['application'] = $application;

    		$sys_logger = new Zend_Log_Writer_Syslog($param);
    		$formatter = new Zend_Log_Formatter_Simple('%priorityName%: %message%' . PHP_EOL);
    		$sys_logger->setFormatter($formatter);
			$this->log->addWriter($sys_logger);
    	}

    	$filter = new Zend_Log_Filter_Priority(intval($log_config->priority));
    	$this->log->addFilter($filter);
	}

	/**
	 * get already existing instance, make new instance or throw an exception
	 * @param string $instanceName
	 * @param string $application
	 */
	public static function getInstance($instanceName = null, $application = null) {
		if ($instanceName === null) {
			if (count(self::$instances) == 0)
    			throw new Exception(__METHOD__ . ': expected an instance name, got none');
    		return self::$instances[0];
		}

		if (!array_key_exists($instanceName, self::$instances)) {
			self::$instances[$instanceName] = new Log($instanceName, $application);
		}

		return self::$instances[$instanceName];
	}

	/**
	 * return SYSTEM_LOG for convenience
	 * @return Zend_Log
	 */
	public static function Log() {
		return self::$instances[SYSTEM_LOG]->getLog();
	}

	/**
	 * get the Zend_Log object
	 * @return Zend_Log
	 */
	public function getLog() {
		$this->log->setEventItem('pid', posix_getpid());
		$this->log->setEventItem('syslog_time', date('Y-m-d H:i:s'));
		return $this->log;
	}
}
