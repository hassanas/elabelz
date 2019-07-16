<?php
class Infomodus_Dhllabel_Model_Src_Exception_DHLConnectionException extends Exception
{
    /**
     * @param string $message
     */
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}
