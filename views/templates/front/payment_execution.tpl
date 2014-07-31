<script type="text/javascript">
<!--
{literal}
	function acceptDNC(msg)
		{
	if ($('#dnc').length && !$('input#dnc:checked').length)
	{
		alert(msg);
		return false;
	}
	else
		return true;
		}
{/literal}
-->
</script>
<script type="text/javascript" src="{$this_PS_url}js/jquery/jquery.fancybox-1.3.4.js"></script>
<link href="{$this_PS_url}css/jquery.fancybox-1.3.4.css" rel="stylesheet" type="text/css" media="screen" />

<style>
<!--
{literal}
#left_column {display:none;}
#center_column {width:757px;}
.debitnote_input label {
width: 150px;
display: block;
float: left;
}
.debitnote_input input[type=text] {
width: 250px;
}
{/literal}
-->
</style>


{capture name=path}{l s='Payment with Debit Note' mod='debitnote'}{/capture}
{include file="{$tpl_dir}/breadcrumb.tpl"} 

<h2>{l s='Order summary' mod='debitnote' mod='debitnote'}</h2>

{assign var='current_step' value='payment'}
{include file="{$tpl_dir}/order-steps.tpl"} 

<h3>{l s='Payment Details for Debit Note' mod='debitnote'}</h3><img src="{$this_path_ssl}debitnote.png" style="float: right;" />

<h4 style="float: left;">{l s='Your Account will be carged with the amount of' mod='debitnote'} {convertPrice price=$total_price}  </h4>

<form action="{$this_path_ssl}validation.php" method="post" class="std" onsubmit="return acceptDNC('{l s='Please accept the terms of Paying with DebitNote before the next step.' mod='debitnote' js=1}');">

<script type='text/javascript'>$('a.iframe').fancybox();</script>

<p>&nbsp;</p>

<p class="required text">
            <label for="accountholder_name">{l s='Name of Accountholder:' mod='debitnote'}</label>
            <input type="text" name="accountholder_name" id="accountholder_name" value="{$accountholder_name}" size="44"/>
            <sup>*</sup>
</p>
<p class="required text">
            <label for="bank_name">{l s='Name of Bank:' mod='debitnote'}</label>
            <input type="text" name="bank_name" id="bank_name" value="{$bank_name}" size="44"/>
            <sup>*</sup>
</p>
<p class="required text">
            <label for="bank_code">{l s='Code of Bank:' mod='debitnote'}</label>
            <input type="text" name="bank_code" id="bank_code" value="{$bank_code}" size="15"/>
            <sup>*</sup>
</p>
<p class="required text">
            <label for="account_number">{l s='Account Number:' mod='debitnote'}</label>
            <input type="text" name="account_number" id="account_number" value="{$account_number}" size="25"/>
            <sup>*</sup>
</p>
<p class="info">{l s='If you do not have an account in Austria, please also fill in the BIC and IBAN:' mod='debitnote'}</p>
<p class="text">
            <label for="bank_bic">{l s='BIC:' mod='debitnote'}</label>
            <input type="text" name="bank_bic" id="bank_bic" value="{$bank_bic}" size="25"/>
       
</p>
<p class="text">
            <label for="bank_iban">{l s='IBAN:' mod='debitnote'}</label>
            <input type="text" name="bank_iban" id="bank_iban" value="{$bank_iban}" size="50"/>
          
</p>
<p class="text">
            <label for="user_ip">{l s='Your IP:' mod='debitnote'}</label>
            &nbsp;{$this_user_ip}
          
</p>
<p class="checkbox">
		<input type="checkbox" name="dnc" id="dnc" value="1" />

		<label for="dnc">{l s='I agree with the terms of paying by Debit Note and I adhere to them unconditionally.' mod='debitnote'} </label><a href="{$this_PS_url}cms.php?id_cms=3&content_only=1" class="iframe">{l s='(read)' mod='debitnote'}</a>
	</p>
<p class="cart_navigation">
<a href="{$base_dir_ssl}order.php?step=3" class="button_large">{l s='Other payment methods' mod='debitnote'}</a>
<input type="submit" name="paymentSubmit" value="{l s='Submit Order' mod='debitnote'}" class="exclusive_large" />
</p>
</form>
