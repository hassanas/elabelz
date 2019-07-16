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


class Mirasvit_Seo_Model_Config
{
    const NO_TRAILING_SLASH = 1;
    const TRAILING_SLASH    = 2;

    const URL_FORMAT_SHORT = 1;
    const URL_FORMAT_LONG  = 2;

    const NOINDEX_NOFOLLOW = 1;
    const NOINDEX_FOLLOW   = 2;
    const INDEX_NOFOLLOW   = 3;
    const INDEX_FOLLOW     = 4;

    const CATEGYRY_RICH_SNIPPETS_PAGE     = 1;
    const CATEGYRY_RICH_SNIPPETS_CATEGORY = 2;

    const PRODUCTS_WITH_REVIEWS_NUMBER    = 1;
    const REVIEWS_NUMBER                  = 2;

    const BREADCRUMBS_WITH_SEPARATOR      = 1;
    const BREADCRUMBS_WITHOUT_SEPARATOR   = 2;

    const META_TITLE_PAGE_NUMBER_BEGIN            = 1;
    const META_TITLE_PAGE_NUMBER_END              = 2;
    const META_TITLE_PAGE_NUMBER_BEGIN_FIRST_PAGE = 3;
    const META_TITLE_PAGE_NUMBER_END_FIRST_PAGE   = 4;

    const META_DESCRIPTION_PAGE_NUMBER_BEGIN            = 1;
    const META_DESCRIPTION_PAGE_NUMBER_END              = 2;
    const META_DESCRIPTION_PAGE_NUMBER_BEGIN_FIRST_PAGE = 3;
    const META_DESCRIPTION_PAGE_NUMBER_END_FIRST_PAGE   = 4;

    const META_TITLE_MAX_LENGTH                         = 55;
    const META_DESCRIPTION_MAX_LENGTH                   = 150;
    const META_TITLE_INCORRECT_LENGTH                   = 25;
    const META_DESCRIPTION_INCORRECT_LENGTH             = 25;

    const PRODUCT_WEIGHT_RICH_SNIPPETS_KG               = 'KGM';
    const PRODUCT_WEIGHT_RICH_SNIPPETS_LB               = 'LBR';
    const PRODUCT_WEIGHT_RICH_SNIPPETS_G                = 'GRM';

    //seo template rule
    const PRODUCTS_RULE                                 = 1;
    const CATEGORIES_RULE                               = 2;
    const RESULTS_LAYERED_NAVIGATION_RULE               = 3;

    //seo condition rich snippets
    const CONDITION_RICH_SNIPPETS_NEW_ALL               = 1;
    const CONDITION_RICH_SNIPPETS_CONFIGURE             = 2;

    //seo organization rich snippets
    const SNIPPETS_ORGANIZATION_JSON                    = 1;
    const SNIPPETS_ORGANIZATION_MICRODATA               = 2;

    public function isAddCanonicalUrl()
    {
        return Mage::getStoreConfig('seo/general/is_add_canonical_url');
    }

    public function getAssociatedCanonicalConfigurableProduct()
    {
        return Mage::getStoreConfig('seo/general/associated_canonical_configurable_product');
    }

    public function getAssociatedCanonicalGroupedProduct()
    {
        return Mage::getStoreConfig('seo/general/associated_canonical_grouped_product');
    }

    public function getAssociatedCanonicalBundleProduct()
    {
        return Mage::getStoreConfig('seo/general/associated_canonical_bundle_product');
    }

    public function getCrossDomainStore()
    {
        return Mage::getStoreConfig('seo/general/crossdomain');
    }
    
    public function isAddPaginatedCanonical()
    {
        return Mage::getStoreConfig('seo/general/paginated_canonical');
    }

    public function getCanonicalUrlIgnorePages()
    {
        $pages = Mage::getStoreConfig('seo/general/canonical_url_ignore_pages');
        $pages = explode("\n", trim($pages));
        $pages = array_map('trim',$pages);

        return $pages;
    }

