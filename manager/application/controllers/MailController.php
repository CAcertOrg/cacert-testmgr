<?php
/**
 * @author markus
 * $Id: IndexController.php 6 2009-11-18 14:52:50Z markus $
 */

require_once(LIBRARY_PATH . '/imap/imapConnection.php');

class MailController extends Zend_Controller_Action
{
	/**
	 * list of email addresses associated with that account
	 * @var array
	 */
	private $addresses = array();

    public function init()
    {
        /* Initialize action controller here */
		$session = Zend_Registry::get('session');
    	$auth = $session->authdata['authed_permissions'];

    	$action = $this->getRequest()->getActionName();

    	$this->view->leftNav('<a href="' .
    		$this->view->url(array('controller' => 'mail', 'action' => 'index'), 'default', true) .
    		'"' . (($action == 'index')?' class="active"':'') . '>' . I18n::_('View own Mails') . '</a>', Zend_View_Helper_Placeholder_Container_Abstract::SET, 1);
    	if ($session->authdata['authed_role'] == 'Admin') {
	   		$this->view->leftNav('<a href="' .
	    		$this->view->url(array('controller' => 'mail', 'action' => 'full'), 'default', true) .
	    		'"' . (($action == 'full')?' class="active"':'') . '>' . I18n::_('View all Mails') . '</a>', Zend_View_Helper_Placeholder_Container_Abstract::SET, 2);
    	}

    	$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
    	$db = Zend_Db::factory($config->ca_mgr->db->auth->pdo, $config->ca_mgr->db->auth);
    	$emails = new CAcert_User_Emails($db);

    	$this->addresses = $emails->getEmailAddressesByLogin($session->authdata['authed_username']);

    }

    public function indexAction()
    {
    	$config = Zend_Registry::get('config');
    	$session = Zend_Registry::get('session');

		$imap_config = $config->imap;
        $imap = imapConnection::getInstance('cacert', $imap_config);
		$imap->imapSwitchMbox('INBOX');

        $ck = $imap->imapCheck();

        $headers = array();
        for ($i=0; $i < $ck->Nmsgs; $i++) {
        	$header = $imap->imapHeader($i+1);

        	// skip all emails that do not belong to the user
			if (!in_array($header->toaddress, $this->addresses))
				continue;

        	$header->uid = $imap->imapUID($i+1);
        	$header->detailslink = $this->view->url(array('controller' => 'mail', 'action' => 'read', 'uid' => $header->uid), 'default', true);
        	$header->deletelink = $this->view->url(array('controller' => 'mail', 'action' => 'delete', 'uid' => $header->uid), 'default', true);
        	$headers[] = $header;
        }

        $this->view->headers = $headers;
    }

    public function fullAction()
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
        	$header->deletelink = $this->view->url(array('controller' => 'mail', 'action' => 'delete', 'uid' => $header->uid), 'default', true);
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

    /**
     * delete message with unique id
     */
	public function deleteAction()
    {
    	$config = Zend_Registry::get('config');
		$uid = $this->getRequest()->getParam('uid', -1);
		$this->view->returnto = $_SERVER['HTTP_REFERER'];

		if ($uid == -1) {
			$this->view->message = I18n::_('You did not select an email for deletion');
		}
		elseif ($this->view->returnto == '') {
			$this->view->message = I18n::_('Please use the delete icons in the mail inventory to delete mails');
		}
		else {
			$imap_config = $config->imap;
	        $imap = imapConnection::getInstance('cacert', $imap_config);
			$imap->imapSwitchMbox('INBOX');

			$header = $imap->imapFetchOverview($uid);

			$session = Zend_Registry::get('session');

			if ($session->authdata['authed_role'] != 'Admin' && !in_array($header->to, $this->addresses)) {
				$this->view->message = I18n::_('This message does not belong to you');
			}
			else {
	        	$imap->imapDelete($uid);
	        	$imap->imapExpunge();
	        	$this->view->message = I18n::_('Message deleted');
			}
		}
    }
}
