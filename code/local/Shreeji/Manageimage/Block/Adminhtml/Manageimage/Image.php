<?php
class Shreeji_Manageimage_Block_Adminhtml_Manageimage_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function _getValue(Varien_Object $row)
    {
        $getimagename = parent::_getValue($row);
        if(!empty($getimagename)):
        $mediapath = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);;
        return "<img src='". $mediapath ."catalog/product/$getimagename' alt='{$getimagename}' title='{$getimagename}' width='100'  />";
        endif;
    }
}