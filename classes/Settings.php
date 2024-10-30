<?php

/**
 * @package Comcure
 * @version 1.0.2
 */
class Settings extends Comcure {

    /**
     * Exclude databases that have an administrator privileges
     * @var array
     */
    private $exclude = array(
        "information_schema",
        "performance_schema",
        "mysql"
    );

    /**
     * Constructor
     * @param string $domain
     */
    public function __construct($domain) {
        parent::__construct($domain);
    }

    /**
     * Create a new host for the specified site
     * @return string
     */
    public function save_settings() {
        unset($_POST['host_id']);
        return $this->curl->request(sprintf($this->api['save_host'], $this->domain), array('host' => $_POST), 'POST');
    }

    /**
     * Update a host's information for a site
     * @return string
     */
    public function update_settings() {
        $url = sprintf($this->api['update_host'], $this->domain, $_POST['host_id']);
        return $this->curl->request($url, array('props' => $_POST), 'PUT');
    }

    /**
     * Test the speed from the back-up servers to the specified web site
     * @return object
     */
    private function speed_test() {
        $test = $this->curl->request(sprintf($this->api['spped_test'], $this->source_url['host']), array('domain' => $this->source_url['host']), 'POST');
        return json_decode($test);
    }

    /**
     * Create a new site. You may optionally provide the hostname of the SAN you
     * @return string
     */
    public function add_website() {
        $test = $this->speed_test();
        if ($test->result) {
            $args = array('domain' => $this->source_url['host'], 'backup_sun' => $test->data->best_server);
            return $this->curl->request($this->api['add_site'], $args, 'POST');
        } else {
            return json_encode($test);
        }
    }

    /**
     * Returns list of all databases
     * @global object $wpdb
     * @return string
     */
    public function show_databases() {
        global $wpdb;
        $_databases = array();
        $databases = $wpdb->get_results("SHOW DATABASES");
        if (count($databases)) {
            foreach ($databases as $database) {
                if (!in_array($database->Database, $this->exclude)) {
                    $_databases[] = $database->Database;
                }
            }
            $data = array(
                'result' => true,
                'databases' => (object) $_databases
            );
        } else {
            $data = array('result' => false, 'reason' => 'Database not found');
        }
        return json_encode($data);
    }

    /**
     * Returns list of all databases that does not belong to this site
     * @return string
     */
    public function show_remote_databases() {
        $_databases = array();
        $response = $this->curl->request(sprintf($this->api['list_databases'], $this->domain, $_GET['agent_id']));
        $json = json_decode($response);
        if ($json->result) {
            if (count($json->data)) {
                foreach ($json->data as $database) {
                    $_databases[] = $database;
                }
                $data = array('result' => true, 'databases' => (object) $_databases);
            } else {
                $data = array('result' => false, 'data' => $_databases);
            }
        } else {
            $data = $json;
        }
        return json_encode($data);
    }

    /**
     * Register and auto-install a database backup agent for a website that can be used to backup databases.
     * @return object
     */
    private function install_agent() {
        $args = array(
            'db_user' => DB_USER,
            'db_pass' => DB_PASSWORD,
            'db_host' => DB_HOST,
            'db_port' => 3306,
            'protocol' => $this->source_url['scheme'],
            'http_host' => $this->source_url['host'],
            'http_port' => $_SERVER['SERVER_PORT'],
            'http_path' => !isset($this->source_url['path']) ? "/" : $this->source_url['path'],
            'http_user' => null,
            'http_path' => null,
            'public_dir' => ABSPATH,
            'managed' => 1,
        );
        $response = $this->curl->request(sprintf($this->api['agent'], $this->domain), array('agent' => $args), 'POST');
        return json_decode($response);
    }

    /**
     * Save database configuration
     * @return string
     */
    public function database_settings() {
        $databases = array();
        foreach ($_POST['databases'] as $key => $value) {
            $databases[] = array(
                'name' => $value,
            );
        }
        $agent_id = (int) $_POST['agent_id'];
        if (!$agent_id) {
            $agent = $this->install_agent();
            if (!$agent->result) {
                print json_encode($agent);
                exit;
            }
            $agent_id = $agent->data->agent->agent_id;
        }
        $args = array('databases' => $databases, 'agent_id' => $agent_id);
        return $this->curl->request(sprintf($this->api['database'], $this->domain), $args, 'POST');
    }

    /**
     * Sync adatabase agent
     * @return string
     */
    public function sync_agent() {
        return $this->curl->request(sprintf($this->api['sync_agent'], $this->domain, (int) $_POST['agent_id']), array('sync_agent' => true), 'POST');
    }

    /**
     * Stop performing database backups and removes existing backups
     * @return string
     */
    public function remove_database() {
        return $this->curl->request(sprintf($this->api['remove_database'], $this->domain, $_POST['db_name']), array(), 'DELETE');
    }

    /**
     * Update database configuration
     * @return string
     */
    public function update_database() {
        return $this->curl->request(sprintf($this->api['update_database'], $this->domain, $_POST['name']), array('database' => $_POST), 'PUT');
    }

}
