<?php
/**
 * @author    Vitalij Rudyuk <rvansp@gmail.com>
 * @copyright 2015
 */
class Infomodus_Dhllabel_Model_Src_Request_Partials_DocImages extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array('DocImage' => array(
        'Type' => null,
        'Image' => null,
        'ImageFormat' => null
    )
    );

    public function setType($type)
    {
        $this->required['DocImage']['Type'] = $type;
        return $this;
    }
    public function setImage($image)
    {
        $this->required['DocImage']['Image'] = $image;
        return $this;
    }
    public function setImageFormat($imageFormat)
    {
        $this->required['DocImage']['ImageFormat'] = $imageFormat;
        return $this;
    }
}
