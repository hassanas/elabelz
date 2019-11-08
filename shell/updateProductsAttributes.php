<?php
require_once 'abstract.php';

/**
 * Its one time script whih will update products Attribues values
 * Created by Hassan Ali Shahzad
 * Date: 02/05/2018
 * Time: 22:59
 *
 */
class Progos_Shell_UpdateProductsAttributes extends Mage_Shell_Abstract
{
    public function run()
    {
        $filePath = Mage::getBaseDir() . "/media/var/feedupdateattribute/" . "attributes-masterfile.csv";
        $importSkuArray = array_map('str_getcsv', file($filePath));
        if (empty($importSkuArray)) {
            echo "Unable to open remote file.\n";
            exit;
        } else {
            $elabelzSkus = array();
            $attributes = array();
            foreach ($importSkuArray as $key=>$data) {
                if($key == 0){
                    $attributes = ($data);
                    unset($importSkuArray[0]);
                    continue;
                }
                $elabelzSkus[] = trim($data[0]);
                unset($importSkuArray[$key]);
                $importSkuArray[$data[0]] = $data;
            }
            $elabelzSkus = array_unique($elabelzSkus);
            $collection = Mage::getSingleton('catalog/product')
                            ->getCollection()
                            ->addAttributeToSelect(array('entity_id','sku','personal_style','occasion','pattern','color','fabric','fit_for_tops','fit_for_bottoms','neckline','dress_style','dress_length','skirt_style','skirt_length','top_length','sleeve_length','jewelry_tone','jewelry_style','shorts_rise','shorts_length','jeans_cut','jeans_rise','jeans_style','maternity_nursing','maternity_trousers_type','heel_height','actual_material','other_comments'))
                            ->addAttributeToFilter('sku', array('in', $elabelzSkus));
            $message = '<html>
                            <head>
                              <title>Attributes Update</title>
                            </head>
                            <body>
                              <p>Dear Concerns!</p>
                              <p>Following Products Attributes has been updated:</p>';

            $message .= '<table border="1">';
            $message .= '<tr><td>Sr#</td><td>Sku</td><tr>';
            $conut = 1;

            foreach ($collection as $product) {

                if(array_key_exists($product->getSku(),$importSkuArray)){
                    $combine = array_combine($attributes, $importSkuArray[$product->getSku()]);
                    foreach ($combine as $attrId=>$attrLabel){
                        if($attrId == 'sku' || $attrLabel == '') continue;
                        $attr = $product->getResource()->getAttribute($attrId);
                        if (is_object($attr) && $attr->usesSource()) {
                            $newAttrId = $attr->getSource()->getOptionId($attrLabel);
                            $product->setData($attrId, $newAttrId);
                        }
                    }
                    try {
                        $product->save();
                        $message .= '<tr>';
                        $message .= '<td>' . $conut++ . '</td>';
                        $message .= '<td><a target="_blank" rel="external" href="' . str_replace("updateProductsAttributes.php/", "", Mage::helper("adminhtml")->getUrl("adminhtml/catalog_product/edit", array("id" => $product->getId()))) . '">' . $product->getSku() . '</a></td>';
                        $message .= '</tr>';
                    } catch (Exception $e) {
                        Mage::log($e->getMessage(), null, 'update_product_attributes.log');
                    }
                }
            }
            $message .= '</table>';
            $message .= '</body></html>';

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            try {
                mail('hassan.ali@progos.org,hassan.khan@progos.org,azhar.farooq@progos.org', 'Product Attribute Update!', $message, $headers);
            } catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }
    }
}

$updateProductsAttributes = new Progos_Shell_UpdateProductsAttributes();
$updateProductsAttributes->run();
echo "Process Finished\n";