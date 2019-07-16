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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Aramex
 * @package     Aramex_Shipping
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Aramex_Shipping_Model_Carrier_Aramex_Source_InternationalAdditionalServices
{
    public function toOptionArray()
    {        
        $arr[] = array('value'=>'AM10', 'label'=>'Morning delivery');
		$arr[] = array('value'=>'CODS', 'label'=>'Cash On Delivery');
		$arr[] = array('value'=>'CSTM', 'label'=>'CSTM');
		$arr[] = array('value'=>'EUCO', 'label'=>'NULL');
		$arr[] = array('value'=>'FDAC', 'label'=>'FDAC');
		
		/*$arr[] = array('value'=>'FRD1', 'label'=>'Free Domicile');*/
		$arr[] = array('value'=>'FRDM', 'label'=>'FRDM');
		$arr[] = array('value'=>'INSR', 'label'=>'Insurance');
		$arr[] = array('value'=>'NOON', 'label'=>'Noon Delivery');
		$arr[] = array('value'=>'ODDS', 'label'=>'Over Size');
		
		$arr[] = array('value'=>'RTRN', 'label'=>'RTRN');
		$arr[] = array('value'=>'SIGR', 'label'=>'Signature Required');
		$arr[] = array('value'=>'SPCL', 'label'=>'Special Services');
		
		
		
        return $arr;
    }
}
