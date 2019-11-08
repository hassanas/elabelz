<?php
$secret = "a1b2c3e4@451";
if($_GET['secret'] != $secret) { 
    die("You cannot access the page");
}
include 'app/Mage.php';
Mage::app();
$setup = Mage::getResourceModel('catalog/setup','catalog_setup');
$setup->removeAttribute('catalog_product','seo_category');
$setup->removeAttribute('catalog_category','seo_category');

$setup->removeAttribute('catalog_product','seo_canonical_store_id');
$setup->removeAttribute('catalog_category','seo_canonical_store_id');

$setup->removeAttribute('catalog_product','seo_meta_robots');
$setup->removeAttribute('catalog_category','seo_meta_robots');

echo "Attrs removed.";
