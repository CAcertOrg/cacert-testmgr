<?php
/**
 * @author markus
 * $Id: $
 */

/**
 * required files
 * @ignore
 */
require_once(LIBRARY_PATH . '/exception/exception.Base.php');

/**
 * Exceptions thrown in the IMAP classes
 *
 * @package SLS
 * @subpackage CONFIG.EXCEPTION
 * @author Markus Warg <mw@it-sls.de>
 */
class IMAPException extends BaseException {
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
