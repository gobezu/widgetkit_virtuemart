<?php 
//$Copyright$

defined('_JEXEC') or die('Restricted access');

/* Available displayable parts are:
 * - $image
 * - $name
 * - $prices
 * - $cart
 * - $description
 * - $short_description
 * - $url
 */
?>

<div class="wkvm-product">
        <?php echo $image; ?>
        <div class="clear"></div>
        <?php echo $name; ?>
        <div class="clear"></div>
        <?php echo $prices; ?>
        <div class="clear"></div>
        <?php echo $cart; ?>
</div>