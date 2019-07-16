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
 * @version     1.2.3
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */

/**
 * Custom Menu extension Helper file
 */
class Apptha_CustomMenu_Helper_Data extends Mage_Core_Helper_Abstract {
 private $_menuData = null;
 
 /**
  * Function to display header menu for web
  *
  * This Function will return Header menu display hierarchy for web
  * 
  * @return string
  */
 public function getMenuData() {
  if (! is_null ( $this->_menuData )) {
   return $this->_menuData;
  }
  $blockClassName = Mage::getConfig ()->getBlockClassName ( 'custommenu/navigation' );
  /** Initialize new block name */
  $block = new $blockClassName ();
  /** Get Store Categories */
  $categories = $block->getStoreCategories ();
  if (is_object ( $categories )) {
  	/** Get Nodes */
   $categories = $block->getStoreCategories ()->getNodes ();
  }
  if (Mage::getStoreConfig ( 'custom_menu/general/ajax_load_content' )) {
   $_moblieMenuAjaxUrl = str_replace ( 'http:', '', Mage::getUrl ( 'custommenu/ajaxmobilemenucontent' ) );
   $_menuAjaxUrl = str_replace ( 'http:', '', Mage::getUrl ( 'custommenu/ajaxmenucontent' ) );
  } else {
   $_moblieMenuAjaxUrl = '';
   $_menuAjaxUrl = '';
  }
  $this->_menuData = array (
    '_block' => $block,
    '_categories' => $categories,
    '_moblieMenuAjaxUrl' => $_moblieMenuAjaxUrl,
    '_menuAjaxUrl' => $_menuAjaxUrl,
    '_showHomeLink' => Mage::getStoreConfig ( 'custom_menu/general/show_home_link' ),
    '_popupWidth' => Mage::getStoreConfig ( 'custom_menu/popup/width' ) + 0,
    '_popupTopOffset' => Mage::getStoreConfig ( 'custom_menu/popup/top_offset' ) + 0,
    '_popupDelayBeforeDisplaying' => Mage::getStoreConfig ( 'custom_menu/popup/delay_displaying' ) + 0,
    '_popupDelayBeforeHiding' => Mage::getStoreConfig ( 'custom_menu/popup/delay_hiding' ) + 0,
    '_rtl' => Mage::getStoreConfig ( 'custom_menu/general/rtl' ) + 0,
    '_mobileMenuEnabled' => Mage::getStoreConfig ( 'custom_menu/general/mobile_menu' ) + 0 
  );
  
  return $this->_menuData;
 }
 
 /**
  * Function to display header menu for mobile
  *
  * This Function will return Header menu display hierarchy for mobile
  * 
  * @return string
  */
 public function getMobileMenuContent() {
 	/** Get Menu Data */
  $menuData = Mage::helper ( 'custommenu' )->getMenuData ();
  extract ( $menuData );
  if (! $_mobileMenuEnabled) {
   return '';
  }
  /**
   * Home Link
   */
  // $homeLinkUrl = Mage::getBaseUrl ( Mage_Core_Model_Store::URL_TYPE_WEB );
  $homeLinkUrl = Mage::getUrl("what-is-new");
  /** Home Link Text */
  $homeLinkText = $this->__ ('What\'s New');
  $homeLink = '';
 if ($_showHomeLink) {
   $homeLink = '
<div id="menu-mobile-0" class="menu-mobile level0">
    <div class="parentMenu">
        <a href="'.$homeLinkUrl.'">
            <span>'.$this->__ ($homeLinkText).'</span>
        </a>
    </div>
</div>';
  }
  /**
   * Menu Content
   */
  $mobileMenuContent = '';
  $mobileMenuContentArray = array ();
  foreach ( $_categories as $_category ) {
   $mobileMenuContentArray [] = $_block->drawCustomMenuMobileItem ( $_category );
  }
  if (count ( $mobileMenuContentArray )) {
   $mobileMenuContent = implode ( "\n", $mobileMenuContentArray );
  }
  /**
   * Result
   */
  return '
'.$homeLink.'
'.$mobileMenuContent.'
<div class="clearBoth"></div>';

 }
 
