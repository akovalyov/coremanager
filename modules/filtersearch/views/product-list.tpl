{if isset($products)}
	<!-- Products list -->
	<ul class="clear">
	{foreach from=$products item=product name=products key=key}
		<li class="ajax_block_product {if $smarty.foreach.products.first}first_item{elseif $smarty.foreach.products.last}last_item{/if} {if $smarty.foreach.products.index % 2}alternate_item{else}item{/if} clearfix">
            <div class="left_block">
                {if isset($comparator_max_item) && $comparator_max_item}
                    <p class="compare">
                        <input type="checkbox" {literal} onclick="javascript:($(document).trigger('click', this))"{/literal} class="comparator" id="comparator_item_{$product.id_product}" value="comparator_item_{$product.id_product}" {if isset($compareProducts) && in_array($product.id_product, $compareProducts)}checked="checked"{/if} />
                        <label for="comparator_item_{$product.id_product}">{l s='Select to compare'}</label>
                    </p>
                {/if}
            </div>
			<div class="center_block">			
				<span class="availability">{if ($product.allow_oosp OR $product.quantity > 0)}{l s='Available' mod='filtersearch'}{else}{l s='Out of stock' mod='filtersearch'}{/if}</span>
                <a href="{$product.link|escape:'htmlall':'UTF-8'}" class="product_img_link" title="{$product.name|escape:'htmlall':'UTF-8'}">
                    <img src="{$link->getImageLink($product.link_rewrite, $product.id_image, 'home_default')}" alt="{$product.legend|escape:'htmlall':'UTF-8'}" {if isset($homeSize)} width="{$homeSize.width}" height="{$homeSize.height}"{/if} />
                    {if isset($product.new) && $product.new == 1}<span class="new">{l s='New'}</span>{/if}
                </a>

				<p class="product_desc">
					<a href="{$product.link|escape:'htmlall':'UTF-8'}">{$product.description_short|strip_tags:'UTF-8'|truncate:360:'...'}</a>
				</p>
			</div>
            <div class="right_block">
                {if isset($product.on_sale) && $product.on_sale && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}<span class="on_sale">{l s='On sale!'}</span>
                {elseif isset($product.reduction) && $product.reduction && isset($product.show_price) && $product.show_price && !$PS_CATALOG_MODE}<span class="discount">{l s='Reduced price!'}</span>{/if}
                {if (!$PS_CATALOG_MODE AND ((isset($product.show_price) && $product.show_price) || (isset($product.available_for_order) && $product.available_for_order)))}
                    <div class="content_price">
                        {if isset($product.show_price) && $product.show_price && !isset($restricted_country_mode)}<span class="price" style="display: inline;">{if !$priceDisplay}{convertPrice price=$product.price}{else}{convertPrice price=$product.price_tax_exc}{/if}</span><br />{/if}
                        {if isset($product.available_for_order) && $product.available_for_order && !isset($restricted_country_mode)}<span class="availability">{if ($product.allow_oosp || $product.quantity > 0)}{l s='Available'}{elseif (isset($product.quantity_all_versions) && $product.quantity_all_versions > 0)}{l s='Product available with different options'}{else}{l s='Out of stock'}{/if}</span>{/if}
                    </div>
                    {if isset($product.online_only) && $product.online_only}<span class="online_only">{l s='Online only'}</span>{/if}
                {/if}
                {if ($product.id_product_attribute == 0 || (isset($add_prod_display) && ($add_prod_display == 1))) && $product.available_for_order && !isset($restricted_country_mode) && $product.minimal_quantity <= 1 && $product.customizable != 2 && !$PS_CATALOG_MODE}
                    {if ($product.allow_oosp || $product.quantity > 0)}
                        {if isset($static_token)}
                            <a class="button ajax_add_to_cart_button exclusive" rel="ajax_id_product_{$product.id_product|intval}" href="{$link->getPageLink('cart',false, NULL, "add=1&amp;id_product={$product.id_product|intval}&amp;token={$static_token}", false)}" title="{l s='Add to cart'}"><span></span>{l s='Add to cart'}</a>
                        {else}
                            <a class="button ajax_add_to_cart_button exclusive" rel="ajax_id_product_{$product.id_product|intval}" href="{$link->getPageLink('cart',false, NULL, "add=1&amp;id_product={$product.id_product|intval}", false)}" title="{l s='Add to cart'}"><span></span>{l s='Add to cart'}</a>
                        {/if}
                    {else}
                        <span class="exclusive"><span></span>{l s='Add to cart'}</span><br />
                    {/if}
                {/if}
                <a class="button lnk_view" href="{$product.link|escape:'htmlall':'UTF-8'}" title="{l s='View'}">{l s='View'}</a>
            </div>
			<br class="clear"/>
		</li>
	{/foreach}
	</ul>
	<!-- /Products list -->
{/if}