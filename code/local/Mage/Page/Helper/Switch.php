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
 * @package     Mage_Page
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Store and language switcher block
 *
 * @category   Mage
 * @package    Mage_Core
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Page_Helper_Switch extends Mage_Core_Block_Template
{
    protected $_storeInUrl;
    public $previousUrl= "rrrrtt";

    public function getCurrentWebsiteId()
    {
        return Mage::app()->getStore()->getWebsiteId();
    }

    public function getCurrentGroupId()
    {
        return Mage::app()->getStore()->getGroupId();
    }

    public function getCurrentStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    public function getRawGroups()
    {
        if (!$this->hasData('raw_groups')) {
            $websiteGroups = Mage::app()->getWebsite()->getGroups();

            $groups = array();
            foreach ($websiteGroups as $group) {
                $groups[$group->getId()] = $group;
            }
            $this->setData('raw_groups', $groups);
        }
        return $this->getData('raw_groups');
    }

    public function getRawStores()
    {
        if (!$this->hasData('raw_stores')) {
            $websiteStores = Mage::app()->getWebsite()->getStores();
            $stores = array();
            foreach ($websiteStores as $store) {
                /* @var $store Mage_Core_Model_Store */
                if (!$store->getIsActive()) {
                    continue;
                }
                $store->setLocaleCode(Mage::getStoreConfig('general/locale/code', $store->getId()));

                $params = array(
                    '_query' => array()
                );
                if (!$this->isStoreInUrl()) {
                    $params['_query']['___store'] = $store->getCode();
                }
                $baseUrl = $store->getUrl('', $params);

                $store->setHomeUrl($baseUrl);
                $stores[$store->getGroupId()][$store->getId()] = $store;
            }
            $this->setData('raw_stores', $stores);
        }
        return $this->getData('raw_stores');
    }

    /**
     * Retrieve list of store groups with default urls set
     *
     * @return array
     */
    public function getGroups()
    {
        if (!$this->hasData('groups')) {
            $rawGroups = $this->getRawGroups();
            $rawStores = $this->getRawStores();

            $groups = array();
            $localeCode = Mage::getStoreConfig('general/locale/code');
            foreach ($rawGroups as $group) {
                /* @var $group Mage_Core_Model_Store_Group */
                if (!isset($rawStores[$group->getId()])) {
                    continue;
                }
                if ($group->getId() == $this->getCurrentGroupId()) {
                    $groups[] = $group;
                    continue;
                }

                $store = $group->getDefaultStoreByLocale($localeCode);

                if ($store) {
                    $group->setHomeUrl($store->getHomeUrl());
                    $groups[] = $group;
                }
            }
            $this->setData('groups', $groups);
        }
        return $this->getData('groups');
    }

    public function getStores()
    {
        if (!$this->getData('stores')) {
            $rawStores = $this->getRawStores();

            $groupId = $this->getCurrentGroupId();
            if (!isset($rawStores[$groupId])) {
                $stores = array();
            } else {
                $stores = $rawStores[$groupId];
            }
            $this->setData('stores', $stores);
        }
        return $this->getData('stores');
    }

    public function getCurrentStoreCode()
    {
        return Mage::app()->getStore()->getCode();
    }

    public function isStoreInUrl()
    {
        if (is_null($this->_storeInUrl)) {
            $this->_storeInUrl = Mage::getStoreConfigFlag(Mage_Core_Model_Store::XML_PATH_STORE_IN_URL);
        }
        return $this->_storeInUrl;
    }
    public function setPreviousUrl($url){
          $session = Mage::getSingleton("core/session");
          $session->setData("previousUrl", $url);
    }
    public function getPreviousUrl(){
        $session = Mage::getSingleton("core/session");
          return $session->getData("previousUrl");
    }


    /**
     * Added by MBT on (Dec 7th 2017)
     * Retrieve list of store details
     * 
     * @return array
     */
    public function getStoresList() {

        $allStores = $this->getStores();

        $refinedStores = array();

        foreach ($allStores as $_eachStoreId => $val) {

            $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();

            $currentStoreData = array(
                "storeCode" => $_storeCode,
                "storeName" => Mage::app()->getStore($_eachStoreId)->getName(),
                "storeId"   => Mage::app()->getStore($_eachStoreId)->getId(),
                "storeUrl"  => Mage::app()->getStore($_eachStoreId)->getCurrentUrl()
            );

            $refinedStores["allStores"][] = $currentStoreData;
            $refinedStores["storeCodes"][] = $_storeCode;

            $storeLang = explode("_", $_storeCode);

            if ($storeLang[0]=='en') {
                $refinedStores["en"][$_storeCode] = $currentStoreData;
            } else {
                $refinedStores["ar"][$_storeCode] = $currentStoreData;
            }

        }

        return $refinedStores;

    }

    /**
     * Added by MBT on (Dec 7th 2017)
     * Retrieve list of store codes for switching stored
     * 
     * @return array
     */
    public function switchStoreCodes ($refinedStores=array()) {

        $switchStoreCodes = array();
        
        if ($refinedStores) {
            foreach ($refinedStores['allStores'] as $_refineStore) {
                
                $_storeLang = explode("_", $_refineStore['storeCode']);

                if ($_storeLang[0]=='en') {

                    $searchedIndex = array_search("ar_".$_storeLang[1], $refinedStores["storeCodes"]);

                    if ($searchedIndex) {
                        $switchStoreCodes[$_refineStore['storeCode']] = $refinedStores["storeCodes"][$searchedIndex];
                    } else {
                        $switchStoreCodes[$_refineStore['storeCode']] = "-";
                    }

                } else {

                    $searchedIndex = array_search("en_".$_storeLang[1], $refinedStores["storeCodes"]);
                    $switchStoreCodes[$_refineStore['storeCode']] = $refinedStores["storeCodes"][$searchedIndex];

                }

            }
            
            return $switchStoreCodes;

        } else {

            return false;

        }

    }
}
