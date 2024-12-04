<?php


defined('BASEPATH') or exit('No direct script access allowed');


class ZoomJwtApiWrapper {

    public function __construct($params) {

        $this->apiKey = $params['apiKey'];

        $this->apiSecret = $params['apiSecret'];

        $this->baseUrl = 'https://api.zoom.us/v2';

        $this->timeout = 30;

    }

    static function urlsafeB64Encode( $string ) {

        return str_replace('=', '', strtr(base64_encode($string), '+/', '-_'));

    }

    private function generateJWT() {

        $token = array(

            'iss' => $this->apiKey,

            'exp' => time() + 60,

        );

        $header = array(

            'typ' => 'JWT',

            'alg' => 'HS256',

        );

        $toSign = 

            self::urlsafeB64Encode(json_encode($header))

            .'.'.

            self::urlsafeB64Encode(json_encode($token))

        ;

        $signature = hash_hmac('SHA256', $toSign, $this->apiSecret, true);

        return $toSign . '.' . self::urlsafeB64Encode($signature);

    }

    private function headers() {

        return array(

            'Authorization: Bearer ' . $this->generateJWT(),

            'Content-Type: application/json',

            'Accept: application/json',

        );

    }

    private function pathReplace( $path, $requestParams ){

        $errors = array();

        $path = preg_replace_callback( '/\\{(.*?)\\}/',function( $matches ) use( $requestParams,$errors ) {

            if (!isset($requestParams[$matches[1]])) {

                $this->errors[] = 'Required path parameter was not specified: '.$matches[1];

                return '';

            }

            return rawurlencode($requestParams[$matches[1]]);

        }, $path);

        if (count($errors)) $this->errors = array_merge( $this->errors, $errors );

        return $path;

    }

    public function doRequest($method, $path, $queryParams=array(), $pathParams=array(), $body='') {

        if (is_array($body)) {

            if (!count($body)) $body = '';

            else $body = json_encode( $body );

        }

        $this->errors = array();

        $this->responseCode = 0;

        $path = $this->pathReplace( $path, $pathParams );

        if (count($this->errors)) return false;

        $method = strtoupper($method);        

        $url = $this->baseUrl.$path;

        if (count($queryParams)) $url .= '?'.http_build_query($queryParams);

        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$url);

        curl_setopt($ch,CURLOPT_HTTPHEADER,$this->headers());

        curl_setopt($ch,CURLOPT_TIMEOUT,$this->timeout);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

        if (in_array($method,array('DELETE','PATCH','POST','PUT'))) {

            if ($method!='DELETE' && strlen($body)) {

                curl_setopt($ch, CURLOPT_POST, true );

                curl_setopt($ch, CURLOPT_POSTFIELDS, $body ); 

            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        }

        $result = curl_exec($ch);

        $contentType = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);

        $this->responseCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        curl_close($ch);

        return json_decode($result,true);

    }

    function requestErrors() {

        return $this->errors;

    }

    function responseCode() {

        return $this->responseCode;

    }

}