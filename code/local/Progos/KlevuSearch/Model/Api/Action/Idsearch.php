<?php

/**
 * This Model overrided as per suggestion from klevu Team for changes in validate method
 *
 * @category       Progos
 * @package        Progos_KlevuSearch
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           30-08-2017 12:04
 */

class Progos_KlevuSearch_Model_Api_Action_Idsearch extends Klevu_Search_Model_Api_Action_Idsearch
{

    /*
     * Method overrided as per klevu team request
     * */

    protected function validate($parameters) {
        $errors = array();

        if (!isset($parameters['ticket']) || empty($parameters['ticket'])) {
            $errors['ticket'] = "Missing ticket (Search API Key)";
        }

        if (!isset($parameters['noOfResults']) || empty($parameters['noOfResults'])) {
            $errors['noOfResults'] = "Missing number of results to return";
        }

        if(!isset($parameters['term']) || empty($parameters['term'])) {
            $errors['term'] = "Missing search term";
        }

        if(!isset($parameters['paginationStartsFrom'])) {
            $errors['paginationStartsFrom'] = "Missing pagination start from value ";
        } else if (intval($parameters['paginationStartsFrom']) < 0) {
            $errors['paginationStartsFrom'] = "Pagination needs to start from 0 or higher";
        }

        if (count($errors) == 0) {
            return true;
        }
        return $errors;
    }

}