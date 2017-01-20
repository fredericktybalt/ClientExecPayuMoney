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
require_once 'modules/admin/models/GatewayPlugin.php';

/**
* @package Plugins
*/
class PluginPayu extends GatewayPlugin
{

    function getVariables()
    {
        /* Specification
               itemkey     - used to identify variable in your other functions
               type        - text,textarea,yesno,password
               description - description of the variable, displayed in ClientExec
        */

        $variables = array (
                    lang("Plugin Name") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                                        "value"         =>lang("Payu")
                                       ),
                    lang("Payu Sandbox") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you want to set eWay into Test mode for testing. Even for testing you will need an eWay ID, that you can find at eWay's website."),
                                        "value"         =>"0"
                                       ),
                    lang("Payu Merchant Key") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Please enter your Merchant Key here"),
                                        "value"         =>""
                                       ),
                    lang("Payu Merchant Salt") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Please enter your Salt here"),
                                        "value"         =>""
                                       ),
                    lang("Payu Test Merchant Key") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Please enter your Test Merchant Key here"),
                                        "value"         =>""
                                       ),
                    lang("Payu Test Merchant Salt") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Please enter your Test Salt here"),
                                        "value"         =>""
                                       ),                                                            
                   lang("Invoice After Signup") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you want an invoice sent to the customer after signup is complete."),
                                        "value"         =>"1"
                                       ),
                    lang("Accept CC Number") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Selecting YES allows the entering of CC numbers when using this plugin type. No will prevent entering of cc information"),
                                        "value"         =>"0"
                                       ),

                    lang("Generate Invoices After Callback Notification") => array (
                                        "type"          =>"yesno",
                                        "description"   =>lang("Select YES if you prefer CE to only generate invoices upon notification of payment via the callback supported by this processor.  Setting to NO will generate invoices normally but require you to manually mark them paid as you receive notification from processor."),
                                        "value"         =>"1"
                                        ),
                   lang("Check CVV2") => array (
                                        "type"          =>"hidden",
                                        "description"   =>lang("Select YES if you want to accept CVV2 for this plugin."),
                                        "value"         =>"0"
                                      ),                   
                   lang("Signup Name") => array (
                                        "type"          =>"text",
                                        "description"   =>lang("Select the name to display in the signup process for this payment type. Example: eWay or Credit Card."),
                                        "value"         =>"Credit Card/Debit Card/Net Banking"
                                       )

        );
        return $variables;
    }

    function singlepayment($params)
    {
        $TEST_BASE_URL='https://test.payu.in/_payment';
        $PROD_BASE_URL='https://secure.payu.in/_payment';
        $callback_url = mb_substr($params['clientExecURL'],-1,1) == "//" ? $params['clientExecURL']."plugins/gateways/payu/callback.php" : $params['clientExecURL']."/plugins/gateways/payu/callback.php";
        

        if ($params['isSignup']==1) {
            $returnURL        = $params["clientExecURL"]."/order.php?step=5&pass=1";
            $returnURL_Cancel = $params["clientExecURL"]."/order.php?step=5&pass=0";
        }else {
//            $returnURL        = $params["clientExecURL"];
//            $returnURL_Cancel = $params["clientExecURL"];
            $returnURL=$params["invoiceviewURLSuccess"];
            $returnURL_Cancel=$params["invoiceviewURLCancel"];
        }

        $payu = array();
        $payu['firstname'] = $params["userFirstName"]; //M
        $payu['lastname'] = $params["userLastName"];
        $payu['email'] =  $params["userEmail"]; //M
        $payu['amount']= $params["invoiceTotal"];  //M
        $payu['cust_id']=$params["userID"];
        $payu['invoice_num']=$params["invoiceNumber"];
        $payu['productinfo']=$params["invoiceDescription"]; //M
        $payu['address1'] = $params["userAddress"];
//        $payu['address2'] = $params["userAddress"];
        $payu['city'] = $params["userCity"];
        $payu['state'] = $params["userState"];
        $payu['zipcode'] = $params["userZipcode"];
        $payu['country'] = $params["userCountry"];
        $payu['phone'] = $params["userPhone"]; //M
        $payu['surl'] = $callback_url; //M
        $payu['furl'] = $callback_url; //M
        $payu['curl'] = $callback_url;
        $payu['service_provider'] = 'payu_paisa'; //M
        $payu['txnid'] =  substr(hash('sha256', mt_rand() . microtime()), 0, 20) . "INV" . $params["invoiceNumber"]; //M
        $payu['hash'] = ''; //M
        $payu['udf1'] = $params['isSignup'];
        if($params["plugin_payu_Payu Sandbox"])
        {
          $payu['key'] = $params["plugin_payu_Payu Test Merchant Key"];
          $payu['url'] = $TEST_BASE_URL;
          $payu['salt'] = $params["plugin_payu_Payu Test Merchant Salt"];
        }
        else 
        {
          $payu['key'] = $params["plugin_payu_Payu Merchant Key"];
          $payu['url'] = $PROD_BASE_URL;
          $payu['salt'] = $params["plugin_payu_Payu Merchant Salt"];
        }
        $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|||||||||";

        $hashVarsSeq = explode('|', $hashSequence);
          $hash_string = '';  
        foreach($hashVarsSeq as $hash_var) {
            $hash_string .= isset($payu[$hash_var]) ? $payu[$hash_var] : '';
            $hash_string .= '|';
          }

        $hash_string .= $payu['salt'];

        $payu['hash'] = strtolower(hash('sha512', $hash_string));


        $code = '
        <html>
        <body>
          <form action="'.$payu['url'].'" method="post" name="payuForm">
            <input type="hidden" name="key" value="'.$payu['key'].'" />
            <input type="hidden" name="hash" value="'.$payu['hash'].'"/>
            <input type="hidden" name="txnid" value="'.$payu['txnid'].'" />
            <input type="hidden" name="amount" value="'.$payu['amount'].'" />
            <input type="hidden" name="firstname" value="'.$payu['firstname'].'" />
            <input type="hidden" name="email" value="'.$payu['email'].'" />
            <input type="hidden" name="phone" value="'.$payu['phone'].'" />
            <input type="hidden" name="productinfo" value="'.$payu['productinfo'].'" />
            <input type="hidden" name="surl" value="'.$payu['surl'].'" />
            <input type="hidden" name="furl" value="'.$payu['furl'].'" />
            <input type="hidden" name="udf1" value="'.$payu['udf1'].'" />
            <input type="hidden" name="service_provider" value="'.$payu['service_provider'].'" />
            <script language="JavaScript">
            document.forms["payuForm"].submit();
            </script>
            </form>
        </body>
        </html>
            ';


    exit;


    }

    // Not supported?
    function credit($params) {
        return "";
    }
}
?>
