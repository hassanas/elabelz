<?php
/**
 * Magento
 * application/x-httpd-php Ship.php ( C++ source, ASCII text )
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
class Apptha_Marketplace_Block_Adminhtml_Widget_Grid_Column_Renderer_Ship
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
        $html = "<ul class='shipping-status'>";
        foreach($commission as $com):
            $value = $com->getShipStatus();
            if($value == 1):
                $htmlCsv .= "Pending,";
            elseif($value == 2):
                $htmlCsv .= "Shipped in House,"; 
            elseif($value == 3):
                $htmlCsv .= "Shipped Via Fetchr,";
            elseif($value == 4):
                $htmlCsv .= "Shipped Via Aramex,";
            elseif($value == 0):
                $htmlCsv .= "Nothing Selected,";
            endif;    
            $product_id = $com->getProductId();
            $increment_id = $com->getIncrementId(); 
            $id = "ship_status_change_".$increment_id."_".$product_id;
            $html .= '<li><select onchange="updateShipStatus('.$id.','.$increment_id.','.$product_id.')" id="'.$id.'" name="' . $this->escapeHtml($name) . '" ' . $this->getColumn()->getValidateClass() . '>';
            foreach ($this->getColumn()->getOptions() as $val => $label){
            $selected = ( ($val == $value && (!is_null($value))) ? ' selected="selected"' : '' );
            $html .= '<option value="' . $this->escapeHtml($val) . '"' . $selected . '>';
            $html .= $this->escapeHtml($label) . '</option>';
           }
           $html.='</select></li>';
        endforeach; 
        $html.='</ul>';
        
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