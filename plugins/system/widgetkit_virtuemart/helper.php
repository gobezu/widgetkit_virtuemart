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
                                $item, 'system/widgetkit_virtuemart', 'plugin', 'plg_widgetkit_virtuemart', 'layouts', dirname(__FILE__) . '/layouts/item.php'
                );

                $product = $item;

                ob_start();
                require $tmpl;
                $result = ob_get_contents();
                ob_end_clean();

                return $result;
        }

        public function getList($params) {
                JLoader::register('mod_virtuemart_product', JPATH_SITE . '/modules/mod_virtuemart_product/helper.php');

                if (!class_exists('VmConfig'))
                        require(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_virtuemart' . DS . 'helpers' . DS . 'config.php');

// Setting
                $max_items = $params->get('max_items', 2); //maximum number of items to display
                $layout = $params->get('layout', 'default');
                $category_id = $params->get('virtuemart_category_id', null); // Display products from this category only
                $filter_category = (bool) $params->get('filter_category', 0); // Filter the category
                $display_style = $params->get('display_style', "div"); // Display Style
                $products_per_row = $params->get('products_per_row', 4); // Display X products per Row
                $show_price = (bool) $params->get('show_price', 1); // Display the Product Price?
                $show_addtocart = (bool) $params->get('show_addtocart', 1); // Display the "Add-to-Cart" Link?
                $headerText = $params->get('headerText', ''); // Display a Header Text
                $footerText = $params->get('footerText', ''); // Display a footerText
                $Product_group = $params->get('product_group', 'featured'); // Display a footerText

                $mainframe = Jfactory::getApplication();
                $virtuemart_currency_id = $mainframe->getUserStateFromRequest("virtuemart_currency_id", 'virtuemart_currency_id', JRequest::getInt('virtuemart_currency_id', 0));


                $key = 'products' . $category_id . '.' . $max_items . '.' . $filter_category . '.' . $display_style . '.' . $products_per_row . '.' . $show_price . '.' . $show_addtocart . '.' . $Product_group . '.' . $virtuemart_currency_id;

                $vendorId = JRequest::getInt('vendorid', 1);

                if ($filter_category)
                        $filter_category = TRUE;

                $productModel = VmModel::getModel('Product');

                $products = $productModel->getProductListing($Product_group, $max_items, $show_price, true, false, $filter_category, $category_id);
                $productModel->addImages($products);

                $totalProd = count($products);
                
                if (empty($products))
                        return false;


                return $products;
        }

}
