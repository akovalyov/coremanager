<?php
/**
 * filtersearch.module.php
 * @author       Adam Lee & Yaakov Albietz - ejectcore.com
 * @copyright   Copyright Eject Core 2009-2010. All rights reserved.
 * @license       GPLv3 License http://www.gnu.org/licenses/gpl-3.0.html
 * @credit    3rd Party Development: Seth Benjamin
 * @package     Filter Search Community Edition
 * @version       v2.1 Final
 *
 */

class FilterSearch extends ProCoreApi
{
    protected $lang;
    protected $name;
    protected $ident;
    protected $currency;
    protected $displayName;
    protected $processSQL;
    protected $inclTax;
    protected $minTax = 0;
    protected $maxTax = 0;
    protected $tmpCount = 0;
    protected $productIds = array();
    protected $productPrices = array();
    protected $productFinalPrices = array();
    protected $productRangeIds = array();
    protected $productPricesWT = array();
    protected $filteredPricesWT = array();
    protected $idFinalPriceAssoc = array();
    protected $idPriceAssoc = array();
    protected $ranges = array();
    protected $attributes = array();
    protected $features = array();
    protected $manufacturers = array();

    function __construct($onAjax = FALSE)
    {
        $cookie = Context::getContext()->cookie;
        $smarty = Context::getContext()->smarty;

        $this->name = 'filtersearch';
        $this->displayName = $this->l('Filter Search', $this->name);
        $this->lang = Language::getLanguage((int)$this->id_lang) ? : $this->id_lang = Configuration::get('PS_LANG_DEFAULT');
        $this->currency = (object)Tools::setCurrency($cookie);
        $this->inclTax = (bool)Configuration::get('PS_TAX');

        parent::__construct($smarty);
        $this->productsInCategory($onAjax);
        $this->productPriceRanges();
        $this->productAttributes();
        $this->productFeatures();
        $this->productManufacturers();
    }

    function __destruct()
    {
        unset($this->currency);
    }

    ## Hooks
    ##############################################

    function hookdisplayleftColumn($params)
    {
        return $this->display();
    }

    function hookdisplayrightColumn($params)
    {
        return $this->hookleftColumn($params);
    }

