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



class Mirasvit_SeoAutolink_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MAX_NUMBER = 999999;

    protected $_isSkipLinks;
    protected $_sizeExplode             = 0;
    protected $_isExcludedTags          = true;
    protected $_maxLinkPerPage          = false;
    protected $_addedLinkSum            = false;
    protected $_replacementsCountGlobal = array();


    protected $debug = array();
    protected $currentNumberOfLinks = 0;
    protected $isStopReplacement = false;

    protected function getConfig()
    {
        return Mage::getSingleton('seoautolink/config');
    }

    /**
     * Returns value of setting "Links limit per page"
     *
     * @return int
     */
    public function getMaxLinkPerPage()
    {
        return $this->getConfig()->getLinksLimitPerPage(Mage::app()->getStore()->getId());
    }


    /**
     * Returns collection of links with keywords which present in our text.
     * Not ALL possible links.
     * try get links with newer query, if returns SQLERROR
     * (for older Magento like 1.4 and specific MySQL configurations) -
     * get links with older query for backward compatibility
     *
     * @param string $text
     * @return Mirasvit_SeoAutolink_Model_Link[]
     */
    public function getLinks($text)
    {
        Varien_Profiler::start('seoautolink_getLinks');

        $textArrayWithMaxSymbols = $this->splitText($text); //return array
        $where = array();
        foreach ($textArrayWithMaxSymbols as $splitTextVal) {
            $where[] = "lower('".addslashes($splitTextVal)."') LIKE CONCAT("."'%'".', lower(keyword), '."'%'".')';
        }

        $links = $this->getLinksCollection();
        $links->getSelect()->where(implode(' OR ', $where))->order('sort_order ASC');

        try {
            count($links); //need to load collection to catch SQLERROR if occured
        } catch (Exception $e) {
            $links = $this->getLinksCollection();
            $links->getSelect()->where("lower(?) LIKE CONCAT('%', lower(keyword), '%')", $text)
                               ->order( array('LENGTH(main_table.keyword) desc') ); //we need to replace long keywords firstly
        }
        Varien_Profiler::stop('seoautolink_getLinks');

        return $links;
    }

    //prepare collection acceptable for both variants of SQL queries.
    private function getLinksCollection()
    {
      $links = Mage::getModel('seoautolink/link')
                    ->getCollection()
                    ->addActiveFilter()
                    ->addStoreFilter(Mage::app()->getStore())
                    ;
      return $links;
    }

    /**
     * Replace special chars in text to their altenatives
     *
     * @param string $source
     * @return string
     */
    public function replaceSpecialCharacters($source)
    {
        // substitute some special html characters with their 'real' value
        $searchTamp = array('&amp;Eacute;',
                            '&amp;Euml;',
                            '&amp;Oacute;',
                            '&amp;Oslash;',
                            '&amp;oslash;',
                            '&amp;Aelig;',
                            '&amp;aelig;',
                            '&amp;Acirc;',
                            '&amp;acirc;',
                            '&amp;eacute;',
                            '&amp;euml;',
                            '&amp;oacute;',
                            '&amp;Agrave;',
                            '&amp;Egrave;',
                            '&amp;Igrave;',
                            '&amp;Iacute;',
                            '&amp;Iuml;',
                            '&amp;Ograve;',
                            '&amp;Ugrave;',
                            '&amp;agrave;',
                            '&amp;egrave;',
                            '&amp;igrave;',
                            '&amp;iacute;',
                            '&amp;iuml;',
                            '&amp;ograve;',
                            '&amp;ugrave;',
                            '&amp;Ccedil;',
                            '&amp;ccedil;',
                            '&amp;ecirc;',
                           );
        $replaceTamp = array('É',
                             'Ë',
                             'Ó',
                             'Ø',
                             'ø',
                             'Æ',
                             'æ',
                             'Å',
                             'å',
                             'é',
                             'ë',
                             'ó',
                             'À',
                             'È',
                             'Ì',
                             'Í',
                             'Ï',
                             'Ò',
                             'Ù',
                             'à',
                             'è',
                             'ì',
                             'í',
                             'ï',
                             'ò',
                             'ù',
                             'Ç',
                             'ç',
                             'ê',
                            );
        $searchT = array('&Eacute;',
                         '&Euml;',
                         '&Oacute;',
                         '&Oslash;',
                         '&oslash;',
                         '&Aelig;',
                         '&aelig;',
                         '&Acirc;',
                         '&acirc;',
                         '&eacute;',
                         '&euml;',
                         '&oacute;',
                         '&Agrave;',
                         '&Egrave;',
                         '&Igrave;',
                         '&Iacute;',
                         '&Iuml;',
                         '&Ograve;',
                         '&Ugrave;',
                         '&agrave;',
                         '&egrave;',
                         '&igrave;',
                         '&iacute;',
                         '&iuml;',
                         '&ograve;',
                         '&ugrave;',
                         '&Ccedil;',
                         '&ccedil;',
                        );
        $replaceT = array('É',
                          'Ë',
                          'Ó',
                          'Ø',
                          'ø',
                          'Æ',
                          'æ',
                          'Å',
                          'å',
                          'é',
                          'ë',
                          'ó',
                          'À',
                          'È',
                          'Ì',
                          'Í',
                          'Ï',
                          'Ò',
                          'Ù',
                          'à',
                          'è',
                          'ì',
                          'í',
                          'ï',
                          'ò',
                          'ù',
                          'Ç',
                          'ç',
                          );

        $source = $this->str_replace($searchTamp, $replaceTamp, $source);
        $source = $this->str_replace($searchT, $replaceT, $source);

        return $source;
    }

    /**
     * Main entry point. Inserts links into text.
     *
     * @param string $text
     *
     * @return string
     */
    public function addLinks($text)
    {
        if (strpos(Mage::helper('core/url')->getCurrentUrl(), '/checkout/onepage/')
            || strpos(Mage::helper('core/url')->getCurrentUrl(), 'onestepcheckout')) {
            return $text;
        }

        if ($this->checkSkipLinks() === true) {
            return $text;
        }
        $this->debug['links'] = array();

        $text = $this->replaceSpecialCharacters($text);
        $links = $this->getLinks($text);
        $text = $this->_addLinks($text, $links);
        $text = $this->getDebugMessage().$text;
        return $text;
    }

    /**
     * Inserts links into text
     *
     * @param string $text
     * @param array $links
     * @param bool|int $replacementCountForTests - max number of replaced words. used only for tests.
     * @return string
     */
    public function _addLinks($text, $links, $replacementCountForTests = false)
    {
        if (!$links || count($links) == 0) {
            return $text;
        }

        Varien_Profiler::start('seoautolink_addLinks');


        foreach ($links  as $link) {
            $replaceKeyword = preg_quote($link->getKeyword()); // Escaping special characters in a keyword
            $this->debug['current_link'] = $link;
            $urltitle = $link->getUrlTitle() ? "title='{$link->getUrlTitle()}' " : '';
            $nofollow = $link->getIsNofollow() ? 'rel=\'nofollow\' ' : '';
            $target = $link->getUrlTarget() ? "target='{$link->getUrlTarget()}' " : '';
            $html = "<a href='{$link->getUrl()}' {$urltitle}{$target}{$nofollow}class='autolink' >{$link->getKeyword()}</a>";

            $maxReplacements = self::MAX_NUMBER;
            if ($link->getMaxReplacements() > 0) {
                $maxReplacements = $link->getMaxReplacements();
            }
            if ($replacementCountForTests) { //for tests
                $maxReplacements = $replacementCountForTests;
            }

            $direction = 0;
            switch ($link->getOccurence()) {
                case Mirasvit_SeoAutolink_Model_Config_Source_Occurence::FIRST:
                    $direction = 0;
                    break;
                case Mirasvit_SeoAutolink_Model_Config_Source_Occurence::LAST:
                    $direction = 1;
                    break;
                case Mirasvit_SeoAutolink_Model_Config_Source_Occurence::RANDOM:
                    $direction = rand(0, 1);
                    break;
            }

            $text = $this->replace($html, $text, $maxReplacements, $replaceKeyword, $direction);
            $this->debug['links'][] = $this->debug['current_link'];
        }
        Varien_Profiler::stop('seoautolink_addLinks');

        return $text;
    }

    /**
     * Returns array of patterns, which will be used to find and replace keywords
     *
     * @return array
     */
    protected function getPatterns()
    {
        $patternsForExclude = $this->getExcludedAutoTags();

          // matches for these expressions will be replaced with a unique placeholder
        $preg_patterns = array(
              '#<!--.*?-->#s'       // html comments
            , '#<a[^>]*>.*?</a>#si' // html links
            , '#<[^>]+>#',           // generic html tag
            //~ , '#&[^;]+;#'           // special html characters
            //~ , '#[^ÉËÓéëóÀÈÌÍÏÒÙàèìíïòùÇç\w\s]+#'   // all non alfanumeric characters, spaces and some special characters
        );

        if ($patternsForExclude) {
            $preg_patterns = array_merge($patternsForExclude, $preg_patterns);
        }

        return $preg_patterns;
    }


    // reconstruct the original text
    protected function _restoreSurceByTranslationTable($translation_table, $source) {
        foreach ($translation_table as $key => $value) {
            $source = $this->str_replace($key, $value, $source);
        }

        return $source;
    }

    /**
     * Replace words and left the same cases
     *
     * @param string $replace - html which will replace the keyword
     * @param string $source - initial text
     * @param int $maxReplacements - max number of replacements in this text.
     * @param bool $replaceKeyword - keyword which will be replaced
     * @param bool $direct - replace direction (from begin or from end of the text)
     * @return string
     */
    protected function replace($replace, $source, $maxReplacements, $replaceKeyword = false, $direct = false)
    {
        if ($maxReplacements > 0 && $this->getRelpacementCount($replaceKeyword) > $maxReplacements) {
           return $source;
        }

        $maxReplacements -= $this->getRelpacementCount($replaceKeyword);

        // $source = iconv('UTF-8', 'ASCII//TRANSLIT', $source);  // for stores with wrong encoding
        // $source = $this->str_replace('<p> </p>', '<p></p><br/>', $source);

        $preg_patterns = $this->getPatterns();

        $pl = new Mirasvit_TextPlaceholder($source, $preg_patterns);
        // raw text, void of any html.

        $source = $pl->get_tokenized_text();
        preg_match_all('/'.$replaceKeyword.'/i', $source, $replaceKeywordVariations, PREG_OFFSET_CAPTURE);

        // we will later need this to put the html we stripped out, back in.
        $translation_table = $pl->get_translation_table();
        // reconstruct the original text (now with links)
        $source = $this->_restoreSurceByTranslationTable($translation_table, $source);

        if (strpos($source, 'xkjndsfkjnakcx') !== false && strpos($source, 'cxmkmweof329jc') !== false) { //reconstruct the original text if was replacement inside block (double check)
            $source = $this->_restoreSurceByTranslationTable($translation_table, $source);
        }

        if (isset($replaceKeywordVariations[0])) {
            $keywordVariations = $replaceKeywordVariations[0];
            if (!empty($keywordVariations)) {
                if ($direct == 1) {
                    $keywordVariations = array_slice($keywordVariations, -$maxReplacements);
                } else {
                    $keywordVariations = array_slice($keywordVariations, 0, $maxReplacements);
                }
                foreach ($keywordVariations as $keywordValue) {
                    $pl = new Mirasvit_TextPlaceholder($source, $preg_patterns);
                    $source = $pl->get_tokenized_text();

                    $replaceForVariation = preg_replace('/(\\<a.*?\\>)(.*?)(\\<\\/a\\>)/', $this->prepareReplacement($keywordValue[0]), $replace);
                    $source = $this->addLinksToSource($maxReplacements, $direct, $source, $keywordValue[0], $replaceForVariation);


                    $translation_table = $pl->get_translation_table();
                    $source = $this->_restoreSurceByTranslationTable($translation_table, $source);
                }
                $this->_sizeExplode = 0;
            }
        }

        return $source;
    }

    protected function _mbSubstr($keyword) {
      if (function_exists('mb_substr')) {
        return mb_substr($keyword, 0, 1);
      }

      return $this->substr($keyword, 0, 1);
    }

    public function prepareReplacement($keyword)
    {
        if(is_numeric($this->_mbSubstr($keyword))) {
            $replacement = "$1 $keyword $3";
        } else {
            $replacement = '$1' . $keyword . '$3';
        }

        return $replacement;
    }

    /**
     * @param int $maxReplacements - maximum allowed number of replacements
     * @param int $direct - direction
     * @param string $source - initial text
     * @param string $replaceKeyword - this keyword will be replaced
     * @param string $replace -  this text will replace the keyword
     * @param bool $replaceNumber - ?
     * @return string
     */
    public function addLinksToSource($maxReplacements, $direct, $source, $replaceKeyword, $replace)
    {
        $originalReplaceKeyword = $replaceKeyword;
        if ($direct == 1) {
            $source = strrev($source);
            $replaceKeyword = strrev($replaceKeyword);
            $replace = strrev($replace);
        }
        $explodeSource = explode($replaceKeyword, $source); // explode text
        $nextSymbol = array('',' ', ',', '.', '!', '?', ')', "\n", "\r", "\r\n"); // symbols after the word
        $prevSymbol = array(',',' ', '(', "\n", "\r", "\r\n"); // symbols before the word
        $nextTextPatternArray = array('(.*?)&nbsp;$', '(.*?)&lt;span&gt;$');    // text pattern after the word
        $prevTextPatternArray = array('^&nbsp;(.*?)', '^&lt;\/span&gt;(.*?)'); // text pattern before the word
        $nextPattern = '/' . implode('|', $nextTextPatternArray) . '/';
        $prevPattern = '/' . implode('|', $prevTextPatternArray) . '/';

        $sizeExplodeSource = count($explodeSource);
        $size = 0;
        $prepareSourse = '';


        $replaceNumberOne = false;

        $numberOfReplacements = 0;
        $isStopReplacement = false;
        foreach ($explodeSource as $keySource => $valSource) {
            $size++;

            // maxReplacements for written letters
            if (!$isStopReplacement && ($size < $sizeExplodeSource)
                && ($direct == 0)
                && (((!empty($explodeSource[$keySource + 1][0]))
                    && (in_array($explodeSource[$keySource + 1][0], $nextSymbol)))
                        || (preg_match($prevPattern, $explodeSource[$keySource + 1]))
                        || (empty($explodeSource[$keySource + 1][0])))
                && ((empty($explodeSource[$keySource][strlen($explodeSource[$keySource]) - 1]))
                    || (preg_match($nextPattern, $explodeSource[$keySource]))
                    || ((!empty($explodeSource[$keySource][strlen($explodeSource[$keySource]) - 1]))
                        && (in_array($explodeSource[$keySource][strlen($explodeSource[$keySource]) - 1], $prevSymbol))))
                && ($this->_sizeExplode < $maxReplacements)
                && !$replaceNumberOne) {
                $prepareSourse .= $valSource.$replace;
                $this->_sizeExplode++;
                $replaceNumberOne = true;
                $numberOfReplacements++;
            } elseif (!$isStopReplacement && ($size < $sizeExplodeSource)
                  && ($direct == 1)
                  && (((!empty($explodeSource[$keySource][strlen($explodeSource[$keySource]) - 1]))
                      && (in_array($explodeSource[$keySource][strlen($explodeSource[$keySource]) - 1], $nextSymbol)))
                          || (preg_match($prevPattern, $explodeSource[$keySource]))
                          || (empty($explodeSource[$keySource][strlen($explodeSource[$keySource]) - 1])))
                  && ((empty($explodeSource[$keySource + 1][0]))
                      || (preg_match($nextPattern, $explodeSource[$keySource + 1]))
                      || ((!empty($explodeSource[$keySource + 1][0]))
                        && (in_array($explodeSource[$keySource + 1][0], $prevSymbol))))
                  && ($this->_sizeExplode < $maxReplacements)
                  && !$replaceNumberOne) {
                $prepareSourse .= $valSource.$replace;
                $this->_sizeExplode++;
                $replaceNumberOne = true;
                $numberOfReplacements++;
            } elseif ($size < $sizeExplodeSource) {
                $prepareSourse .= $valSource.$replaceKeyword;
            } else {
                $prepareSourse .= $valSource;
            }

            if ($this->currentNumberOfLinks +  $numberOfReplacements == $this->getMaxLinkPerPage()) {
                $isStopReplacement = true;
            }
        }

        $this->addReplacementCount($originalReplaceKeyword, $numberOfReplacements); //to use maxReplacements  the desired number of times
        $this->currentNumberOfLinks = $this->currentNumberOfLinks +  $numberOfReplacements;

        if ($direct == 1) {
            $prepareSourse = strrev($prepareSourse);
        }
        // if we use $maxReplacements is set, we may replace the same keyword with several calls if this functions
        // that's why we have to sum numbers.
        $this->debug['current_link']->setActualNumberOfReplacements(
            $this->debug['current_link']->getActualNumberOfReplacements() + $numberOfReplacements
        );
        return $prepareSourse;
    }

    /**
     * Get number of already done replacements for word on the page globally
     *
     * @param string $keyword
     * @return int
     */
    protected function getRelpacementCount($keyword) {
        if (!isset($this->_replacementsCountGlobal[strtolower($keyword)])) {
            $this->_replacementsCountGlobal[strtolower($keyword)] = 0;
        }
        return $this->_replacementsCountGlobal[strtolower($keyword)];
    }

    /**
     * Increase number of already done replacements for word on the page globally
     *
     * @param string $keyword
     * @param int $cnt
     * @return void
     */
    protected function addReplacementCount($keyword, $cnt){
        if (!isset($this->_replacementsCountGlobal[strtolower($keyword)])) {
            $this->_replacementsCountGlobal[strtolower($keyword)] = 0;
        }
        $this->_replacementsCountGlobal[strtolower($keyword)] += $cnt;
    }

    /**
     * Split text to array to create the sql query
     *
     * @param string $text
     * @return array
     */
    public function splitText($text)
    {
        $maxTextSymbols = 1000; //number of characters for split the text
        $numberReturnWords = 5;      //number of words which will in every part of the split text
        $textSymbolsCount = iconv_strlen($text);
        if ($textSymbolsCount > $maxTextSymbols) {
            $selectNumber = ceil($textSymbolsCount / $maxTextSymbols);
        }

        $textArrayWithMaxSymbols = array();
        if (isset($selectNumber)) {
            $textArray = str_split($text, $maxTextSymbols);
            foreach ($textArray as $textKey => $textVal) {
                if ($textKey == 0) {
                    $keyBefore = $textKey;
                    $textArrayWithMaxSymbols[$textKey] = $textVal;
                } else {
                    $currentText = explode(' ', $textVal, $numberReturnWords);
                    if (count($currentText) == $numberReturnWords) {
                        $currentTextShift = $currentText;
                        array_shift($currentTextShift);
                        $textArrayWithMaxSymbols[$textKey] = implode(' ', $currentTextShift);
                        $currentTextPop = $currentText;
                        array_pop($currentTextPop);
                        $textArrayWithMaxSymbols[$keyBefore] .=  implode(' ', $currentTextPop);
                        $keyBefore = $textKey;
                    } else {
                        $textArrayWithMaxSymbols[$textKey] = implode(' ', $currentText);
                    }
                }
            }
        }

        if (empty($textArrayWithMaxSymbols)) {
            $textArrayWithMaxSymbols[] = $text;
        }

        return $textArrayWithMaxSymbols;
    }

    /*
    * analog of ctype_alnum, but with support of Cyrillic
    */
    public function is_alnum($string)
    {
        return (bool) preg_match("/^[a-zA-Z\p{Cyrillic}0-9]+$/u", $string);
    }

    public function strlen($string)
    {
        return Mage::helper('core/string')->strlen($string);
    }

    public function substr($string, $offset, $length = null)
    {
        return Mage::helper('core/string')->substr($string, $offset, $length);
    }

    public function substr_replace($output, $replace, $posOpen, $posClose)
    {
        return substr_replace($output, $replace, $posOpen, $posClose);
    }

    public function stripos($source, $find, $pos = null)
    {
        if (extension_loaded('mbstring')) {
            $pos = Mage::helper('core/string')->strpos(mb_convert_case($source, MB_CASE_LOWER, 'UTF-8'),  mb_convert_case($find, MB_CASE_LOWER, 'UTF-8'), $pos);
        } else {
            $pos = stripos($source, $find, $pos);
        }

        return $pos;
    }

    public function get_char($source, $pos)
    {
        if ($pos < 0 || $pos >= $this->strlen($source)) {
            return false;
        }

        return $this->substr($source, $pos, 1);
    }

    public function str_replace($search, $replace, $source)
    {
        return str_replace($search, $replace, $source);
    }

    public function strrev($string)
    {
        return Mage::helper('core/string')->strrev($string);
    }

    public function checkSkipLinks()
    {
        if ($this->_isSkipLinks === false) {
            return false;
        }
        if (!$skipLinks = Mage::registry('skip_auto_links')) {
            $skipLinks = $this->getConfig()->getSkipLinks(Mage::app()->getStore()->getStoreId());
            if ($skipLinks) {
                Mage::register('skip_auto_links', $skipLinks);
            } else {
                $this->_isSkipLinks = false;
            }
        }
        if (Mage::helper('seoautolink/pattern')->checkArrayPattern(
                    Mage::getSingleton('core/url')->parseUrl(Mage::helper('core/url')->getCurrentUrl())->getPath(),
                    $skipLinks
                )
            ) {
            $this->_isSkipLinks = true;

            return true;
        }

        $this->_isSkipLinks = false;

        return false;
    }

    public function getExcludedAutoTags()
    {
        if (!Mage::registry('excluded_auto_links_tags') && $this->_isExcludedTags) {
            $excludedTags = $this->getConfig()->getExcludedTags(Mage::app()->getStore()->getId());
            if ($excludedTags) {
                Mage::register('excluded_auto_links_tags', $excludedTags);
            } else {
                $this->_isExcludedTags = false;
            }
        } elseif ($this->_isExcludedTags) {
            $excludedTags = Mage::registry('excluded_auto_links_tags');
        }

        $patternsForExclude = array();
        if (isset($excludedTags)) {
            foreach ($excludedTags as $tag) {
                $tag = str_replace(' ', '', $tag);
                $patternsForExclude[] = '#'.'<'.$tag.'[^>]*>.*?</'.$tag.'>'.'#si';
            }

            return $patternsForExclude;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getDebugMessage()
    {
        if (!isset($_GET['debug_autolinks'])) {
            return '';
        }
        $html = array();
        $html[] = "<div style='color:red'>";
        $html[] = "Links limit per page: ". ($this->getMaxLinkPerPage()?$this->getMaxLinkPerPage():"unlimited")."</br>";
//        $html[] = "Unique links in block: ".count($this->linksInBlock)."<br>";
        foreach($this->debug['links'] as $link) {
            $html[] = " - ".$link->getKeyword()." (#: ".(int)$link->getActualNumberOfReplacements().", Max #: ".($link->getMaxReplacements()?(int)$link->getMaxReplacements():"unlimited").")<br>";
        }
        $html[] = "</div>";

        return implode("\n", $html);
    }
}

class Mirasvit_TextPlaceholder
{
    public $_translation_table = array();

    public function __construct($text, $patterns)
    {
        $this->_tokenized_text = preg_replace_callback($patterns, array($this, 'placeholder'), $text);
    }

    public function get_translation_table()
    {
        return $this->_translation_table;
    }

    public function get_tokenized_text()
    {
        return $this->_tokenized_text;
    }

    public function placeholder($matches)
    {
        $sequence = count($this->_translation_table);
        $placeholder = ' xkjndsfkjnakcx'.$sequence.'cxmkmweof329jc ';
        $this->_translation_table[$placeholder] = $matches[0];

        return $placeholder;
    }

}