    public function getNoindexPages()
    {
        $pages = Mage::getStoreConfig('seo/general/noindex_pages2');
        $pages = unserialize($pages);
        $result = array();
        if (is_array($pages)) {
            foreach ($pages as $value) {
                $result[] = new Varien_Object($value);
            }
        }
        return $result;
    }

    public function getHttpsNoindexPages()
    {
        return Mage::getStoreConfig('seo/general/https_noindex_pages');
    }

    public function isAlternateHreflangEnabled($store)
    {
        return Mage::getStoreConfig('seo/general/is_alternate_hreflang', $store);
    }

    public function getHreflangLocaleCode($store)
    {
        return trim(Mage::getStoreConfig('seo/general/hreflang_locale_code', $store));
    }

    public function isPagingPrevNextEnabled()
    {
        return Mage::getStoreConfig('seo/general/is_paging_prevnext');
    }

    public function isCategoryMetaTagsUsed()
    {
        return Mage::getStoreConfig('seo/general/is_category_meta_tags_used');
    }

    public function isProductMetaTagsUsed()
    {
        return Mage::getStoreConfig('seo/general/is_product_meta_tags_used');
    }

///////////// Extended Settings
    public function getMetaTitlePageNumber($store) {
        return Mage::getStoreConfig('seo/extended/meta_title_page_number', $store);
    }

    public function getMetaDescriptionPageNumber($store) {
        return Mage::getStoreConfig('seo/extended/meta_description_page_number', $store);
    }

    public function getMetaTitleMaxLength($store) {
        return Mage::getStoreConfig('seo/extended/meta_title_max_length', $store);
    }

    public function getMetaDescriptionMaxLength($store) {
        return Mage::getStoreConfig('seo/extended/meta_description_max_length', $store);
    }

///////////// Rich Snippets and Opengraph
    public function isRichSnippetsEnabled($store)
    {
        return Mage::getStoreConfig('seo/snippets/is_rich_snippets', $store);
    }

    public function isEnabledRichSnippetsPaymentMethod() {
        return Mage::getStoreConfig('seo/snippets/rich_snippets_payment_method');
    }

    public function isEnabledRichSnippetsDeliveryMethod() {
        return Mage::getStoreConfig('seo/snippets/rich_snippets_delivery_method');
    }

    public function isEnabledRichSnippetsProductCategory() {
        return Mage::getStoreConfig('seo/snippets/rich_snippets_product_category');
    }

    public function getRichSnippetsBrandAttributes()
    {
        return $this->_prepereAttributes(Mage::getStoreConfig('seo/snippets/rich_snippets_brand_config'));
    }

    public function getRichSnippetsModelAttributes()
    {
        return $this->_prepereAttributes(Mage::getStoreConfig('seo/snippets/rich_snippets_model_config'));
    }

    public function getRichSnippetsColorAttributes()
    {
        return $this->_prepereAttributes(Mage::getStoreConfig('seo/snippets/rich_snippets_color_config'));
    }

    public function getRichSnippetsWeightCode()
    {
        return Mage::getStoreConfig('seo/snippets/rich_snippets_weight_config');
    }

    public function isEnabledRichSnippetsDimensions()
    {
        return Mage::getStoreConfig('seo/snippets/rich_snippets_dimensions_config');
    }

    public function getRichSnippetsDimensionUnit()
    {
        return trim(Mage::getStoreConfig('seo/snippets/rich_snippets_dimensional_unit'));
    }

    public function getRichSnippetsHeightAttributes() {
        return $this->_prepereAttributes(Mage::getStoreConfig('seo/snippets/rich_snippets_height_config'));
    }

    public function getRichSnippetsWidthAttributes() {
        return $this->_prepereAttributes(Mage::getStoreConfig('seo/snippets/rich_snippets_width_config'));
    }

