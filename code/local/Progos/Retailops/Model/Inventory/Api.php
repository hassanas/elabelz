<?php
class Progos_Retailops_Model_Inventory_Api extends RetailOps_Api_Model_Inventory_Api
{
    /**
     * Update stock data of multiple products at once
     *
     * Changes : From Line 24 to 29
     * Changes Description : During Channel verification its fetching all records of order and then its inventory which is causing timeout issue.
     * So here we have stop from syncing.
     * Changes By : Saroop Chand <saroop.chand@progos.org>
     *
     * @param array $itemData
     * @return array
     */
    public function inventoryPush($itemData)
    {
        if (isset($itemData['records'])) {
            $itemData = $itemData['records'];
        }
        $response = array();
        $response['records'] = array();
        $resourceModel = Mage::getResourceModel('retailops_api/api');

        $orderQtys = array();
        if( !empty( $itemData ) ) {
            Mage::log('In', null, 'retailops.log');
            Mage::log(print_r($itemData, true), null, 'retailops.log');
            $orderQtys = $resourceModel->getRetailopsNonretrievedQtys();
        }
        $productIds = $this->getProductIds($itemData);

        foreach ($itemData as $item) {
            try {
                Mage::dispatchEvent(
                    'retailops_inventory_push_record',
                    array('record' => $stockObj)
                );

                $stockObj = $resourceModel->subtractNonretrievedQtys($orderQtys, $item);

                Mage::dispatchEvent(
                    'retailops_inventory_push_record_qty_processed',
                    array('record' => $stockObj)
                );

                $this->update($productIds[$stockObj->getSku()], $stockObj->getData());

                $result['status'] = RetailOps_Api_Helper_Data::API_STATUS_SUCCESS;
            } catch (Mage_Core_Exception $e) {
                $result['status'] = RetailOps_Api_Helper_Data::API_STATUS_FAIL;
                $result['error'] = array(
                    'code'      => $e->getCode(),
                    'message'   => $e->getMessage()
                );
            }
            $response['records'][] = $result;
        }

        return $response;
    }
}
