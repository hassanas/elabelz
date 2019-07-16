<?php
/**
 * Atwix
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
 * @category    Atwix Mod
 * @package     Atwix_Unserialize
 * @author      Atwix Core Team
 * @copyright   Copyright (c) 2016 Atwix (http://www.atwix.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Atwix_Unserialize_Helper_Core_Unserializearray extends Mage_Core_Helper_UnserializeArray
{
    /**
     * Borrowed from wordpress/wp-includes/functions.php
     *
     * @param $data
     * @return bool
     */
    function isSerialized($data) {
        // if it isn't a string, it isn't serialized
        if ( ! is_string( $data ) )
            return false;
        $data = trim( $data );
        if ( 'N;' == $data )
            return true;
        $length = strlen( $data );
        if ( $length < 4 )
            return false;
        if ( ':' !== $data[1] )
            return false;
        $lastc = $data[$length-1];
        if ( ';' !== $lastc && '}' !== $lastc )
            return false;
        $token = $data[0];
        switch ( $token ) {
            case 's' :
                if ( '"' !== $data[$length-2] )
                    return false;
            case 'a' :
            case 'O' :
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b' :
            case 'i' :
            case 'd' :
                return (bool) preg_match( "/^{$token}:[0-9.E-]+;\$/", $data );
        }
        return false;
    }

    /**
     * We won't unserialize string if it is not serialized
     *
     * @param string $str
     * @return array
     * @throws Exception
     */
    public function unserialize($str)
    {
        if($this->isSerialized($str)) {
            Mage::log('Enter in ext if',null,'cartEmpty.log');
            return parent::unserialize($str);
        }
        Mage::log('Enter in ext wif',null,'cartEmpty.log');
        return $str;
    }
}