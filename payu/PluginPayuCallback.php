/**
 *
 *
 * ClientExec Plugin
 *
 * ClientExec payment gateway plugin PayuMoney
 *
 * @Author : Frederick T Tybalt
 * 
 * Version : 1.0
 *
 * Release Date : 7 Jun 2016 
 *
 * Contact : social@rick.co.in
 *
 *
 */


<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginPayuCallback extends PluginCallback
{
    var $pluginFolderName = 'payu';  

    function processCallback()
    {
        $pluginName = $this->settings->get('plugin_'.$this->pluginFolderName.'_Plugin Name');

        $payur = array();
        $payur['status'] = $_POST["status"];
        $payur['firstname'] = $_POST["firstname"];
        $payur['amount'] = $_POST["amount"];
        $payur['txnid'] = $_POST["txnid"];
        $payur['hash'] = $_POST["hash"];
        $payur['key'] = $_POST["key"];
        $payur['productinfo'] = $_POST["productinfo"];
        $payur['email'] = $_POST["email"];
        $payur['error'] = $_POST["Error"];
        $payur['mihpayid'] = $_POST["mihpayid"];
        $payur['mode'] = $_POST["mode"];
        $payur['PG_TYPE'] = $_POST["PG_TYPE"];
        $payur['bank_ref_num'] = $_POST["bank_ref_num"];
        $payur['unmappedstatus'] = $_POST["unmappedstatus"];
        $payur['payuMoneyId'] = $_POST["payuMoneyId"];
        $payur['udf1'] = $_POST["udf1"]; // For Checking Sign up = 1

        // Create Plugin class object to interact with CE.
        $tInvoiceID = explode("INV", $payur['txnid']);
        $payur['InvoiceID'] = (count($tInvoiceID) > 1 ? $tInvoiceID[1] : $payur['txnid']);
        $cPlugin = new Plugin($payur['InvoiceID'], $this->pluginFolderName, $this->user);
        $cPlugin->setAmount($payur['amount']);
        $cPlugin->setAction('charge');

        if($cPlugin->GetPluginVariable("plugin_payu_Payu Sandbox"))
        {
          $payur['org_key'] = $cPlugin->GetPluginVariable("plugin_payu_Payu Test Merchant Key");
          $payur['salt'] = $cPlugin->GetPluginVariable("plugin_payu_Payu Test Merchant Salt");
        }
        else 
        {
          $payur['org_key'] = $cPlugin->GetPluginVariable("plugin_payu_Payu Merchant Key");
          $payur['salt'] = $cPlugin->GetPluginVariable("plugin_payu_Payu Merchant Salt");
        }        


        If (isset($_POST["additionalCharges"])) {
            $payur['additionalCharges'] = $_POST["additionalCharges"];
            $retHashSeq = $payur['additionalCharges'].'|'.$payur['salt'].'|'.$payur['status'].'||||||||||'.$payur['udf1'].'|'.$payur['email'].'|'.$payur['firstname'].'|'.$payur['productinfo'].'|'.$payur['amount'].'|'.$payur['txnid'].'|'.$payur['key'];
        }
        else {    
            $retHashSeq = $payur['salt'].'|'.$payur['status'].'||||||||||'.$payur['udf1'].'|'.$payur['email'].'|'.$payur['firstname'].'|'.$payur['productinfo'].'|'.$payur['amount'].'|'.$payur['txnid'].'|'.$payur['key'];
        }
        
        $hash = hash("sha512", $retHashSeq);




        if (($hash != $payur['hash']) && ($payur['org_key'] != $payur['key'])) {
            echo "Invalid Transaction. Please try again";
        }
        else {
            switch($payur['status']) {
                case "success":
                    $transaction = " Your Payment of " . $payur['amount'] . " was accepted";
                    $cPlugin->PaymentAccepted($payur['amount'],$transaction,$payur['InvoiceID']);

// check sign up url
            if ( $payur['udf1']  == 1 ) {
                if ( $this->settings->get('Signup Completion URL') != '' ) {
                    $returnURL = $this->settings->get('Signup Completion URL'). '?success=1';
                } else {
                    $returnURL = CE_Lib::getSoftwareURL()."/order.php?step=complete&pass=1";
                    }
            } 
            else {
                $returnURL = CE_Lib::getSoftwareURL()."/index.php?fuse=billing&paid=1&controller=invoice&view=invoice&id=" . $tInvoiceID;
            }
            header("Location: " . $returnURL);
                break;

                case "pending":
                    $transaction = " Your Payment of ". $payur['amount'] . " was pending";
                    $cPlugin->PaymentPending($transaction,$payur['InvoiceID']);

 // check sign up url
            if ( $payur['udf1']  == 1 ) {
                if ( $this->settings->get('Signup Completion URL') != '' ) {
                    $returnURL = $this->settings->get('Signup Completion URL'). '?success=0';
                } else {
                    $returnURL = CE_Lib::getSoftwareURL()."/order.php?step=complete&pass=0";
                    }
            } 
            else {
                $returnURL = CE_Lib::getSoftwareURL()."/index.php?fuse=billing&paid=0&controller=invoice&view=invoice&id=" . $tInvoiceID;
            }
            header("Location: " . $returnURL);                   
                break;

                case "failure":
                    $transaction = " Your Payment of " . $payur['amount'] . " was rejected";
                    $cPlugin->PaymentRejected($transaction);
                    CE_Lib::log(4, "$pluginName callback returned an error. Code: " . $payur['Error']);

  // check sign up url
            if ( $payur['udf1']  == 1 ) {
                if ( $this->settings->get('Signup Completion URL') != '' ) {
                    $returnURL = $this->settings->get('Signup Completion URL'). '?success=0';
                } else {
                    $returnURL = CE_Lib::getSoftwareURL()."/order.php?step=complete&pass=0";
                    }
            } 
            else {
                $returnURL = CE_Lib::getSoftwareURL()."/index.php?fuse=billing&paid=0&controller=invoice&view=invoice&id=" . $tInvoiceID;
            }
            header("Location: " . $returnURL);                  
                break;
            }
               
        }  
        exit; 

    }

}

