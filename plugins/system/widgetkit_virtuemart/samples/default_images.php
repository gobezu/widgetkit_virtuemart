<?php
//$Copyright$

defined('_JEXEC') or die('Restricted access');

if (!empty($this->product->images) and count ($this->product->images) > 0) {
        echo $this->product->wkvm;
}