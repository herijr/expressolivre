<?php

namespace App;

use App\Encoding\UTF8;

class Request
{
    private $utf8;

    public function __construct()
    {
        $this->utf8 = new UTF8();
    }

    private function cleanCharsControl($string)
    {
        return preg_replace('/[[:cntrl:]]/', ' ', $string);
    }

    public function getParams($request)
    {
        if (is_array($request->get()) && count($request->get()) > 0) {
            
            return $request->get();
        
        } else {

            $req = "";

            // application/x-www-form-urlencoded
            if (preg_match("/(application\/x-www-form-urlencoded)/i", $request->headers->get('Content-type'))) {
                $req = ($request->post('params') != null) ? urldecode($request->post('params')) : $request->post();
            }

            // application/json
            if (preg_match("/(application\/json)/i", $request->headers->get('Content-Type'))) {
                $req = $request->getBody();
            }

            // multipart/form-data;
            if (preg_match("/(multipart\/form-data)/i", $request->headers->get('Content-type'))) {
                $req = ($request->post('params') != null) ? urldecode($request->post('params')) : $request->post();
            }

            if( is_string($req) ){
                $req = json_decode($this->cleanCharsControl($req), true);
                $req = $this->utf8->encoding($req);
            }

            return (is_array($req) && count($req) > 0) ? $req : false;
        }
    }
}
