<?php
// -------------------------------------------------
// Module debit note
// ------------------
// This Module was developed for using in PrestaShop 1.3.1
// Author: Franz Hüttenmeyr - franz@huettys.at
// Version: 0.1
// This Module comes with absolute no warrenty - use it as it is or change it - i don't feel responsible for any Problems
// This Module is written with help from the tutorial: http://www.davidstclair.co.uk/Creating-Simple-PrestaShop-Payment-Module.htm
// --------------------------------------------------

if (!defined('_PS_VERSION_'))
	exit;

class DebitNote extends PaymentModule
{
    private     $_html = '';
    private     $_postErrors = array();
	
	public $extra_mail_vars;
    
    public function __construct()
    {
        $this->name = "debitnote";
        $this->tab  = "payments_gateways";
        $this->version = '0.2.1';
        
        $this->currencies = true;
		$this->currencies_mode = 'checkbox';
        
        parent::__construct();
        
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Debit Note Module');
        $this->description = $this->l('Accept Payment per Debit Note');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details? All Account Informations from your Customers will be lost!');
        
        if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency set for this module');
    	
	}
    
    
    public function install()
    {
			if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);
			
			return parent::install() &&
			$this->registerHook('invoice') &&
			$this->registerHook('payment') &&
			$this->registerHook('paymentReturn') &&
			$this->createDebitNoteTable() &&
			$this->createOrderState()&&
			Configuration::updateValue('DEBITNOTE_CREDITOR_IDENTIFIER', DEXXXX);
			
    }
    
    public function uninstall()
    {
        if (!$this->removeDebitNoteTable()
			OR !Db::getInstance()->delete('ps_order_state', 'id_order_state ='.Configuration::get('PS_OS_DEBITNOTE'),10)
			OR !Db::getInstance()->delete('ps_order_state_lang', 'id_order_state ='.Configuration::get('PS_OS_DEBITNOTE'),10)
			OR !Configuration::deleteByName('PS_OS_DEBITNOTE')
			OR !Configuration::deleteByName('DEBITNOTE_CREDITOR_IDENTIFIER')
            OR !parent::uninstall()
			)
	return false;
	
        return true;
    }
    
	private function createOrderState()
	{
		if (!Configuration::get('PS_OS_DEBITNOTE'))
		{
			$order_state = new OrderState();
			$order_state->name = array();

			foreach (Language::getLanguages() as $language)
			{
				if (Tools::strtolower($language['iso_code']) == 'fr')
					$order_state->name[$language['id_lang']] = 'Paiement par prélèvement automatique';
				elseif (Tools::strtolower($language['iso_code']) == 'de')
					$order_state->name[$language['id_lang']] = 'Zahlung per Lastschrift';
				elseif (Tools::strtolower($language['iso_code']) == 'it')
					$order_state->name[$language['id_lang']] = 'Il pagamento tramite addebito diretto';
				elseif (Tools::strtolower($language['iso_code']) == 'es')
					$order_state->name[$language['id_lang']] = 'El pago mediante domiciliación bancaria';
				elseif (Tools::strtolower($language['iso_code']) == 'br')
					$order_state->name[$language['id_lang']] = 'O pagamento por débito directo';
				else
					$order_state->name[$language['id_lang']] = 'Payment by direct debit';
			}

			$order_state->send_email = true;
			$order_state->color = '#10c8f6';
			$order_state->hidden = false;
			$order_state->delivery = false;
			$order_state->logable = true;
			$order_state->invoice = false;
			$order_state->template = 'debitnote';

			if ($order_state->add())
			{
				$source = dirname(__FILE__).'/logo2.gif';
				$destination = dirname(__FILE__).'/../../img/os/'.(int)$order_state->id.'.gif';
				copy($source, $destination);
				$sourcemail = dirname(__FILE__).'/mails/';
				$destinationmail = dirname(__FILE__).'/../../mails/';
				$this->CopyMailFolder($sourcemail, $destinationmail);
			}
			Configuration::updateValue('PS_OS_DEBITNOTE', (int)$order_state->id);
			}
	}
	
    
    private function createDebitNoteTable()
    {
        // Function called by Install
        // creates the order_debitnote table required for storing the information for debit notes
        /*
         ## Column Description ##
         * id_payment               primary key
         * id_order                 Stores the order id
         * accountholder_name       Stores the name of the Account Holder
         * account_number           Stores the account Number (stored as text because of leading zeros)
         * bank_code                Stores the bankcode for the account
         * bank_name                Stores the name of the Bank
         * bank_bic                 Stores the BIC of the Bank (for interational transfer EU)
         * bank_iban                Stores the IBAN of the Bank (for interational transfer EU)
         * ip_address               Stores the IP Address of the user (for security reasons - if someone uses false bank details)         
        */
        
        $db = Db::getInstance();
        $query = "CREATE TABLE `"._DB_PREFIX_."order_debitnote` (
            `id_payment` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `id_order` INT NOT NULL,
            `accountholder_name` TEXT NOT NULL,
            `account_number` TEXT NOT NULL,
            `bank_code` INT NOT NULL,
            `bank_name` TEXT NOT NULL,
            `bank_bic` TEXT NULL,
            `bank_iban` TEXT NULL,
            `ip_address` TEXT NOT NULL) ENGINE=MYISAM";
            
        $db->Execute($query);
        
        return true;
        
    }

    private function removeDebitNoteTable()
    {
         $db = Db::getInstance();
         $query = "Drop table `"._DB_PREFIX_."order_debitnote` ";
         $db->Execute($query);
        
        return true;
    }


   
        
     


    function hookPayment($params)
    {
                
        if (!$this->active)
	    return ;
		if (!$this->checkCurrency($params['cart']))
	    return ;
       
        
        $this->smarty->assign(array(
            'this_path' => $this->_path,
			'this_path_debitnote' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
        ));
        Tools::safePostVars();
	$css_files = array(__PS_BASE_URI__.'css/thickbox.css' => 'all');
        
        return $this->display(__FILE__, 'payment.tpl');
    }

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if ($state == Configuration::get('PS_OS_DEBITNOTE') || $state == Configuration::get('PS_OS_OUTOFSTOCK'))
		{
			$this->smarty->assign(array(
				'total_price' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'this_user_ip' => htmlspecialchars($ip, ENT_COMPAT, 'UTF-8'),
				'status' => 'ok',
				'id_order' => $params['objOrder']->id
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}
	
    public function execPayment($cart)
    {
        if (!$this->active)
            return;
	if (!$this->_checkCurrency($cart))
            return ;
        global $cookie, $smarty;
        
         $ip = $this->get_real_ip();
        
        $smarty->assign(array(
        'this_PS_url' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__,
        'total_price' => $cart->getOrderTotal(true,Cart::BOTH),
        'this_path' => $this->_path,
        'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/',
        'this_user_ip' => htmlspecialchars($ip, ENT_COMPAT, 'UTF-8')));
        return $this->display(__FILE__, 'payment_execution.tpl');
    }

    function get_real_ip()
    {
         $ip = false;
         if(!empty($_SERVER['HTTP_CLIENT_IP']))
         {
              $ip = $_SERVER['HTTP_CLIENT_IP'];
         }
         if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
         {
              $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
              if($ip)
              {
                   array_unshift($ips, $ip);
                   $ip = false;
              }
              for($i = 0; $i < count($ips); $i++)
              {
                   if(!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i]))
                   {
                        if(version_compare(phpversion(), "5.0.0", ">="))
                        {
                             if(ip2long($ips[$i]) != false)
                             {
                                  $ip = $ips[$i];
                                  break;
                             }
                        }
                        else
                        {
                             if(ip2long($ips[$i]) != - 1)
                             {
                                  $ip = $ips[$i];
                                  break;
                             }
                        }
                   }
              }
         }
         return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }  

    function writeDebitNoteDetails($order_id, $accountholder_name, $account_number, $bank_code, $bank_name, $bank_bic, $bank_iban, $ip_address)
    {
        $db = Db::getInstance();
        $queryIns = 'INSERT INTO `'._DB_PREFIX_.'order_debitnote` (`id_order`, `accountholder_name`, `account_number`, `bank_code`, `bank_name`, `bank_bic`, `bank_iban`, `ip_address`) VALUES ("'.intval($order_id).'", "'.$accountholder_name.'", "'.$account_number.'", "'.$bank_code.'", "'.$bank_name.'", "'.$bank_bic.'", "'.$bank_iban.'", "'.$ip_address.'");';
        
        $result = $db->Execute($queryIns);
                             
    }

    function hookInvoice($params)
    {
        $id_order = $params['id_order'];
        
        global $smarty;
        
        $debitNoteDetails = $this->readDebitNoteDetails($id_order);
        
        $smarty->assign(array(
            'AccountHolderName' => $debitNoteDetails['accountholder_name'],
            'BankName' => $debitNoteDetails['bank_name'],
            'BankCode' => $debitNoteDetails['bank_code'],
            'AccountNumber' => $debitNoteDetails['account_number'],
            'BankBIC' => $debitNoteDetails['bank_bic'],
            'BankIBAN' => $debitNoteDetails['bank_iban'],
            'id_order' => $id_order,
            'this_page' => $_SERVER['REQUEST_URI'],
            'this_path' => $this->_path,
            'this_path_ssl' => Configuration::get('PS_FO_PROTOCOL').$_SERVER['HTTP_HOST'].__PS_BASE_URI__."modules/{$this->name}/"));

        return $this->display(__FILE__, 'invoice_block.tpl');
       
        
    }
    
    function readDebitNoteDetails($id_order)
    {
        $db = Db::getInstance();
        $result = $db->ExecuteS('
                                SELECT * FROM `'._DB_PREFIX_.'order_debitnote` WHERE `id_order` = "'.intval($id_order).'";');
        
        return $result[0];
    }

    private function _postValidation()
    {
       if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('name'))
				$this->_postErrors[] = $this->l('\'The "To the order of" field is required.');
			elseif (!Tools::getValue('address'))
				$this->_postErrors[] = $this->l('Address is required.');
		} 
    }
	
	public function getContent()
	{
		$output = null;
		
		if (Tools::isSubmit('submit'.$this->name))
			{
				$debitidentifier = strval(Tools::getValue('DEBITNOTE_CREDITOR_IDENTIFIER'));
				if (!$debitidentifier  || empty($debitidentifier) || !Validate::isGenericName($debitidentifier)
					)
					$output .= $this->displayError( $this->l('Invalid Configuration values') ); 
				else
				{
					Configuration::updateValue('DEBITNOTE_CREDITOR_IDENTIFIER', $debitidentifier);
					
					
					$output .= $this->displayConfirmation($this->l('Settings updated'));
				}
			}
			return $output.$this->displayForm();
		
	}
	
	public function displayForm()
			{
				// Get default Language
				$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
				 
				// Init Fields form array
				$fields_form[0]['form'] = array(
					'legend' => array(
						'title' => $this->l('Settings'),
					),
					'input' => array( 
						array(
							'type' => 'text',
							'label' => $this->l('Creditor Identifier'),
							'name' => 'DEBITNOTE_CREDITOR_IDENTIFIER',
							'size' => 50,
							'required' => true
						),	
						
						
					),
					
					'submit' => array(
						'title' => $this->l('Save'),
						'class' => 'button'
					)
				);
				 
				$helper = new HelperForm();
				 
				// Module, t    oken and currentIndex
				$helper->module = $this;
				$helper->name_controller = $this->name;
				$helper->token = Tools::getAdminTokenLite('AdminModules');
				$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
				 
				// Language
				$helper->default_form_language = $default_lang;
				$helper->allow_employee_form_lang = $default_lang;
				 
				// Title and toolbar
				$helper->title = $this->displayName;
				$helper->show_toolbar = true;        // false -> remove toolbar
				$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
				$helper->submit_action = 'submit'.$this->name;
				$helper->toolbar_btn = array(
					'save' =>
					array(
						'desc' => $this->l('Save'),
						'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
						'&token='.Tools::getAdminTokenLite('AdminModules'),
					),
					'back' => array(
						'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
						'desc' => $this->l('Back to list')
					)
				);
				 
				// Load current value
				$helper->fields_value['DEBITNOTE_CREDITOR_IDENTIFIER'] = Configuration::get('DEBITNOTE_CREDITOR_IDENTIFIER');
				
				 
				return $helper->generateForm($fields_form);
			}	
	
    private function _checkCurrency($cart)
	{
		$currency_order = new Currency(intval($cart->id_currency));
		$currencies_module = $this->getCurrency();
		$currency_default = Configuration::get('PS_CURRENCY_DEFAULT');
		
		if (is_array($currencies_module))
			foreach ($currencies_module AS $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
	}
	
	public function checkCurrency($cart)
	{
		$currency_order = new Currency((int)($cart->id_currency));
		$currencies_module = $this->getCurrency((int)$cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}
	
	private function CopyMailFolder($source, $dest, $options=array('folderPermission'=>0744,'filePermission'=>0744))
    {
	/**
     * Copy file or folder from source to destination, it can do
     * recursive copy as well and is very smart
     * It recursively creates the dest file or directory path if there weren't exists
     * Situtaions :
     * - Src:/home/test/file.txt ,Dst:/home/test/b ,Result:/home/test/b -> If source was file copy file.txt name with b as name to destination
     * - Src:/home/test/file.txt ,Dst:/home/test/b/ ,Result:/home/test/b/file.txt -> If source was file Creates b directory if does not exsits and copy file.txt into it
     * - Src:/home/test ,Dst:/home/ ,Result:/home/test/** -> If source was directory copy test directory and all of its content into dest     
     * - Src:/home/test/ ,Dst:/home/ ,Result:/home/**-> if source was direcotry copy its content to dest
     * - Src:/home/test ,Dst:/home/test2 ,Result:/home/test2/** -> if source was directoy copy it and its content to dest with test2 as name
     * - Src:/home/test/ ,Dst:/home/test2 ,Result:->/home/test2/** if source was directoy copy it and its content to dest with test2 as name
     * @todo
     *     - Should have rollback technique so it can undo the copy when it wasn't successful
     *  - Auto destination technique should be possible to turn off
     *  - Supporting callback function
     *  - May prevent some issues on shared enviroments : http://us3.php.net/umask
     * @param $source //file or folder
     * @param $dest ///file or folder
     * @param $options //folderPermission,filePermission
     * @return boolean
     */ 
        $result=false;
       
        if (is_file($source)) {
            if ($dest[strlen($dest)-1]=='/') {
                if (!file_exists($dest)) {
                    cmfcDirectory::makeAll($dest,$options['folderPermission'],true);
                }
                $__dest=$dest."/".basename($source);
            } else {
                $__dest=$dest;
            }
            $result=copy($source, $__dest);
            chmod($__dest,$options['filePermission']);
           
        } elseif(is_dir($source)) {
            if ($dest[strlen($dest)-1]=='/') {
                if ($source[strlen($source)-1]=='/') {
                    //Copy only contents
                } else {
                    //Change parent itself and its contents
                    $dest=$dest.basename($source);
                    mkdir($dest);
                    chmod($dest,$options['filePermission']);
                }
            } else {
                if ($source[strlen($source)-1]=='/') {
                    //Copy parent directory with new name and all its content
                    mkdir($dest,$options['folderPermission']);
                    chmod($dest,$options['filePermission']);
                } else {
                    //Copy parent directory with new name and all its content
                    mkdir($dest,$options['folderPermission']);
                    chmod($dest,$options['filePermission']);
                }
            }

            $dirHandle=opendir($source);
            while($file=readdir($dirHandle))
            {
                if($file!="." && $file!="..")
                {
                     if(!is_dir($source."/".$file)) {
                        $__dest=$dest."/".$file;
                    } else {
                        $__dest=$dest."/".$file;
                    }
                    //echo "$source/$file ||| $__dest<br />";
                    $result=$this->CopyMailFolder($source."/".$file, $__dest, $options);
                }
            }
            closedir($dirHandle);
           
        } else {
            $result=false;
        }
        return $result;
    } 

}







?>