<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Config
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Config_Writer_Db.php 43 2009-12-21 14:12:34Z markus $
 */

/**
 * Usage:
 * require_once(LIBRARY_PATH . '/config/Config_Writer_Db.php');
 * $writer = new Config_Writer_Db();
 * $writer->setTableName('system_config');
 * $writer->write(Zend_Registry::get('config_dbc'), Zend_Registry::get('config'));
 *
 * $writer = new Config_Writer_Db();
 * $writer->setTableName('dnssec_org_param');
 * $writer->write(Zend_Registry::get('config_dbc'), dnssec_org_conf, 'dnssec_org_id="2"');
 */

/**
 * @see Zend_Config_Writer
 */
require_once 'Zend/Config/Writer.php';

/**
 * @category   Zend
 * @package    Zend_Config
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Config_Writer_Db extends Zend_Config_Writer
{
    /**
     * String that separates nesting levels of configuration data identifiers
     *
     * @var string
     */
    protected $_nestSeparator = '.';

    protected $_set = null;

    protected $_tableName = null;

    /**
     * Set the nest separator
     *
     * @param  string $filename
     * @return Zend_Config_Writer_Ini
     */
    public function setNestSeparator($separator)
    {
        $this->_nestSeparator = $separator;

        return $this;
    }

    public function setTableName($name)
    {
        $this->_tableName = $name;

        return $this;
    }

    /**
     * Defined by Zend_Config_Writer
     *
     * use set to limit impact when a shared config file is used (i.e. config per item using foreign keys)
     *
     * @param  string $filename
     * @param  Config_Db $config
     * @param  string $set
     * @return void
     */
    public function write($db = null, $config = null, $set = null) {
    	$this->_set = $set;

    	// this method is specialized for writing back Config objects (which hold config_db objects)
        if ($config !== null) {
        	if ($config instanceof Config)
            	$this->setConfig($config->getConfig());
            else {
            	$this->setConfig($config);
            }
        }

        if ($this->_config === null) {
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('No config was set');
        }

        if ($db === null) {
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('No db was set');
        }

        $sql = array();

        $string = 'delete from ' . $this->_tableName;
        if ($this->_set !== null) {
			$string .= ' where ' . $this->_set;
        }

		$sql[] = $string;

        $iniString   = '';
        $extends     = $this->_config->getExtends();
        $sectionName = $this->_config->getSectionName();

        foreach ($this->_config as $key => $data) {
     		$sql= array_merge($sql, $this->addEntry($sectionName, $key, $data));
	    }

	    try {
	    	$db->beginTransaction();
    		foreach ($sql as $command) {
    			#Log::Log()->debug($command);
	    		$db->query($command);
			}
			$db->commit();
	    } catch (Exception $e) {
			$db->rollBack();
			Log::Log()->err($e);
			throw $e;
	    }
	}

	/**
	 * build key value pairs, key is created by recursively adding section names, delimited by "."
	 * @param string $prefix
	 * @param string $key
	 * @param mixed $data
	 */
    protected function addEntry($prefix, $key, $data) {
    	$sql = array();

    	if ($data instanceof Zend_Config) {
			if ($prefix != '')
    			$prefix .= '.';
    		$prefix .= $key;
    		foreach ($data as $k => $v) {
    			$sql = array_merge($sql, $this->addEntry($prefix, $k, $v));
    		}
    	}
    	else {
    		$string = 'insert into ' . $this->_tableName . ' set ';
    		$pkey = $prefix;
    		if ($pkey != '')
    			$pkey .= '.';
    		$pkey .= $key;
    		$string .= 'config_key=' . $this->_prepareValue($pkey) . ', ';
    		$string .= 'config_value=' . $this->_prepareValue($data);
    		if ($this->_set !== null)
    			$string .= ', ' . $this->_set;

    		$sql[] = $string;
    	}

    	return $sql;
	}

    /**
     * Add a branch to an INI string recursively
     *
     * @param  Zend_Config $config
     * @return void
     */
    protected function _addBranch(Zend_Config $config, $parents = array())
    {
        $iniString = '';

        foreach ($config as $key => $value) {
            $group = array_merge($parents, array($key));

            if ($value instanceof Zend_Config) {
                $iniString .= $this->_addBranch($value, $group);
            } else {
                $iniString .= implode($this->_nestSeparator, $group)
                           .  ' = '
                           .  $this->_prepareValue($value)
                           .  "\n";
            }
        }

        return $iniString;
    }

    /**
     * Prepare a value for INI
     *
     * @param  mixed $value
     * @return string
     */
    protected function _prepareValue($value)
    {
        if (is_integer($value) || is_float($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return ($value ? 'true' : 'false');
        } else {
            return '"' . addslashes($value) .  '"';
        }
    }
}
