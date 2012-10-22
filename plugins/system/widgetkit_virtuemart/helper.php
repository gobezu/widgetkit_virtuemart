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

        private static function getWidget($productId, $type, $onlyRetrieve = false) {
                $name = 'wkvm_auto_'.$productId;
                $db = JFactory::getDbo();
                
                $db->setQuery('SELECT id, name, content FROM #__widgetkit_widget WHERE name = '.$db->quote($name));
                
                $rec = $db->loadObject();
                
                if ($onlyRetrieve) return $rec;
                
                if ($rec) {
                        $rec->content = json_decode($rec->content);
                        $rec->settings = $rec->content->settings;
                } else {
                        static $map = array(
                                'gallery' => array('style'=>'default','width'=>'auto','height'=>'auto','autoplay'=>1,'order'=>'default','interval'=>5000,'duration'=>500,'index'=>0,'navigation'=>1,'buttons'=>1,'slices'=>20,'animated'=>'randomSimple','caption_animation_duration'=>500,'lightbox'=>0),
                                'slideshow' => array('style'=>'default','width'=>'auto','height'=>'auto','autoplay'=>1,'order'=>'default','interval'=>5000,'duration'=>500,'index'=>0,'navigation'=>1,'buttons'=>1,'slices'=>20,'animated'=>'randomSimple','caption_animation_duration'=>500),
                                'slideset' => array('style'=>'default','width'=>'auto','height'=>'auto','autoplay'=>1,'interval'=>5000,'index'=>0,'navigation'=>1,'buttons'=>1,'title'=>1,'duration'=>300,'items_per_set'=>3,'effect'=>'slide'),
                                'accordion' => array('style'=>'default','width'=>'auto','order'=>'default','duration'=>500,'index'=>0,'collapseall'=>1,'matchheight'=>1)
                        );

                        $defaultSettings = $map[$type];
                        $settings = array();
                        $isWidthSet = false;
                        
                        foreach ($defaultSettings as $key => $defaultSetting) {
                                if (!isset($settings[$key]) || empty($settings[$key]) && $settings[$key] !== '0' && $settings[$key] !== 0) {
                                        $settings[$key] = $defaultSetting;
                                }
                                
                                if ($key == 'width' && !empty($settings[$key])) $isWidthSet = true;
                        }
                                                
                        $rec = new stdClass();
                        $rec->id = '';
                        $rec->content = '';
                        $rec->settings = $settings;
                        $rec->name = $name;
                }
                
                return $rec;
        } 
        
        private static function save($product, $params) {
                $type = $params->get('widget_type', 'gallery');
                $widget = self::getWidget($product->virtuemart_product_id, $type);
                
                if (!empty($widget) && !empty($widget->id) && !(bool) $params->get('keep_synch', 0)) return $widget->id;
                
                $widgetkit = Widgetkit::getInstance();
                $wh = $widgetkit->getHelper('widget');
                
                $style = $params->get('widget_style_'.$type, 'default');
                $images = $product->images;
                
                if (empty($images)) {
                        if (!empty($widget) && !empty($widget->id)) $wh->delete($widget->id);
                        
                        return true;
                }
                
                $captionPart = $params->get('caption_part', '');
                
                if ($type == 'gallery') {
                        $paths = array();
                        $captions = array();
                        $links = array();

                        foreach ($images as $image) {
                                $file = preg_replace('/^(\/|)images/', '', $image->file_url);
                                $path = preg_replace('/^(\/|)images/', '', $image->file_url_folder);
                                if (!in_array($path, $paths)) $paths[] = $path;
                                $captions[$file] = $captionPart ? $image->$captionPart : '';
                                $links[$file] = '';
                        }
                        
                        $data = array(
                                'type' => $type, 
                                'id' => $widget->id,
                                'name' => $widget->name, 
                                'settings' => $widget->settings,
                                'style' => $style,
                                'captions' => $captions,
                                'links' => $links,
                                'paths' => $paths
                        );                        
                } else if ($type == 'slideshow') {
                        $items = array();
                        $titlePart = $params->get('title_part');
                        $contentPart = $params->get('content_part', '');
                        $contentPartPosition = $params->get('content_part_position', '');
                        $url = JURI::base();
                        
                        foreach ($images as $image) {
                                $id = uniqid();
                                $title = $titlePart ? $image->$titlePart : '';
                                $caption = $captionPart ? $image->$captionPart : '';
                                $alt = $image->file_meta;
                                
                                if (!$alt) $alt = $caption ? $caption : $title;
                                
                                $content = JHtml::image($url.$image->file_url, $alt);
                                
                                if ($contentPart) {
                                        if ($contentPartPosition == 'before') {
                                                $content = $image->$contentPart . $content;
                                        } else {
                                                $content = $content . $image->$contentPart;
                                        }
                                }
                                
                                $items[$id] = array('title'=>$title, 'content'=>$content, 'caption'=>$caption);
                        }
                        
                        $data = array(
                                'type' => $type, 
                                'id' => $widget->id,
                                'name' => $widget->name, 
                                'settings' => $widget->settings,
                                'style' => $style,
                                'items' => $items
                        );                        
                }
                
                $data['settings']['style'] = $style;
                $source = $params->get('thumb_size_source', 'custom');
                
                if ($source == 'custom') {
                        $data['settings']['thumb_width'] = $params->get('thumb_width', 100);
                        $data['settings']['thumb_height'] = $params->get('thumb_height', 100);
                } else if ($source == 'vm') {
                        if (!class_exists('VmConfig')) {
                                require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';
                        }
                        
                        VmConfig::loadConfig();
                        
                        $data['settings']['thumb_width'] = VmConfig::get('img_width', $params->get('thumb_width', 100));
                        $data['settings']['thumb_height'] = VmConfig::get('img_height', $params->get('thumb_height', 100));
                }
                
                return $wh->save($data);
        }
        
        public static function delete($productId) {
                $params = self::isInstalled();
                
                if (!$params) return false;
                
                $widget = self::getWidget($productId, null, true);
                
                if (!$widget) return;
                
                $widgetkit = Widgetkit::getInstance();
                $wh = $widgetkit->getHelper('widget');
                
                return $wh->delete($widget->id);                
        }        
        
        public static function render($product, $params) {
                if (!self::isInstalled()) return '';
                
                $widgetId = self::save($product, $params);
                $widgetkit = Widgetkit::getInstance();
                $wh = $widgetkit->getHelper('widget');
                
                $out = $wh->render($widgetId);
                
                return $out;
        }
        
        private static function isInstalled() {
                jimport('joomla.filesystem.file');
                
                if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/classes/widgetkit.php')
				|| !JComponentHelper::getComponent('com_widgetkit', true)->enabled) {
                        trigger_error('<b>Widgetkit Virtuemart plugin</b>: Widgetkit is not installed.');
                        return;
                }
                
                require_once JPATH_ADMINISTRATOR.'/components/com_widgetkit/widgetkit.php';
                
                return true;
                
//                jimport('joomla.plugin');
//                
//                $plg = JPluginHelper::getPlugin('system', 'widgetkit_virtuemart');
//                
//                if (!$plg) {
//                        trigger_error('<b>Widgetkit Virtuemart plugin</b>: Widgetkit Virtuemart is either not installed properly or disabled.');
//                        return;
//                }
//                
//                $params = new JRegistry($plg->params);
//                
//                return $params;
        }
}
