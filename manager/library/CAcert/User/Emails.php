<?php

class CAcert_User_Emails {
	public function __construct() {
		Log::Log()->debug(__METHOD__);
	}

	/**
	 * get list of email addresses by login, needed to be able to filter emails
	 * @param string $addr
	 * @return array
	 */
	public function getEmailAddressesByLogin($addr) {
		$db = Zend_Registry::get('auth2_dbc');

		/**
		 * find out user id by email address
		 */
		$sql = 'select users.id from users where email=?';

		$id = $db->fetchOne($sql, array($addr));

		/**
		 * get secondary email addresses
		 */
		$sql = 'select email.email from email where memid=?';

		$res = $db->query($sql, array($id));

		$emails = array();

		$num = $res->rowCount();
		for ($i = 0; $i < $num; $i++) {
			$row = $res->fetch(PDO::FETCH_ASSOC);
			$emails[] = $row['email'];
		}

		/**
		 * get additional addresses by domains
		 */
		$sql = 'select domains.domain from domains where memid=?';

		$res = $db->query($sql, array($id));
		$num = $res->rowCount();
		$variants = array('root','hostmaster','postmaster','admin','webmaster');
		for ($i = 0; $i < $num; $i++) {
			$row = $res->fetch(PDO::FETCH_ASSOC);

			foreach ($variants as $variant) {
				$emails[] = $variants . '@' . $row['domain'];
			}
		}

		Log::Log()->debug(__METHOD__ . ' mail addresses ' . var_export($emails, true));
		return $emails;
	}
}