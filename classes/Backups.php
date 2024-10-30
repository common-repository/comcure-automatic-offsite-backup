<?php

/**
 * @package Comcure
 * @version 1.0.2
 */
class Backups extends Comcure {

    /**
     * Constructor
     * @param string $domain
     */
    public function __construct($domain) {
        parent::__construct($domain);
    }

    /**
     * Returns the most recent operations performed for the given site
     * @return string
     */
    public function load_events() {
        return $this->curl->request(sprintf($this->api['snapshotlog'], $this->domain));
    }

    /**
     * Request a backup of a given site right away
     * @return string
     */
    public function on_demand() {
        return $this->curl->request(sprintf($this->api['ondemand'], $this->domain));
    }

    /**
     * Returns a list of snapshots taken from the specified site
     * @return string
     */
    public function list_snapshots() {
        return $this->curl->request(sprintf($this->api['list_snapshots'], $this->domain));
    }

    /**
     * Returns a list of databases taken from the specified site
     * @return string
     */
    public function list_databases() {
        return $this->curl->request(sprintf($this->api['list_db_snapshots'], $this->domain, (int) $_GET['id']));
    }

    /**
     * Restore a snapshot
     * @return string
     */
    public function restore_snapshot() {
        return $this->curl->request(sprintf($this->api['restore_snapshot'], $this->domain, (int) $_GET['id']), array('args' => array('conn' => null, 'forceRescue' => false)), 'POST');
    }

    /**
     * Restore a database
     * @return string
     */
    public function restore_database() {
        return $this->curl->request(sprintf($this->api['restore_database'], $this->domain, $_POST['name']), array('snapshot' => (int) $_POST['snapshot']), 'PUT');
    }

}
