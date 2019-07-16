<?php

class Infomodus_Dhllabel_Model_Src_Request_Partials_ShipmentPiece extends Infomodus_Dhllabel_Model_Src_Request_Partials_Piece
{
    protected $required = array(
        'PieceID' => null,
        'PackageType' => null,
        'Weight' => null,
        'Width' => null,
        'Height' => null,
        'Depth' => null,
        'PieceReference' => null
    );

    public function setPieceReference(Infomodus_Dhllabel_Model_Src_Request_Partials_PieceReference $pieceReference)
    {
        $this->required['PieceReference'] = $pieceReference;

        return $this;
    }
}
