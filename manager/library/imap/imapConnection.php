<?php
/**
 * @author markus
 * $Id: $
 */

/**
 * required files
 * @ignore
 */
require_once('exception.IMAPException.php');

define('IMAP_RETRIES', 5);

/**
 * Wraps PHP built in imap commands within a class, features open, hold, close connection and
 * issue imap commands.
 *
 * @author markus
 */
class imapConnection {

	/**
	 * Array mit den bereits vorhandenen Instanzen
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Instanzname, die unterschiedlichen Entitäten werden über den Namen
	 * auseinandergehalten.
	 * @var string
	 */
	private $instanceName = '';

	/**
	 * Configsection
	 * @var Config
	 */
	private $config = null;

	/**
	 * IMAP Resource
	 * @var imap_stream
	 */
	private $imap = null;

	/**
	 * Servername, Port, Flags
	 * @var string
	 */
	private $server = '';

    /**
     * Name der zuletzt geöffneten Mailbox
     * @var string
     */
    private $mbox = '';

	/**
	 * wird auf true gesetzt, wenn imapPing die Connection neu aufbaut
	 * @var boolean
	 */
	private $reopenedConnection = false;

	/**
	 * liefert eine Liste der verfügbaren Folder
	 * @param string $pattern
	 * @return array
	 */
	public function imapList($pattern = '*') {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_list($this->imap, $this->server, $pattern);
	}


	/**
	 * Checkt die Anzahl Messages in einer Mailbox
	 * return array
	 */
	public function imapCheck() {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_check($this->imap);
	}


	/**
	 * per imap_reopen die aktuelle Connection auf eine andere mbox umstellen
	 * @param string $mbox
	 * @return boolean
	 */
	public function imapSwitchMbox($mbox) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		if (imap_reopen($this->imap, $this->server.$mbox) === false) {
			throw new IMAPException(__METHOD__ . ' reopen failed');
		}

        $this->mbox = $mbox;

