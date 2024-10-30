<?php

/**
 * @package Comcure
 * @version 1.0.2
 */
class Curl {

    /**
     *
     * @var resource
     */
    private $handle = null;

    /**
     *
     * @var object
     */
    private $api;

    /**
     * 
     * @param object $api
     */
    public function __construct($api) {
        $this->api = $api;
        $this->init();
    }

    /**
     * 
     * @throws Exception
     * @return null
     */
    private function init() {
        $this->handle = curl_init();
        if ($this->handle === null) {
            throw new Exception("Failed to initialize cURL handle.");
        }
        curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, false);
        if (isset($this->api->agent)) {
            curl_setopt($this->handle, CURLOPT_USERAGENT, $this->api->agent);
        }
    }

    /**
     * 
     * @param string $command
     * @param array $args
     * @param string $method
     * @return string
     */
    public function request($command, $args = array(), $method = 'GET') {
        $url = "https://{$this->api->url}{$command}";
        $args = json_encode($args);
        $params = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->api->cookie_file,
            CURLOPT_COOKIEFILE => $this->api->cookie_file,
            CURLOPT_POSTFIELDS => $args,
            CURLOPT_CONNECTTIMEOUT => $this->api->timeout,
            CURLOPT_URL => $url,
            CURLOPT_PORT => $this->api->port,
        );
        if ($method == 'GET') {
            unset($params[CURLOPT_POSTFIELDS]);
        } else {
            $headers = array(
                "Content-Type: application/json",
                "Content-Length: " . strlen($args),
            );
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt_array($this->handle, $params);
        $response = curl_exec($this->handle);

        if ($response === false) {
            return json_encode(array('result' => false, 'reason' => "Failed to connect to site. " . curl_error($this->handle)));
        }
        return $response;
    }

}
