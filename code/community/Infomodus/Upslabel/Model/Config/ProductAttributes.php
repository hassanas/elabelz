<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 16.12.11
 * Time: 10:55
 * To change this template use File | Settings | File Templates.
 */
class Infomodus_Upslabel_Model_Config_ProductAttributes
{
    public function toOptionArray()
    {
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->addVisibleFilter()->setOrder('main_table.frontend_label', 'ASC');
        $attributeArray = array(array(
            'label' => '',
            'value' => ''
        ));

        foreach($attributes as $attribute){
            $attributeArray[] = array(
                'label' => $attribute->getData('frontend_label'),
                'value' => $attribute->getData('attribute_code')
            );
        }
        return $attributeArray;
    }
}