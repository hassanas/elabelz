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


class Mirasvit_Seo_Model_System_Config_Observer extends Varien_Object
{
     /**
     * Info about Hreflang locale code
     */
    public function hreflangLocaleCodeInfo($e)
    {
        //ISO 3166-1 Alpha 2  http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
        $localeCodes = array('AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR',
            'AS', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH',
            'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY',
            'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO',
            'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ',
            'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
            'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ',
            'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID',
            'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP',
            'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB',
            'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME',
            'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT',
            'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL',
            'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL',
            'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN',
            'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH',
            'TJ', 'TK', 'TLa', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG',
            'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS',
            'YE', 'YT', 'ZA', 'ZM', 'ZW');


        $controllreAction = $e->getEvent()->getControllerAction();
        if (!$controllreAction) {
            return;
        }
        $params = $controllreAction->getRequest()->getParams();
        if (isset($params['section']) && $params['section'] == 'seo'
            && ($data = $controllreAction->getRequest()->getPost('groups'))
            && isset($data['general']['fields']['hreflang_locale_code']['value'])) {
                $localeCodeValue = trim($data['general']['fields']['hreflang_locale_code']['value']);
                if ($localeCodeValue && !in_array($localeCodeValue, $localeCodes)
                    && !in_array(strtoupper($localeCodeValue), $localeCodes)) {
                    Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('seo')->__('Wrong Hreflang locale code value: "' . $localeCodeValue . '".'
                        . ' Need to use the <a target="_blank" href="http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2">ISO 3166-1 Alpha 2 format</a>'));
                }
        }

        return $this;
    }

    /**
     * Info about Max Length for Meta Title and Max Length for Meta Description
     */
    public function maxLengthInfo($e)
    {
        $controllreAction = $e->getEvent()->getControllerAction();
        if (!$controllreAction) {
            return;
        }

        $seoSection = false;
        $params     = $controllreAction->getRequest()->getParams();
        if (isset($params['section']) && $params['section'] == 'seo'
            && ($data = $controllreAction->getRequest()->getPost('groups')) ) {
                $seoSection = true;
        }
        if ($seoSection
            && isset($data['extended']['fields']['meta_title_max_length']['value'])
            && ($metaTitleMaxLength = trim($data['extended']['fields']['meta_title_max_length']['value']))) {
                if (ctype_digit($metaTitleMaxLength) && (int)$metaTitleMaxLength < Mirasvit_Seo_Model_Config::META_TITLE_INCORRECT_LENGTH) {
                    Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('seo')->__('"Max Length for Meta Title" value: "' . $metaTitleMaxLength
                        . '" less then ' . Mirasvit_Seo_Model_Config::META_TITLE_INCORRECT_LENGTH
                        . '. Will be used default value "' . Mirasvit_Seo_Model_Config::META_TITLE_MAX_LENGTH . '".'));
                } elseif(!ctype_digit($metaTitleMaxLength)) {
                    $metaTitleInfo = 'Wrong "Max Length for Meta Title" value: "' . $metaTitleMaxLength . '".'
                                        . ' Have to be integer value.';
                    $metaTitleMaxLength = (int)$metaTitleMaxLength;
                    if ($metaTitleMaxLength <  Mirasvit_Seo_Model_Config::META_TITLE_INCORRECT_LENGTH) {
                        $metaTitleInfo .= ' Will be used recommended value "' . Mirasvit_Seo_Model_Config::META_TITLE_MAX_LENGTH . '".';
                    } else {
                        $metaTitleInfo .= ' Will be used value "' . $metaTitleMaxLength . '".';
                    }
                    Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('seo')->__($metaTitleInfo));
                }
        }

        if ($seoSection
            && isset($data['extended']['fields']['meta_title_max_length']['value'])
            && ($metaDescriptionMaxLength = trim($data['extended']['fields']['meta_description_max_length']['value']))) {
                if (ctype_digit($metaDescriptionMaxLength) && (int)$metaDescriptionMaxLength < Mirasvit_Seo_Model_Config::META_DESCRIPTION_INCORRECT_LENGTH) {
                    Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('seo')->__('"Max Length for Meta Description" value: "' . $metaDescriptionMaxLength
                        . '" less then ' . Mirasvit_Seo_Model_Config::META_DESCRIPTION_INCORRECT_LENGTH
                        . '. Will be used default value "' . Mirasvit_Seo_Model_Config::META_DESCRIPTION_MAX_LENGTH . '".'));
                } elseif (!ctype_digit($metaDescriptionMaxLength)) {
                    $metaDescriptionInfo = 'Wrong "Max Length for Meta Description" value: "' . $metaDescriptionMaxLength . '".'
                                            . ' Have to be integer value.';
                    $metaDescriptionMaxLength = (int)$metaDescriptionMaxLength;
                    if ($metaDescriptionMaxLength <  Mirasvit_Seo_Model_Config::META_DESCRIPTION_INCORRECT_LENGTH) {
                        $metaDescriptionInfo .= ' Will be used recommended value "' . Mirasvit_Seo_Model_Config::META_DESCRIPTION_MAX_LENGTH . '".';
                    } else {
                        $metaDescriptionInfo .= ' Will be used value "' . $metaDescriptionMaxLength . '".';
                    }
                    Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('seo')->__($metaDescriptionInfo));
                }
        }

        return $this;
    }

    /**
     * Info about reindex Catalog URL Rewrites
     */
    public function categoryUrlFormatInfo($e) {
        $controllreAction = $e->getEvent()->getControllerAction();
        if (!$controllreAction) {
            return;
        }

        $params = $controllreAction->getRequest()->getParams();

        if (isset($params['section']) && $params['section'] == 'seo'
            && isset($params['groups']['url']['fields']['category_url_format']['value'])
            && in_array($params['groups']['url']['fields']['category_url_format']['value'], array(0,1))) {
                $storeCode = $controllreAction->getRequest()->getParam('store');
                $websiteCode = $controllreAction->getRequest()->getParam('website');
                $storeId = 0;

                if ($storeCode && $websiteCode) {
                    $storeId = Mage::getModel('core/store')->load($storeCode)->getId();
                } elseif (!$storeCode && $websiteCode) {
                    $websiteId = Mage::getModel('core/website')->load($websiteCode)->getId();
                    $storeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
                }

                $isUrlFormat = $this->getConfig()->isCategoryUrlFormatEnabled($storeId);

                if(in_array($isUrlFormat, array(0,1)) && $isUrlFormat != $params['groups']['url']['fields']['category_url_format']['value']) {
                    $this->addCategoryUrlFormatInfoMessage($isUrlFormat);
                }
        }
    }

    public function addCategoryUrlFormatInfoMessage($isUrlFormat) {
        if ($isUrlFormat) {
            $message = '"Remove Parent Category Path for Category URLs" was disabled. To apply need Reindex Catalog URL Rewrites.';
        } else {
            $message = 'To apply "Remove Parent Category Path for Category URLs" need Reindex Catalog URL Rewrites. Before enabling <a href="' . Mage::helper('adminhtml')->getUrl('*/seo_system_checkDuplicate/index') . '" target="_blank">check duplicate urls</a>';
        }
        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('seo')->__($message));
    }

    public function getConfig()
    {
        return Mage::getSingleton('seo/config');
    }
}