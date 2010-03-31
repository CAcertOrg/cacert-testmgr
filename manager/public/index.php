<?php
/*
 * @done Zend_Locale (http://framework.zend.com/manual/en/zend.locale.functions.html)
 * @done Zend_Translate (Buch 381ff, 383) http://framework.zend.com/manual/en/zend.translate.using.html
 * @done Session Tabelle aufräumen (auto expire eingebaut, Housekeeper fehlt aber)
 * @todo Zend_Auth (LoginController - Reaktion auf falsche Eingaben fehlt noch)
 * @todo Zend_Filter_Input - Zeichensätze aus Userinput filtern
 * @todo Zend_Measure, Zend_Currency
 * @todo Zend_Date
 * @todo Daemon / CLI Zend_Console_Getopt (Buch 203ff)
 * @todo Zend_Mail (Buch 279ff)
 * @todo Zend_Form(!), Zend_Validate, Zend_Filter
 * @todo Zend_Log - Formatierung der Texte
 * @todo Funktionsmodule - jedes Modul prüft die Rechte anhand der Session und Zend_Acl und fügt ggf. einen Link ins Menü ein (TOP / LEFT)
 * @todo addMessages mit übersetzten Strings (LoginController -> getForm, ...)
 * @todo favicon
 * @todo sinnvolle Defaults, wenn system_config leer ist (globale Config BIND)
 * @todo sinnvolle Defaults, wenn system_config leer ist (Organisationsconfig BIND)
 * @todo Links zum Löschen für Zonen / Organisationen, Rechtechecks in ActionController (foreign key constraints beachten!)
 * @todo ConfigBIND left Menu geht nicht aus, wenn man die selektierte Org deaktiviert (init vor Action)
 * @todo Textausgabe, wenn Attribute aus Defaults initialisiert und NICHT aus der DB geladen wurden
 */

require_once('../library/global/defines.php');

try {
	/** Zend Autoloader */
	require_once 'Zend/Loader/Autoloader.php';
	Zend_Loader_Autoloader::getInstance();

	// Create application, bootstrap, and run
	$application = new Zend_Application(
	    APPLICATION_ENV,
	    APPLICATION_PATH . '/configs/application.ini'
	);

	/** override settings from application.ini, if necessary
	$fc = Zend_Controller_Front::getInstance();
	$fc->setControllerDirectory(realpath(APPLICATION_PATH . '/controllers'));
	$fc->setParam('noViewRenderer', false);
	$fc->throwExceptions(true);
	$fc->setParam('noErrorHandler', false);
	*/

	$application->bootstrap()
	            ->run();
} catch (Exception $e) {
	print "Exception: " . $e->getMessage() . "\n";
	print $e->getTraceAsString() . "\n";
	Log::Log()->emerg($e);
}
