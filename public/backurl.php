<?php

/**
 * This file is called by helloasso when then user has ended its transaction
 * This function display a message to the user : success message or failure message.  
 */

	// *** pervent from Dolibarr login check
	if (!defined('NOLOGIN'))		define("NOLOGIN", 1); // This means this output page does not require to be logged.
	if (!defined('NOCSRFCHECK'))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
	if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
	if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

	// Load Dolibarr environment
	$res = 0;
	// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
	if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
	// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
	$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
	while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
	if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
	if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
	// Try main.inc.php using relative path
	if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
	if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
	if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
	if (!$res) die("Include of main fails");

	// Log the data returned from helloasso
	$returnVars=$_GET;
	$ipn_log_file = 'return_helloasso.log';
	$fp = fopen($ipn_log_file, 'a');
	fwrite($fp, " *** backurl returned from server " . date("d-m-y h:i:s") . " : " . json_encode($returnVars) . "\n");
	fclose($fp);

	// *** Analyse the return cause and display the message
	// print("<div>");
	// if (!empty($conf->global->HELLOASSO_HEADER_AFTER_PAYMENT))
	// 	print $conf->global->HELLOASSO_HEADER_AFTER_PAYMENT;

	$message="message vide";
	$action = $_GET["action"];
	if (empty($action))
		$message="Cette page doit être appelée avec le paramètre action";

	if ($action === "backurl") {
		$message= "Retour sur notre site après à votre demande sur le site Helloasso : Facture (" . $_GET["ref"].")";
	} else if ($action === "errorurl") {
		$message= $message= HELLOASSO_RETURN_MSG_ERROR . "(ref:" $_GET["ref"] . ") - Cause : " .
			$_GET["error"];
	} else if ($action === "returnurl") {
		if ($_GET["code"] === "succeeded")
			$message= HELLOASSO_RETURN_MSG_SUCCESS . "(ref:" $_GET["ref"] . ")";
		else if ($_GET["code"] === "refused")
			$message= HELLOASSO_RETURN_MSG_REFUSED . "(ref:" $_GET["ref"] . ")";
		else
			$message="Retour sur notre site sans cause identifiée par Helloasso,  pour la facture n° : " . $_GET["ref"];
	} else {
		$message="Action de retour non reconnue : " . $action . " - Facture (" . $_GET["ref"] ."). Veuillez contacter l'administrateur";
	}

	global $conf;

		// *** Display message  
	print "<link rel='stylesheet' href='./main.css' type='text/css'>";
	print("<div class='helloassopage'>");
	if (!empty($conf->global->HELLOASSO_HEADER_AFTER_PAYMENT))
		print '<div class=\'helloassoheader\'>'. $conf->global->HELLOASSO_HEADER_AFTER_PAYMENT.'</div>';
	
	print '<div class=\'helloassomessage\'>'  . $message;
	if (!empty($conf->global->HELLOASSO_URL_AFTER_PAYMENT))
		print  '<div class=\'helloassobutton\'><a href="' .$conf->global->HELLOASSO_URL_AFTER_PAYMENT. '"><button class=\'hellobutton\'>Cliquez ici pour revenir sur le site de réservation</button></a></div>';
	print "</div>";
