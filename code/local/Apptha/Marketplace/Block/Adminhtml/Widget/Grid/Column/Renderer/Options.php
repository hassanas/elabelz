<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Grid column widget for rendering grid cells that contains mapped values
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Apptha_Marketplace_Block_Adminhtml_Widget_Grid_Column_Renderer_Options
   extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Select
{
    /**
     * Render a grid cell as options
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $name = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
        $value = $row->getData($this->getColumn()->getIndex());
        $commission = Mage::getModel('marketplace/commission')->getCollection()
                     ->addFieldToFilter('id',$value )
                     ->addFieldToFilter('is_buyer_confirmation','Yes');
        $html = "<ul class='supplier-status'>";
        foreach($commission as $com):
            $value = $com->getIsSellerConfirmation();
            if($value == "Rejected"):
                $htmlCsv .= "Out of Stock,";
            elseif($value == "Yes"):
                $htmlCsv .= "Confirmed,";
            elseif($value == "No"):
                $htmlCsv .= "Need To Contact,";
            endif;
            $marketplace_id = $com->getId();
            $order_id = $com->getOrderId();
            $product_id = $com->getProductId();
            $increment_id = $com->getIncrementId(); 
            $id = "item_status_change_".$increment_id."_".$product_id;
            $html .= "<li><div class=".$id."_div>";
            if($value == "Rejected"):
               $html .="<p>Out Of Stock</p>"; 
            else:
            $html .= '<select onchange="updateItemStatus('.$id.','.$marketplace_id.','.$order_id.')" id="'.$id.'" name="' . $this->escapeHtml($name) . '" ' . $this->getColumn()->getValidateClass() . '>';
            foreach ($this->getColumn()->getOptions() as $val => $label){
            $selected = ( ($val == $value && (!is_null($value))) ? ' selected="selected"' : '' );
            $html .= '<option value="' . $this->escapeHtml($val) . '"' . $selected . '>';
            $html .= $this->escapeHtml($label) . '</option>';
           }
           $html.='</select>';
           endif;
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