    public function getRichSnippetsDepthAttributes() {
        return $this->_prepereAttributes(Mage::getStoreConfig('seo/snippets/rich_snippets_depth_config'));
    }

    public function isEnabledRichSnippetsCondition()
    {
        return Mage::getStoreConfig('seo/snippets/rich_snippets_product_condition_config');
    }

    public function getRichSnippetsConditionAttribute()
    {
        return $this->_prepereAttributes(Mage::getStoreConfig('seo/snippets/rich_snippets_product_condition_attribute'));
    }

    public function getRichSnippetsNewConditionValue()
    {
        return trim(Mage::getStoreConfig('seo/snippets/rich_snippets_product_condition_new'));
    }

    public function getRichSnippetsUsedConditionValue()
    {
        return trim(Mage::getStoreConfig('seo/snippets/rich_snippets_product_condition_used'));
    }

    public function getRichSnippetsRefurbishedConditionValue()
    {
        return trim(Mage::getStoreConfig('seo/snippets/rich_snippets_product_condition_refurbished'));
    }

    public function getRichSnippetsDamagedConditionValue()
    {
        return trim(Mage::getStoreConfig('seo/snippets/rich_snippets_product_condition_damaged'));
    }

    protected function _prepereAttributes($attributes) {
        $attributes = strtolower(trim($attributes));
        $attributes = explode(",", trim($attributes));
        $attributes = array_map('trim',$attributes);
        $attributes = array_diff($attributes, array(null));

        return $attributes;
    }

    public function isDeleteWrongSnippets($store)
    {
        return Mage::getStoreConfig('seo/snippets/delete_wrong_snippets', $store);
    }

    public function getCategoryRichSnippets($store)
    {
        return Mage::getStoreConfig('seo/snippets/category_rich_snippets', $store);
    }

    public function getCategoryRichSnippetsPriceText($store)
    {
        return Mage::getStoreConfig('seo/snippets/category_rich_snippets_price_text', $store);
    }

    public function getCategoryRichSnippetsRatingText($store)
    {
        return Mage::getStoreConfig('seo/snippets/category_rich_snippets_rating_text', $store);
    }

    public function getCategoryRichSnippetsRewiewCountText($store)
    {
        return Mage::getStoreConfig('seo/snippets/category_rich_snippets_rewiew_count_text', $store);
    }

    public function getRichSnippetsRewiewCount($store)
    {
        return Mage::getStoreConfig('seo/snippets/category_rich_snippets_rewiew_count', $store);
    }

     public function isHideCategoryRichSnippets($store)
    {
        return Mage::getStoreConfig('seo/snippets/hide_category_rich_snippets', $store);
    }

    public function isBreadcrumbs($store)
    {
        return Mage::getStoreConfig('seo/snippets/is_breadcrumbs', $store);
    }

    public function getBreadcrumbsSeparator($store)
    {
        $separator = trim(Mage::getStoreConfig('seo/snippets/breadcrumbs_separator', $store));
        if(empty($separator)) {
            return false;
        }
        return $separator;
    }

    public function getOrganizationSnippets()
    {
        return Mage::getStoreConfig('seo/snippets/is_organization_snippets');
    }

    public function getNameOrganizationSnippets()
    {
        return Mage::getStoreConfig('seo/snippets/name_organization_snippets');
    }

    public function getManualNameOrganizationSnippets()
    {
        return trim(Mage::getStoreConfig('seo/snippets/manual_name_organization_snippets'));
    }

    public function getCountryAddressOrganizationSnippets()
    {
        return Mage::getStoreConfig('seo/snippets/country_address_organization_snippets');
    }

    public function getManualCountryAddressOrganizationSnippets()
    {
        return trim(Mage::getStoreConfig('seo/snippets/manual_country_address_organization_snippets'));
    }

    public function getManualLocalityAddressOrganizationSnippets()
    {
        return trim(Mage::getStoreConfig('seo/snippets/manual_locality_address_organization_snippets'));
    }