 /**
  * Function to display header top menu label
  *
  * This Function will return Header top menu
  * 
  * @return string
  */
 public function getMenuContent() {
 	/** Get Menu Data
 	 * 
 	 */
  $menuData = Mage::helper ( 'custommenu' )->getMenuData ();
  extract ( $menuData );
  /**
   * Home Link
   */
  // $homeLinkUrl = Mage::getBaseUrl ( Mage_Core_Model_Store::URL_TYPE_WEB );
  
  
  $homeLinkUrl = Mage::getUrl ( "what-is-new" );
  $homeLinkText = $this->__ ( 'What\'s New' );
  $homeLink = '';
  if ($_showHomeLink) {
   $homeLink = '
<div class="menu">
    <div class="parentMenu menu0">
        <a href="'.$homeLinkUrl.'">
            <span>'.$this->__ ($homeLinkText).'</span>
        </a>
    </div>
</div>';

  }
  /**
   * Menu Content
   */
  $popupMenuContent = '';
  foreach ( $_categories as $_category ) {
   $_block->drawCustomMenuItem ( $_category );
  }
  $topMenuArray = $_block->getTopMenuArray ();
  if (count ( $topMenuArray )) {
   $topMenuContent = implode ( "\n", $topMenuArray );
  }
  $popupMenuArray = $_block->getPopupMenuArray ();
  if (count ( $popupMenuArray )) {
   $popupMenuContent = implode ( "\n", $popupMenuArray );
  }

$designer_deals = '
<div id="menu7" class="menu act" onmouseover="ShowMenuPopup(this, event, \'popup7\');" onmouseout="HideMenuPopup(this, event, \'popup7\', \'menu7\')">
<div class="parentMenu">
<a href="javascript:void(0);" rel="http://localhost/elabels/women">
<span>Whatss New</span>
</a>
</div>
</div>
<div id="popup7" class="custom-menu-popup" onmouseout="HideMenuPopup(this, event, \'popup7\', \'menu7\')" onmouseover="PopupOver(this, event, \'popup\', \'menu7\')">
<div class="block1">Designer Deals
<div class="clearBoth"></div>
</div></div>
';

$homeLink = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId("custom-menu-top-first-link")->toHtml();
$cutom_data = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId("custom-menu-top");

//Mage::getModel('cms/block')->load('topcustomlinks')->getContent().
  /**
   * Result
   */
  $topMenu = '<div class="customMenuItemContainer">'.
  				$homeLink.$topMenuContent.
				$cutom_data->toHtml().
				'</div><div class="clearBoth"></div>';
				
  return array (
    'topMenu' => $topMenu,
    'popupMenu' => $popupMenuContent
	//.Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('topcustomlinkscontents')->toHtml()
	,
	//Mage::getModel('cms/block')->load('topcustomlinkscontents')->getContent(),
  );
 }
 
 /**
  * Checking for mobile device or not
  */
 public function isMobile()
 {
 	$regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|"
 			. "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|"
 					. "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|"
 							. "symbian|smartphone|mmp|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|"
 									. "jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220"
 											. ")/i";
 
 	if (preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']))) {
 		return TRUE;
 	}
 
 	if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
 		return TRUE;
 	}
 
 	$mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
 	$mobile_agents = array(
 			'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
 			'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
 			'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
 			'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
 			'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
 			'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
 			'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
 			'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
 			'wapr','webc','winw','winw','xda ','xda-');
 
 	if (in_array($mobile_ua,$mobile_agents)) {
 		return TRUE;
 	}
 
 	if (isset($_SERVER['ALL_HTTP']) && strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini') > 0) {
 		return TRUE;
 	}
 
 	return FALSE;
 }

    public function categoryTree(){
        if(Mage::getStoreConfig('custom_menu/general/database_top_menu_mega')) {
            $tabel_exist = Mage::getSingleton('core/resource')
                ->getConnection('core_write')
                ->isTableExists(trim('progos_custommenu_menu'), null);
            $language = explode('_', Mage::app()->getLocale()->getLocaleCode());
            $language = $language[0];
            if ($tabel_exist) {
                $collection = Mage::getModel("progos_custommenu/menu")->getCollection();
                $collection->addFieldToFilter('id', 1);
                if ($collection && $collection->getSize() >= 1) {
                    foreach ($collection as $col) {
                        if ($col->getId()) {
                            if ($language == "en") {
                                $str = $col->getCategoriesEn();
                            } elseif ($language == "ar") {
                                $str = $col->getCategoriesAr();
                            }
                        }
                    }
                    return json_decode($str);
                }
            }
        }else{
            $language = explode('_', Mage::app()->getLocale()->getLocaleCode());
            $language = $language[0];
            $str = file_get_contents('media/mobile_json/categories_' . $language . '.json');
            return json_decode($str);
        }

    }
    /**
     * 
     */
    public function buildMenu()
    {
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $menuBlock = Mage::app()->getLayout()->createBlock('custommenu/topmenu');
        $mobileMenuBlock = Mage::app()->getLayout()->createBlock('progos_page/page_html_header');
        $allStores = Mage::app()->getStores();
        $storesCode = array('en_ae', 'ar_ae'); 
        foreach($allStores as $store) {
            if(in_array($store->getCode(), $storesCode)) {
                $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getStoreId());
                $data = $menuBlock->categoryTree();
                $menuBlock->setData('categories', $data)
                    ->setStoreId($store->getStoreId())
                    ->setTemplate('custommenu/buildMenu.phtml');
                $content = $menuBlock->toHtml();
                $this->saveContent($content, $store, 'desktop');

                $mobileCategories = $mobileMenuBlock->categoryTree();
                $mobileMenuBlock->setData('categories', $mobileCategories)
                    ->setStoreId($store->getStoreId())
                    ->setTemplate('custommenu/buildMobileMenu.phtml');
                $mobileContent = $mobileMenuBlock->toHtml();
                $this->saveContent($mobileContent, $store, 'mobile');

                $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            }
	}
    }
    
    /**
     * 
     * @param string $content
     * @param Mage_Core_Model_Store $store
     */
    public function saveContent($content, $store, $type)
    {
        $cmsBlock = Mage::getModel('cms/block')->load('system_topmenu_'. $type. '_'.  $store->getCode(), 'identifier');
        if (!$cmsBlock->getId()) {
            $staticBlock = array(
                'title' => 'System Top Menu '. $type. ' '. $store->getCode(),
                'identifier' => 'system_topmenu_'. $type. '_'. $store->getCode(),
                'content' => $content,
                'stores' => array(0),
                'is_active' => 1
            );
            Mage::getModel('cms/block')->setData($staticBlock)->save();
        } 
        else {
            $cmsBlock->setData('content', $content)->save();
        }
    }
}
