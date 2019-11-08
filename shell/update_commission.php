<?php 
require_once('app/Mage.php');
umask(0);
Mage::app();
// ************************************************************

$items = Mage::getModel('marketplace/commission')->getCollection();
$count = 0;
foreach ($items as $item) {
  $product = Mage::getModel('catalog/product')->load($item->getProductId());
  $sellerprofile = Mage::getModel('marketplace/sellerprofile')->load($item->getSellerId(), "seller_id");
  $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

  $parent_product = Mage::getModel('catalog/product')->load($parentIds[0]);

  $price = $parent_product->getPrice();
  $commission = ((int) $sellerprofile->getCommission() * (int) $price) / 100;

// echo $parent_product->getSpecialPrice();
  if ($parent_product->getSpecialPrice()) {
    $product_amount = $parent_product->getSpecialPrice();
  } else {
    $product_amount = $parent_product->getPrice();
  }

  $item->setProductAmt($product_amount);
  $item->setCommissionFee($commission);
  $seller_amount = $price - $commission;
  $item->setSellerAmount($seller_amount);
  $item->save();
  $count++;
}
?>