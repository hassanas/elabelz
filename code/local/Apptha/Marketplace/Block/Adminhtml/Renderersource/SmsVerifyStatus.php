<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_Marketplace
 * @version     0.1.7
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2015 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 */

/**
 * Renderer to display customer details
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_SmsVerifyStatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to display customer details
     * 
     * Return the customer details
     * @return string
     */
    public function render(Varien_Object $row) {

         $status = $row->getData($this->getColumn()->getIndex());

        if($status == 'yes'){

            $image = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/success_msg_icon.gif';

        }else if ($status == 'no'){

            $image = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/cancel_icon.gif';

        }else{

           $image = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/error_msg_icon.gif';

        }

        return "<img src='".$image."'>";
    }
    public function renderExport(Varien_Object $row) {

        $status = $row->getData($this->getColumn()->getIndex());

        if($status == 'yes') {

            $image = 'Yes';

        }else if($status == 'no') {

            $image = 'No';

        }else {

            $image = 'Error';

        }
        
        return $image;
    }
}

