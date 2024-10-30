<?php

/**
 * @package Comcure
 * @version 1.0.2
 */
class Overview extends Comcure {

    /**
     * Constructor
     * @param string $domain
     */
    public function __construct($domain) {
        parent::__construct($domain);
    }

    /**
     * Returns data needed to populate the CP
     * @return string
     */
    public function cp_data() {
        return $this->curl->request($this->api['get_data']);
    }

    /**
     * Return list of databases
     * @return string
     */
    public function get_databases() {
        return $this->curl->request(sprintf($this->api['database'], $this->domain));
    }

}
