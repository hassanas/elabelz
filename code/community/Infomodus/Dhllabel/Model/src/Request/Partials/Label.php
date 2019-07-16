<?php
/**
 * @author    Danail Kyosev <ddkyosev@gmail.com>
 * @copyright 2014, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Infomodus_Dhllabel_Model_Src_Request_Partials_Label extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'LabelTemplate' => '8X4_A4_PDF',
    );

    public function setLabelTemplate($labelTemplate)
    {
        $this->required['LabelTemplate'] = $labelTemplate;

        return $this;
    }
}
