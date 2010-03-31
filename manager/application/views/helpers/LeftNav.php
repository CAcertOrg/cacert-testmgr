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
 * @version    $Id: LeftNav.php 8 2009-11-24 10:32:47Z markus $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_View_Helper_Placeholder_Container_Standalone */
require_once 'Zend/View/Helper/Placeholder/Container/Standalone.php';

/**
 * Helper for building an applications top navigation bar
 *
 * @uses       Zend_View_Helper_Placeholder_Container_Standalone
 * @package    Zend_View
 * @subpackage Helper
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_View_Helper_LeftNav extends Zend_View_Helper_Placeholder_Container_Standalone
{
    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'Zend_View_Helper_LeftNav';

    protected $items = array();

    /**
     * Retrieve placeholder for navigation element and optionally set state
     *
     * Single Link elements to be made with $this->url(array('controller'=>'<controller>'), 'default', true);
     *
     * @param  string $link
     * @param  string $setType
     * @param  string $setPos
     * @return Zend_View_Helper_LeftNav
     */
    public function leftNav($link = null, $setType = Zend_View_Helper_Placeholder_Container_Abstract::APPEND, $setPos = 0)
    {
    	$link = (string) $link;
        if ($link !== '') {
            if ($setType == Zend_View_Helper_Placeholder_Container_Abstract::SET) {
                if ($setPos != 0)
                	$this->items[$setPos] = $link;
                else
                	$this->items[] = $link;
            } elseif ($setType == Zend_View_Helper_Placeholder_Container_Abstract::PREPEND) {
                $this->items = array_merge(array($link), $this->items);
            } else {
                $this->items[] = $link;
            }
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
		$output = '';
    	$indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        $output .= $indent . "<ul>\n";
        foreach ($this->items as $item) {
                $output .= $indent . "<li>" . $item . "</li>\n";
 		}
		$output .= $indent . "</ul>\n";

        return $output;
    }
}
