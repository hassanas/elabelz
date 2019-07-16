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


class Mirasvit_Seo_Helper_Snippets extends Mage_Core_Helper_Abstract
{
    public function prepareDimensionCode($code)
    {
        $validCodes = array(
            'dm' => 'DMT',
            'decimetre' => 'DMT',
            'cm' => 'CMT',
            'centimetre' => 'CMT',
            'mm' => 'MMT',
            'millimetre' => 'MMT',
            'hm' => 'HMT',
            'hectometre' => 'HMT',
            'nm' => 'C45',
            'nanometre' => 'C45',
            'dam' => 'A45',
            'decametre' => 'A45',
            'fth' => 'AK',
            'fathom' => 'AK',
            'in' => 'INH',
            'inch' => 'INH',
            'ft' => 'FOT',
            'foot' => 'FOT',
            'yd' => 'YRD',
            'yard' => 'YRD',
            'fur' => 'M50',
            'furlong' => 'M50',
        );

        $code = strtolower($code);
        if (isset($validCodes[$code])) {
            return $validCodes[$code];
        }

        $code = strtoupper($code);
        if (in_array($code, $validCodes)) {
            return $code;
        }

        return false;
    }
}
