<?php

/**
 * @package Comcure
 * @version 1.0.2
 */
class Comcure {

    /**
     *
     * @var integer
     */
    public $timeout = 30;

    /**
     *
     * @var string
     */
    public $agent = "Comcure Wordpress Plugin";

    /**
     *
     * @var string
     */
    public $url = "manage.comcure.com";

    /**
     *
     * @var integer
     */
    public $port = 443;

    /**
     *
     * @var array
     */
    protected $api = array(
        'register_plugin' => '/user.html?register_plugin',
        'sign_up' => '/user.html?signup',
        'auth' => '/api/cc',
        'get_data' => '/api/cc/cp_data',
        'add_site' => '/api/cc/site',
        'snapshotlog' => '/api/site/%s/log',
        'spped_test' => '/api/site/speed/%s',
        'save_host' => '/api/site/%s/host',
        'update_host' => '/api/site/%s/host/%d',
        'ondemand' => '/api/site/%s/ondemand',
        'agent' => '/api/site/%s/database/agent',
        'sync_agent' => '/api/site/%s/database/agent/%d/sync',
        'database' => '/api/site/%s/database',
        'update_database' => '/api/site/%s/database/%s',
        'remove_database' => '/api/site/%s/database/%s',
        'list_databases' => '/api/site/%s/database/%d/db_list',
        'list_snapshots' => '/api/site/%s/snapshot',
        'list_db_snapshots' => '/api/site/%s/snapshot/%d/database',
        'restore_snapshot' => '/api/site/%s/snapshot/%d/restore',
        'restore_database' => '/api/site/%s/database/%s/restore',
    );

    /**
     *
     * @var object
     */
    protected $curl;

    /**
     *
     * @var bool
     */
    public $token = false;

    /**
     *
     * @var bool
     */
    public $loggedIn = false;

    /**
     *
     * @var bool
     */
    public $notification = false;

    /**
     *
     * @var string
     */
    public $cookie_file;

    /**
     *
     * @var string
     */
    protected $source_url;

    /**
     *
     * @var string
     */
    protected $domain;

    /**
     * Constructor
     * @param string $domain
     */
    public function __construct($domain) {
        $this->source_url = parse_url(get_option('siteurl'));
        $this->cookie_file = $this->get_temp_dir()
                . DIRECTORY_SEPARATOR
                . md5($this->source_url['host'])
                . ".cookies";
        $this->curl = new Curl($this);
        $this->register_plugin();
        if (!$this->token) {
            $this->loggedIn = false;
        }
        if (isset($_COOKIE['wp_comcure']) && $_COOKIE['wp_comcure'] == $this->token) {
            $this->loggedIn = true;
        }
        $this->domain = $domain;
    }

    /**
     * Set a cookie
     * @return null
     */
    private function set_cookie() {
        setcookie('wp_comcure', $this->token, strtotime("+1 year"), SITECOOKIEPATH, COOKIE_DOMAIN, false);
    }

    /**
     * Delete a cookie
     * @return null
     */
    private function remove_cookie() {
        setcookie('wp_comcure', null, strtotime("-1 year"), SITECOOKIEPATH, COOKIE_DOMAIN, false);
        @unlink($this->cookie_file);
    }

    /**
     * Delete a cookie and returns JSON string
     * @return string
     */
    public function logout() {
        do_action($this->remove_cookie());
        return json_encode(array('result' => true));
    }

    /**
     * The register plug-in in the remote server and deploy it to the database
     * @global object $wpdb
     */
    public function register_plugin() {
        global $wpdb;
        $sql_string = file_get_contents(dirname(dirname(__FILE__)) . '/comcure.sql');
        $sql = sprintf($sql_string, $wpdb->prefix);
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        $this->token = $this->get_token();
        if (!$this->token) {
            $response = $this->curl->request($this->api['register_plugin'], array(), 'POST');
            $json = json_decode($response);
            if ($json->success) {
                $wpdb->query(
                        $wpdb->prepare("INSERT INTO {$wpdb->prefix}comcure SET `token`=%s", urldecode($json->cpt))
                );
            } else {
                ?>
                <script type="text/javascript">alert('<?php echo $json->error; ?>');</script>
                <?php
            }
        }
    }

    /**
     * Returns unique token
     * @global object $wpdb
     * @return string
     */
    private function get_token() {
        global $wpdb;
        $row = $wpdb->get_row("SELECT `token` FROM `{$wpdb->prefix}comcure`");
        if (!is_null($row)) {
            return $row->token;
        }
    }

    /**
     * Log in as the specified account, authenticating with the given email and password
     * @return string
     */
    public function login() {
        $args = array('account' => $_POST['email'], 'password' => $_POST['password']);
        $response = $this->curl->request($this->api['auth'], $args, 'POST');
        $json = json_decode($response);
        if ($json->result) {
            do_action($this->set_cookie());
        }
        return json_encode($json);
    }

    /**
     * Returns temp directory
     * @return string
     */
    protected function get_temp_dir() {
        if (!function_exists('sys_get_temp_dir')) {
            $temp = tempnam(__FILE__, '');
            if (file_exists($temp)) {
                unlink($temp);
                return dirname($temp);
            }
        }
        return sys_get_temp_dir();
    }

}
