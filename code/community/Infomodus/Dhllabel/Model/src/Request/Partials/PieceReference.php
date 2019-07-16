<?php
/**
 * @author    Vitalij Rudyuk <rvansp@gmail.com>
 * @copyright 2014
 */
class Infomodus_Dhllabel_Model_Src_Request_Partials_PieceReference extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'ReferenceID' => null,
        'ReferenceType' => null,
    );

    public function setReferenceID($referenceID)
    {
        $this->required['ReferenceID'] = $referenceID;

        return $this;
    }

    public function setReferenceType($referenceType)
    {
        $this->required['ReferenceType'] = $referenceType;

        return $this;
    }
}
