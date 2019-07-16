<?php
/**
 * @author    Danail Kyosev <ddkyosev@gmail.com>
 * @copyright 2014, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Infomodus_Dhllabel_Model_Src_Response_GetQuoteResponse
{
    private $parsed;
    private $prices;
    private $errors;

    /**
     * A quote response can have more that one shipping option with different price rates
     * @param Partials\Price[] $prices
     */
    public function setPrices($prices)
    {
        $this->prices = $prices;

        return $this;
    }

    /**
     * A quote response can have more that one shipping option with different price rates
     * @return Partials\Price[]
     */
    public function getPrices()
    {
        return $this->prices;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Parses a quote response XML and provides price information
     * @param string $data XML response from Infomodus_Dhllabel_Model_Src_Request_GetQuoteRequest
     * @todo add error response handling
     */
    public function __construct($data)
    {
        $this->parsed = simplexml_load_string(utf8_encode($data));

        $this->populatePrices();
    }

    private function populatePrices()
    {
        if($this->parsed->GetQuoteResponse && $this->parsed->GetQuoteResponse->BkgDetails && $this->parsed->GetQuoteResponse->BkgDetails->QtdShp) {
            $qtdShp = $this->parsed->GetQuoteResponse->BkgDetails->QtdShp;
            $prices = array();
            foreach ($qtdShp as $type) {
                /*if ($type->TransInd == 'Y') {*/
                    $prices[] = new Infomodus_Dhllabel_Model_Src_Response_Partials_Price($type);
                    $this->setPrices($prices);
                /*}*/
            }
        } else {
            $this->errors = array($this->xml2array($this->parsed));
        }
    }

    public function xml2array($xmlObject, $out = array())
    {
        foreach ((array)$xmlObject as $index => $node) {
            $out[$index] = (is_object($node)) ? $this->xml2array($node) : (is_array($node)) ? $this->xml2array($node) : $node;
        }
        return $out;
    }
}
