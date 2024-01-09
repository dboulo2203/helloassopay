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

        try {
 
            // ***  Get the invoice
            $invoice = new Facture($db);
            $result = $invoice->fetch($invoiceId);

            if (!$result) {
                throw new Exception('Invoice not found or error getting invoice : ' . $invoiceId);
            }
            $invoice_id = $invoice->id;

            if ($invoice->paye==="1")
                throw new Exception("Cette facture est déjà payée. ");

            $totalpaid = $invoice->getSommePaiement();
            $totalcreditnotes = $invoice->getSumCreditNotesUsed();
            $totaldeposits = $invoice->getSumDepositsUsed();
            $invoice->remaintopay_calculated = price2num($invoice->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');
            // echo "$invoice->remaintopay_calculated" . $invoice->remaintopay_calculated;
           //  echo "remain to pay " .$remaintopay;
           if (!(floatval($invoice->remaintopay_calculated) >0))
                throw new Exception("Il ne reste rien à payer sur cette facture. Remain=". $invoice->remaintopay_calculated);
                

            // *** Get the customer/thirdparty
            $thirdparty_id = $invoice->socid;
            $thirdparty = new Societe($db);
            $resultThirdparty = $thirdparty->fetch($thirdparty_id);           
            if (!$resultThirdparty) {
                throw new Exception('l\'adhérent n\'a pas été trouvé :' . $thirdparty_id);
            }
  
            // *** Return data
            return array($invoice, $thirdparty);

        } catch (Exception $e) {
 
            throw new Exception(  $e->getMessage(). " : "  . $invoiceId);
         }
    }

    /**
     * on helloasso successful return, we create the payment 
     */
    function createInvoicePayment($data) {
   
        global  $db;
        global $conf;

        // echo(json_encode($data). "<br>");
        try{
            // *** Get the data
    
            // *** Open the invoice
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $facture = new Facture($db);
            $result = $facture->fetch($data->invoice_id);

            if (!$result) {
                throw new Exception(' Invoice not found : ' . $data->invoice_id); 
            }
            // log_in_file( );i
                        
            // ****
            $amount= (float) $data->paymentamount/100;
            if (((float) $data->paymentamount)/100 != (float) $facture->total_ttc){
                $realAmount=((float) $data->paymentamount)/100;
                throw new Exception ("Montants paiement (".$realAmount.") et montant facture (".(float) $facture->total_ttc.") différents. InvoiceID : " . $invoice_id . " - ",600);
            }
                // Get the customer
            $thirdparty_id=$facture->socid;

            $thirdparty = new Societe($db);
            $resultThirdparty= $thirdparty->fetch($thirdparty_id);
            if (!$resultThirdparty){
                throw new Exception('thirdparty not found', 600); 		
            }
            // log_in_file();
                
            // *** Create payment with API function
    /*        include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/api_invoices.class.php';
            $invoicesAPI= nex Invoices();
            $retourCreatePayment = $invoicesAPI->addPaymentDistributed(
                [$data->invoice_id =>  ["amount"=> $amount ,  "multicurrency_amount"=> ""]], 
                (new DateTime())->getTimestamp(),
                $conf->global->HELLOASSO_PAYMENTMODE,
                "yes",
                $conf->global->HELLOASSO_BANK_ACCOUNT_FOR_PAYMENTS,
                "",
                "",
                "",
                $conf->global->HELLOASSO_PAYMENTMODE,
                true
            );
           if ($retourCreatePayment == false) {
                throw new Exception ("Erreur de création du paiement" );
            }			
    */

            // *** Create payment with API call
            $payment = [
                        "arrayofamounts" => [$data->invoice_id =>  ["amount"=> $amount ,  "multicurrency_amount"=> ""]]	,				
                        "datepaye" => (new DateTime())->getTimestamp(),
                        // "paymentid"=> $conf->global->HELLOASSO_PAYMENTMODE,
                        "paymentid"=> $conf->global->HELLOASSO_PAYMENTMODE,
                        "closepaidinvoices"=> "yes",
                         "accountid"=> $conf->global->HELLOASSO_BANK_ACCOUNT_FOR_PAYMENTS,
                        "num_paiement"=> "",
                        "comment"=> "",
                        "chqemetteur"=> "",
                        "fk_paiement"=> $conf->global->HELLOASSO_PAYMENTMODE,
                        "accepthigherpayment"=> true
                    ];
            echo " payment : " . json_encode($payment). "<br>";
            $apiKey= $conf->global->HELLOASSO_DOLIKEY_FOR_PAYMENTCREATE;
            // "9d901cdc8e88a2dd69338a2e5300b09db6b46eeb";
            $protocol= isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
            $apiUrl= $protocol."://" . $_SERVER['HTTP_HOST']."/dolibarr/api/index.php/";

            $paymentId = $this->CallAPI("POST", $apiKey, $apiUrl . "invoices/paymentsdistributed", json_encode($payment));	
            if ($paymentId == false) {
                throw new Exception ("Erreur de création du paiement" );
            }			
            // log_in_file(" *** returned from HelloAsso server  : ". json_encode($returnVars) . " -	invoice Found : " . $data->invoice_id . ",	- customer Found : " . $thirdparty_id . ", " .$thirdparty->ref. " - Payment created  : " . $paymentId );
                
			
	    } catch(Exception $exp) {
		    throw new Exception($exp->getMessage(),$exp->getCode());
        // log_in_file("*** Error : ".	$exp->getMessage() . " *** returned from Up2pay server  : ". json_encode($returnVars) . "\n");

	    }		
    }

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
