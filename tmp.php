<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/debitnote.php');

// Gather submitted debit note payment details

$accountholder_name     = $_POST['accountholder_name'];
$account_number         = $_POST['account_number'];
$bank_code              = $_POST['bank_code'];
$bank_name              = $_POST['bank_name'];
$bank_bic               = $_POST['bank_bic'];
$bank_iban              = $_POST['bank_iban'];
$ip_address             = $_POST['ip_address'];

$currency = new Currency(intval(isset($_POST['currency_payment']) ? $_POST['currency_payment'] : $cookie->id_currency));
$total = floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', ''));

?>


    
    

    
  
    
    <tr>
        <td>
            {l s='IBAN:' mod='debitnote'}
        </td>
        <td>
            <input type="text" name="bank_iban" id="bank_iban" value="{$bank_iban}" size="50" />
        </td>
    </tr>
    
    
   
    
    

</table>
