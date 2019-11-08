<?php
/**
 *
 * Daily Run Script at 16:30
 * Created by Hassan Ali Shahzad
 * Date: 27/03/2017
 * Time: 18:47
 *
 */

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'abstract.php';

class UpdateAnotahEleganzaInventory extends Mage_Shell_Abstract
{

    public function run()
    {
        ini_set('memory_limit', '-1');
        $configurations = Mage::helper('vendorqtyupdate/config')->getConfigurations('anotah');
        if (!empty($configurations)) {
            $remoteCsvPath = $configurations['path'];
            $csvDataArray = array_map('str_getcsv', file($remoteCsvPath));
            $storeId = Mage_Core_Model_App::ADMIN_STORE_ID; // Zero for admin/default store
            $title = "ANOTAH & ELEGANZA Qty Update";
            $message = "<html>
                            <head>
                              <title>{$title}</title>
                            </head>
                            <body>
                              <p>Dear Concerns!</p>
                              ";
            if (empty($csvDataArray)) {
                $message .= "<p>Today File not  available/accessible on this location : {$remoteCsvPath} </p>";
                $message .= '<p>Please have a look the file permissions or  pressence on above location.</p>';
            } else {
                $supplierSkus = array();
                $supplierSkusQty = array();
                $first = FALSE;
                foreach ($csvDataArray as $sku) {
                    if ($first != TRUE) {
                        $first = TRUE;
                        continue;
                    }
                    if (empty($sku[0])) continue;
                    $supplierSkus[] = $sku[0];
                    $supplierSkusQty[$sku[0]] = ((integer)$sku[1] > 0) ? (integer)$sku[1] : 0;
                }
                $collection = Mage::getSingleton('catalog/product')
                    ->getCollection()
                    ->addAttributeToSelect(array('entity_id'))
                    ->addAttributeToFilter('type_id', array('eq' => 'simple'))
                    ->addAttributeToFilter('supplier_sku', array('in', $supplierSkus));
                $message .= '<p>Following Products has been updated toady:</p>';
                $message .= '<table border="1">';
                $message .= '<tr><td><b>Elabels Sku</b></td><td><b>Supplyer Sku</b></td><td><b>Old Qty</b></td><td><b>New Qty</b></td><tr>';
                $catalogInventoryModel = Mage::getSingleton('cataloginventory/stock_item');
                $updated = 0;
                foreach ($collection as $product) {
                    $stockItem = $catalogInventoryModel->loadByProduct((integer)$product->getId());
                    $newQty = (integer)$supplierSkusQty[$product->getSupplierSku()];
                    $oldQty = (integer)$stockItem->getQty();
                    //get on hold orders items from magento
                    $onHoldOrderTotal = (integer)Mage::getModel('progos_skuvault/productQtySync_cron')->getOrderStatusSumApi($product->getId());
                    $newQty = $newQty - $onHoldOrderTotal;
                    $newQty = ($newQty>0) ? $newQty : 0;
                    if ($stockItem->getId() > 0 && $oldQty != $newQty) { // only update if qty changed
                        $stockItem->setQty($newQty);
                        $stockItem->setIsInStock(($newQty > 0) ? 1 : 0);
                        $stockItem->save();
                        $message .= '<tr>';
                        $message .= '<td>' . $product->getSku() . '</td>';
                        $message .= '<td>' . $product->getSupplierSku() . '</td>';
                        $message .= '<td>' . $oldQty . '</td>';
                        $message .= '<td>' . $newQty . '</td>';
                        $message .= '</tr>';
                        $updated++;
                        // enable product status
                        if ($oldQty == 0 && $newQty > 0) {
                            Mage::getModel('catalog/product_status')->updateProductStatus((integer)$product->getId(), (integer)$storeId, Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
                        }
                        if ($newQty == 0) {
                            Mage::getModel('catalog/product_status')->updateProductStatus((integer)$product->getId(), (integer)$storeId, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
                        }
                    }
                }
                if ($updated == 0) {
                    $message .= '<tr><td align="center" colspan="4">Non of the Product\'s quantity changed in this build</td></tr>';
                }

                $message .= '</table>';
            }
            $message .= '</body></html>';
            $headers = "MIME-Version: 1.0" . PHP_EOL;
            $headers .= "Content-type:text/html;charset=UTF-8" . PHP_EOL;
            try {
                mail($configurations['emails'], "{$title} Alert!", $message, $headers);
            } catch (Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }
    }
}

$updateInventory = new UpdateAnotahEleganzaInventory();
$updateInventory->run();
echo "Process Finished\n";