    public function getManualPostalCodeOrganizationSnippets()
    {
        return trim(Mage::getStoreConfig('seo/snippets/manual_postal_code_organization_snippets'));
    }

    public function getStreetAddressOrganizationSnippets()
    {
        return Mage::getStoreConfig('seo/snippets/street_address_organization_snippets');
    }

    public function getManualStreetAddressOrganizationSnippets()
    {
        return trim(Mage::getStoreConfig('seo/snippets/manual_street_address_organization_snippets'));
    }

    public function getTelephoneOrganizationSnippets()
    {
        return Mage::getStoreConfig('seo/snippets/telephone_organization_snippets');
    }

    public function getManualTelephoneOrganizationSnippets()
    {
        return trim(Mage::getStoreConfig('seo/snippets/manual_telephone_organization_snippets'));
    }

    public function getManualFaxnumberOrganizationSnippets()
    {
        return trim(Mage::getStoreConfig('seo/snippets/manual_faxnumber_organization_snippets'));
    }

    public function getEmailOrganizationSnippets()
    {
        return Mage::getStoreConfig('seo/snippets/email_organization_snippets');
    }

    public function getManualEmailOrganizationSnippets()
    {
        return Mage::getStoreConfig('seo/snippets/manual_email_organization_snippets');
    }





    public function isOpenGraphEnabled()
    {
        return Mage::getStoreConfig('seo/snippets/is_opengraph');
    }

///////////// SEO URL
    public function isEnabledSeoUrls()
    {
        return Mage::getStoreConfig('seo/url/layered_navigation_friendly_urls');
    }

    public function getTrailingSlash()
    {
        return Mage::getStoreConfig('seo/url/trailing_slash');
    }

    public function getProductUrlFormat()
    {
       return Mage::getStoreConfig('seo/url/product_url_format');
    }

    public function getProductUrlKey($store)
    {
       return Mage::getStoreConfig('seo/url/product_url_key', $store);
    }

    public function isCategoryUrlFormatEnabled($store)
    {
        return Mage::getStoreConfig('seo/url/category_url_format', $store);
    }

    public function isEnabledTagSeoUrls()
    {
        return Mage::getStoreConfig('seo/url/tag_friendly_urls');
    }

    public function isEnabledReviewSeoUrls()
    {
        return Mage::getStoreConfig('seo/url/review_friendly_urls');
    }

///////////// IMAGE
    public function getIsEnableImageFriendlyUrls()
    {
        return Mage::getStoreConfig('seo/image/is_enable_image_friendly_urls');
    }

    public function getImageUrlTemplate()
    {
        return Mage::getStoreConfig('seo/image/image_url_template');
    }
    public function getIsEnableImageAlt()
    {
        return Mage::getStoreConfig('seo/image/is_enable_image_alt');
    }

    public function getImageAltTemplate()
    {
        return Mage::getStoreConfig('seo/image/image_alt_template');
    }

///////////// INFO
    public function isInfoEnabled($storeId = null)
    {
        if (!$this->_isInfoAllowed()) {
            return false;
        }

        return Mage::getStoreConfig('seo/info/info', $storeId);
    }

    public function isShowAltLinkInfo($storeId = null)
    {
        return Mage::getStoreConfig('seo/info/alt_link_info', $storeId);
    }

    public function isShowTemplatesRewriteInfo($storeId = null)
    {
        return Mage::getStoreConfig('seo/info/templates_rewrite_info', $storeId);
    }

    protected function _isInfoAllowed($storeId = null)
    {
        $ips = Mage::getStoreConfig('seo/info/allowed_ip', $storeId);
        if ($ips == '') {
            return true;
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $clientIp = $_SERVER['REMOTE_ADDR'];
        }

        if (!$clientIp) {
            return false;
        }
        $ips = explode(',', $ips);
        $ips = array_map('trim',$ips);


        return in_array($clientIp, $ips);
    }
}
