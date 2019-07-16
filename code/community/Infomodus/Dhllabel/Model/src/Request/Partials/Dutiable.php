<?php
/**
 * @author    Vitalij Rudyuk <rvansp@gmail.com>
 * @copyright 2014
 */
class Infomodus_Dhllabel_Model_Src_Request_Partials_Dutiable extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'DeclaredValue' => null,
        'DeclaredCurrency' => null,
        'TermsOfTrade' => null
    );

    public function setDeclaredValue($declaredValue)
    {
        $this->required['DeclaredValue'] = $declaredValue;

        return $this;
    }

    public function setDeclaredCurrency($declaredCurrency)
    {
        $this->required['DeclaredCurrency'] = $declaredCurrency;

        return $this;
    }

    public function setTermsOfTrade($declaredCurrency)
    {
        $this->required['TermsOfTrade'] = $declaredCurrency;

        return $this;
    }
}
