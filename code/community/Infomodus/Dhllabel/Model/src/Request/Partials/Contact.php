<?php
/**
 * @author    Danail Kyosev <ddkyosev@gmail.com>
 * @copyright 2014, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Infomodus_Dhllabel_Model_Src_Request_Partials_Contact extends Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array(
        'PersonName' => null,
        'PhoneNumber' => null,
        'PhoneExtension' => null,
        'FaxNumber' => null,
        'Telex' => null,
        'Email' => null,
    );

    /**
     * @param string $personName Contact's name
     */
    public function setPersonName($personName)
    {
        $this->required['PersonName'] = $personName;

        return $this;
    }

    /**
     * @param string $phoneNumber Contact's phone number
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->required['PhoneNumber'] = $phoneNumber;

        return $this;
    }

    public function setEmail($email)
    {
        $this->required['Email'] = $email;

        return $this;
    }
}
