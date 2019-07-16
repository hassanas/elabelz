<?php
/**
 * @author    Vitalij Rudyuk <rvansp@gmail.com>
 * @copyright 2014
 */
class Infomodus_Dhllabel_Model_Src_Request_Partials_Reference extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'ReferenceID' => null
    );

    public function setReferenceId($referenceId)
    {
        $this->required['ReferenceID'] = $referenceId;

        return $this;
    }
}
