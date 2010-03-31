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
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: UserInfo.php 33 2009-12-10 15:08:38Z markus $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * Helper for displaying an user info div somewhere
 *
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_UserInfo extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'Zend_View_Helper_UserInfo';

    private $items = array();

    /**
     * Retrieve placeholder for navigation element and optionally set state
     *
     * Single Link elements to be made with $this->url(array('controller'=>'<controller>'), 'default', true);
     *
     * @param  array $data
     * @return Zend_View_Helper_UserData
     */
    public function UserInfo($ar = null, $setType = Zend_View_Helper_Placeholder_Container_Abstract::APPEND, $setPos = 0)
    {
    	if ($ar !== null && is_array($ar)) {
    		$this->items = $ar;
    	}
    	return $this;
    }

    /**
     * Turn helper into string
     *
     * @param  string|null $indent
     * @param  string|null $locale
     * @return string
     */
    public function __toString($indent = null, $locale = null)
    {
    	$session = Zend_Registry::get('session');
		$this->items = $session->authdata;

    	$output = '';

    	if ($session->authdata['authed'] !== true)
			return $output;

#    	$indent = (null !== $indent)
#                ? $this->getWhitespace($indent)
#                : $this->getIndent();
		$indent = '';

        $output .= $indent . "<div id=\"userinfo\">\n";
		$output .= $indent . "\tUser: " . $this->items['authed_username'] . "<br>\n";
		$output .= $indent . "\tName: " . $this->items['authed_fname'] . ' ' . $this->items['authed_lname'] . "<br>\n";
		$output .= $indent . "\tRole: " . $this->items['authed_role'] . "<br>\n";
		if ($this->items['authed_by_crt'] === true)
			$output .= $indent . "\tLoginmethod: CRT<br>\n";
		else
			$output .= $indent . "\tLoginmethod: PASSWD<br>\n";
		$output .= $indent . "</div>\n";

        return $output;
    }
}
