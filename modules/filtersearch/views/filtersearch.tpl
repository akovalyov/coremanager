{*
	{l s='Clear All' mod='filtersearch'}
	{l s='Price Ranges' mod='filtersearch'}
	{l s='Manufacturers' mod='filtersearch'}
*}

{if $ranges OR $attributes OR $features OR $manufacturers}
	{if $stylesheet}
		<link rel="stylesheet" href="{$assets}filtersearch.css" type="text/css" media="screen" charset="utf-8" />
		{*<link rel="stylesheet" href="{$coreAssets}themes/base/ui.all.css" type="text/css" media="screen" charset="utf-8" />*}
		{literal}<!--[if IE]> 
			<style type="text/css"> 
				.filterSearchModule ul li, 
				.filterSearchModule ul li a,
				.filterSearchModule h3 { font-family: Arial, Helvetica, sans-serif; margin 0; zoom: 1; font-weight: bold; }
				.filterSearchModule ul li a { font-size:11px; font-weight: bold; }
			</style> 
		<![endif]-->{/literal}		
		<script type='text/javascript'>
		  var viewLess			 = "{l s='View Less' mod='filtersearch'}",
				viewMore		    = "{l s='View More' mod='filtersearch'}",
				pagingNext		 = "{l s='Next' mod='filtersearch'}",
				pagingPrevious	 = "{l s='Previous' mod='filtersearch'}",
				pagingFirst		 = "{l s='First' mod='filtersearch'}",
				pagingLast		 = "{l s='Last' mod='filtersearch'}",
				showAll			 = "{l s='Show All' mod='filtersearch'}",
				productPlural   = "{l s='Product' mod='filtersearch'}",
				productsPlural  = "{l s='Products' mod='filtersearch'}",
				ajaxLoader      = true,
				currencySign    = '{$currencySign}',
				currentId		 = {$currentId},
				productCount	 = {$countIds},
				productsPerPage = {$PPPage};
		</script>
	{/if}
	
	<div class="filterSearchModule">
     
     	<div id="tleft"></div>
     	<div id="tcenter"></div>
     	<div id="tright"></div>
     
		<form method="get" action="">
			<div class="filterViewAllFilters">
				<input type="button" value="{l s='Clear All' mod='filtersearch'}" onclick='return false;' />
			</div>
			<br clear="all" />
			{$rangeDisplay}
			{$groupDisplay}
		</form>
		
      <div id="bleft"></div>
      <div id="bcenter"></div>
      <div id="bright"></div>         
	</div>
	<br clear="all" />
{/if}