    function install()
    {
        if (file_exists(dirname(__FILE__) . '/install.sql')) $this->processSQL = 'install.sql';
        $sql = file_get_contents(dirname(__FILE__) . '/' . $this->processSQL);
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql AS $k => $query) $this->db->Execute(trim($query));
        $this->hookModule($this->name, array(7, 9));
    }

    function uninstall()
    {
        if (!$this->db->Execute('DROP TABLE ' . _DB_PREFIX_ . 'filter_group')) return FALSE;
    }

    ## Display
    ##############################################

    function display($stylesheet = TRUE)
    {
        $smarty = Context::getContext()->smarty;

        $smarty->assign(array(
            'currentId' => $this->ident,
            'stylesheet' => $stylesheet,
            'coreAssets' => $this->assets,
            'assets' => __PS_BASE_URI__ . 'modules/coremanager/modules/' . $this->name . '/assets/',
            'PPPage' => (int)Configuration::get('PS_PRODUCTS_PER_PAGE'),
            'countIds' => count($this->productIds),
            'currencySign' => $this->currency->sign,
            'ranges' => $this->ranges,
            'attributes' => $this->attributes,
            'features' => $this->features,
            'rangeDisplay' => $this->outputRangeDisplay(),
            'groupDisplay' => $this->outputGroupDisplay(),
            'manufacturers' => $this->manufacturers));
        return $this->view('filtersearch', array(), $smarty, $this->name);
    }

    function outputRangeDisplay()
    {
        $output = '';
        $noDecimals = ($this->currency->decimals == 0 ? TRUE : FALSE);

        if ($this->ranges != FALSE && count($this->ranges) > 0) {
            $output .= '
					<div class="filterGroup" rel="ranges">
						<div class="filterEntry">
							<h3>' . $this->l('Price Ranges', 'filtersearch.module') . '</h3>
								<ul>';

            foreach ($this->ranges AS $range) {
                $rangeMin = ($noDecimals ? number_format($range['digitMin'], 2, '', '') : $range['digitMin']);
                $rangeMax = ($noDecimals ? number_format($range['digitMax'], 2, '', '') : $range['digitMax']);
                $output .= '
							<li class="range_' . $rangeMin . '_' . $rangeMax . ' filterEnabled">
								<label><input type="checkbox" name="range" value="' . $rangeMin . '_' . $rangeMax . '" />
								<a>' . $range['min'] . ' - ' . $range['max'] . '</a> (<span class="pCount">' . $range['count'] . '</span></label>)
							</li>';
            }

            $output .= '
								</ul>
							</div>
						</div>';
            return $output;
        }
    }

    function outputGroupDisplay()
    {
        $output = '';
        if ($this->attributes != FALSE && count($this->attributes) > 0) {
            $output = '<div class="filterGroup" rel="attributes">';

            foreach ($this->attributes AS $group => $attribute) {
                $output .= '
						<div class="filterEntry">
							<h3>' . $group . '</h3>
							<ul rel="' . $group . '" class="attribute_group">';

                foreach ($attribute AS $value) {
                    $output .= '
						<li class="attribute_' . $value['id_attribute'] . ' filterEnabled">
							<label><input type="checkbox" name="attribute" value="' . $value['id_attribute'] . '" />
							<a>' . Tools::truncate($value['name'], 20) . '</a> (<span class="pCount">' . $value['count'] . '</span></label>)
						</li>';
                }

                $output .= '
							</ul>
						</div>';
            }

            $output .= '</div>';
        }

        if ($this->features != FALSE AND count($this->features) > 0) {
            $output .= '<div class="filterGroup" rel="features">';

            foreach ($this->features AS $group => $feature) {
                $output .= '
						<div class="filterEntry">
							<h3>' . $group . '</h3>
							<ul rel="' . $group . '" class="feature">';

                foreach ($feature AS $value) {
                    $output .= '
								<li class="feature_' . $value['id_feature_value'] . ' filterEnabled">
									<input type="checkbox" name="feature" value="' . $value['id_feature_value'] . '" />
									<a>' . Tools::truncate($value['value'], 20) . '</a> (<span class="pCount">' . $value['count'] . '</span>)
								</li>';
                }

                $output .= '
							</ul>
						</div>';
            }

            $output .= '</div>';
        }

        if ($this->manufacturers != FALSE AND count($this->manufacturers) > 0) {
            $output .= '
					<div class="filterGroup" rel="manufacturers">
						<div class="filterEntry">
							<h3>' . $this->l('Manufacturers', 'filtersearch.module') . '</h3>
							<ul rel="' . $this->l('Manufacturers', 'filtersearch.module') . '" class="manufacturer">';

            foreach ($this->manufacturers AS $manufacturer) {
                $output .= '
								<li class="manufacturer_' . $manufacturer['id_manufacturer'] . ' filterEnabled">
									<input type="checkbox" name="manufacturer" value="' . $manufacturer['id_manufacturer'] . '" />
									<a>' . Tools::truncate($manufacturer['name'], 20) . '</a> (<span class="pCount">' . $manufacturer['count'] . '</span>)
								</li>';
            }

            $output .= '
							</ul>
						</div>
					</div>';
        }
        return $output;
    }

    ## Products
    ##############################################

    function productsInCategory($onAjax = FALSE)
    {
        $this->ident = Tools::getValue(!$onAjax ? 'id_category' : 'ident');

        if ($this->ident > 0) {
            $category = new Category();
            $category->id = $this->ident;
            $products = $category->getProducts($this->lang, 1, $category->getProducts($this->lang, 1, 1, NULL, NULL, TRUE));
            $productIds = $productPrices = $productFinalPrices = $productPricesWT = array();

            if ($products !== FALSE && count($products) > 0 && is_array($products)) {
                foreach ($products AS $product) {
                    $productIds[] = $product['id_product'];
                    $productFinalPrices[] = $product['price'];
                    $productInst = new Product($product['id_product'], TRUE, $this->lang);
                    $this->productFinalPricesWT[] = $productInst->getPrice($this->inclTax);
                }
            } else {
                $productIds = $productPrices = $productFinalPrices = array();
            }

            $this->idFinalPriceAssoc = $products != FALSE ? (count($productIds) > 0 ? array_combine($productIds, $productFinalPrices) : array()) : array();
            $this->productIds = $productIds;

            sort($productPrices);
            $this->productFinalPrices = $productFinalPrices;
        }
    }

    ## Filter
    ##############################################

    function sortByTabs(Smarty $smarty)
    {
        return $this->view('sortby-tabs', array(), $smarty, $this->name);
    }

    function performSearch(Smarty $smarty, $page = 1, $perPage = 10, $orderBy = 'newest', $orderWay = 'DESC')
    {
        global $cookie;

        $page = (int)$page;
        $perPage = (int)$perPage;

        if ($orderBy == 'newest') $orderBy = 'date_add';
        $orderByPrefix = $orderBy == 'id_product' || $orderBy == 'date_add' ? 'p' : ($orderBy == 'name' ? 'pl' : ($orderBy == 'manufacturer' ? 'm' : ($orderBy == 'position' ? 'cp' : '')));

        if ($orderBy == 'manufacturer') $orderBy = 'name';
        if ($orderBy == 'price') $orderBy = 'price';


        /**
         * it is the original sql block
         */
//        $sql = '
//				SELECT	DISTINCT p.*, pa.`id_product_attribute`, pl.`description`, pl.`description_short`, pl.`available_now`,
//						pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`,
//						pl.`name`, i.`id_image`, il.`legend`, m.`name` AS manufacturer_name, tl.`name` AS tax_name, t.`rate`,
//						cl.`name` AS category_default, DATEDIFF(p.`date_add`, DATE_SUB(NOW(),
//						INTERVAL ' . (Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY)) > 0 AS new,
//						(p.price - IF((DATEDIFF(reduction_from, CURDATE()) <= 0 AND DATEDIFF(reduction_to, CURDATE()) >=0) OR reduction_from = reduction_to, IFNULL(reduction_price, (p.price * reduction_percent / 100)),0)) AS orderprice
//				FROM `' . _DB_PREFIX_ . 'category_product` cp
//				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = cp.`id_product`
//				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (p.`id_product` = pa.`id_product` AND default_on = 1)
//				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = ' . $this->lang . ')
//				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . $this->lang . ')
//				LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_product` = p.`id_product` AND i.`cover` = 1)
//				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = ' . $this->lang . ')
//				LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON t.`id_tax` = p.`id_tax`
//				LEFT JOIN `' . _DB_PREFIX_ . 'tax_lang` tl ON (t.`id_tax` = tl.`id_tax` AND tl.`id_lang` = ' . $this->lang . ')
//				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
//				WHERE p.`id_product` IN(' . implode(',', count($this->productAssocIds()) > 0 ? $this->productAssocIds() : $this->productIds) . ')
//				AND p.`active` = 1 ORDER BY ' . (!empty($orderByPrefix) ? $orderByPrefix . '.' : '') . $orderBy . ' ' . $orderWay;
        $sql = '
				SELECT	DISTINCT p.*, pa.`id_product_attribute`, pl.`description`, pl.`description_short`, pl.`available_now`,
						pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`,
						pl.`name`, i.`id_image`, il.`legend`, m.`name` AS manufacturer_name,
						cl.`name` AS category_default, DATEDIFF(p.`date_add`, DATE_SUB(NOW(),
						INTERVAL ' . (Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY)) > 0 AS new
				FROM `' . _DB_PREFIX_ . 'category_product` cp
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = cp.`id_product`
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (p.`id_product` = pa.`id_product` AND default_on = 1)
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (p.`id_category_default` = cl.`id_category` AND cl.`id_lang` = ' . $this->lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . $this->lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_product` = p.`id_product` AND i.`cover` = 1)
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = ' . $this->lang . ')

				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE p.`id_product` IN(' . implode(',', count($this->productAssocIds()) > 0 ? $this->productAssocIds() : $this->productIds) . ')
				AND p.`active` = 1 ORDER BY ' . (!empty($orderByPrefix) ? $orderByPrefix . '.' : '') . $orderBy . ' ' . $orderWay;

        if ($fullResult = $this->db->ExecuteS($sql)) {

            $limitedResult = array_slice($fullResult, (($page - 1) * $perPage), $perPage);

            if ($orderBy == 'orderprice') Tools::orderbyPrice($limitedResult, $orderWay);

            ob_start();
            print $this->view('product-list', array('products' => Product::getProductsProperties($this->lang, $limitedResult)), $smarty, $this->name);

            $products = ob_get_clean();
            $filterCount = count($this->productAssocIds()) > 0 ? count($this->productAssocIds()) : count($this->productIds);

            return array(
                'products' => $products,
                'filterCount' => $filterCount,
                'disable' => $this->disableFilters($fullResult),
                'enable' => $this->disableFilters($fullResult, TRUE)
            );
        } else {
            return array(
                'products' => '',
                'filterCount' => '',
                'disable' => '',
                'enable' => ''
            );
        }
    }

    function disableFilters(Array $result, $inverse = FALSE)
    {
        $products = $productIds = $rCount = array();

        foreach ($result AS $result) {
            $product = new Product($result['id_product'], TRUE, $this->lang);
            $finalPrice = $product->getPrice($this->inclTax);
            array_push($products, array('id_product' => $result['id_product'], 'price' => $finalPrice));
        }

        if (count($products) > 0) {
            foreach ($products AS $product) array_push($productIds, $product['id_product']);

            $assembly = array('Attributes', 'Features', 'Manufacturers');
            $attributes = $this->productAttributes($productIds, FALSE, FALSE);
            $features = $this->productFeatures($productIds, FALSE, FALSE);
            $manufacturers = $this->productManufacturers($productIds, FALSE);

            foreach ($assembly AS $value) {
                ${'tmp' . $value} = ${'tmp' . $value . 'Cmp'} = ${'count' . $value} = array();

                if (is_array(${strtolower($value)})) {
                    foreach (${strtolower($value)} AS $set) {
                        if (strtolower($value) == 'features') {
                            foreach ($set AS ${strtolower(substr($value, -1))}) {
                                array_push(${'count' . $value}, array('id' => ${strtolower(substr($value, -1))}['id_' . strtolower(substr($value, 0, -1)) . '_value'], 'count' => ${strtolower(substr($value, -1))}['count']));
                                array_push(${'tmp' . $value}, ${strtolower(substr($value, -1))}['id_' . strtolower(substr($value, 0, -1)) . '_value']);
                            }
                        } elseif (strtolower($value) != 'manufacturers') {
                            foreach ($set AS ${strtolower(substr($value, -1))}) {
                                array_push(${'count' . $value}, array('id' => ${strtolower(substr($value, -1))}['id_' . strtolower(substr($value, 0, -1))], 'count' => ${strtolower(substr($value, -1))}['count']));
                                array_push(${'tmp' . $value}, ${strtolower(substr($value, -1))}['id_' . strtolower(substr($value, 0, -1))]);
                            }
                        } else {
                            array_push(${'count' . $value}, array('id' => $set['id_' . strtolower(substr($value, 0, -1))], 'count' => $set['count']));
                            array_push(${'tmp' . $value}, $set['id_' . strtolower(substr($value, 0, -1))]);
                        }
                    }
                }

                foreach ($this->{strtolower($value)} AS $set) {
                    if (strtolower($value) == 'features') {
                        foreach ($set AS ${strtolower(substr($value, -1))}) array_push(${'tmp' . $value . 'Cmp'}, ${strtolower(substr($value, -1))}['id_' . strtolower(substr($value, 0, -1)) . '_value']);
                    } elseif (strtolower($value) != 'manufacturers') {
                        foreach ($set AS ${strtolower(substr($value, -1))}) array_push(${'tmp' . $value . 'Cmp'}, ${strtolower(substr($value, -1))}['id_' . strtolower(substr($value, 0, -1))]);
                    } else {
                        array_push(${'tmp' . $value . 'Cmp'}, $set['id_' . strtolower(substr($value, 0, -1))]);
                    }
                }

                if ($inverse === FALSE && count($_GET) < 3) ${'tmp' . $value . 'Cmp'} = array();
            }

            $difference = array(
                'range' => $this->getRangeWithFilter($products, $inverse),
                'attribute' => array_merge(array_unique($inverse === FALSE ? array_diff($tmpAttributesCmp, $tmpAttributes) : array_intersect($tmpAttributesCmp, $tmpAttributes))),
                'feature' => array_merge(array_unique($inverse === FALSE ? array_diff($tmpFeaturesCmp, $tmpFeatures) : array_intersect($tmpFeaturesCmp, $tmpFeatures))),
                'manufacturer' => array_merge(array_unique($inverse === FALSE ? array_diff($tmpManufacturersCmp, $tmpManufacturers) : array_intersect($tmpManufacturersCmp, $tmpManufacturers))),
                'attribute_count' => $countAttributes,
                'feature_count' => $countFeatures,
                'manufacturer_count' => $countManufacturers);

            if ($inverse === TRUE) {
                foreach ($this->getRangeWithFilter($products, $inverse, TRUE) AS $range)
                    if ($range['count'] > 0) $rCount[] = $range;
                $difference['range_count'] = $rCount;
            }

            return $difference;
        } else {
            return FALSE;
        }
    }

    function getGroupByTypeId($type, $id)
    {
        $group = array();

        switch ($type) {
            case 'feature' :
                $group = $this->db->getRow('
						SELECT `id_feature` as `group`
						FROM `' . _DB_PREFIX_ . 'feature_value`
						WHERE `id_feature_value` = ' . $id);
                break;
            case 'attribute' :
                $group = $this->db->getRow('
						SELECT `id_attribute_group` as `group`
						FROM `' . _DB_PREFIX_ . 'attribute`
						WHERE `id_attribute` = ' . $id);
                break;
            default :
                $group['group'] = 0;
                break;
        }

        return isset($group['group']) ? $group['group'] : FALSE;
    }

    function productFilter()
    {
        $filter = array();
        $whitelist = array('act', 'ident', 'page', 'perpage', 'orderby', 'orderway');
        if (count($_GET) > count($whitelist)) {
            $list = array();
            $i = 0;

            foreach ($_GET AS $type => $id) {
                if (!in_array($type, $whitelist) && $type !='controller') {
                    $type = preg_replace('/_[0-9]+/', '', $type);

                    array_push($filter, array(
                        'type' => $type,
                        'group' => $this->getGroupByTypeId($type, (int)$id),
                        'id' => $id));

                    $list[] = $type;
                }
            }
        }

        return $filter;
    }

    function productAssocIds()
    {
        $query = '';
        $range_low = $range_high = 0;
        $feature = $attribute = $manufacturer = $range = array();
        $filter = $this->productFilter();
        $uriRange = $this->uriRangeDecode(FALSE);

        if ($uriRange !== FALSE) {
            extract($uriRange);
        }
        $this->productRangeCount($range_low, $range_high);

        $pIDs = implode(',', $this->productIds);

        foreach ($filter AS $set) {
            switch ($set['type']) {
                case 'feature' :
                    $query = '
							SELECT fp.`id_product`
							FROM `' . _DB_PREFIX_ . 'feature_value` fv
							LEFT JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl ON(fv.`id_feature_value` = fvl.`id_feature_value` AND fvl.`id_lang` = ' . $this->lang . ')
							LEFT JOIN `' . _DB_PREFIX_ . 'feature_product` fp ON(fv.`id_feature_value` = fp.`id_feature_value`)
							WHERE fv.`id_feature_value` = ' . $set['id'] . '
							AND fp.`id_product` IN(' . $pIDs . ')
							GROUP BY fp.`id_product`';
                    break;
                case 'attribute' :
                    $query = '
							SELECT pa.`id_product`
							FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
							LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON(pac.`id_product_attribute` = pa.`id_product_attribute`)
							WHERE pac.`id_attribute` = ' . $set['id'] . '
							AND pa.`id_product` IN(' . $pIDs . ')
							GROUP BY pa.`id_product`';
                    break;
                case 'manufacturer' :
                    $query = '
							SELECT p.`id_product`
							FROM `' . _DB_PREFIX_ . 'product` p
							WHERE p.`id_manufacturer` IN(' . (is_array($set['id']) ? implode(',', $set['id']) : $set['id']) . ')
							AND p.`id_product` IN(' . $pIDs . ')
							GROUP BY p.`id_product`';
                    break;
                case 'range' :
                    $query = '
							SELECT `id_product`
							FROM `' . _DB_PREFIX_ . 'product`
							WHERE `id_product` IN(' . implode(',', $this->productRangeIds) . ')
							GROUP BY `id_product`';
                    break;
            }

            if ($result = $this->db->ExecuteS($query)) {
                $tmp = array();
                foreach ($result AS $row) array_push($tmp, $row['id_product']);
                if (is_array($$set['type'])) array_push($$set['type'], $tmp);
            }
//            print_r($this->productAssocIds());
        }

        $attribute_sect = $feature_sect = $manufacturer_sect = $range_sect = array();
        $prodAssoc = array('attribute', 'feature', 'manufacturer', 'range');

        foreach ($prodAssoc AS $array) {
            if (count($$array) > 1) {
                for ($a = 0, $b = count($$array); $a < $b; $a++) {
                    if (isset(${$array}[$a + 1])) ${$array . '_sect'} = array_intersect($a === 0 ? current($$array) : ${$array . '_sect'}, ${$array}[$a + 1]);
                }
            } else {
                ${$array . '_sect'} = current($$array);
            }
        }

        foreach ($_GET AS $key => $value) {
            if (preg_match('/(attribute|feature|manufacturer|range)_\d+/', $key, $matches)) {
                $productIds = ${$matches[1] . '_sect'};
                break;
            }
        }

        foreach ($prodAssoc AS $arr) if (!empty(${$arr . '_sect'})) $productIds = array_intersect($productIds, ${$arr . '_sect'});

        return isset($productIds) ? $productIds : array();
    }


    ## Manufacturers
    ##############################################

    function productManufacturers($products = array(), $global = TRUE)
    {
        if (count($products) > 0 || count($this->productIds) > 0) {
            $products = count($products) > 0 ? $products : $this->productIds;
            $manufacturers = $this->db->ExecuteS('
					SELECT COUNT(DISTINCT p.`id_product`) as `count`, m.`id_manufacturer`, m.`name`
					FROM `' . _DB_PREFIX_ . 'manufacturer` m
					LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON(p.`id_manufacturer` = m.`id_manufacturer`)
					WHERE p.`id_product` IN(' . implode(',', $products) . ')
					AND p.`active` = 1 GROUP BY m.`name`');

            if ($global === TRUE) $this->manufacturers = $manufacturers;

            return $manufacturers;
        } else {
            return FALSE;
        }
    }

    ## Attributes
    ##############################################

    function productAttributes($products = array(), $inclGroups = TRUE, $global = TRUE)
    {
        $attributes = array();

        if (count($products) > 0 || count($this->productIds) > 0) {
            $incr = 0;
            $products = count($products) > 0 ? $products : $this->productIds;
            $attrGroups = $this->db->ExecuteS('
					SELECT ag.`id_attribute_group`, agl.`public_name`
					FROM `' . _DB_PREFIX_ . 'attribute_group` ag
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON(ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . $this->lang . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON(a.`id_attribute_group` = ag.`id_attribute_group`)
					LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON(a.`id_attribute` = pac.`id_attribute`)
					LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON(pac.`id_product_attribute` = pa.`id_product_attribute`)
					WHERE pa.`id_product` IN(' . implode(',', $products) . ')
					GROUP BY ag.`id_attribute_group`');

            foreach ($attrGroups AS $group) {
                $attributes[($inclGroups === TRUE ? $group['public_name'] : $incr)] = $this->db->ExecuteS('
						SELECT COUNT(DISTINCT pa.`id_product`) as `count`, a.`id_attribute`, al.`name`
						FROM `' . _DB_PREFIX_ . 'attribute` a
						LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON(a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . $this->lang . ')
						LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON(al.`id_attribute` = pac.`id_attribute`)
						LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON(pac.`id_product_attribute` = pa.`id_product_attribute`)
						WHERE a.`id_attribute_group` = "' . $group['id_attribute_group'] . '"
						AND pa.`id_product` IN(' . implode(',', $products) . ')
						GROUP BY al.`name`');
                $incr++;
            }

            if ($global === TRUE) $this->attributes = $attributes;

            return $attributes;
        } else {
            return FALSE;
        }
    }

    ## Features
    ##############################################

    function productFeatures($products = array(), $inclGroups = TRUE, $global = TRUE)
    {
        if (count($products) > 0 || count($this->productIds) > 0) {
            $incr = 0;
            $products = count($products) > 0 ? $products : $this->productIds;
            $features = array();
            $featGroups = $this->db->ExecuteS('
					SELECT f.`id_feature`, fl.`name`
					FROM `' . _DB_PREFIX_ . 'feature` f
					LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl ON(f.`id_feature` = fl.`id_feature` AND fl.`id_lang` = ' . $this->lang . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'feature_value` fv ON(fl.`id_feature` = fv.`id_feature`)
					LEFT JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl ON(fv.`id_feature_value` = fvl.`id_feature_value` AND fvl.`id_lang` = ' . $this->lang . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'feature_product` fp ON(fvl.`id_feature_value` = fp.`id_feature_value`)
					WHERE fp.`id_product` IN(' . implode(',', $products) . ')
					GROUP BY fl.`name`');

            foreach ($featGroups AS $group) {
                $features[($inclGroups === TRUE ? $group['name'] : $incr)] = $this->db->ExecuteS('
						SELECT COUNT(DISTINCT fp.`id_product`) as `count`, f.`id_feature`, fvl.`value`, fvl.`id_feature_value`
						FROM `' . _DB_PREFIX_ . 'feature` f
						LEFT JOIN `' . _DB_PREFIX_ . 'feature_lang` fl ON(f.`id_feature` = fl.`id_feature` AND fl.`id_lang` = ' . $this->lang . ')
						LEFT JOIN `' . _DB_PREFIX_ . 'feature_value` fv ON(fl.`id_feature` = fv.`id_feature`)
						LEFT JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl ON(fv.`id_feature_value` = fvl.`id_feature_value` AND fvl.`id_lang` = ' . $this->lang . ')
						LEFT JOIN `' . _DB_PREFIX_ . 'feature_product` fp ON(fvl.`id_feature_value` = fp.`id_feature_value`)
						WHERE fp.`id_product` IN(' . implode(',', $products) . ')
						AND fl.`name` = "' . $group['name'] . '"
						GROUP BY fvl.`id_feature_value`');
                $incr++;
            }

            if ($global === TRUE) $this->features = $features;

            return $features;
        } else {
            return FALSE;
        }
    }

    ## Price
    ##############################################

    function productPriceRanges($prices = array(), $productAssoc = array(), $global = TRUE)
    {

        $cent = 0.01;
        $ranges = array();
        $rangeStrings = $this->productPriceRangeStrings($prices, $productAssoc);

        if ($rangeStrings !== FALSE) {
            foreach ($rangeStrings AS $string) {
                $exString = explode(':', $string);
                if ($count = (isset($exString[2]) ? $exString[2] : FALSE)) {
                    if ($count > 0) {
                        $pMin = round(Tools::convertPrice($exString[0], $this->currency));
                        $pMax = round(Tools::convertPrice($exString[1], $this->currency));
                        $ranges[] = array(
                            'digitMin' => isset($exString[0]) ? preg_replace('/\D+/', '', Tools::displayPrice($pMin, $this->currency)) : FALSE,
                            'digitMax' => isset($exString[1]) ? preg_replace('/\D+/', '', Tools::displayPrice($pMax, $this->currency)) : FALSE,
                            'min' => isset($exString[0]) ? Tools::displayPrice($pMin, $this->currency) : FALSE,
                            'max' => isset($exString[1]) ? Tools::displayPrice($pMax - $cent, $this->currency) : FALSE,
                            'count' => $count);
                    }
                }
            }
            if ($global === TRUE) $this->ranges = $ranges;

            return $ranges;
        }
    }

    function productPriceRangeStrings($prices = array(), $productAssoc = array(), $nbRange = 6)
    {
        if (count($this->productIds) > 0) {
            $productIdMin = array_search(min(count($prices) < 1 ? $this->productFinalPrices : $prices), count($productAssoc) < 1 ? $this->idFinalPriceAssoc : $productAssoc);
            $productIdMax = array_search(max(count($prices) < 1 ? $this->productFinalPrices : $prices), count($productAssoc) < 1 ? $this->idFinalPriceAssoc : $productAssoc);
            $productMin = new Product($productIdMin, TRUE, $this->lang);
            $productMax = new Product($productIdMax, TRUE, $this->lang);
            $min = $productMin->getPrice($this->inclTax);
            $max = $productMax->getPrice($this->inclTax);
            $this->minTax = 0; //Tax::getApplicableTax($productMin->id_tax, $productMin->tax_rate);
            $this->maxTax = 1; //Tax::getApplicableTax($productMax->id_tax, $productMax->tax_rate);

            $explicit = FALSE;
            $range = $retRanges = array();
            $mxAvg = (float)($max / $nbRange);
            $mxAvg = ($nbRange > 6 ? $mxAvg + 1 : $mxAvg);
            $minTmp = $explicit ? $min : floor($min);

            for ($i = 1; $i <= $nbRange; $i++) {
                $temp = $min + $mxAvg;

                if ($temp > $max) $temp = $max;
                if ($minTmp != $min) $minTmp = $explicit ? $minTmp : round($minTmp);
                if ($temp != $max) $temp = $explicit ? $temp : round($temp);

                $range[$minTmp] = $min = $temp;
                $minTmp = $temp + 1;
            }

            $i = 0;
            $nbr = count($range) - 1;

            foreach ($range AS $min => $max) {
                if ($i == 0) {
                    $minPer = 0;
                } else {
                    $minPer = round($min, strlen(intval($min)) < 4 ? -1 : -floor(log10($min)));
                }

                if ($i == $nbr) {
                    $maxPer = round($max, strlen(intval($max)) < 4 ? -1 : -floor(log10($max)) - $i == $nbr ? 1 : 0);
                } else {
                    $maxPer = round($max, strlen(intval($max)) < 4 ? -1 : -floor(log10($max))) - 0.01;
                }

                if ($explicit == TRUE) {
                    if ($min < $max)
                        $retRanges[] = $min . ':' . $max . ':' . $this->productRangeCount($min, $max);
                } else {
                    if ($minPer < $maxPer)
                        $retRanges[] = $minPer . ':' . $maxPer . ':' . $this->productRangeCount($minPer, $maxPer);
                }

                $i++;
            }
            return $retRanges;
        } else {
            return FALSE;
        }
    }


    function getRangeWithFilter($products, $enabled, $returnCounts = FALSE)
    {
        $ignoreTax = FALSE;
        $ranges = $filterRanges = $rangeCounts = array();
        $glRanges = $this->productPriceRanges(array(), array(), FALSE);
        $noDecimals = ($this->currency->decimals == 0 ? TRUE : FALSE);

        foreach ($glRanges AS $key => $range) {
            $rangeMin = ($noDecimals ? number_format($range['digitMin'], 2, '', '') : $range['digitMin']);
            $rangeMax = ($noDecimals ? number_format($range['digitMax'], 2, '', '') : $range['digitMax']);
            array_push($ranges, $rangeMin . '_' . $rangeMax);
        }

        foreach ($this->productFilter() AS $filter) {
            if ($filter['type'] == 'range' && in_array($filter['id'], $ranges)) {
                $ignoreTax = TRUE;
                break;
            }
        }

        foreach ($glRanges AS $key => $range) {
            array_push($filterRanges, $range['digitMin'] . '_' . $range['digitMax']);
        }

        foreach ($ranges AS $filter) {
            list($low, $high) = explode('_', $filter);

            $count = 0;
            $low = substr($low, 0, -2) . '.' . substr($low, -2);
            $high = substr($high, 0, -2) . '.' . substr($high, -2);
            $low /= $this->currency->conversion_rate;
            $high /= $this->currency->conversion_rate;

            foreach ($products AS $set) {
                if ($set['price'] >= floor($low) && $set['price'] <= ceil($high)) $count++;
            }
            array_push($rangeCounts, array('id' => $filter, 'count' => $count));
        }

        $retRanges = $retRangesOp = array();

        foreach ($rangeCounts AS $r) {
            if ($r['count'] < 1) {
                array_push($retRanges, $r['id']);
            } else {
                array_push($retRangesOp, $r['id']);
            }
        }

        $retRanges = $enabled !== FALSE ? $ranges : $retRanges;

        if ($returnCounts === TRUE && $enabled === TRUE) {
            return $rangeCounts;
        } else {
            return $enabled === TRUE ? $retRangesOp : $retRanges;
        }
    }

    function uriRangeDecode($round = TRUE, $products = array(), $ignoreTax = FALSE)
    {
        foreach ($_GET AS $key => $value) {
            if (preg_match('/(range)_\d+/', $key, $matches)) {
                $range = TRUE;
                $tmpIds = array();
                $tmpLow = preg_replace('/_\d+/', '', $value);
                $tmpHigh = preg_replace('/\d+_/', '', $value);
                $rangeLow = (substr($tmpLow, 0, strlen($tmpLow) - 2) . '.' . substr($tmpLow, -2)) / $this->currency->conversion_rate;
                $rangeHigh = (substr($tmpHigh, 0, strlen($tmpHigh) - 2) . '.' . substr($tmpHigh, -2)) / $this->currency->conversion_rate;
                break;
            }
        }

        if (!isset($range) && count($products) > 0) {
            $tmpIds = $tmpPrices = $tmpRanges = array();

            foreach ($products AS $product) {
                array_push($tmpIds, $product['id_product']);
                array_push($tmpPrices, $product['price']);
            }

            $assoc = array_combine($tmpPrices, $tmpIds);

            ksort($assoc);

            $rangeLow = $this->inclTax ? $this->priceToDefault(current($assoc)) : min($assoc);
            $rangeHigh = $this->inclTax ? $this->priceToDefault(end($assoc)) : max($assoc);
        } elseif (isset($rangeLow) && isset($rangeHigh) && $rangeLow < 1 && $rangeHigh < 1) {
            return FALSE;
        }

        return isset($rangeLow) && isset($rangeHigh) ? array(
            'range_low' => number_format($round === TRUE ? floor($rangeLow) : $rangeLow, 2, '.', ''),
            'range_high' => number_format($round === TRUE ? ceil($rangeHigh) : $rangeHigh, 2, '.', '')
        ) : array();
    }

    function priceToDefault($id_product, $inclTax = FALSE)
    {
        $product = new Product(NULL, FALSE, $this->lang);
        $product->id = $id_product;
        $this->currency->id = Configuration::get('PS_CURRENCY_DEFAULT');

        return Tools::convertPrice($product->getPrice($inclTax), $this->currency);
    }

    function priceWithTax($id_product)
    {
        $product = new Product(NULL, FALSE, $this->lang);
        $product->id = $id_product;

        return Tools::convertPrice($product->getPrice(TRUE), $this->currency);
    }

    function productRangeCount($min, $max)
    {
        $productIdAssoc = array();
        $count = 0;

        if (count($this->idFinalPriceAssoc) > 0) {
            foreach ($this->idFinalPriceAssoc AS $product => $price) {

                if ($price >= floor($min) && $price <= ceil($max)) {
                    $productIdAssoc[] = $product;
                    $count++;
                }
            }
            $uriRange = $this->uriRangeDecode(FALSE);

            if ($uriRange !== FALSE && count($uriRange) > 0) {
                $this->productRangeIds = $productIdAssoc;
            }

            return $count;
        } else {
            return FALSE;
        }
    }
}