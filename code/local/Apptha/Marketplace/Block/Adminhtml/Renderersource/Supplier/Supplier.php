<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Supplier_Supplier extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $customerId = $row->getData($this->getColumn()->getIndex());
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $cId = Mage::helper("adminhtml")->getUrl('*/adminhtml_sellerreview/edit', array('id' => $value));
        $seller = Mage::getModel ( 'marketplace/sellerprofile' )->collectprofile ( $customer->getId() );
        if ($customer->getId()) {
            $store_title = !empty($seller->getStoreTitle())?$seller->getStoreTitle():"Untitled";
            $additional_details = "<strong>Email:&nbsp;</strong>" . $customer->getEmail() . "<br>";
            $additional_details .= "<strong>Name:&nbsp;</strong>" . $customer->getName() . "<br>";
            $additional_details .= "<strong>Contact:&nbsp;</strong>" . $seller->getContact() . "<br>";
            $additional_details .= "<strong>Location:&nbsp;</strong>" . $seller->getState() . ", " . Mage::app()->getLocale()->getCountryTranslation($seller->getCountry()) . "<br><br>";
            return "<a title='Click to Edit' target='_blank' href='" . $cId . "'>" . $store_title . "</a><br>$additional_details";
        } else {
            return "N/A";
        }
    }

}

