  
  /**
	* filtersearch.js
	* @author  	   Adam Lee & Yaakov Albietz - ejectcore.com
	* @copyright   Copyright Eject Core 2009-2010. All rights reserved.
	* @license 	   GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
	* @credit   	3rd Party Development: Seth Benjamin
	* @package     Filter Search Community Edition
	* @version 	   v2.1 Final
	*
	*/
	
	var animationSpeed = 500,
		 limitTimeout   = 200,
  		 filterCount    = productCount,
		 responder      = modulePath() + 'modules/filtersearch/filtersearch.json.php',
		 filterList     = [],
		 nArray         = [],
		 orderBy        = 'newest',
		 orderWay       = 'desc',
		 plural         = '',
		 pagerClone,
		 sortObj,
		 productSelector;
		
	$$('.ajax_add_to_cart_button').live('click', function(){
		var idProduct =  $$(this).attr('rel').replace('ajax_id_product_', '');
		ajaxCart.add(idProduct, null, false, this);
		return false;
	});
	
	$$(function() {
		productSortByTabs('#newest_desc');
		generateFilterList(loadUri(), productSelector);
		productPaging(filterCount, true, false);
		
		$$(document).ajaxComplete(function(event, request, settings) {
			if(settings.dataType === 'json' && settings.url.indexOf('act=filter') != -1) {
				var wait = setInterval(function() {
					if(!$$('.filterGroup li').is(':animated')) {
						clearInterval(wait);
						$$('#product_list').slideDown();
						$$('.filterAjaxLoader, .filterAjaxLoaderAbs, .filterAjaxLoaderAbsTop').fadeOut('slow', function() {
							$$(this).css('opacity', 0).animate({ height : '2px' }, animationSpeed, 'linear', function() {
								$$(this).remove();
							});
						});
					}
				}, limitTimeout);
			}
		});
	});

	function productSortByTabs(tab, noAction) {
		tab             = tab.substr(1);
		productSelector = '.filter_product_listing';
		
		$$('#productsSortForm').remove();
		$$('#' + tab).html('');
		
		$$(productSelector).find('ul').removeAttr('id');
		
		$$.getJSON(responder, { 'act' : 'tabs', 'sortby' : tab }, function(data) {
			if($$('#product_list').length > 0) {
				$$('#product_list').before('<p class="clear" />' + data.tabs).remove();
	
				if(noAction == undefined) tabActions();
				
				$$('#' + tab).append('<div class="' + productSelector.substr(1, productSelector.length) + '"></div>');
				pagerClone = $$('div.pagination').clone(true);
				
				$$('div.pagination').eq(0).prependTo('#' + tab);
				$$('div.pagination').eq(1).appendTo('#' + tab);
			} else {
			
				$$('#' + tab).append('<div class="' + productSelector.substr(1, productSelector.length) + '"></div>');
				$$($$(pagerClone).get()).eq(0).clone(true).prependTo('#' + tab);
				$$($$(pagerClone).get()).eq(1).clone(true).appendTo('#' + tab);
			}
	
			performFilter(true, 1, productSelector, true);
		});
	}	
		
	function tabActions() {
		$$('#tabs').bind('tabsselect', function(event, ui) {
			sortBy   = $$(ui.panel).attr('id');
			orderBy  = sortBy.split('_')[0];
			orderWay = sortBy.split('_')[1];
			productSortByTabs('#' + $$(ui.panel).attr('id'), true);
		});
	}
	
	function productPaging(filterPage, onLoad, resetPage, selector) {
		pageCount = Math.ceil(parseFloat(parseInt(filterPage) / productsPerPage));
		
		if(filterPage != undefined) {
			pageClick = function(clickedNumber) {
				$('ul.pagination').pager({
					pagenumber: clickedNumber,
					pagecount: pageCount,
					buttonClickCallback: pageClick
				});
				performFilter(false, clickedNumber, selector);
			}
	
			if(onLoad == true || resetPage == true) {
				$$('.pagination').fadeIn('slow');
				
				$('ul.pagination').pager({
					pagenumber: 1,
					pagecount: pageCount,
					buttonClickCallback: pageClick
				});
				
				selectProductsPerPage(filterPage, onLoad);

				if(onLoad == true) {
					$$('div.pagination').clone(true).insertBefore(selector == undefined ? '#product_list' : selector);
				}
			}
		}
		return false;
	}
	
	function selectProductsPerPage(filterPage, onPagerLoad) {
		var newSelector = '.filterNbItem';
		var prevShowAll = $$('option:selected', newSelector).hasClass('filterShowAll');
		
		if(prevShowAll == false) {
			if(onPagerLoad) {
				$$('.pagination .button_mini, #nb_item option').remove();
				sortObj = $$('#nb_item').addClass(newSelector.substr(1)).removeAttr('id').clone();			
			} else {
				$(newSelector).replaceWith(sortObj);
			}		
		}
		
		if($$(newSelector).html() != null) {
			$$.each(['20', '30', '40', '50'], function(k, n) {
				$$(newSelector).append($$('<option></option>').val(n).text(n));
			});
		}
	
		$$('option', newSelector).each(function(k) {
			if($$(this).val() > filterPage) $$(this).remove();
			if(productsPerPage == $$(this).text() && k > 0) {
				$$(this).attr('selected', true);
			}
		});
	
		if($$('.filterShowAll', newSelector).length < 1)
			$$(newSelector).append($$('<option></option>').addClass('filterShowAll').val(productCount).text(showAll));
		
		$$(newSelector).change(function() {
			productsPerPage = $$('option:selected', this).attr('selected', true).val();
			performFilter(true, undefined, productSelector);
		});
	}
	
	function performFilter(resetPage, page, selector, onTabLoad) {
		var uri    = window.location.hash.substr(1, window.location.hash.length).split('=', 2),
			filters = new Object();
		
		if(uri[1] != undefined && uri[1].split(',').length > 0) {
			filters['act']      = 'filter';
			filters['ident']    = currentId;
			filters['page']     = page == undefined ? 1 : page;
			filters['perpage']  = productsPerPage;
			filters['orderby']  = orderBy;
			filters['orderway'] = orderWay;
	
			$.each(uri[1].split(','), function(k, v) {
				if(v.substr(1, v.length - 2).split(':')[1] != undefined) {
					filters[v.substr(1, v.length - 2).split(':')[0] + '_' + k] = v.substr(1, v.length - 2).split(':')[1];
				}
			});
	
			$$.getJSON(responder, filters, function(data) {
		 		filterCount = data.filterCount;
				
				$$(selector == undefined ? '#product_list' : selector).animate({ opacity : .3 }, animationSpeed, 'linear', function() {
					$$(this).html(data.products);
					$$(this).find('ul').attr('id', 'product_list').show();
					$$(this).animate({ opacity : 1 }, animationSpeed, 'linear');
					plural = (filterCount < 2) ? productPlural : productsPlural;
					$$('.category_title span').text(filterCount + ' ' + plural);
					productPaging(filterCount, false, resetPage, selector);
				});
				filteredChildren(data);
			});
		} else {
			filters['act']      = 'filter';
			filters['ident']    = currentId;
			filters['page']     = page == undefined ? 1 : page;
			filters['perpage']  = productsPerPage;
			filters['orderby']  = orderBy;
			filters['orderway'] = orderWay;
	
			$$.getJSON(responder, filters, function(data) {
				$$(selector == undefined ? '#product_list' : selector).animate({ opacity : .3 }, animationSpeed, 'linear', function() {
					$$(this).html(data.products);
					$$(this).find('ul').attr('id', 'product_list').show();
					$$(this).animate({ opacity : 1 }, animationSpeed, 'linear');
					productPaging(filterCount, false, resetPage, selector);
				});
			});
		}
		
		$$('#tabs').prepend('<div class="filterAjaxLoaderAbsTop" class="clear"></div>');
				
		if(onTabLoad == true) {
			$$('.pagination').hide();
		}
	}
	
	function filteredChildren(data) {
		if(data != undefined) {
			var iObj, item;
			
			$$.each(data.enable, function(type) {

				$$.each(data.enable[type], function(k, id) {
					if(data.enable[type + '_count'] != undefined && data.enable[type + '_count'][k] != undefined) {
						item = data.enable[type + '_count'][k];
						$$('li.' + type + '_' + item.id + ' .pCount', 'div[rel^=' + type + ']').text(item.count);
					}
					iObj = $$('li.' + type + '_' + id, 'div[rel^=' + type + ']').removeClass('filterDisabled').addClass('filterEnabled');
					iObj.animate({ opacity : 1 }, animationSpeed, 'linear', function() {
						$$(this).slideDown();
					});
				});
				
				$$('div[rel^=' + type + '] .filterEntry').each(function() {
					if($$(this).is(':hidden') && $$('li.filterEnabled', this).length > 0) {
						$$(this).slideDown();
					}
				});
			});
			
			$$.each(data.disable, function(type) {
				$$.each(data.disable[type], function(k, id) {
					iObj = $$('li.' + type + '_' + id, 'div[rel^=' + type + ']').addClass('filterDisabled').removeClass('filterEnabled');	
					iObj.animate({ opacity : 0 }, animationSpeed, 'linear', function() {
						$$(this).slideUp();
					});
				});
				
				$$('div[rel^=' + type + '] .filterEntry').each(function() {
					if($$(this).is(':visible') && $$('li.filterEnabled', this).length < 1) {
						$$(this).slideUp();
					}
				});
			});
		}
	}
	
	function generateFilterList(toPush, selector) {
		$.each(toPush, function(k, v) {
			filterList.push(v);
		});

		if(filterList.length > 0) performFilter(true, undefined, selector);
	
		$$('.filterViewAllFilters input').click(function() {
			window.location.hash = '#filter=';
			$$('.filterSearchModule input:checkbox').attr('checked', false);
			filterList = [];
			performFilter(true, undefined, selector);
		});
	
		$$('.clearSelection').click(function() {
			var internal = $$(this).parents('div').eq(0).get(),
				tmpList  = [],
				newUri   = [],
				uri      = window.location.hash.replace('#filter=', '').split(',');
	
			$$('ul li', internal).each(function() {
				$$('input', this).attr('checked', false);
				tmpList.push(parseInt($$(this).attr('class').split('_')[1]));
			});
	
			newUri = $.grep(uri, function(v) {
				return $.inArray(parseInt(v.match(/\d+/)), tmpList) == -1;
			});
	
			window.location.hash = 'filter=' + newUri.join(',');
			filterList = [];
			performFilter(true, undefined, selector);
		});
	
		$$('.filterSearchModule input:checkbox').click(function() {
			var clObj = $$(this);
	
			if(ajaxLoader == true) {
				$$('.filterSearchModule').append('<div class="filterAjaxLoaderAbs" class="clear"></div>');
			}
	
			if(clObj.is(':checked')) {
				filterList.push('[' + clObj.attr('name') + ':' + clObj.val() + ']');
			} else {
				filterList = $.grep(filterList, function(v) {
					return v != '[' + clObj.attr('name') + ':' + clObj.val() + ']';
				});
			}
	
			doUri(filterList);
			performFilter(true, undefined, selector);
		});
	
		return false;
	}
	
	function loadUri() {
		var uri    = window.location.hash.substr(1, window.location.hash.length).split('=', 2),
			toPush = [];
	
		if(uri[0] != undefined && uri[1] != undefined && uri[1] != '') {
			$.each(uri[1].split(','), function(k, v) {
				toPush.push(v);
				$$(':checkbox', '.' + v.substr(1, v.length-2).replace(':', '_')).attr('checked', true);
			});
		}
	
		return toPush;
	}
	
	function doUri(list) {	
		window.location.hash = 'filter=' + list.join(',');
	}