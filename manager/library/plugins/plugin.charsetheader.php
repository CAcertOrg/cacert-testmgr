<?php
/**
 * @author markus
 * $Id: plugin.charsetheader.php 13 2009-11-24 14:52:56Z markus $
 */
class CharsetHeader extends Zend_Controller_Plugin_Abstract {
	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$response = $this->getResponse();
		if ($response->canSendHeaders() === true) {
			$response->setHeader('Content-Type', 'text/html; charset=utf-8');
		}
	}
}