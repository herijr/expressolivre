<?php

namespace App\Encoding;

class ISO8859
{
    public function encoding($data)
    {
        if (!is_array($data)) return  $this->sconv($data);
        $result = array();
        foreach ($data as $k => $v) $result[$this->sconv($k)] = is_array($v) ? $this->encoding($v) : $this->sconv($v);
        return $result;
    }

    private function sconv($obj)
    {
        return (is_string($obj) && (!$this->isUTF8($obj))) ? $obj : mb_convert_encoding($obj, "ISO-8859-1", "UTF-8");
    }

    private function isUTF8($str)
    {
        return (iconv('UTF-8', 'UTF-8//IGNORE', $str) == $str);
    }
}
