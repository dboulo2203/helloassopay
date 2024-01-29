<?php


/**
 * Set of functions that operate action on the DOlibarr database 
 */
class DoliWrapper
{


    /**
     * Return an array with the Dolibarr object of the invoice and the thirdparty
     * @param : invoiceId : Id of the invoice
     * @return : array : 
     */
    function getInvoiceDetails($invoiceId)
    {
        include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
        include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
         
        global  $db;
        global $conf;
        global $langs;

       //  try {
 
        // Translations
            $langs->loadLangs(array("admin", "helloassopay@helloassopay"));

            // ***  Get the invoice
            $invoice = new Facture($db);
            $result = $invoice->fetch($invoiceId);

            if (!$result) {
                throw new Exception($langs->trans("ErrorInvoiceNotFound")  . " (".$invoiceId.")");
            }
            $invoice_id = $invoice->id;

            if ($invoice->statut !=="1")
                throw new Exception($langs->trans("ErrorInvoiceNotPayable")  . " (".$invoiceId.")");

            // *** Define the remain to pay
            $totalpaid = $invoice->getSommePaiement();
            $totalcreditnotes = $invoice->getSumCreditNotesUsed();
            $totaldeposits = $invoice->getSumDepositsUsed();
            $invoice->remaintopay_calculated = price2num($invoice->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');
            if (!(floatval($invoice->remaintopay_calculated) >0))
                throw new Exception($langs->trans("ErrorNothingToPayInInvoice") . $invoice->remaintopay_calculated);
                

            // *** Get the customer/thirdparty
            $thirdparty_id = $invoice->socid;
            $thirdparty = new Societe($db);
            $resultThirdparty = $thirdparty->fetch($thirdparty_id);           
            if (!$resultThirdparty) {
                throw new Exception($langs->trans("ErrorThirdpartyNotFound")  . " (".$thirdparty_id.")" );
            }
  
            // *** Return data
            return array($invoice, $thirdparty);

       //  } catch (Exception $e) {
 
       //     throw new Exception(  $e->getMessage(). " : "  . $invoiceId, );
       //   }
    }

    /**
     * on helloasso successful return, we create the payment 
     * 
     */
    function createInvoicePayment($data) {
   
        global  $db;
        global $conf;

        // echo(json_encode($data). "<br>");
       //  try{
            // *** Get the data 
            // *** Open the invoice
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $facture = new Facture($db);
            $result = $facture->fetch($data->invoice_id);

            if (!$result) {
                throw new Exception($langs->trans("ErrorInvoiceNotFound") . " (" . $data->invoice_id. ")"); 
            }
            // log_in_file( );i
                        
            // ****
            $amount= (float) $data->paymentamount/100;
  
            // *** Get the customer
            $thirdparty_id=$facture->socid;

            $thirdparty = new Societe($db);
            $resultThirdparty= $thirdparty->fetch($thirdparty_id);
            if (!$resultThirdparty){
                throw new Exception($langs->trans("ErrorThirdpartyNotFound") , 600); 		
            }
                 
             // *** Create payment with API call
  /*           $payment = [
                        "arrayofamounts" => [$data->invoice_id =>  ["amount"=> $amount ,  "multicurrency_amount"=> ""]]	,				
                        "datepaye" => (new DateTime())->getTimestamp(),
                        "paymentid"=> $conf->global->HELLOASSO_PAYMENTMODE,
                        "closepaidinvoices"=> "yes",
                         "accountid"=> $conf->global->HELLOASSO_BANK_ACCOUNT_FOR_PAYMENTS,
                        "num_paiement"=>  $paymentdata->orderid,
                        "comment"=> $data->paymentid,
                        "chqemetteur"=> "",
                        "fk_paiement"=> $conf->global->HELLOASSO_PAYMENTMODE,
                        "accepthigherpayment"=> true
                    ];
           
            $apiKey= $conf->global->HELLOASSO_DOLIKEY_FOR_PAYMENTCREATE;
            $protocol= isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
            $apiUrl= $protocol."://" . $_SERVER['HTTP_HOST']."/dolibarr/api/index.php/";

            $paymentId = $this->CallAPI("POST", $apiKey, $apiUrl . "invoices/paymentsdistributed", json_encode($payment));	
 
            if ($paymentId == false) {
                throw new Exception ("Erreur de création du paiement" );
            }			
  */               
        // *** // *** Create payment with API functions call 
  		require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
        require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/api_invoices.class.php';
	
        $_GET['DOLAPIKEY']=$conf->global->HELLOASSO_DOLIKEY_FOR_PAYMENTCREATE;
        $apiAccess = new DolibarrApiAccess($db);
        $apiAccess->__isAllowed();

        $apiInvoices = new Invoices($db);
        $paymentID = $apiInvoices->addPaymentDistributed(
             [$data->invoice_id =>  ["amount"=> $amount ,  "multicurrency_amount"=> ""]],
            (new DateTime())->getTimestamp(),
            $conf->global->HELLOASSO_PAYMENTMODE,
            "yes",
            $conf->global->HELLOASSO_BANK_ACCOUNT_FOR_PAYMENTS,
            $paymentdata->orderid,
            $data->paymentid,
            "",
            $conf->global->HELLOASSO_PAYMENTMODE,
            "",
            true
        );			
    }

    /**
     * The curl calls to the Dolibarr API (not used)
     */
	public function CallAPI($method, $apikey, $url, $data = false, $jsonDataEncoding = true)
	{
		$curl = curl_init();
		$httpheader = ['DOLAPIKEY: ' . $apikey];

		switch ($method) {
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);
				if ($jsonDataEncoding)
					$httpheader[] = "Content-Type:application/json";

				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

				break;
			case "PUT":

				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				if ($jsonDataEncoding)
					$httpheader[] = "Content-Type:application/json";

				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		// Optional Authentication:
		//    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		//    curl_setopt($curl, CURLOPT_USERPWD, "username:password");

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);

		$result = curl_exec($curl);
		$curl_error = curl_error($curl);
		curl_close($curl);

		// *** Error return false
		if ($result == "false")
			throw new Exception("Error return false, " . $curl_error, 500);

		//*** error : return Evaluating 
		$testValid = json_decode($result, true);


		if (is_null($testValid))
			throw new Exception("Error invalid return value : (" . $result . ")", 500);

		// *** If there is an error then we throw an exception
		if ($testValid["error"]["code"] == 404)
			return false;

		if (isset($testValid["error"]) && $testValid["error"]["code"] >= "300") {
			throw new Exception($testValid["error"]["message"], $testValid["error"]["code"]);
		}

		return $testValid;
	}
	
}
