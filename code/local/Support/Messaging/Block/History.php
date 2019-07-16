<?php
class Support_Messaging_Block_History extends Mage_Core_Block_Template {

    protected function _prepareLayout() {
        parent::_prepareLayout ();
        $collection = $this->threads();
        // $collection = Mage::getModel ('messaging/thread')->getCollection();
        $this->setCollection($collection);

        $pager = $this->getLayout()->createBlock('page/html_pager', 'history.pager');
        $pager->setAvailableLimit ( array (
            10 => 10,
            20 => 20,
            30 => 30
        ));
        // $pager->setLimit(10);
        $pager->setCollection($collection);
        $this->setChild ('pager', $pager);
        return $this;
    }
 
    public function threads() {
        $is_seller = Mage::helper("messaging")->isSeller();
        $customer_id = Mage::helper("messaging")->getSession()->getCustomerId();
        $collection = Mage::getModel('messaging/thread')->getCollection();
        if ($is_seller) {
            $collection->addFieldToFilter("for", $customer_id); 
        } else {
            $collection->addFieldToFilter("from", $customer_id); 
        }
        $collection->setOrder("last_activity", "desc");
        return $collection;
    }


 /**
  * Function to get the product details
  *
  * Return product collection
  * 
  * @return array
  */
 public function manageProductss() {
  $multi_submit = $this->getRequest ()->getPost ( 'multi_submit' );
  $entityIds = $this->getRequest ()->getParam ( 'id' );
  $delete = $this->getRequest ()->getPost ( 'multi' );
  /**
   * Check if submit buttom submitted.
   */
  if ($multi_submit) {
   if (count ( $entityIds ) > 0 && $delete == 'delete') {
    foreach ( $entityIds as $entityIdData ) {
     Mage::register ( 'isSecureArea', true );
     Mage::helper ( 'marketplace/marketplace' )->deleteProduct ( $entityIdData );
     Mage::unregister ( 'isSecureArea' );
    }
    Mage::getSingleton ( 'core/session' )->addSuccess ( $this->__ ( "selected Products are Deleted Successfully" ) );
    $url = Mage::getUrl ( 'marketplace/product/manage' );
    Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
   }
   
   if (count ( $entityIds ) == 0 && $delete == 'delete') {
    Mage::getSingleton ( 'core/session' )->addError ( $this->__ ( "Please select a product to delete" ) );
    $url = Mage::getUrl ( 'marketplace/product/manage' );
    Mage::app ()->getFrontController ()->getResponse ()->setRedirect ( $url );
   }
  }
  $filterPrice = $this->getRequest ()->getParam ( 'filter_price' );
  $filterStatus = $this->getRequest ()->getParam ( 'filter_status' );
  $filterId = $this->getRequest ()->getParam ( 'filter_id' );
  $filterName = $this->getRequest ()->getParam ( 'filter_name' );
  $filterQuantity = $this->getRequest ()->getParam ( 'filter_quantity' );
  $filterProductType = $this->getRequest ()->getParam ( 'filter_product_type' );
  $cusId = Mage::getSingleton ( 'customer/session' )->getCustomer ()->getId ();
  $products = Mage::getModel ( 'catalog/product' )->getCollection ();
  $products->addAttributeToSelect ( '*' );
  $products->addAttributeToFilter ( 'seller_id', array (
    'eq' => $cusId 
  ) );  
  
  $products = Mage::helper('marketplace/product')->productFilterByAttribute('name',$filterName,$products);
  $products = Mage::helper('marketplace/product')->productFilterByAttribute('entity_id',$filterId,$products); 
  $products = Mage::helper('marketplace/product')->productFilterByAttribute('price',$filterPrice,$products);
  $products = Mage::helper('marketplace/product')->productFilterByAttribute('status',$filterStatus,$products);

  /**
   * confirming filter product type is not empty
   */
  if (! empty ( $filterProductType )) {
   $products->addAttributeToFilter ( 'type_id', array (
     'eq' => $filterProductType 
   ) );
  }
  /**
   * Check filter quantity is not equal to empty
   */
  if ($filterQuantity != '') {
   $products->joinField ( 'qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left' )->addAttributeToFilter ( 'qty', array (
     'eq' => $filterQuantity 
   ) );
  }
  
  $products->addAttributeToFilter ( 'visibility', array (
    'eq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH 
  ) );
  $products->addAttributeToSort ( 'entity_id', 'DESC' );
  
  return $products;
 }

    public function getPagerHtml() {
        return $this->getChildHtml('pager');
    }
 
 /**
  * Function to get multi select url
  *
  * Return the multi select option url
  * 
  * @return string
  */
 public function getmultiselectUrl() {
  return Mage::getUrl ( 'marketplace/product/manage' );
 }
 /**
  * Function to get multi select url
  *
  * Return the multi select option url
  * 
  * @return string
  */
 public function getBulkUploadUrl() {
  return Mage::getUrl ( 'marketplace/bulkupload/bulkupload' );
 }
}