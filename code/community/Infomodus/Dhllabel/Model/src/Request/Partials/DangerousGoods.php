<?php
/**
 * @author    Vitalij Rudyuk <rvansp@gmail.com>
 * @copyright 2015
 */
class Infomodus_Dhllabel_Model_Src_Request_Partials_DangerousGoods extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'DG' => array()
    );

    public function addPiece(Infomodus_Dhllabel_Model_Src_Request_Partials_DgPiece $piece)
    {
        if (! isset($this->required['DG'])) {
            $this->required['DG'] = array();
        }

        $this->required['DG'][] = $piece;

        return $this;
    }

    public function setPieces(array $pieces)
    {
        $this->required['DG'] = $pieces;

        return $this;
    }
}
