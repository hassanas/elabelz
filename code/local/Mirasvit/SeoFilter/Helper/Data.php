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


/**
* This file is part of the Mirasvit_SeoFilter project.
*
* Mirasvit_SeoFilter is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License version 3 as
* published by the Free Software Foundation.
*
* This script is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
* FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
*
* PHP version 5
*
* @category Mirasvit_SeoFilter
* @package Mirasvit_SeoFilter
* @author Michael Türk <tuerk@flagbit.de>
* @copyright 2012 Flagbit GmbH & Co. KG (http://www.flagbit.de). All rights served.
* @license http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
* @version 0.1.0
* @since 0.1.0
*/
/**
 * Helper for simple normalization of strings and translation issues
 *
 * @category Mirasvit_SeoFilter
 * @package Mirasvit_SeoFilter
 * @author Michael Türk <tuerk@flagbit.de>
 * @copyright 2012 Flagbit GmbH & Co. KG (http://www.flagbit.de). All rights served.
 * @license http://opensource.org/licenses/gpl-3.0 GNU General Public License, version 3 (GPLv3)
 * @version 0.1.0
 * @since 0.1.0
 */
class Mirasvit_SeoFilter_Helper_Data extends Mage_Core_Helper_Abstract
{
   /**
	 * normalize Characters
	 * Example: ü -> ue
	 *
	 * @param string $string
	 * @return string
	 */
	public function normalize($string)
	{
	    $table = array(
	        'Š'=>'S',  'š'=>'s',  'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z',  'ž'=>'z',  'Č'=>'C',  'č'=>'c',  'Ć'=>'C',  'ć'=>'c',
	        'À'=>'A',  'Á'=>'A',  'Â'=>'A',  'Ã'=>'A',  'Ä'=>'Ae', 'Å'=>'A',  'Æ'=>'A',  'Ç'=>'C',  'È'=>'E',  'É'=>'E',
	        'Ê'=>'E',  'Ë'=>'E',  'Ì'=>'I',  'Í'=>'I',  'Î'=>'I',  'Ï'=>'I',  'Ñ'=>'N',  'Ò'=>'O',  'Ó'=>'O',  'Ô'=>'O',
	        'Õ'=>'O',  'Ö'=>'Oe', 'Ø'=>'O',  'Ù'=>'U',  'Ú'=>'U',  'Û'=>'U',  'Ü'=>'Ue', 'Ý'=>'Y',  'Þ'=>'B',  'ß'=>'Ss',
	        'à'=>'a',  'á'=>'a',  'â'=>'a',  'ã'=>'a',  'ä'=>'ae', 'å'=>'a',  'æ'=>'a',  'ç'=>'c',  'è'=>'e',  'é'=>'e',
	        'ê'=>'e',  'ë'=>'e',  'ì'=>'i',  'í'=>'i',  'î'=>'i',  'ï'=>'i',  'ð'=>'o',  'ñ'=>'n',  'ò'=>'o',  'ó'=>'o',
	        'ô'=>'o',  'õ'=>'o',  'ö'=>'oe', 'ø'=>'o',  'ù'=>'u',  'ú'=>'u',  'û'=>'u',  'ý'=>'y',  'ý'=>'y',  'þ'=>'b',
	        'ÿ'=>'y',  'Ŕ'=>'R',  'ŕ'=>'r',  'ü'=>'ue', '/'=>'',   '-'=>'',   '&'=>'',   ' '=>'',   '('=>'',   ')'=>''
	    );

	    $string = strtr($string, $table);
	    $string = Mage::getSingleton('catalog/product_url')->formatUrlKey($string);
	    $string = str_replace(array('-'), '', $string); //убираем системные разделители частей урла
	    return $string;
	}
}