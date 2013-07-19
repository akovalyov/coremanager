{if isset($products)}
	<!-- Products list -->
	<ul class="clear">
	{foreach from=$products item=product name=products key=key}
		<li class="ajax_block_product {if $smarty.foreach.products.first}first_item{elseif $smarty.foreach.products.last}last_item{/if} {if $smarty.foreach.products.index % 2}alternate_item{else}item{/if} clearfix">
            <div class="left_block">
                {if isset($comparator_max_item) && $comparator_max_item}
                    <p class="compare">
                        <input type="checkbox" class="comparator" id="comparator_item_{$product.id_product}" value="comparator_item_{$product.id_product}" {if isset($compareProducts) && in_array($product.id_product, $compareProducts)}checked="checked"{/if} />
                        <label for="comparator_item_{$product.id_product}">{l s='Select to compare'}</label>
                    </p>
                {/if}
            </div>
			<div class="center_block">			
				<span class="availability">{if ($product.allow_oosp OR $product.quantity > 0)}{l s='Available' mod='filtersearch'}{else}{l s='Out of stock' mod='filtersearch'}{/if}</span>
				<a href="{$product.link|escape:'htmlall':'UTF-8'}" class="product_img_link" title="{$product.name|escape:'htmlall':'UTF-8'}">
                    <img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'home_default')}" alt="{$product.legend|escape:'htmlall':'UTF-8'}" {if isset($homeSize)} width="{$homeSize.width}" height="{$homeSize.height}"{/if} />
				</a>
				<h3>
					{if $product.new == 1}<span class="new">{l s='new' mod='filtersearch'}</span>{/if}
					<a href="{$product.link|escape:'htmlall':'UTF-8'}" title="{$product.legend|escape:'htmlall':'UTF-8'}">{$product.name|truncate:35:'...'|escape:'htmlall':'UTF-8'}</a>
				</h3>
				<p class="product_desc">
					<a href="{$product.link|escape:'htmlall':'UTF-8'}">{$product.description_short|strip_tags:'UTF-8'|truncate:360:'...'}</a>
				</p>
			</div>
			<div class="right_block">
				{if $product.on_sale}
					<span class="on_sale">{l s='On sale!' mod='filtersearch'}</span>
{*				{elseif ($product.reduction_price != 0 || $product.reduction_percent != 0) && ($product.reduction_from == $product.reduction_to OR ($smarty.now|date_format:'%Y-%m-%d' <= $product.reduction_to && $smarty.now|date_format:'%Y-%m-%d' >= $product.reduction_from))}
					<span class="discount">{l s='Price lowered!' mod='filtersearch'}</span>*}
				{/if}
				{if !$priceDisplay || $priceDisplay == 2}
					<div>
						<span class="price" style="display: inline;">{convertPrice price=$product.price}</span>
						{if $priceDisplay == 2} {l s='+Tx' mod='filtersearch'}{/if}
					</div>
				{/if}
				<div>
					<span class="price" style="display: inline;">{convertPrice price=$product.price_tax_exc}</span>
					{if $priceDisplay == 2} {l s='-Tx' mod='filtersearch'}{/if}
				</div>
				{if ($product.allow_oosp OR $product.quantity > 0) && $product.customizable != 2}
					<a class="button ajax_add_to_cart_button exclusive" rel="ajax_id_product_{$product.id_product|intval}" href="{$link->getPageLink('cart',false, NULL, "add=1&amp;id_product={$product.id_product|intval}", false)}">{l s='Add to cart' mod='filtersearch'}</a>
				{else}
						<span class="exclusive">{l s='Add to cart' mod='filtersearch'}</span>
				{/if}
				<a class="button" href="{$product.link|escape:'htmlall':'UTF-8'}" title="{l s='View'}">{l s='View' mod='filtersearch'}</a>
			</div>
			<br class="clear"/>
		</li>
	{/foreach}
	</ul>
	<!-- /Products list -->
{/if}