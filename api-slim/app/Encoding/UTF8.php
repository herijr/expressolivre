<?php

namespace App\Encoding;

class UTF8
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
        return (is_string($obj) && (!$this->isUTF8($obj))) ? (utf8_encode($obj)) : $obj;
    }

    private function isUTF8($str)
    {
        return (iconv('UTF-8', 'UTF-8//IGNORE', $str) == $str);
    }
}
