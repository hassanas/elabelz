<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Model_Info_Observer extends Varien_Object
{
    protected $_keywordData       = array();
    protected $_keywordUsePercent = array();
    protected $_imgWithEmptyAlt   = array();
    protected $_imgWithoutAlt     = array();
    protected $_keywordInText     = '';
    protected $_canonicalCount    = 0;

    public function getConfig()
    {
        return Mage::getSingleton('seo/config');
    }

    public function appendInfo($observer) //it will not work if the FPC or Varnish enabled
    {
        if (Mage::app()->getRequest()->getParam('ajax') || Mage::app()->getRequest()->getParam('isAjax')) {
            return;
        }
        
        $excludedActions = array('searchautocomplete_ajax_get');
        if (!$this->getConfig()->isInfoEnabled(Mage::app()->getStore()->getStoreId())
            || in_array(Mage::helper('seo')->getFullActionCode(), $excludedActions)) {
            return;
        }

        if (strpos(Mage::helper('core/url')->getCurrentUrl(), 'checkout')
            || strpos(Mage::helper('core/url')->getCurrentUrl(), 'customer/account')) {
                return;
        }

        $canonical  = '';
        $actionName = Mage::helper('seo')->getFullActionCode();
        $head       = Mage::app()->getLayout()->getBlock('head');

        if (!$head || !is_object($head)) {
            return;
        }

        $metaTitle             = $head->getTitle();
        $metaKeywords          = $head->getKeywords();
        $metaDescription       = $head->getDescription();
        $robots                = $head->getRobots();
        $metaTitleLength       = Mage::helper('core/string')->strlen($metaTitle);
        $metaDescriptionLength = Mage::helper('core/string')->strlen($metaDescription);

        if (is_object($observer) && is_object($observer->getFront())) {
            $body = $observer->getFront()->getResponse()->getBody();
            preg_match_all('/<link\s*rel="canonical"\s*href="(.*?)"\s*\/>/', $body, $canonicalArray);
            if (isset($canonicalArray[1][0])) {
                $this->_canonicalCount = count($canonicalArray[1]);
                $canonical = $canonicalArray[1][0];
            }
            $this->_prepareKeyword($metaKeywords, $body);

            $info = '
            <div class="seo-info">
                <div class="seo-info-base-window' . $this->_getDemoStyle() . '">
                    <h1 class="seo-info-h1-style">SEO Toolbar</h1>
                    <span id="m-seo-additional-info-hide-show-button" class="seo-additional-info-hide-show-button-style" onclick="seoInfoHide(this, false)">hide</span>
                    <div id="m-seo-info-scroll-hide-show" class="seo-info-scroll">
                        <h2 class="seo-info-h2-style"><b style="float:left;">Full Action Name:&nbsp;</b>
                            <div class="seo-info-text-style">' . $actionName . '</div>
                        </h2>
                        <hr class="seo-info-hr-style"/>
                        <h2 class="seo-info-h2-style"><b style="float:left;">Robots Meta Header:&nbsp;</b>
                            <div class="seo-info-text-style">' . $robots . '</div>
                        </h2>
                        <hr class="seo-info-hr-style"/>
                        <h2 class="seo-info-h2-style"><b style="float:left;">Canonical URL:&nbsp;</b>
                            <div class="seo-additional-info-text-style">' . $this->_getCanonicalInfoText($canonical) . '</div>
                        </h2>
                        <hr class="seo-info-hr-style"/>
                        <h2 class="seo-info-h2-style"><b style="float:left;">Number of H1 tags:&nbsp;</b>
                            <div class="seo-additional-info-text-style">' . $this->_getFirstLevelTitleInfo($body) . '</div>
                        </h2>
                        <hr class="seo-info-hr-style"/>
                        <h2 class="seo-info-h2-style"><b style="float:left;">Meta Title:&nbsp;</b>
                            <div class="seo-info-text-style">' . $metaTitle . '</div>
                            <div class="seo-additional-info-text-style">' . $this->_getInfoText($metaTitleLength, false, false) . '</div>
                        </h2>
                        <hr class="seo-info-hr-style"/>
                        <h2 class="seo-info-h2-style"><b style="float:left;">Meta Description:&nbsp;</b>
                            <div class="seo-info-text-style">' . $metaDescription . '</div>
                            <div class="seo-additional-info-text-style">' . $this->_getInfoText(false, false, $metaDescriptionLength) . '</div>
                        </h2>
                        <hr class="seo-info-hr-style"/>
                        <h2 class="seo-info-h2-style"><b style="float:left;">Meta Keywords&nbsp;</b>
                            <div class="seo-info-text-style">' . $this->_getInfoText(false, $metaKeywords, false) . '</div>
                        </h2>
                        '.$this->_getKeywordInfo().'
                        <hr class="seo-info-hr-style"/>
                        <h2 class="seo-info-h2-style"><b style="float:left;">Image Alt&nbsp;</b>
                            <div class="seo-info-text-style">' . $this->_getImageAltInfo($body) . '</div>
                            <div class="seo-info-text-style">' . $this->_getImageAltLinks() . '</div>
                        </h2>
                        '.$this->_getTemplatesRewriteInfo().'
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                function seoInfoHide(elem, value) {
                    if (elem) {
                        var elementText = elem.innerHTML;
                    } else {
                        elem = document.getElementById("m-seo-additional-info-hide-show-button")
                        elementText = value;
                    }
                    var infoBlock = document.getElementById("m-seo-info-scroll-hide-show");
                    if (elementText == "hide") {
                        infoBlock.addClassName("m-seo-info-scroll-hide");
                        elem.innerHTML = "show";
                        setSeoToolbarCookie("m_seo_toolbar_status", "hide", 10);
                    }
                    if (elementText == "show") {
                        infoBlock.removeClassName("m-seo-info-scroll-hide");
                        elem.innerHTML = "hide";
                        setSeoToolbarCookie("m_seo_toolbar_status", "show", 10);
                    }
                }

                document.observe("dom:loaded", function(){
                    var cookieStatus = checkSeoToolbarCookie();

                    var seoToolbarStatus = "hide";
                    var domainName      = location.hostname;
                    if( domainName.indexOf("mirasvit.com") >= 0) {
                        seoToolbarStatus = "show";
                    }

                    if (!cookieStatus && seoToolbarStatus) {
                        seoInfoHide(false, seoToolbarStatus);
                    } else if (cookieStatus) {
                        seoInfoHide(false, cookieStatus);
                    } else {
                        seoInfoHide(false, "hide");
                    }
                });

                function setSeoToolbarCookie(cname, cvalue, exdays) {
                    var path = "path=/";
                    var d    = new Date();
                    d.setTime(d.getTime() + (exdays*24*60*60*1000));
                    var expires = "expires="+d.toUTCString();
                    document.cookie = cname + "=" + cvalue + "; " + expires + "; " + path;
                }

                function getSeoToolbarCookie(cname) {
                    var name = cname + "=";
                    var ca = document.cookie.split(\';\');
                    for(var i=0; i<ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0)==\' \') c = c.substring(1);
                        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
                    }
                    return "";
                }

                function checkSeoToolbarCookie() {
                    var mSeoToolbar = getSeoToolbarCookie("m_seo_toolbar_status");
                    if (mSeoToolbar == "hide" || mSeoToolbar == "show") {
                        return mSeoToolbar;
                    }

                    return false;
                }
            </script>';

            echo $info;
        }
    }

    protected function  _getDemoStyle()
    {
        if (isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], "mirasvit.com")) {
            return " m-seo-info-demo-style";
        }

        return;
    }

    protected function _getTemplatesRewriteInfo()
    {
        if (!$this->getConfig()->isShowTemplatesRewriteInfo(Mage::app()->getStore()->getStoreId())) {
            return;
        }

        $infoTemplatesApplied  = false;
        $rewrite               = Mage::helper('seo')->checkRewrite(true);
        $infoTemplates         = '<div class="seo-info-text-style m-seo-info-template-table-row">' .
                                    '<div class="m-seo-info-template-table-cell m-seo-info-id-width"><small><b>ID</b></small></div>' .
                                    '<div class="m-seo-info-template-table-cell m-seo-info-rule-name-width"><small><b>Rule Name</b></small></div>' .
                                    // '<div class="m-seo-info-template-table-cell m-seo-info-sort-order-width"><small><b>Sort Order</b></small></div>' .
                                    // '<div class="m-seo-info-template-table-cell m-seo-info-rules-processing-width"><small><b>Rules Processing</b></small></div>' .
                                    '<div class="m-seo-info-template-table-cell m-seo-info-status-width"><small><b>Status</b></small></div>' .
                                ' </div>';
        $isCategory             = Mage::registry('current_category') || Mage::registry('category');
        $isProduct              = Mage::registry('current_product') || Mage::registry('product');
        $isFilter               = false;

        if ($isCategory) {
            $filters = Mage::getSingleton('catalog/layer')->getState()->getFilters();
            $isFilter = count($filters) > 0;
        }

        if ($seoTemplateRule = Mage::helper('seo')->checkTempalateRule($isProduct, $isCategory, $isFilter, true)) {
            foreach ($seoTemplateRule as $keyTemplate => $valueTemplate) {
                $infoTemplatesAdditional = '';
                $infoSortOrder           = '';
                $infoStopRulesProcessing = '';
                $templateName            = '';

                if ($seoTemplateRule['applied'] == $keyTemplate && !$rewrite) {
                    $infoTemplatesAdditional = '<span class="seo-info-text-style seo-info-correct">Applied</span>';
                    if (isset($seoTemplateRule['stop_rules_processing'])) {
                        $infoTemplatesAdditional .= '<span class="seo-info-text-style seo-info-correct">,&nbsp;rules processing stopped</span>';
                    }
                    if (isset($seoTemplateRule['sort_order'])) {
                        $infoTemplatesAdditional .= '<span class="seo-info-text-style seo-info-correct">,&nbsp;sort order</span>';
                    }
                    if ($isCategory && !$isProduct && $this->getConfig()->isCategoryMetaTagsUsed()) {
                        $infoTemplatesAdditional .= '<br/>
                            <span class="seo-info-text-style seo-info-notice">
                                ("Use meta tags from categories if they are not empty" is enabled)
                            </span>';
                    }
                    if ($isProduct && $this->getConfig()->isProductMetaTagsUsed()) {
                        $infoTemplatesAdditional .= '<br/>
                            <span class="seo-info-text-style seo-info-notice">
                                ("Use meta tags from products if they are not empty" is enabled)
                            </span>';
                    }
                }

                if (is_object($valueTemplate)) {
                    $templateName = '<div class="seo-additional-info-template-style m-seo-info-template-left">' .  $valueTemplate->getName() . '</div>';
                }

                if (is_object($valueTemplate)) {
                    $infoSortOrder .= '<div class="seo-additional-info-template-style">' . $valueTemplate->getSortOrder() . '</div>';
                } else {
                    $infoSortOrder .= '-';
                }

                if (is_object($valueTemplate) && ($valueTemplate->getStopRulesProcessing())) {
                    $infoStopRulesProcessing .= '<div class="seo-additional-info-template-style">enabled</div>';
                } else {
                    $infoStopRulesProcessing .= '-';
                }

                if (!in_array($keyTemplate, array('applied', 'stop_rules_processing', 'sort_order'))) {
                    $infoTemplates .= '<div class="seo-info-text-style m-seo-info-template-table-row">' .
                                            '<div class="m-seo-info-template-table-cell">' . $keyTemplate . '</div>' .
                                            '<div class="m-seo-info-template-table-cell">' . $templateName . ' </div>' .
                                            // '<div class="m-seo-info-template-table-cell">' . $infoSortOrder . ' </div>' .
                                            // '<div class="m-seo-info-template-table-cell">' . $infoStopRulesProcessing . ' </div>' .
                                            '<div class="m-seo-info-template-table-cell">' . $infoTemplatesAdditional . '</div>' .
                                      ' </div>';
                    $infoTemplatesApplied = true;
                }
            }
        }

        if ($rewrite) {
            $infoTemplates .= '<div class="seo-info-text-style m-seo-info-template-table-row">' .
                                            '<div class="m-seo-info-template-table-cell">' . $rewrite->getRewriteId() . '</div>' .
                                            '<div class="m-seo-info-template-table-cell m-seo-info-template-left">' . $rewrite->getUrl() . '</div>' .
                                            // '<div class="m-seo-info-template-table-cell"> </div>' .
                                            // '<div class="m-seo-info-template-table-cell"> </div>' .
                                            '<div class="m-seo-info-template-table-cell seo-info-correct">Applied, rewrite</div>' .
                                      ' </div>';
            $infoTemplatesApplied = true;
        }

        if (!$infoTemplatesApplied) {
            return;
        }

        $infoTemplatesHtml =
        '<hr class="seo-info-hr-style"/>
        <h2 class="seo-info-h2-style"><b>SEO Templates and Rewrites&nbsp;</b>
            <div class="seo-info-text-style m-seo-info-template-table">' . $infoTemplates . '</div>
        </h2>';

        return $infoTemplatesHtml;
    }

    protected function _getImageAltLinks()
    {
        if (!$this->getConfig()->isShowAltLinkInfo(Mage::app()->getStore()->getStoreId())) {
            return;
        }

        $imageAltLinksInfo = '';
        if (count($this->_imgWithoutAlt) > 0) {
            $imageAltLinksInfo .= '<div class="seo-info-text-style seo-info-notice">Images without alt: </div>';
            foreach ($this->_imgWithoutAlt as $linkWithoutAlt) {
                $imageAltLinksInfo .= '<div class="seo-additional-info-link-style"><a href="'. $linkWithoutAlt . '"target="_blank">'. $linkWithoutAlt . '</a></div>';
            }
        }

        if (count($this->_imgWithEmptyAlt) > 0) {
            $imageAltLinksInfo .= '<div class="seo-info-text-style seo-info-notice">Images with empty alt: </div>';
            foreach ($this->_imgWithEmptyAlt as $linkWithEmptyAlt) {
                $imageAltLinksInfo .= '<div class="seo-additional-info-link-style"><a href="' . $linkWithEmptyAlt . '"target="_blank">'. $linkWithEmptyAlt . '</a></div>';
            }
        }

        return $imageAltLinksInfo;
    }

    protected function _getImageAltInfo($body)
    {
        preg_match_all('/<img[^>]+>/i',$body, $imagesData);
        if (isset($imagesData[0]) && $imagesData[0]) {
            $img             = array();
            $imgPrepared     = array();

            foreach($imagesData[0] as $imgTag)
            {
                preg_match_all('/(alt|src)=("[^"]*")/i', $imgTag, $img[$imgTag]);
            }

            if ($img) {
                foreach($img as $imgKey => $imgArray) {
                    if (isset($imgArray[0]) && isset($imgArray[1]) && isset($imgArray[2])) {
                        foreach ($imgArray[1] as $tagKey => $tag) {
                            $tagValue = trim($imgArray[2][$tagKey], '"..\'');
                            $imgPrepared[$imgKey][$tag] = $tagValue;
                        }
                    }
                }
            }
            if($imgPrepared) {
                foreach ($imgPrepared as $imgKey => $imgTagsArray) {
                    if (array_key_exists('alt', $imgTagsArray) && $imgTagsArray['alt'] == '') {
                        $this->_imgWithEmptyAlt[] = $imgTagsArray['src'];
                    } elseif (!array_key_exists('alt', $imgTagsArray)) {
                        $this->_imgWithoutAlt[] = $imgTagsArray['src'];
                    }
                }

                $infoImagesAlt =  '
                <br/><div class="seo-info-text-style"><span class="seo-info-image-alt">Total amount of images: </span><span>' . count($imgPrepared) . '</span></div>'
                . '<div class="seo-info-text-style"><span class="seo-info-image-alt">Images without alt attribute: </span><span class="seo-info-notice">' . count($this->_imgWithoutAlt) . '</span></div>'
                . '<div class="seo-info-text-style"><span class="seo-info-image-alt">Images with empty alt attribute: </span><span class="seo-info-notice">' . count($this->_imgWithEmptyAlt) . '</span></div>';
                if (count($this->_imgWithoutAlt) > 0 || count($this->_imgWithEmptyAlt) > 0) {
                    $infoImagesAlt .= '<div class="seo-info-notice"> Some alt tags are empty or missing.</div>';
                } else {
                    $infoImagesAlt .=  '<div class="seo-info-correct">Correct.</div>';
                }

                return $infoImagesAlt;
            }
        }

        return;
    }

    protected function _getFirstLevelTitleInfo($body)
    {
        $firstLevelTitle = substr_count($body, '</h1');

        if ($firstLevelTitle == 1) {
            return '<span class="seo-info-correct">One H1 tag. Correct.</span>';
        } elseif ($firstLevelTitle > 1) {
            return '<span class="seo-info-incorrect">' . $firstLevelTitle . ' H1 tags. Incorrect.</span>';
        }

        return  '<span class="seo-info-notice">There is no H1 tag on the page.</span>';
    }

    protected function _prepareKeyword($metaKeywords, $body)
    {
        if ($metaKeywords) {
            $bodyWithoutHead       = preg_replace('/<head>(.*?)<\/head>/ims', '', $body, 1);
            $bodyWithoutHead       = strtolower($bodyWithoutHead);
            $metaKeywordsLowerCase = strtolower($metaKeywords);
            $metaKeywordsArray     = explode(',', $metaKeywordsLowerCase);
            $metaKeywordsArray     = array_map('trim', $metaKeywordsArray);

            $nextSymbol= array('',' ', ',', '.', '!', '?', "\n", "\r", "\r\n", "<");    // symbols after the word
            $prevSymbol= array(',',' ', "\n", "\r", "\r\n", ">"); // symbols before the word
            foreach ($metaKeywordsArray as $keyword) {
                if (!$keyword) {
                    continue;
                }
                $size              = 0;
                $keywordCount      = 0;
                $explodeSource     = explode($keyword, $bodyWithoutHead);
                $sizeExplodeSource = count($explodeSource);

                foreach ($explodeSource as $keySource => $valSource) {
                    $size++;
                    if (($size < $sizeExplodeSource)
                        && (((!empty($explodeSource[$keySource + 1][0]))
                            && (in_array($explodeSource[$keySource + 1][0], $nextSymbol)))
                                || (empty($explodeSource[$keySource + 1][0])))
                        && ((empty($explodeSource[$keySource][strlen($explodeSource[$keySource])-1]))
                            || ((!empty($explodeSource[$keySource][strlen($explodeSource[$keySource])-1]))
                                && (in_array($explodeSource[$keySource][strlen($explodeSource[$keySource])-1], $prevSymbol))))) {
                                        $keywordCount++;
                    }
                }
                $this->_keywordData[$keyword] = $keywordCount;
            }


            if ($keywordSum = array_sum($this->_keywordData)) {
                foreach ($this->_keywordData as $keyword => $keywordCount) {
                    $this->_keywordUsePercent[$keyword] = round(($keywordCount *100)/$keywordSum, 1);
                }
            } else {
                foreach ($this->_keywordData as $keyword => $keywordCount) {
                    $this->_keywordUsePercent[$keyword] = 0;
                }
                $this->_keywordInText = "No such keywords in the text.";
            }
        }


        return;
    }

    protected function _getKeywordInfo()
    {
        if (!$this->_keywordData) {
            return;
        }
        arsort($this->_keywordData);
        $keywordInfo = '';
        foreach ($this->_keywordData as $keyword => $keywordCount) {
            $keywordInfo .='
            <div class="seo-info-progress-container">
                <span class="seo-info-progress-label">'.$keyword.'</span>
                <div class="seo-info-progress seo-info-progress-success">
                    <div style="width: '.$this->_keywordUsePercent[$keyword].'%;" aria-valuemax="100" aria-valuenow="0" aria-valuemin="0" role="progressbar" class="seo-info-progress-bar" >
                    </div>
                </div>
                &nbsp;&nbsp;&nbsp;'.$keywordCount.'&nbsp;&nbsp;('.$this->_keywordUsePercent[$keyword].'%)
            </div>';
        }

        return $keywordInfo;
    }

    protected function _getInfoText($metaTitleLength, $metaKeywords, $metaDescriptionLength)
    {
        $length        = false;
        $currentLength = false;

        if ($metaKeywords !== false && !$metaKeywords) {
            return ' <div class="seo-info-incorrect">Meta Keywords have to be added.</div>';
        } elseif($metaKeywords !== false && $this->_keywordInText) {
            return '<div class="seo-info-incorrect">'.$this->_keywordInText.'</div>';
        }elseif($metaKeywords !== false) {
            return '<br/>';
        }

        if ($metaTitleLength) {
             $currentLength = $metaTitleLength;
        }

        if ($metaDescriptionLength) {
            $currentLength = $metaDescriptionLength;
        }

        if ($metaTitleLength && $metaTitleLength > Mirasvit_Seo_Model_Config::META_TITLE_MAX_LENGTH) {
            $length = Mirasvit_Seo_Model_Config::META_TITLE_MAX_LENGTH;
        }

        if ($metaDescriptionLength && $metaDescriptionLength > Mirasvit_Seo_Model_Config::META_DESCRIPTION_MAX_LENGTH) {
            $length = Mirasvit_Seo_Model_Config::META_DESCRIPTION_MAX_LENGTH;
        }

        if ($length) {
            return ' <span class="seo-info-notice">Length = ' . $currentLength . '. Recommended length up to ' . $length . ' characters. Can be configured in SEO Extended Settings.</span>';
        }

        if ($metaTitleLength === 0) {
            return ' <span class="seo-info-incorrect">Meta Title have to be added.</span>';
        }

        if ($metaDescriptionLength === 0) {
            return ' <span class="seo-info-incorrect">Meta Description have to be added.</span>';
        }

        if ($currentLength) {
            $corectInfo = ' <span class="seo-info-correct">Length = ' . $currentLength . '. Correct.</span>';
        } else {
            $corectInfo = ' <span class="seo-info-correct">Correct.</span>';
        }

        return $corectInfo;
    }

    protected function _getCanonicalInfoText($canonical)
    {
        if ($this->_canonicalCount > 1) {
            return ' <span class="seo-info-incorrect">' . $this->_canonicalCount . ' canonical on the page.</span>';
        }
        if ($canonical
            && ($canonicalUrl = Mage::helper('seo')->getCanonicalUrl())
            && $canonicalUrl != $canonical) {
                return  '<div class="seo-info-text-style">' . $canonical . '</div> <span class="seo-info-notice">Canonical created not using Mirasvit SEO extension.</span>';
        }
        if ($canonical) {
            return  '<div class="seo-info-text-style">' . $canonical . '</div> <span class="seo-info-correct">Correct.</span>';
        }

        return ' <span class="seo-info-incorrect">Missing canonical URL.</span>';
    }

}