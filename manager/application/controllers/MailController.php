<?php
/**
 * @author markus
 * $Id: IndexController.php 6 2009-11-18 14:52:50Z markus $
 */

require_once(LIBRARY_PATH . '/imap/imapConnection.php');

class MailController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
    	$config = Zend_Registry::get('config');
		$imap_config = $config->imap;
        $imap = imapConnection::getInstance('cacert', $imap_config);
		$imap->imapSwitchMbox('INBOX');

        $ck = $imap->imapCheck();

        $headers = array();
        for ($i=0; $i < $ck->Nmsgs; $i++) {
        	$header = $imap->imapHeader($i+1);
        	$header->uid = $imap->imapUID($i+1);
        	$header->detailslink = $this->view->url(array('controller' => 'mail', 'action' => 'read', 'uid' => $header->uid), 'default', true);
        	$headers[] = $header;
        }

        $this->view->headers = $headers;
    }

    public function readAction()
    {
    	$config = Zend_Registry::get('config');
		$imap_config = $config->imap;
        $imap = imapConnection::getInstance('cacert', $imap_config);
		$imap->imapSwitchMbox('INBOX');

		$uid = $this->getRequest()->getParam('uid');

		$body = $imap->imapBodyByUID($uid);

		$this->view->mail_body = $body;
    }
}
