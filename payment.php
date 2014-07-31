<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/debitnote.php');

if (!$cookie->isLogged())
    Tools::redirect('authentication.php?back=order.php');
   
$debitnote = new DebitNote();
echo $debitnote->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');

?>