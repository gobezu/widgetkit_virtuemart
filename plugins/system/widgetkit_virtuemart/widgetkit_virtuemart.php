<?php
//$Copyright$

/** ORIGINAL copyright adapted from corresponding Joomla! plugin
* @package   Widgetkit
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

class plgSystemWidgetkit_Virtuemart extends JPlugin {
	public $widgetkit;
        
        public function onContentPrepare($context, &$product, &$params, $limitstart) {
                if (!$product || !isset($product->virtuemart_product_id) || !(bool) $this->params->get('product_detail', 0)) {
                        $product->text = str_replace('[wkvm]', '', $product->text);
                        return '';
                }
                
                $product_model = VmModel::getModel('product');
                $product_model->addImages($product);
                
                $wkvm = WidgetkitVirtuemartWidgetkitHelper::render($product, $this->params);
                $product->wkvm = $wkvm;
                $product->text = str_replace('[wkvm]', $wkvm, $product->text);
                
                return '';                
        }
        
        public function onContentPrepareForm($form, $data) {
                if ($data->element != 'widgetkit_virtuemart') return;
                
                JFactory::getLanguage()->load('plg_system_widgetkit_virtuemart', JPATH_ADMINISTRATOR);                
                
                $type = isset($data->params['widget_type']) ? $data->params['widget_type'] : 'slideshow';
                
                jimport('joomla.filesystem.file');
                $xml = JPATH_SITE.'/media/widgetkit/widgets/'.$type.'/'.$type.'.xml';
                $xml = JFile::read($xml);
                $xml = trim($xml);

                $xml = str_replace(
                        array('<widget>', '<settings>', '</widget>', '</settings>', 'setting'), 
                        array('<form><fields name="params">', '<fieldset name="widgetstyle"><field name="widget_style" type="folderlist" default="default" hide_none="true" label="Widget style" directory="media/widgetkit/widgets/'.$type.'/styles" filter="" exclude="" /><field type="spacer" name="generalsettingsseparator" label="General widget settings" />', '</fields></form>', '<field type="spacer" name="specificsettingsseparator" label="Widget style specific settings" /></fieldset>', 'field'), 
                        $xml
                );
                
                // remove the style setting as the type is incompatible
                // ofc we could define a field type but we really don't need it 
                // as we already know the style
                $xml = preg_replace('#<[^\>]+type=[\"\']style[\"\'][^\>]+>#', '', $xml);
                $style = isset($data->params['widget_style']) ? $data->params['widget_style'] : 'default';
                $sxml = simplexml_load_file(JPATH_SITE.'/media/widgetkit/widgets/'.$type.'/styles/'.$style.'/config.xml');

                foreach ($sxml->xpath('///setting') as $field) {
                        $field = str_replace('setting', 'field', $field->asXML());
                        $xml = str_replace('</fieldset>', $field.'</fieldset>', $xml);
                }
                
                $form->load($xml);
        }
        
        public function plgVmOnDeleteProduct($product, $ok) {
                WidgetkitVirtuemartWidgetkitHelper::delete($product);
        }
        
        private static $SESSIONID = 'WKVM_SESSION';
        
	public function onAfterInitialise() {
		jimport('joomla.filesystem.file');
                
		if (!JFile::exists(JPATH_ADMINISTRATOR.'/components/com_widgetkit/classes/widgetkit.php')
				|| !JComponentHelper::getComponent('com_widgetkit', true)->enabled) {
			return;
		}
                
		require_once JPATH_ADMINISTRATOR.'/components/com_widgetkit/classes/widgetkit.php';

		$this->widgetkit = Widgetkit::getInstance();
                
		$path = JPATH_ROOT.'/plugins/system/widgetkit_virtuemart/';
		$this->widgetkit['path']->register($path, 'widgetkit_virtuemart.root');
		$this->widgetkit['path']->register($path.'widgets', 'widgetkit_virtuemart.widgets');
		$this->widgetkit['path']->register($path.'assets', 'widgetkit_virtuemart.assets');

		require_once $path.'helper.php';
                
		$this->widgetkit['event']->bind('admin', array($this, 'init'));
		$this->widgetkit['event']->bind('site', array($this, 'init'));
		$this->widgetkit['event']->bind('site', array($this, 'loadAssets'));
		$this->widgetkit['event']->bind('widgetoutput', array($this, '_applycontentplugins'));
                
                $input = JFactory::getApplication()->input;
                $option = $input->get('option', '', 'cmd');
                $task = $input->get('task', '', 'cmd');
                
                if ($option == 'com_virtuemart') {
                        $session = JFactory::getSession();
                        
                        if ($task == 'save' || $task == 'apply') {
                                $id = $input->get('virtuemart_product_id', array(), 'array');
                                $id = $id[key($id)];
                                $session->set(self::$SESSIONID, $id);
                        } else {
                                $id = $session->get(self::$SESSIONID);
                                
                                if ($id) {
                                        WidgetkitVirtuemartWidgetkitHelper::delete($id);
                                        $session->clear(self::$SESSIONID);
                                        JFactory::getApplication()->enqueueMessage(JText::_('Widgetkit gallery of product reset'), 'info');
                                }
                        }
                }
	}
        
        public function onExtensionAfterSave($context, $data, $isNew) {
                if ($data->element != 'widgetkit_virtuemart' || !(bool) $this->params->get('keep_synch', 1)) return;
                
                WidgetkitVirtuemartWidgetkitHelper::delete();
        }

	public function init() {
		foreach ($this->widgetkit['path']->dirs('widgetkit_virtuemart.widgets:') as $widget) {
			if ($file = $this->widgetkit['path']->path("widgetkit_virtuemart.widgets:{$widget}/{$widget}.php")) {
				require_once $file;
			}
		}
	}

	public function loadAssets() {
		$this->widgetkit['asset']->addFile('css', 'widgetkit_virtuemart.assets:css/style.css');
	}

	public function _applycontentplugins(&$text) {

		// import joomla content plugins
		JPluginHelper::importPlugin('content');

		$registry      = new JRegistry('');
		$dispatcher    = JDispatcher::getInstance();
		$article       = JTable::getInstance('content');
		$article->text = $text;

		$dispatcher->trigger('onPrepareContent', array(&$article, &$registry, 0));
		$dispatcher->trigger('onContentPrepare', array('com_widgetkit', &$article, &$registry, 0));

		$text = $article->text;
        }
}

class VirtuemartWidget {
	public $widgetkit;
	public $type;
	public $options;
        
	public function __construct() {
		$this->widgetkit = Widgetkit::getInstance();
		$this->type = strtolower(str_replace('Virtuemart', '', get_class($this)));
		$this->options = $this->widgetkit['system']->options;
                
		$this->widgetkit['event']->bind('dashboard', array($this, 'dashboard'));
		$this->widgetkit['event']->bind("render", array($this, 'render'));
		$this->widgetkit['event']->bind("task:edit_{$this->type}_virtuemart", array($this, 'edit'));
		$this->widgetkit['event']->bind("task:save_{$this->type}_virtuemart", array($this, 'save'));
                $this->widgetkit['path']->register($this->widgetkit['path']->path('widgetkit_virtuemart.widgets:'.$this->type), "virtuemart{$this->type}");
 	}

	public function dashboard() {
                $this->widgetkit['asset']->addFile('js', 'widgetkit_virtuemart.assets:js/dashboard.js');
		$widget_ids = array();
                
		foreach ($this->widgetkit['widget']->all($this->type) as $widget) {
			if (isset($widget->virtuemart)) {
				$widget_ids[] = $widget->id;
			}
		}

		$this->widgetkit['asset']->addString('js', 'jQuery(function($) { $(\'div.dashboard #'.$this->type.'\').VirtuemartDashboard({edit_ids : '.json_encode($widget_ids).'}); });');
	}
        
	public function edit($id = null) {
                $xml = simplexml_load_file($this->widgetkit['path']->path("{$this->type}:{$this->type}.xml"));
                $type = $this->type;
		$widget = $this->widgetkit[$this->type]->get($id ? $id : $this->widgetkit['request']->get('id', 'int'));
                $this->widgetkit['path']->register($this->widgetkit['path']->path('widgetkit_virtuemart.root:layouts'), 'layouts');
                
                $style = isset($widget->settings['style']) ? $widget->settings['style'] : '';
                
                if (empty($style)) $style = 'default';
                
		$style_xml = simplexml_load_file($this->widgetkit['path']->path("{$this->type}:styles/{$style}/config.xml"));
                
                // reuse of module settings
                $module = 'mod_virtuemart_product';

                $lang = JFactory::getLanguage();
                $lang->load('com_modules');
                $lang->load($module, JPATH_SITE);

                $grp = 'params';

                $exclFlds = array(
                        'mod_virtuemart_product'=>array('moduleclass_sfx', 'layout', 'products_per_row', 'display_style', 'headerText', 'footerText')
                );

                $inclFldSets = array(
                        'mod_virtuemart_product'=>array('basic')
                );

                $exclFlds = (array) $exclFlds[$module];
                $inclFldSets = (array) $inclFldSets[$module];
                $frm = JFile::read(JPATH_SITE.'/modules/'.$module.'/'.$module.'.xml');
                $frm = preg_replace(
                        '#</fieldset>#', 
                        '<field name="caption_part" type="list" default="" label="Caption part"><option value="">No caption</option><option value="product_s_desc">Short description</option><option value="product_desc">Product description</option><option value="name">Name</option></field></fieldset>', 
                        $frm, 
                        1
                );
                $frm = JForm::getInstance('virtuemartwidget', $frm, array(), true, '//config');
                $fss = $frm->getFieldsets();
                $modHTML = array(JHtml::_('sliders.start', 'module-sliders'));
                $addedModule = false;

                foreach ($fss as $fsName => $fs) {
                        if (!in_array($fsName, $inclFldSets)) continue;

                        $label = JText::_(!empty($fs->label) ? $fs->label : 'COM_MODULES_'.$fsName.'_FIELDSET_LABEL');
                        $modHTML[] = JHtml::_('sliders.panel', $label, $fsName.'-options');

                        if (isset($fs->description) && trim($fs->description)) {
                                $modHTML[] = '<p class="tip">'.htmlspecialchars(JText::_($fs->description), ENT_COMPAT, 'UTF-8').'</p>';
                        }

                        $flds = $frm->getFieldset($fsName);

                        $modHTML[] = '<fieldset class="panelform">';
                        $hiddenFlds = array();

                        foreach ($flds as $fldName => $fld) {
                                $fldName = str_replace($grp.'_', '', $fldName);

                                if (in_array($fldName, $exclFlds)) continue;

                                $value = isset($widget->virtuemart[$fldName]) ? $widget->virtuemart[$fldName] : null;
                                $input = $frm->getInput($fldName, $grp, $value);

                                if (!$fld->hidden) {
                                        $modHTML[] = $fld->label.$input;
                                } else {
                                        $hiddenFlds[] = $input;
                                }
                        }
                        
                        if (!$addedModule) {
                                $hiddenFlds[] = '<input type="hidden" name="params[module]" value="'.$module.'" />';
                                $addedModule = true;
                        }

                        if (!empty($hiddenFlds)) $modHTML[] = implode('', $hiddenFlds);

                        $modHTML[] = '</fieldset>';
                }

                $modHTML[] = JHtml::_('sliders.end');
                $modHTML = implode('', $modHTML);
                
		echo $this->widgetkit['template']->render("edit", compact('widget', 'xml', 'style_xml', 'type', 'modHTML'));
	}

	public function render($widget) {
                if (isset($widget->virtuemart) && $widget->type == $this->type) {
                        $widget->items = array();
                        $params = $this->widgetkit['data']->create($widget->virtuemart);
                        $items = $this->widgetkit['widgetkitvirtuemart']->getList($params);
                        $widgetItems = self::renderItems($items, $params, $this->widgetkit);
                        $widget->items = $widgetItems;         
                }
	}
        
        static protected function renderItems($items, $params, $widgetKit) {
                $i = 0;
                $widgetItems = array();
                foreach($items as $i => $item) {
                        $widgetItems[$i]['title'] = $item->product_name;
                        $widgetItems[$i]['content'] = $widgetKit['widgetkitvirtuemart']->renderItem($item, $params);
                        $widgetItems[$i]['navigation'] = $item->product_name;
                        $widgetItems[$i]['caption'] = '';
                        $part = $params->get('caption_part', '');
                        $widgetItems[$i]['caption'] = empty($part) ? '' : $item->$part;
                }
                return $widgetItems;
        }

	public function save() {
		// save data
		$data['type']     = $this->type;
		$data['id']       = $this->widgetkit['request']->get('id', 'int');
		$data['name']     = $this->widgetkit['request']->get('name', 'string');
		$data['settings'] = $this->widgetkit['request']->get('settings', 'array');
                $data['partsettings'] = $this->widgetkit['request']->get('partsettings', 'array');
		$data['style']    = $this->widgetkit['request']->get('settings.style', 'array');
		$data['virtuemart']	  = $this->widgetkit['request']->get('params', 'array');

		// convert numeric strings to real integers
		if (isset($data["settings"]) && is_array($data["settings"])) {
			$data["settings"] = array_map(create_function('$item', 'return is_numeric($item) ? (float)$item : $item;'), $data["settings"]);
		}
                
		$this->edit($this->widgetkit['widget']->save($data));
	}

}
