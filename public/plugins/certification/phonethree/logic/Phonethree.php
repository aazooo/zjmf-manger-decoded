<?php
namespace certification\phonethree\logic;

class Phonethree
{
    public function createLinkstrings($para)
    {
        $arg = '';
        foreach ($para as $key => $val) {
            if ($key == 'realname') {
                $val = urlencode($val);
            }
            $arg .= $key . '=' . $val . '&';
        }
        $arg = trim($arg, '&');
        return $arg;
    }
    public function httpsPhoneThree($appcode, $querys, $host)
    {
        $method = 'GET';
        $headers = [];
        array_push($headers, 'Authorization:APPCODE ' . $appcode);
        $url = $host . '?' . $querys;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result, true);
    }
}