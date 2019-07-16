<?php
class Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial
{
    protected $required = array();

    public function toArray()
    {
        return $this->convertToArray($this->required);
    }

    private function convertToArray($data)
    {
        $result = array();
        foreach ($data as $key => $value) {
            if ($value instanceof Infomodus_Dhllabel_Model_Src_Request_Partials_RequestPartial) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = $this->convertToArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
