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
 * Renderer to get the order date
 */
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Sellerstatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Function to render order date 
     * 
     * Return the date
     * @return date
     */
    public function render(Varien_Object $row) {
        $name = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
        $value = $row->getData($this->getColumn()->getIndex());
        $commission = Mage::getModel('marketplace/commission')->getCollection()
                     ->addFieldToFilter('id',$value );
        $html = "<ul class='supplier-status'>";
        foreach($commission as $com): 
            $value = $com->getSellerStatus();
            $entity_id = $com->getId();
            if($value == 0):
               $seller_status = "Not Picked From Seller";
               $htmlCsv = "Not Picked From Seller";
            elseif($value == 1):
               $seller_status = "Picked From Seller";
               $htmlCsv = "Picked From Seller";
            endif;

            $increment_id = $com->getIncrementId(); 
            $id = "seller_status_change_".$value;
            $html .= "<li><div class=".$value."_div>";
            $html .= '<select onchange="updateSellerStatus('.$id.','.$entity_id.')" id="'.$id.'" name="' . $this->escapeHtml($name) . '" ' . $this->getColumn()->getValidateClass() . '>';
            
            foreach ($this->getColumn()->getOptions() as $val => $label){
                $selected = ( ($val == $value && (!is_null($value))) ? ' selected="selected"' : '' );
                $html .= '<option value="' . $this->escapeHtml($val) . '"' . $selected . '>';
                $html .= $this->escapeHtml($label) . '</option>';
               }
               $html.='</select>';
               $html .="</div></li>";
            endforeach;

            $html .= "</ul>";
        
            if (strpos(Mage::app()->getRequest()->getRequestString(), '/exportCsv/')) {
                return $htmlCsv;
            }
            elseif (strpos(Mage::app()->getRequest()->getRequestString(), '/exportExcel/')) {
                return $htmlCsv;
            }
            else{ 
            return $html;
            }
        } 
}