<script type="text/javascript">
{literal}
	$$("#tabs").tabs();
{/literal}
</script>

<div id="tabs">
	<ul>
		<li><a href="#newest_desc">{l s='Newest' mod='filtersearch'}</a></li>
		<li><a href="#name_asc">{l s='Name A-Z' mod='filtersearch'}</a></li>
		<li><a href="#name_desc">{l s='Name Z-A' mod='filtersearch'}</a></li>
		<li><a href="#price_asc">{l s='Price Lowest' mod='filtersearch'}</a></li>
		<li><a href="#price_desc">{l s='Price Highest' mod='filtersearch'}</a></li>
	</ul>
	<div id="newest_desc"></div>
	<div id="name_asc"></div>
	<div id="name_desc"></div>
	<div id="price_asc"></div>
	<div id="price_desc"></div>
</div>
<br class="clear" />