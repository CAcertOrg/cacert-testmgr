<?php
/**
 * @package SLS
 * @subpackage EXCEPTION
 */

/**
 * extend PHPs standard exception by some details
 *
 * @package SLS
 * @subpackage EXCEPTION
 * @author Markus Warg <mw@it-sls.de>
 * @since 2009-02-23 16:10
 * @version $Id: exception.Base.php 90 2010-03-09 09:48:27Z markus $
 */
class BaseException extends Exception {
	/**
	 * additional data / string
	 * @var string
	 */
	protected $extra = '';

	/**
	 * location of thrower
	 * @var string
	 */
	protected $exception_location = '';

    /**
     * make new object
     *
	 * @access public
     * @param string $message
     * @param int $code
     */
    public function __construct($message, $code = 0, $extra = '') {
        $bt = debug_backtrace();

    	$remove_exception = 0;
    	while( $remove_exception < count($bt) && isset($bt[$remove_exception]['class']) && eregi('exception', $bt[$remove_exception]['class']) ) {
    		$remove_exception++;
    	}

		if ($remove_exception > 0)
			$remove_exception--;

		if ($remove_exception < count($bt)) {
    		$this->exception_location = $bt[$remove_exception]['file'].':'.$bt[$remove_exception]['line'];
		}

       	$this->extra = $extra;

        parent::__construct($message,$code);
    }

    /**
     * Make a string out of this exception
	 *
	 * @access public
	 * @return string
     */
	public function __toString() {
    	$out = __CLASS__ . '['.$this->code.']:';

    	if ($this->exception_location != '')
    		$out.= $this->exception_location;
    	$out .= ':';

    	$out .= " {$this->message}";

    	if (isset($this->extra) && strlen($this->extra) > 0)
        	$out .= " ({$this->extra})\n";

        return $out;
    }

	/**
	 * get the extra info string
	 *
	 * @access public
	 * @return string
	 */
	public function getExtraInfo() {
		return $this->extra;
	}

	/**
	 * get the exception location string
	 *
	 * @access public
	 * @return string
	 */
	public function getExceptionLocation() {
		return $this->exception_location;
	}

}
?>