<?php
class Infomodus_Dhllabel_Model_Src_Request_Partials_Piece extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'PieceID' => null,
        'PackageType' => null,
        'Weight' => null,
        'Width' => null,
        'Height' => null,
        'Depth' => null,
    );

    /**
     * @param integer $pieceId Piece sequence number
     */
    public function setPieceId($pieceId)
    {
        $this->required['PieceID'] = $pieceId;

        return $this;
    }

    /**
     * @param string $height
     */
    public function setHeight($height)
    {
        $this->required['Height'] = $height;

        return $this;
    }

    /**
     * @param string $depth
     */
    public function setDepth($depth)
    {
        $this->required['Depth'] = $depth;

        return $this;
    }

    /**
     * @param string $width
     */
    public function setWidth($width)
    {
        $this->required['Width'] = $width;

        return $this;
    }

    /**
     * @param string $weight
     */
    public function setWeight($weight)
    {
        $this->required['Weight'] = $weight;

        return $this;
    }

    public function setPackageType($packageType)
    {
        $this->required['PackageType'] = $packageType;

        return $this;
    }
}
