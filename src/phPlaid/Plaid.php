<?php
/**
 * Copyright (c) 2014 Tony Peng
 *
 * Licensed under the MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace phPlaid;

require_once __DIR__.'/PlaidApiException.php';

class Plaid
{
    const GET = 'GET';
    const POST = 'POST';
    const DELETE = 'DELETE';
    const PATCH = 'PATCH';

    private static $API_DEV_BASE_URL = 'https://tartan.plaid.com';
    private static $API_PROD_BASE_URL = 'https://api.plaid.com';

    private $_serviceURL;

    private $_clientID;
    private $_secret;

    private $_accessToken;

    public function __construct($clientID, $secret, $development=false)
    {
        $this->_clientID = $clientID;
        $this->_secret = $secret;

        $this->_serviceURL = $development ? self::$API_DEV_BASE_URL : self::$API_PROD_BASE_URL;
    }

    public function setAccessToken($accessToken)
    {
        $this->_accessToken = $accessToken;
    }

    public function api($path, $method = self::GET, $params = array())
    {
        if(substr($path, 0, 1) != '/') {
            $path = '/'.$path;
        }

        $params['client_id'] = $this->_clientID;
        $params['secret'] = $this->_secret;

        if($this->_accessToken) {
            $params['access_token'] = $this->_accessToken;
        }

        $request_path = $this->_serviceURL.$path;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $query_str = http_build_query($params);

        if($method == self::GET && $query_str) {
            $request_path .= '?'.$query_str;
        } else if($method == self::POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_str);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query_str);
        }

        curl_setopt($ch, CURLOPT_URL, $request_path);

        $resp = curl_exec($ch);

        $json = json_decode($resp);

        if(!$json || (isset($json->code) && isset($json->message) && isset($json->resolve))) {
            $code = '-1';
            $message = 'Invalid response. (got \''.$resp.'\')';
            $resolve = 'Check requested API endpoint and method.';

            if(isset($json->code) && isset($json->message) && isset($json->resolve)) {
                $code = $json->code;
                $message = $json->message;
                $resolve = $json->resolve;
            }

            throw new PlaidApiException($message, $resolve, $code);
        }

        return $json;
    }
}