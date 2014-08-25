<?php


/**
 * @since 1.5.0
 */
class DebitnoteValidationModuleFrontController extends ModuleFrontController
{
	public function postProcess()
	{
		$cart = $this->context->cart;
		
		$invoice = new Address((int)$cart->id_address_invoice);
		$customer = new Customer($cart->id_customer);
		
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

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
	
		
		// Getting differents vars
		$context = Context::getContext();
		$id_lang = (int)$context->language->id;
		$id_shop = (int)$context->shop->id;
		
		if (!$context)
			$context = Context::getContext();
		$order = new Order($this->id_order);
		
		//$order = $params['order'];
		
		//$configuration = Configuration::getMultiple(array('PS_SHOP_EMAIL', 'PS_MAIL_METHOD', 'PS_MAIL_SERVER', 'PS_MAIL_USER', 'PS_MAIL_PASSWD', 'PS_SHOP_NAME', 'PS_MAIL_COLOR'), $id_lang, null, $id_shop);
			
				
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');
			
		if (!Validate::isLoadedObject($invoice))
			Tools::redirect('index.php?controller=order&step=1');
	
			
			
		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$message = ('Kontoinhaber:'.$accountholder_name.'<br>'.'  Kto:'.$account_number.'  BLZ:'.$bank_code.'  Bank:'.$bank_name.'  BIC:'.$bank_bic.'  IBAN:'.$bank_iban);
		
		//Mail Vars für Kunden Mail debitnote.html
		 $mailVars = array(			
			'{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
			'{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
			'{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
			'{email}' => $this->context->customer->email,
			'{date}' => Tools::displayDate(date('Y-m-d H:i:s'),null , 0),			
			'{invoice_company}' => $invoice->company,
			'{invoice_firstname}' => $invoice->firstname,
			'{invoice_lastname}' => $invoice->lastname,
			'{invoice_address2}' => $invoice->address1,
			'{invoice_address1}' => $invoice->address2,
			'{invoice_city}' => $invoice->city,
			'{invoice_postal_code}' => $invoice->postcode,
			'{invoice_country}' => $invoice->country,
			'{BankBIC}' => $bank_bic,
			'{BankIBAN}' => $bank_iban,
			'{Debit_identifier}' => Configuration::get('DEBITNOTE_CREDITOR_IDENTIFIER'),
			'{shop_name}' => Configuration::get('PS_SHOP_NAME'),
			'{shop_address1}' => Configuration::get('PS_SHOP_ADDR1'),
			'{shop_address2}' => Configuration::get('PS_SHOP_ADDR2'),
			'{shop_plz}' => Configuration::get('PS_SHOP_CODE'),
			'{shop_city}' => Configuration::get('PS_SHOP_CITY')
					
			
		);
		

		$this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_DEBITNOTE'), $total, $this->module->displayName, $message, $mailVars, (int)$currency->id, false, $customer->secure_key);
		$this->module->writeDebitNoteDetails($this->module->currentOrder, $accountholder_name, $account_number, $bank_code, $bank_name, $bank_bic, $bank_iban, '123');
		Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}
