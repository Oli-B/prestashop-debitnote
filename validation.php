<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/debitnote.php');

// Gather submitted debit note payment details

$context = Context::getContext();
$cart = $context->cart;
$debitnote = new DebitNote();

//if ($cart->id_customer == 0 OR $cart->id_address_delivery == 0 OR $cart->id_address_invoice == 0 OR !$debitnote->active)
//			Tools::redirect('index.php?controller=order&step=1');

// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'debitnote')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

$accountholder_name     = $_POST['accountholder_name'];
$account_number         = $_POST['account_number'];
$bank_code              = $_POST['bank_code'];
$bank_name              = $_POST['bank_name'];
$bank_bic               = $_POST['bank_bic'];
$bank_iban              = $_POST['bank_iban'];
$ip_address             = $_POST['ip_address'];
 
$customer = new Customer((int)$cart->id_customer);
if (!Validate::isLoadedObject($customer))
	Tools::redirect('index.php?controller=order&step=1');
 
//$currency = new Currency(intval(isset($_POST['currency_payment']) ? $_POST['currency_payment'] : $cookie->id_currency));
//$total = floatval(number_format($cart->getOrderTotal(true, 3), 2, '.', ''));
$currency = $context->currency;
$total = (float)($cart->getOrderTotal(true, Cart::BOTH));


//$debitnote->validateOrder($cart->id, 3, $total, $debitnote->displayName, NULL, NULL, $currency->id,$customer->secure_key);

//$message = ('Kontoinhaber:'.$accountholder_name.'Kto:'.$account_number.'  BLZ:'.$bank_code.'  Bank:'.$bank_name.'  BIC:'.$bank_bic.'  IBAN:'.$bank_iban);
//$message = ('Kontoinhaber:'.$accountholder_name);
$message = ('Kontoinhaber:'.$accountholder_name.'<br>'.'  Kto:'.$account_number.'  BLZ:'.$bank_code.'  Bank:'.$bank_name.'  BIC:'.$bank_bic.'  IBAN:'.$bank_iban);

$debitnote->validateOrder($cart->id, Configuration::get('PS_OS_DEBITNOTE'), $total, $debitnote->displayName, $message, NULL, (int)$currency->id, false, $customer->secure_key);

$debitnote->writeDebitNoteDetails($debitnote->currentOrder, $accountholder_name, $account_number, $bank_code, $bank_name, $bank_bic, $bank_iban, '123');

$order = new Order($debitnote->currentOrder);


Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$debitnote->id.'&id_order='.$debitnote->currentOrder.'&key='.$customer->secure_key);


//Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$bankwire->id.'&id_order='.$bankwire->currentOrder.'&key='.$customer->secure_key);




?>