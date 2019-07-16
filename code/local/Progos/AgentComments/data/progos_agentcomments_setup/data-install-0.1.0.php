<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Data script for all the classification
 */
$classfications = array(
    array(
        'class_title' => 'Blue',
    ),
    array(
        'class_title' => 'Green',
    ),
    array(
        'class_title' => 'Red',
    ),
);
foreach ($classfications as $class) {
    Mage::getModel('progos_agentcomments/classification')
        ->setData($class)
        ->save();
}