<?php

class Cript
{

    private $method = "AES-128-ECB";

    private $pwd = "#define@your@key#";

    public function encript($value)
    {
        if (empty($value)){
            return "";
        }
        
        $result = openssl_encrypt($value, $this->method, $this->pwd);
        if ($result) {
            return $result;
        } else {
            return "";
        }
    }

    public function decript($value)
    {
        if (empty($value)){
            return "";
        }
        
        $result = openssl_decrypt($value, $this->method, $this->pwd, 0);
        if ($result) {
            return $result;
        } else {
            return "";
        }
    }
}

?>
