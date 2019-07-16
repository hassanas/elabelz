<?php
/**
 * @author Naveed Abbas
 */

/**
 * Class Progos_Restmob_Model_Catalog_Layer_Filter_Attribute
 */
class Progos_Restmob_Model_Catalog_Layer_Filter_Attribute extends Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute
{
    public function applyFilterToCollection($value, $notUsingFieldForCompatibilityWithEnterprise = null) {
        $attribute  = $this->getAttributeModel();
        $collection = $this->getLayer()->getProductCollection();
        if (Mage::helper('amshopby')->useSolr()) {
            $fieldName = Mage::getResourceSingleton('enterprise_search/engine')
                ->getSearchEngineFieldName($attribute, 'nav');
            $prefix = '{!tag=' . $attribute->getAttributeCode() . '}';
            $collection->addFqFilter(array($prefix . $fieldName => $value));
        } else {
            $alias      = $this->_getAttributeTableAlias();
            $connection = $this->_getResource()->getReadConnection();

                if ($this->getUseAndLogic()) {
                foreach ($value as $i => $attrValue) {
                    $alias = $alias . $i;
                    $conditions = array(
                        "{$alias}.entity_id = e.entity_id",
                        $connection->quoteInto("{$alias}.attribute_id = ?", $attribute->getAttributeId()),
                        $connection->quoteInto("{$alias}.store_id = ?",     $collection->getStoreId()),
                        $connection->quoteInto("{$alias}.value = ?",      $attrValue)
                    );

                    $collection->getSelect()->join(
                        array($alias => $this->_getResource()->getMainTable()),
                        join(' AND ', $conditions),
                        array()
                    );
                }
            } else {

                $conditions = array(
                    "{$alias}.entity_id = e.entity_id",
                    $connection->quoteInto("{$alias}.attribute_id = ?", $attribute->getAttributeId()),
                    $connection->quoteInto("{$alias}.store_id = ?",     $collection->getStoreId()),
                    $connection->quoteInto("{$alias}.value IN(?)",      $value)
                );

                $collection->getSelect()->join(
                    array($alias => $this->_getResource()->getMainTable()),
                    join(' AND ', $conditions),
                    array()
                );
            }
        }


        if (count($value) > 1){
            $collection->getSelect()->distinct(true);
        }

        if (isset($_REQUEST['debug'])) {
            Zend_Debug::dump($collection->getSelect()->__toString());
        }
        // its app request then only in this case do the following process add $productIds in registry filter_products_ids_for_app
        if(Mage::app()->getRequest()->getHeader('platform') == 'app') {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = $collection->getSelect()->__toString();
            $productIds = array();
            foreach ($readConnection->fetchAll($query) as $product){
                $productIds[] = $product ["entity_id"];
            }
            Mage::unregister('filter_products_ids_for_app');
            Mage::register('filter_products_ids_for_app', $productIds);
        }
    }
}