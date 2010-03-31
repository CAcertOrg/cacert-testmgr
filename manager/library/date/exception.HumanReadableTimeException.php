<?php
/**
 * @package SLS
 * @subpackage CONFIG.EXCEPTION
 */

/**
 * required files
 * @ignore
 */
require_once(LIBRARY_PATH . '/exception/exception.Base.php');

/**
 * Exceptions thrown in the DNSSEC library classes
 *
 * @package SLS
 * @subpackage CONFIG.EXCEPTION
 * @author Markus Warg <mw@it-sls.de>
 * @since 2009-02-25 13:05
 * @version $Id: exception.HumanReadableTimeException.php 91 2010-03-10 10:36:25Z markus $
 */
class HumanReadableTimeException extends BaseException {
    /**
     * make new object
     *
	 * @access public
     * @param string $message
     * @param int $code
     * @param string $extra
     */
	/*
    public function __construct($message,$code = 0,$extra = '') {
        parent::__construct($message,$code, $extra);
    }
    */
}
?>