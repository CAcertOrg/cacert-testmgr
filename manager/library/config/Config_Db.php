<?php
/**
 * Add database driven configuration to the framework, source based on Zend_Config_Ini
 *
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
 * @version    $Id: Config_Db.php 27 2009-12-03 13:00:29Z markus $
 */


/**
 * @see Zend_Config
 */
require_once 'Zend/Config.php';


/**
 * @category   Zend
 * @package    Zend_Config
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Config_Db extends Zend_Config
{
	/**
     * String that separates nesting levels of configuration data identifiers
     *
     * @var string
     */
    protected $_nestSeparator = '.';

    /**
     * String that separates the parent section name
     *
     * @var string
     */
    protected $_sectionSeparator = ':';

    /**
     * Wether to skip extends or not
     *
     * @var boolean
     */
    protected $_skipExtends = false;

    /**
     * Loads the section $section from the config file $filename for
     * access facilitated by nested object properties.
     *
     * If the section name contains a ":" then the section name to the right
     * is loaded and included into the properties. Note that the keys in
     * this $section will override any keys of the same
     * name in the sections that have been included via ":".
     *
     * If the $section is null, then all sections in the ini file are loaded.
     *
     * If any key includes a ".", then this will act as a separator to
     * create a sub-property.
     *
     * example ini file:
     *      [all]
     *      db.connection = database
     *      hostname = live
     *
     *      [staging : all]
     *      hostname = staging
     *
     * after calling $data = new Zend_Config_Ini($file, 'staging'); then
     *      $data->hostname === "staging"
     *      $data->db->connection === "database"
     *
     * The $options parameter may be provided as either a boolean or an array.
     * If provided as a boolean, this sets the $allowModifications option of
     * Zend_Config. If provided as an array, there are two configuration
     * directives that may be set. For example:
     *
     * $options = array(
     *     'allowModifications' => false,
     *     'nestSeparator'      => '->'
     *      );
     *
     * @param  Zend_Db       $dbc
     * @param  string	     $db_table
     * @param  string|null   $section
     * @param  boolean|array $options
     * @throws Zend_Config_Exception
     * @return void
     */
    public function __construct($dbc, $db_table, $section = null, $options = false)
    {
        if (empty($dbc)) {
            /**
             * @see Zend_Config_Exception
             */
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('Database connection is not set');
        }

        if (empty($db_table)) {
            /**
             * @see Zend_Config_Exception
             */
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('Database table is not set');
        }

        $allowModifications = false;
        if (is_bool($options)) {
            $allowModifications = $options;
        } elseif (is_array($options)) {
            if (isset($options['allowModifications'])) {
                $allowModifications = (bool) $options['allowModifications'];
            }
            if (isset($options['nestSeparator'])) {
                $this->_nestSeparator = (string) $options['nestSeparator'];
            }
            if (isset($options['skipExtends'])) {
                $this->_skipExtends = (bool) $options['skipExtends'];
            }
        }

        $iniArray = $this->_loadIniFile($dbc, $db_table, $section);
		$section = null;

        if (null === $section) {
            // Load entire file
            $dataArray = array();
            foreach ($iniArray as $sectionName => $sectionData) {
                if(!is_array($sectionData)) {
                    $dataArray = array_merge_recursive($dataArray, $this->_processKey(array(), $sectionName, $sectionData));
                } else {
                    $dataArray[$sectionName] = $this->_processSection($iniArray, $sectionName);
                }
            }
            parent::__construct($dataArray, $allowModifications);
        } else {
            // Load one or more sections
            if (!is_array($section)) {
                $section = array($section);
            }
            $dataArray = array();
            foreach ($section as $sectionName) {
                if (!isset($iniArray[$sectionName])) {
                    /**
                     * @see Zend_Config_Exception
                     */
                    require_once 'Zend/Config/Exception.php';
                    throw new Zend_Config_Exception("Section '$sectionName' cannot be found");
                }
                $dataArray = array_merge($this->_processSection($iniArray, $sectionName), $dataArray);

            }
            parent::__construct($dataArray, $allowModifications);
        }

        $this->_loadedSection = $section;
    }

    /**
     * Load data from database and preprocess the section separator (':' in the
     * section name (that is used for section extension) so that the resultant
     * array has the correct section names and the extension information is
     * stored in a sub-key called ';extends'. We use ';extends' as this can
     * never be a valid key name in an INI file that has been loaded using
     * parse_ini_file().
     *
     * @param Zend_Db $dbc
     * @param string $db_table
     * @throws Zend_Config_Exception
     * @return array
     */
    protected function _loadIniFile($dbc, $db_table, $section = null)
    {
        set_error_handler(array($this, '_loadFileErrorHandler'));
        $loaded = $this->_parse_ini_db($dbc, $db_table, $section); // Warnings and errors are suppressed
        restore_error_handler();
        // Check if there was a error while loading file
        if ($this->_loadFileErrorStr !== null) {
            /**
             * @see Zend_Config_Exception
             */
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception($this->_loadFileErrorStr);
        }

        $iniArray = array();
        foreach ($loaded as $key => $data)
        {
            $pieces = explode($this->_sectionSeparator, $key);
            $thisSection = trim($pieces[0]);
            switch (count($pieces)) {
                case 1:
                    $iniArray[$thisSection] = $data;
                    break;

                case 2:
                    $extendedSection = trim($pieces[1]);
                    $iniArray[$thisSection] = array_merge(array(';extends'=>$extendedSection), $data);
                    break;

                default:
                    /**
                     * @see Zend_Config_Exception
                     */
                    require_once 'Zend/Config/Exception.php';
                    throw new Zend_Config_Exception("Section '$thisSection' may not extend multiple sections");
            }
        }

        return $iniArray;
    }

    /**
     * read config from (current db in $dbc).$db_table
     *
     * @param Zend_Db $dbc
     * @param string $db_table
     * @param string $section
     * @return array
     */
    protected function _parse_ini_db($dbc, $db_table, $section) {
    	$sql = 'select * from ' . $db_table;
		if ($section !== null) {
			$sql .= ' where ' . $section;
		}

    	$db_config = $dbc->query($sql);

    	$config = array();

    	while (($row = $db_config->fetch()) !== false) {
    		$key = explode('.', $row['config_key']);
    		$depth = count($key);
    		$ci = &$config;
    		for ($cnt = 0; $cnt < $depth; $cnt++) {
    			if ($cnt == ($depth - 1))
    				$ci[$key[$cnt]] = $row['config_value'];
    			elseif (!isset($ci[$key[$cnt]]))
    				$ci[$key[$cnt]] = array();
    			$ci = &$ci[$key[$cnt]];
    		}
    	}
    	return $config;
    }

    /**
     * Process each element in the section and handle the ";extends" inheritance
     * key. Passes control to _processKey() to handle the nest separator
     * sub-property syntax that may be used within the key name.
     *
     * @param  array  $iniArray
     * @param  string $section
     * @param  array  $config
     * @throws Zend_Config_Exception
     * @return array
     */
    protected function _processSection($iniArray, $section, $config = array())
    {
        $thisSection = $iniArray[$section];

        foreach ($thisSection as $key => $value) {
            if (strtolower($key) == ';extends') {
                if (isset($iniArray[$value])) {
                    $this->_assertValidExtend($section, $value);

                    if (!$this->_skipExtends) {
                        $config = $this->_processSection($iniArray, $value, $config);
                    }
                } else {
                    /**
                     * @see Zend_Config_Exception
                     */
                    require_once 'Zend/Config/Exception.php';
                    throw new Zend_Config_Exception("Parent section '$section' cannot be found");
                }
            } else {
                $config = $this->_processKey($config, $key, $value);
            }
        }
        return $config;
    }

    /**
     * Assign the key's value to the property list. Handles the
     * nest separator for sub-properties.
     *
     * @param  array  $config
     * @param  string $key
     * @param  string $value
     * @throws Zend_Config_Exception
     * @return array
     */
    protected function _processKey($config, $key, $value)
    {
        if (strpos($key, $this->_nestSeparator) !== false) {
            $pieces = explode($this->_nestSeparator, $key, 2);
            if (strlen($pieces[0]) && strlen($pieces[1])) {
                if (!isset($config[$pieces[0]])) {
                    if ($pieces[0] === '0' && !empty($config)) {
                        // convert the current values in $config into an array
                        $config = array($pieces[0] => $config);
                    } else {
                        $config[$pieces[0]] = array();
                    }
                } elseif (!is_array($config[$pieces[0]])) {
                    /**
                     * @see Zend_Config_Exception
                     */
                    require_once 'Zend/Config/Exception.php';
                    throw new Zend_Config_Exception("Cannot create sub-key for '{$pieces[0]}' as key already exists");
                }
                $config[$pieces[0]] = $this->_processKey($config[$pieces[0]], $pieces[1], $value);
            } else {
                /**
                 * @see Zend_Config_Exception
                 */
                require_once 'Zend/Config/Exception.php';
                throw new Zend_Config_Exception("Invalid key '$key'");
            }
        } else {
            $config[$key] = $value;
        }
        return $config;
    }
}
