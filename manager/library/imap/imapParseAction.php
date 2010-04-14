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

/**
 * Supportklasse für imapCleanMailbox
 * Soll den in der Config pro Mailbox angegebenen Action-String auswerten
 * und in eine automatisiert weiterverarbeitbare Form bringen.
 */
class imapParseAction {
    /**
     * Actions als Konstanten
     * @var integer
     */
    const IMAP_ACTION_DELETE = 1;
    const IMAP_ACTION_ARCHIVE = 2;

    /**
     * gesetzte Action
     * @var integer
     */
    private $action = 0;

    /**
     * Modifikator keep gesetzt?
     * @var boolean
     */
    private $delete_keep = false;

    /**
     * Wert des Modifikators keep
     * @var integer
     */
    private $delete_keep_num = 0;

    /**
     * getAction
     * @return integer
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * getDeleteKeep
     * @return boolean
     */
    public function getDeleteKeep() {
        return $this->delete_keep;
    }

    /**
     * getDeleteKeepNum
     * @return integer
     */
    public function getDeleteKeepNum() {
        return $this->delete_keep_num;
    }

    /**
     * Konstruktor, parst eine Zeile mit Tokens und ermittelt, welche Aktionen
     * auf einer Mailbox ausgeführt werden sollen
     * @param string $action
     */
    public function __construct($action) {
        $args = explode(' ', $action);

        $numargs = count($args);

        for ($arg = 0; $arg < $numargs; $arg++) {
            switch ($args[$arg]) {
                case 'delete':
                    $this->action = self::IMAP_ACTION_DELETE;
                    break;
                case 'keep':
                    $arg++;
                    $this->delete_keep = true;
                    $this->delete_keep_num = $args[$arg];
                    break;
                default:
                    /**
                     * @todo Exception werfen
                     */
                    break;
            }
        }
    }
}
