<?php
//$Copyright$

// no direct access
defined('_JEXEC') or die('Restricted access');

class WidgetkitVirtuemartWidgetkitHelper extends WidgetkitHelper {
        private static function getItemLayout($item, $ext, $extType, $layoutDir, $extLayoutDir, $default) {
                $extDir = '/' . $extType . 's/' . $ext;
                $tmpl = JFactory::getApplication()->getTemplate();

                $dirs = array(
                    JPATH_SITE . '/templates/' . $tmpl . '/html/' . $layoutDir . '/',
                    JPATH_SITE . $extDir . '/' . $extLayoutDir . '/'
                );

                $tmpl = '';

                // In priority order
                $files = array(
                    'i' . $item->virtuemart_product_id . '.php',
                    'c' . $item->virtuemart_category_id . '.php',
                    'item.php'
                );

                foreach ($dirs as $dir) {
                        foreach ($files as $file) {
                                if (JFile::exists($dir . $file)) {
                                        $tmpl = $dir . $file;
                                        break;
                                }
                        }

                        if (!empty($tmpl))
                                break;
                }

                return $tmpl;
        }

        public function renderItem($item, $params) {
                static $currency;
                
                if (!isset($currency)) $currency = CurrencyDisplay::getInstance();
                
                $tmpl = self::getItemLayout(
                        $item, 
                        'system/widgetkit_virtuemart', 
                        'plugin', 
                        'plg_widgetkit_virtuemart', 
                        'layouts', 
                        dirname(__FILE__) . '/layouts/item.php'
                );

                if ((bool) $params->get('show_price', 1)) {
                        $prices = $currency->createPriceDiv('salesPrice', '', $item->prices, FALSE, FALSE, 1.0, TRUE);
                        
                        if ($item->prices['salesPriceWithDiscount'] > 0) {
                                $prices .= $currency->createPriceDiv('salesPriceWithDiscount', '', $item->prices, FALSE, FALSE, 1.0, TRUE);
                        }
                } else {
                        $prices = '';
                }
                
                $url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $item->virtuemart_product_id . '&virtuemart_category_id=' . $item->virtuemart_category_id);
                
                if (!empty($item->images[0])) {
                        $image = $item->images[0]->displayMediaThumb('class="featuredProductImage" border="0"', FALSE);
                        $image = JHTML::_('link', $url, $image, array('title' => $item->product_name));
                } else {
                        $image = '';
                }
                
                $name = JHTML::_('link', $url, $item->product_name, array('title' => $item->product_name));
                
                if ((bool) $params->get('show_addtocart', 1)) {
                        $cart = mod_virtuemart_product::addtocart($item);
                } else {
                        $cart = '';
                }
                
                $short_description = $item->product_s_desc;
                $description = $item->product_desc;
                
                ob_start();
                require $tmpl;
                $result = ob_get_contents();
                ob_end_clean();

                return $result;
        }

        public function getList($params) {
                JLoader::register('mod_virtuemart_product', JPATH_SITE . '/modules/mod_virtuemart_product/helper.php');

                if (!class_exists('VmConfig')) require JPATH_ROOT . '/administrator/components/com_virtuemart/helpers/config.php';

                $max_items = $params->get('max_items', 2);
                $layout = $params->get('layout', 'default');
                $category_id = $params->get('virtuemart_category_id', null);
                $filter_category = (bool) $params->get('filter_category', 0);
                $show_price = (bool) $params->get('show_price', 1);
                $Product_group = $params->get('product_group', 'featured');

                if ($filter_category) $filter_category = TRUE;

                $productModel = VmModel::getModel('Product');
                $products = $productModel->getProductListing($Product_group, $max_items, $show_price, true, false, $filter_category, $category_id);
                $productModel->addImages($products);

                return $products;
        }

}
