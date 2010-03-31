<?php
/**
 * @author markus
 * $Id: defines.php 95 2010-03-19 14:14:39Z markus $
 */

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));
defined('LIBARARY_PATH')
    || define('LIBRARY_PATH', realpath(dirname(__FILE__) . '/..'));
defined('FWACTIONS_PATH')
    || define('FWACTIONS_PATH', LIBRARY_PATH . '/actions');
defined('LOCALE_PATH')
    || define('LOCALE_PATH', realpath(dirname(__FILE__) . '/../../locale'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

define('SYSTEM_CONFIG', 'system_config');
define('SYSTEM_LOG', 'log');

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    LIBRARY_PATH,
    get_include_path(),
)));
