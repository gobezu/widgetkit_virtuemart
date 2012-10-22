Pulls together Virtuemart and Widgetkit and provides the following functionalities:
- displaying Virtuemart products with Widgetkit anywhere on your site and
- product detail images replaced with Widgetkit gallery or slideshow, either by using the `[wkvm]` plugin code in your product description or through the provided field `$this->product->wkvm` in your layout (sample layout is provided in the samples folder which you can copy to your `SITEROOT/templates/YOURTEMPLATE/html/com_virtuemart/productdetails/default_images.php`.

You don't need any additional knowledge to get this going:
- install and activate the plugin located in the system folder
- go to the Widgetkit component and click on "Use Virtuemart"
- you will be presented with the already familiar options of:
    - which products to select from Virtuemart
    - how to display the selected products in Widgetkit

Layout overrides
----------------
- there is a default layout template provided in `layouts/item.php`. You can customize that layout to your liking. Best practice for such customization is through override. You can copy that file to your `templates/YOURTEMPLATE/html/plg_widgetkit_virtuemart` folder (you probably need to create that folder yourself).
- the products layout can vary based on product, product category or simply generic one that applies to all by applying the naming conventions below:
    - `i<virtuemart product id>.php` - product specific layout
    - `c<virtuemart category id>.php` - category specific layout
    - `item.php` - generic layout

Requirements
------------
Virtuemart products module (included in your Virtuemart installation) must be installed but not necessarily activated

Related links
-------------
- JED entry of this extension, please make sure to rate if you use this extension(TBA)
- [Virtuemart](http://Virtuemart.net)
- [Widgetkit](http://www.yootheme.com/widgetkit)