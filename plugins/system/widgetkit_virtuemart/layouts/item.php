<?php 
if (!empty($product->images[0])) {
        $image = $product->images[0]->displayMediaThumb('class="featuredProductImage" border="0"', FALSE);
} else {
        $image = '';
}
echo JHTML::_ ('link', JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product->virtuemart_product_id . '&virtuemart_category_id=' . $product->virtuemart_category_id), $image, array('title' => $product->product_name));
echo '<div class="clear"></div>';
$url = JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $product->virtuemart_product_id . '&virtuemart_category_id=' .
        $product->virtuemart_category_id); ?>
<a href="<?php echo $url ?>"><?php echo $product->product_name ?></a>        <?php    echo '<div class="clear"></div>';
// $product->prices is not set when show_prices in config is unchecked
if ($show_price and  isset($product->prices)) {
        echo '<div class="product-price">'.$currency->createPriceDiv ('salesPrice', '', $product->prices, FALSE, FALSE, 1.0, TRUE);
        if ($product->prices['salesPriceWithDiscount'] > 0) {
                echo $currency->createPriceDiv ('salesPriceWithDiscount', '', $product->prices, FALSE, FALSE, 1.0, TRUE);
        }
        echo '</div>';
}