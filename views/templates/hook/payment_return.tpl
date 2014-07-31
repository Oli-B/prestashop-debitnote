

{if $status == 'ok'}
<p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='debitnote'}
		<br /><br />
		{l s='Your bankaccount will be charged with the amount off:' mod='debitnote'}
		<br /><br />- {l s='an amount of' mod='debitnote'} <span class="price"> <strong>{$total_price}</strong>
		</span>
		
		{if !isset($reference)}
			<br /><br />- {l s='Your order number is #%d .' sprintf=$id_order mod='debitnote'}
		{else}
			<br /><br />- {l s='Your order number is %s .' sprintf=$reference mod='debitnote'}
		{/if}		<br /><br />{l s='An e-mail has been sent to you with this information.' mod='debitnote'}
		<br /><br /> <strong>{l s='Your order will be sent as soon as we receive your settlement.' mod='debitnote'}</strong>
		<br /><br />{l s='For any questions or for further information, please contact our' mod='debitnote'} <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='debitnote'}</a>.
	</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='debitnote'} 
		<a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='debitnote'}</a>.
	</p>
{/if}