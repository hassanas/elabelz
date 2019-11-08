<?php

// 1 LOAD INITIALS
define('MAGENTO_ROOT', getcwd());
$mageFilename = MAGENTO_ROOT . '/app/Mage.php';
require_once $mageFilename;
//umask(0);

function logMsg($msg, $display = true)
{
    if($display === true) echo $msg;
    Mage::log("clean-and-warm-cache:".$msg);
}

logMsg("Running script...\n");

logMsg('RefreshCache started @ ' . date('d-m-Y H:i:s') . "\n");

ini_set('display_errors', 1);

/* Store or website code */
$mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : '';

/* Run store or run website */
$mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';

Mage::app($mageRunCode,$mageRunType);

// 2 CLEAN CACHE
Mage::app()->cleanCache();

try {
    $allTypes = Mage::app()->useCache();
    foreach($allTypes as $type => $blah) {
      Mage::app()->getCacheInstance()->cleanType($type);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

// 3 FIND SITEMAPS
// todo: per store selector yes/no, now it runs 1 domain and for all stores

$collection = Mage::getModel('sitemap/sitemap')->getCollection();

//echo $collection->getSelect();

foreach($collection as $sitemap) {
    $url = substr_replace(Mage::getBaseUrl() ,"",-1) . $sitemap->getData('sitemap_path') . $sitemap->getData('sitemap_filename');
    crawlUrl($url);
}

// 4 CRAWL SITEMAPS
// Take from http://www.pixelenvision.com/1572/php-cache-warmer-preloader-for-w3-total-cache/
// W3 TOTAL CACHE WARMER (PRELOADER) by Pixel Envision (E.Gonenc)
// Version 2.1 - 21 August 2011

function crawlUrl($sitemap_url) {

    //Configuration options
    $delay = 0.5;               // Delay in seconds between page checks, default is half a second
    $quiet = false;              // Do not output process log (true/false)
    $trailing_slash = false;    // Add trailing slash to URL's, that might fix cache creation problems (true/false)

    $sitemap = $sitemap_url;    //Path to sitemap file relative to the warm.php

    //Do not change anything below this line unless you know what you are doing
    ignore_user_abort(TRUE);
    set_time_limit(600);

    $xml = simplexml_load_file($sitemap);
    foreach ($xml->url as $url_list) { 

        $url = $url_list->loc;

        if($trailing_slash==true) {$url = rtrim($url,"/")."/";}

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt ($ch, CURLOPT_HEADER, true);
        curl_setopt ($ch, CURLOPT_NOBODY, true);
        $ret = curl_exec ($ch);
        curl_close ($ch);

        usleep($delay*1000000);

        if($quiet!=true) {logMsg("Warmed: ".$url."\n");}

    }
    unset($xml);

if($quiet!=true) {logMsg("Crawled: " . $sitemap . "\n");}

}

logMsg('RefreshCache finished @ ' . date('d-m-Y H:i:s') . "\n");

echo "Script completed!\n\n";

exit;
