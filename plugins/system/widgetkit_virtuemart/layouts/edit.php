<?php
//$Copyright$
// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<div id="widgetkit" class="wrap">
        <?php echo $this['template']->render('title', array('title' => ($widget->id ? 'Edit' : 'Add') . ' ' . ucfirst($type))); ?>

        <form id="form" method="post" action="<?php echo $this['system']->link(array('task' => "save_{$type}_virtuemart")); ?>">
                <div class="sidebar">
                        <div class="box">
                                <h3>Settings</h3>
                                <div class="content">
                                        <?php
                                        $settings = array();

                                        foreach (array($xml, $style_xml) as $x) {
                                                if ($setting = $x->xpath('settings/setting')) {
                                                        $settings = array_merge($settings, $setting);
                                                }
                                        }
                                        
                                        foreach ($settings as $setting) {
                                                $name = (string) $setting->attributes()->name;
                                                $type = (string) $setting->attributes()->type;
                                                $label = (string) $setting->attributes()->label;
                                                $name = (string) $setting->attributes()->name;
                                                $default = (string) $setting->attributes()->default;
                                                $value = isset($widget->settings[$name]) ? $widget->settings[$name] : $default;

                                                echo '<div class="option">';
                                                echo '<h4>' . $label . '</h4>';
                                                echo '<div class="value">';
                                                echo $this['field']->render($type, 'settings[' . $name . ']', $value, $setting);
                                                echo '</div>';
                                                echo '</div>';
                                        }
                                        ?>
                                </div>
                        </div>
                </div>

                <div class="form">
                        <input type="hidden" value="<?php echo $widget->id; ?>" name="id" id="widget_id" />
                        <input type="text" value="<?php echo $widget->name; ?>" name="name" placeholder="Enter name here..." class="name" required />
                        <div class="box">
                                <h3>About...</h3>
                                <div class="content">
                                <?php if (!empty($modID) && is_numeric($modID)) : ?>
                                <p style="color:#fff; background:#c61f29; padding:20px;">
                                        Please don't change the name above which is auto generated.
                                </p>
                                <?php endif; ?>
                                <p>
                                        Each item rendering is based on overridable template located at plugins/system/widget_virtuemart/layouts/item.php. This layout is based on mod_virtuemart_product's where we have fleshed out item specific parts. 
                                        Override templates can be placed at yourtemplatefolder/html/plg_widgetkit_virtuemart. Note: you will most probably need to create the folder yourself.
                                </p>
                                <p>
                                        Templates can be item or category specific or global with the following priority order and naming convention applying:
                                </p>
                                <ul>
                                        <li>item specific layout - file named as i&lt;productidid&gt;.php</li>
                                        <li>category specific layout - file named as c&lt;categoryid&gt;.php</li>
                                        <li>generic layout - file named as item.php</li>
                                </ul>
                                </div>
                        </div>                        
                        <div class="virtuemart box">
                                <h3>Virtuemart product settings</h3>
                                <div class="content">
                                <?php echo $modHTML; ?>
                                </div>
                        </div>
                        <p class="actions">
                                <input type="submit" value="Save changes" class="button-primary action save"/>
                                <span></span>
                        </p>
                </div>
        </form>
</div>