		return true;
	}


	/**
	 * setzt ein Flag bei allen in $sequence aufgeführten Messages
	 * @param string $sequence
	 * @param string $flag
	 * @param integer $options
	 * @return boolean
	 */
	public function imapSetflagFull($sequence, $flag, $options = 0) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_setflag_full($this->imap, $sequence, $flag, $options);
	}


	/**
	 * liefert die Mailheader
	 * @return array
	 */
	public function imapHeaders() {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_headers($this->imap);
	}

	/**
	 * liefert die Header zu genau einer Mail mit der gegebenen ID
	 * @param integer $number
	 * @return array
	 */
	public function imapHeader($number) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_headerinfo($this->imap, $number);
	}

	/**
	 * liefert die Header zu genau einer Mail mit der gegebenen UID
	 * @param integer $uid
	 * @return array
	 */
	public function imapFetchHeader($uid) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}

		$ret = imap_fetchheader($this->imap, $uid, FT_UID);

        return $ret;
	}

	/**
	 * liefert die Header zu genau einer Mail mit der gegebenen UID
	 * @param integer $uid
	 * @return array
	 */
	public function imapFetchOverview($uid) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}

		$ret = imap_fetch_overview($this->imap, $uid, FT_UID);

        return $ret[0];
	}

	/**
	 * liefert den Body zu genau einer Mail mit der gegebenen ID
	 * @param integer $number
	 * @return string
	 */
	public function imapBody($number) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_body($this->imap, $number);
	}

	/**
	 * liefert den Body zu genau einer Mail mit der gegebenen UID
	 * @param integer $uid
	 * @return string
	 */
	public function imapBodyByUID($uid) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_body($this->imap, $uid, FT_UID );
	}

	/**
	 * markiert die Nachricht mit der unique ID zum löschen
	 * @param integer $uid
	 * return boolean
	 */
	public function imapDelete($uid) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		$ret = imap_delete($this->imap, $uid, FT_UID);

		if ($ret !== true) {
			print "imap delete returned false for ".$uid."\n";
		}

		return $ret;
	}

	/**
	 * löscht alle zum löschen markierten Nachrichten
	 * @return boolean
	 */
	public function imapExpunge() {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_expunge($this->imap);
	}

	/**
	 * kopiert die Nachricht mit der gegebenen uid in die gegebene Mailbox *auf dem selben Server*
	 * @param integer $uid
	 * @param string $dest_mbox
	 * @return boolean
	 */
	public function imapMailCopy($uid, $dest_mbox) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_mail_copy($this->imap, $uid, $dest_mbox, CP_UID);
	}

	/**
	 * verschiebt die Nachricht mit der gegebenen uid in die gegebene Mailbox *auf dem selben Server*
	 * @param integer $uid
	 * @param string $dest_mbox
	 * @return boolean
	 */
	public function imapMailMove($uid, $dest_mbox) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);
        /*
         * dont't add the server part,
         * only the mailbox name works fine
         *
         * $dest_mbox = $this->server.$dest_mbox;
         */
		return imap_mail_move( $this->imap, $uid, $dest_mbox, CP_UID);
	}

	/**
	 * legt eine neue Mailbox *auf dem selben Server* an
	 * @param string $mbox
	 * @return boolean
	 */
	public function imapCreateMailbox($mbox) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_createmailbox($this->imap, $this->server.$mbox);
	}

	/**
	 * fragt ab, ob eine mbox unterhalb von mbox_root existiert und liefert true zurück, falls ja
	 * Funktion existiert nicht direkt als IMAP Kommando, aus einzelnen Kommando's zusammengebaut
	 *
	 * @param string $mbox_root
	 * @param string $mbox
	 * @return boolean
	 */
	public function imapMailboxExists($mbox_root, $mbox) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		$folderlist = $this->imapList($mbox_root);
		$foundFolder = false;
		foreach ($folderlist as $folder) {
			if (strpos($folder, $mbox) !== false) {
				return true;
			}
		}

		return false;
	}

	const AR_YYYY = 'Y';
	const AR_YYYYMM = 'Ym';
	const AR_YYYYMMDD = 'Ymd';

	/**
	 * erzeugt eine Archivmailbox zur Mailbox $mbox, dabei wird das Archiv unterhalb von $mbox
	 * auf dem selben Server angelegt, der Name der Mailbox enthält je nach $mode noch einen Datumsstamp
	 * Wenn der Input ($mbox) bereits mehrere Ebenen enthält (NOC3.domain.incoming z.B.), dann
	 * wird automatisch nur der am weitesten rechts stehende Teil extrahiert und benutzt.
	 * NOC3.domain.incoming => NOC3.domain.incoming.incoming-200705
	 * @param string $mbox
	 * @param string $mode
	 * @param integer $timestamp
	 * @param string $delimiter
	 * @return string
	 */
	public static function imapMakeArchiveName($mbox, $mode, $timestamp = null, $delimiter = '-') {
		if ($timestamp === null)
			$timestamp = time();

		$ar = explode('.', $mbox);

		$sub_mbox = $ar[count($ar) - 1];

		return $mbox.'.'.$sub_mbox.$delimiter.date($mode,$timestamp);
	}

	public static function imapMakePrefixedArchiveName($mbox, $mode, $prefix = '', $timestamp = null, $delimiter = '-') {
		if ($timestamp === null)
			$timestamp = time();

		$ar = explode('.', $mbox);

		$sub_mbox = $ar[count($ar) - 1];

		return $mbox.'.'.$prefix.$delimiter.$sub_mbox.$delimiter.date($mode,$timestamp);
	}

	/**
	 * liefert die unique ID der Nachricht mit der laufenden msg_number
	 * @param integer $msg_number
	 * @return integer
	 */
	public function imapUID($msg_number) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_uid($this->imap, $msg_number);
	}


	/**
	 * liefert die laufende msg_number der Nachricht, die die unique ID uid hat
	 * @param integer $uid
	 * @return integer
	 */
	public function imapMsgNo($uid) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}
        $this->imapPing(true);

		return imap_msgno($this->imap, $uid);
	}


	/**
	 * prüft, ob die Connection noch aktiv ist, Exception falls keine Connection definiert ist,
     * oder die Connection geschlossen wurde
     * wenn reconnect = true, dann wird bei einer geschlossenen Connection die Connection neu aufgebaut
     * @param boolean $reconnect
	 * @return boolean
	 */
	public function imapPing($reconnect = false) {
		if ($this->imap === null) {
			throw new IMAPException(__METHOD__ . ' not connected');
		}

		$ret = imap_ping($this->imap);

        if ($ret === false) {
            if ($reconnect === true) {
				$this->imap = $this->imapOpen($this->server.$this->mbox,
					$this->config->username,
					$this->config->password,
					OP_HALFOPEN);

                $ret = imap_ping($this->imap);

                if ($ret === false) {
                    throw new IMAPException(__METHOD__ . ' reconnect failed');
                }

                $this->reopenedConnection = true;
            }
            else {
                throw new IMAPException(__METHOD__ . ' not connected');
            }
        }

		return true;
	}


	public function __destruct() {
		if ($this->imap !== null) {
			imap_close($this->imap);
			$this->imap = null;
		}
	}


	/**
	 * true, wenn imapPing die Connection neu aufgemacht hat
	 * Variable wird auf false gesetzt wenn $flush true ist
	 * @param boolean $flush
	 * @return boolean
	 */
	public function connectionReopened($flush = true) {
		$ret = $this->reopenedConnection;
		if ($flush === true) {
			$this->reopenedConnection = false;
		}
		return $ret;
	}


	/**
	 * interne IMAP Open Methode
	 *
	 * @param string $servername
     * @param string $username
     * @param string password
     * @param integer $flags
	 * @return resource
	 */
    protected function imapOpen($server, $username, $password, $flags) {
	    return imap_open($server, $username, $password, $flags);
    }


	/**
	 * privater Konstruktor, wird exklusiv von getInstance aufgerufen
	 *
	 * @param $instanceName
	 * @param $config
	 */
	protected function __construct($instanceName, $config) {
		$this->instanceName = $instanceName;
		$this->config = $config;

		if (!isset($this->config->mailhost)) {
			throw new IMAPException(__METHOD__ . ' config attribute missing: "mailhost"');
		}
		if (!isset($this->config->username)) {
			throw new IMAPException(__METHOD__ . ' config attribute missing: "username"');
		}
		if (!isset($this->config->password)) {
			throw new IMAPException(__METHOD__ . ' config attribute missing: "password"');
		}
		if (!isset($this->config->port)) {
			throw new IMAPException(__METHOD__ . ' config attribute missing: "port"');
		}

		$this->server = '{'.$this->config->mailhost.':'.$this->config->port.'/imap';
		if( isset($this->config->use_tls) &&
			$this->config->use_tls != 0 ) {
			$this->server .= '/tls';
		}
		$this->server .= '/novalidate-cert}';

		$mbox = '';

        $this->mbox = $mbox;

		$this->imap = null;

		$this->imap = $this->imapOpen($this->server.$mbox,
			$this->config->username,
			$this->config->password,
			OP_HALFOPEN);

		if ($this->imap === false) {
			$this->imap = null;
			throw new IMAPException(__METHOD__ . ' not connected');
		}

		$this->reopenedConnection = false;
	}


	/**
	 * sucht nach einer bereits vorhandenen Instanz, wird keine gefunden,
	 * dann wird eine neue Instanz angelegt.
	 * Man kann die Config-Variable weglassen, wenn man sicher ist, dass
	 * bereits eine Instanz mit dem gewünschten instanceName existiert,
	 * existiert aber keiner, dann liefert getInstance eine Exception.
	 *
	 * @param $instance
	 * @param $config
	 * @return imapConnection
	 */
	public static function getInstance($instanceName,$config = null) {
		if (!self::$instances)
			self::$instances = array();

		foreach (self::$instances as $instance) {
			if ($instance->getInstanceName() == $instanceName)
				return $instance;
		}

		/*
		if (!$config instanceof Config) {
			throw new IMAPException(__METHOD__ . ' no config');
		}
		*/

		$object = new imapConnection($instanceName, $config);

		self::$instances[] = $object;

		return $object;
	}


	/**
	 * Liefert den Namen der aktuellen Instanz
	 * @return string
	 */
	public function getInstanceName() {
		return $this->instanceName;
	}


}
