<?php
/**
 * @package Comcure
 * @version 1.0.2
 */
add_action('admin_menu', 'comcure_admin_menu', 9999, 1);
add_action('admin_enqueue_scripts', 'comcure_load_css_and_js');
add_action('admin_enqueue_scripts', 'comcure_load_js');

function comcure_load_css_and_js() {
    wp_register_style('comcure.css', COMCURE_PLUGIN_URL . 'static/css/comcure.css');
    wp_enqueue_style('comcure.css');
    ?>
    <script type="text/javascript">
        var comcure_plugin_url = '<?php echo COMCURE_PLUGIN_URL;?>';
    </script>
    <?php
    wp_register_script('overlay.js', COMCURE_PLUGIN_URL . 'static/js/overlay.js', array('jquery'));
    wp_enqueue_script('overlay.js');
}

$page = comcure_get();

function comcure_load($page) {
    $parts = explode("-", $page);
    $owner = reset($parts);    
    $class = ucfirst(end($parts));
    $file = __DIR__ . "/classes/" . $class . ".php";
    if (file_exists($file) && $owner == 'comcure') {
        require_once $file;
        return new $class(comcure_get('domain'));
    }
}

$api = comcure_load($page);

register_activation_hook(__FILE__, array($api, 'register_plugin'));

function comcure_load_js() {
    global $api;
    if (!isset($api->loggedIn)) {
        return;
    }
    if (is_object($api) && $api->loggedIn) {
        $js = 'dev';
        if (PRODUCTION) {
            $js = 'min';
        }
        wp_register_script('comcure.' . $js . '.js', COMCURE_PLUGIN_URL . 'static/js/comcure.' . $js . '.js');
        wp_enqueue_script('comcure.' . $js . '.js');
    } else {
        wp_register_script('login.js', COMCURE_PLUGIN_URL . 'static/js/login.js');
        wp_enqueue_script('login.js');
    }
}

function comcure_init() {
    global $api;
    if (is_object($api) && !$api->loggedIn) {
        comcure_login_form();
    } else {
        comcure_dashboard();
    }
}

if (is_object($api)) {
    $action = comcure_get('action');
    if (method_exists($api, $action)) {
        print $api->$action();
        exit;
    }
}

function comcure_dashboard() {
    $server = str_replace('www.', '', $_SERVER['SERVER_NAME']);
    global $api, $page;
    ?>
    <div class="updated" id="flash-message"><p><strong></strong></p></div>
    <div class="wrap" id="comcure">        
        <a class="logo" target="_blank" href="https://comcure.com" title="<?php _e('Cloud Backups for your Website'); ?>"><?php echo _('Version'), " ", COMCURE_VERSION; ?></a>
        <?php if (is_object($api) && $api->loggedIn): ?>
            <select name="domain" class="domain" id="domain"></select>
        <?php endif; ?>
        <h2 class="nav-tab-wrapper">
            <a id="overview" class="nav-tab<?php if ($page == 'comcure-overview'): ?> nav-tab-active<?php endif; ?>" href="javascript:void(0)"><?php _e('Overview'); ?></a>
            <a id="backups" class="nav-tab<?php if ($page == 'comcure-backups'): ?> nav-tab-active<?php endif; ?>" href="javascript:void(0)"><?php _e('Website Backups'); ?></a>
            <a id="settings" class="nav-tab<?php if ($page == 'comcure-settings'): ?> nav-tab-active<?php endif; ?>" href="javascript:void(0)"><?php _e('Website Settings'); ?></a>
            <?php if (is_object($api) && $api->loggedIn): ?>
                <a id="logout" class="logout" href="javascript:void(0)"><?php _e('Log Out'); ?></a>
            <?php endif; ?>
        </h2>
        <div id="comcure-backups" class="postbox-cantainer comcure">
            <div class="metabox-holder">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="comcure-sidebar-name">
                            <div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br /></div>
                            <h3 class="hndle"><span><?php _e('Website Backups'); ?></span></h3>
                            <div class="comcure-holder">
                                <table class="backups">
                                    <tr>
                                        <td class="head"><?php _e('Storage IP Address'); ?></td>
                                        <td id="storeage_ip"></td>
                                        <td class="head"><?php _e('Date Added'); ?></td>
                                        <td id="created"></td>
                                    </tr>
                                    <tr>
                                        <td class="head"><?php _e('Storage Location'); ?></td>
                                        <td id="storage_location"></td>
                                        <td class="head"><?php _e('Last Scheduled Back-up'); ?></td>
                                        <td id="last_snapshot">Waiting to complete</td>
                                    </tr>
                                    <tr>
                                        <td class="head"><?php _e('Disk Usage'); ?></td>
                                        <td id="disk_usage"></td>
                                        <td class="head"><?php _e('Last On-Demand Backup'); ?></td>
                                        <td id="last_ondemand_snapshot"><a class="ondemand_snapshot_site" href="javascript: void(0)"></a></td>
                                    </tr>
                                    <tr>
                                        <td class="head"><?php _e('Traffic Used (last 30 days)'); ?></td>
                                        <td id="monthly_traffic"></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <select name="snapshots" id="snapshots"></select>
            <div class="postbox-cantainer" id="list_databases">
                <div class="metabox-holder">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <div class="comcure-sidebar-name">
                                <div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br /></div>
                                <h3 class="hndle"><span><?php _e('Databases'); ?></span></h3>
                                <div class="comcure-holder">
                                    <img class="loader" src="<?php echo COMCURE_PLUGIN_URL;?>/static/images/loading-trans.gif">
                                    <table class="list_databases">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Name'); ?></th>
                                                <th><?php _e('Size'); ?></th>
                                                <th><?php _e('Type'); ?></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="postbox-cantainer" id="list_snapshots">
                <div class="metabox-holder">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <div class="comcure-sidebar-name">
                                <div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br /></div>
                                <h3 class="hndle"><span><?php _e('Snapshots'); ?></span></h3>
                                <div class="comcure-holder">
                                    <table class="list_snapshots">
                                        <tbody>
                                            <tr id="1">
                                                <th colspan="3"><?php _e('Daily'); ?></th>
                                            </tr>
                                            <tr id="3">
                                                <th colspan="3"><?php _e('Weekly'); ?></th>
                                            </tr>
                                            <tr id="4">
                                                <th colspan="3"><?php _e('Monthly'); ?></th>
                                            </tr>
                                            <tr id="2">
                                                <th colspan="3"><?php _e('On-Demand'); ?></th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="postbox-cantainer" id="events">
                <div class="metabox-holder">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <div class="comcure-sidebar-name">
                                <div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br /></div>
                                <h3 class="hndle"><span><?php _e('Recent Events'); ?></span></h3>
                                <div class="comcure-holder">
                                    <img class="loader" src="<?php echo COMCURE_PLUGIN_URL;?>/static/images/loading-trans.gif">
                                    <table class="events">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Time'); ?></th>
                                                <th><?php _e('Description'); ?></th>
                                                <th><?php _e('Details'); ?></th>
                                                <th><?php _e('Status'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>        
        <div id="comcure-overview" class="postbox-cantainer comcure">
            <h3 class="welcome">Welcome, </h3>            
            <div class="upgrade_plan"><a href="https://manage.comcure.com/cp/">Update your plan or add-ons</a> to backup more websites.</div>
            <div class="metabox-holder">
                <div class="meta-box-sortables">
                    <div class="postbox">
                        <div class="comcure-sidebar-name">
                            <div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br /></div>
                            <h3 class="hndle"><span><?php _e('Usage'); ?></span><span class="total_price"></span></h3>                            
                            <div class="comcure-holder">
                                <table class="usage">
                                    <tr>
                                        <td class="head"><?php _e('Service Plan'); ?></td>
                                        <td id="service_plan_name"></td>                                        
                                        <td class="head"><?php _e('Websites'); ?></td>
                                        <td id="websites" class="gauge">
                                            <div class="gauge_bar"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="head"><?php _e('Anti-virus Scanning'); ?></td>
                                        <td id="anti_virus_scanning"><?php _e('No'); ?></td>
                                        <td class="head"><?php _e('Databases'); ?></td>
                                        <td id="databases" class="gauge">
                                            <div class="gauge_bar"></div>
                                        </td>
                                    </tr>                                    
                                    <tr>
                                        <td class="head"><?php _e('Two-factor Authentication'); ?></td>
                                        <td id="two_factor_authentication"><?php _e('No'); ?></td>
                                        <td class="head"><?php _e('Disk Space'); ?></td>
                                        <td id="disk_space" class="gauge">
                                            <div class="gauge_bar"></div>
                                        </td>                                        
                                    </tr>
                                    <tr>
                                        <td class="head"><?php _e('Encrypted Backups'); ?></td>
                                        <td id="encrypted_backups"><?php _e('No'); ?></td>
                                        <td class="head"><?php _e('Inodes'); ?></td>
                                        <td id="inodes" class="gauge">
                                            <div class="gauge_bar"></div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="postbox-cantainer" id="website-list">
                <div class="metabox-holder">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <div class="comcure-sidebar-name">
                                <div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br /></div>
                                <h3 class="hndle"><span><?php _e('Your Websites'); ?></span></h3>
                                <div class="comcure-holder">
                                    <table class="websites">
                                        <thead>
                                            <tr>
                                                <th><?php _e('Website'); ?></th>
                                                <th><?php _e('Disk Space'); ?></th>
                                                <th><?php _e('Inodes (Files/Folders)'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wrap comcure" id="comcure-settings">
            <h2>
                <a id="website-settings" class="active"><?php _e('Website Settings'); ?></a>
                <a id="database-settings"><?php _e('Database Settings'); ?></a>
            </h2>            
            <form method="post" action="" id="form-database-settings">
                <input type="hidden" name="agent_id" id="agent_id" value="" />
                <table class="form-table" id="database-list">
                    <tbody></tbody>
                </table>
                <p class="submit"><input type="submit" name="submit" id="save-database" class="button button-primary" value="<?php _e('Save Database Settings'); ?>"  /></p>
            </form>
            <form method="post" action="" id="form-website-settings">
                <input type="hidden" name="host_id" id="host_id" value="" />
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="hostname"><?php _e('Website Location'); ?></label></th>
                        <td>
                            <input name="hostname" type="text" id="hostname" value="<?php echo $server; ?>" class="regular-text code" />
                            <p class="description"><?php _e('This is the name (or IP address) of the server hosting your files'); ?> (<?php _e('e.g.'); ?> <b><?php echo $server; ?></b> <?php _e('or'); ?> <b>ftp.<?php echo $server; ?></b>).</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="protocol"><?php _e('Protocol'); ?></label></th>
                        <td>
                            <select name="protocol" id="protocol" class='postform' >
                                <option class="level-0" value="sftp" selected="selected"><?php _e('SFTP'); ?></option>
                                <option class="level-0" value="ftp"><?php _e('FTP'); ?></option>
                                <option class="level-0" value="ftps"><?php _e('FTPS (secure, via TLS/SSL)'); ?></option>
                            </select>
                            <label for="port"><?php _e('Port'); ?></label>
                            <input name="port" type="text" id="port" value="22" class="small-text" />
                            <p class="description"><?php _e('What protocol (e.g. FTP, SFTP) do you connect to your server with? And the remote port to use to connect to the server.'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="user"><?php _e('Username'); ?></label></th>
                        <td>
                            <input name="user" type="text" id="user" value="" class="regular-text ltr" />
                            <p class="description"><?php _e('The username for accessing your website files.'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="pass"><?php _e('Password'); ?></label></th>
                        <td>
                            <input name="pass" type="password" id="pass" value="" class="regular-text ltr" />
                            <p class="description"><?php _e('The password for accessing your website files.'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="priv_key" id="private-key"><?php _e('Private key'); ?></label></th>
                        <td>
                            <textarea name="priv_key" id="priv_key" rows="5" cols="120"></textarea>
                            <p class="description" id="priv-key-desc"><?php _e('We can also use your private key to authenticate to your server instead of password. Only supported with SFTP. If the key is passphrase-protected, please enter it into the <b>Password</b> field above.'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="base_dir"><?php _e('Base Directory'); ?></label></th>
                        <td>
                            <input name="base_dir" type="text" id="base_dir" value="<?php echo ABSPATH; ?>" class="regular-text code" />
                            <p class="description"><?php _e('Which folder on your server contains your website files (e.g. "/public_html", "/var/www")?'); ?></p>
                        </td>
                    </tr>
                </table>
                <a href="javascript:void(0)" id="preferences"><?php _e('Preferences'); ?></a>
                <table class="form-table" id="form-preferences">
                    <tr valign="top">
                        <th scope="row"><label for="notify_when"><?php _e('Backup Notifications'); ?></label></th>
                        <td>
                            <select name="notify_when" id="notify_when" class='postform' >
                                <option value="always"><?php _e('After every backup'); ?></option>
                                <option value="changes"><?php _e('Only when files change'); ?></option>
                                <option value="errors"><?php _e('On errors only'); ?></option>
                                <option value="never"><?php _e('Never'); ?></option>
                            </select>
                            <p class="description"><?php _e('When would you like to receive backup e-mail notifications?'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="backup_time"><?php _e('Backup Time'); ?></label></th>
                        <td>
                            <select class="postform" name="backup_time" id="backup_time">
                                <option value=""><?php _e('Auto'); ?></option>
                                <option value="00:00">00:00</option>
                                <option value="00:30">00:30</option>
                                <option value="01:00">01:00</option>
                                <option value="01:30">01:30</option>
                                <option value="02:00">02:00</option>
                                <option value="02:30">02:30</option>
                                <option value="03:00">03:00</option>
                                <option value="03:30">03:30</option>
                                <option value="04:00">04:00</option>
                                <option value="04:30">04:30</option>
                                <option value="05:00">05:00</option>
                                <option value="05:30">05:30</option>
                                <option value="06:00">06:00</option>
                                <option value="06:30">06:30</option>
                                <option value="07:00">07:00</option>
                                <option value="07:30">07:30</option>
                                <option value="08:00">08:00</option>
                                <option value="08:30">08:30</option>
                                <option value="09:00">09:00</option>
                                <option value="09:30">09:30</option>
                                <option value="10:00">10:00</option>
                                <option value="10:30">10:30</option>
                                <option value="11:00">11:00</option>
                                <option value="11:30">11:30</option>
                                <option value="12:00">12:00</option>
                                <option value="12:30">12:30</option>
                                <option value="13:00">13:00</option>
                                <option value="13:30">13:30</option>
                                <option value="14:00">14:00</option>
                                <option value="14:30">14:30</option>
                                <option value="15:00">15:00</option>
                                <option value="15:30">15:30</option>
                                <option value="16:00">16:00</option>
                                <option value="16:30">16:30</option>
                                <option value="17:00">17:00</option>
                                <option value="17:30">17:30</option>
                                <option value="18:00">18:00</option>
                                <option value="18:30">18:30</option>
                                <option value="19:00">19:00</option>
                                <option value="19:30">19:30</option>
                                <option value="20:00">20:00</option>
                                <option value="20:30">20:30</option>
                                <option value="21:00">21:00</option>
                                <option value="21:30">21:30</option>
                                <option value="22:00">22:00</option>
                                <option value="22:30">22:30</option>
                                <option value="23:00">23:00</option>
                                <option value="23:30">23:30</option>
                            </select>
                            <p class="description"><?php _e('What time (e.g. off-peak hours for your site) would you like to schedule your backups? The times listed are in your local time.'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="pattern_type"><?php _e('Filter Policy'); ?></label></th>
                        <td>
                            <select name="pattern_type" id="pattern_type" class='postform' >
                                <option value="exclude"><?php _e('Exclude Files'); ?></option>
                                <option value="include"><?php _e('Include Files'); ?></option>
                            </select>
                            <p class="description"><?php _e('You may specify certain types of files to include or exclude in your backups.'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="pattern"><?php _e('File Filters'); ?></label></th>
                        <td>
                            <input name="pattern" type="text" id="pattern" value="*.tmp, *.log" class="regular-text ltr" />
                            <p class="description"><?php _e('Which files should be excluded from your backups?'); ?> (<?php _e('e.g.'); ?> "*.log, *.tmp")</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="skip_errors"><?php _e('Error Handling'); ?></label></th>
                        <td>
                            <select name="skip_errors" id="skip_errors" class='postform' >
                                <option value="1"><?php _e('Log errors and continue'); ?></option>
                                <option value="0"><?php _e('Abort backup on errors'); ?></option>
                            </select>
                            <p class="description"><?php _e('What should happen if any files or folders fail to be backed up?'); ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings'); ?>"  /></p>
            </form>
        </div>
    </div>
    <?php
}

function comcure_login_form() {
    ?>
    <div class="login">
        <a class="logo" target="_blank" href="https://www.comcure.com" title="<?php _e('Cloud Backups for your Website'); ?>"></a>
        <div id="login_error"></div>
        <form name="loginform" id="comcure-login" action="plugins.php?page=<?php echo $_GET['page'] ?>" method="post">
            <p>
                <label for="email"><?php _e('Email'); ?><br />
                    <input type="text" name="email" id="email" class="input" value="<?php echo get_option('admin_email'); ?>" size="20" /></label>
            </p>
            <p>
                <label for="user_pass"><?php _e('Password'); ?><br />
                    <input type="password" name="password" id="user_pass" class="input" value="" size="20" /></label>
            </p>
            <p><a href="https://www.comcure.com/signup.html" target="blank"><?php _e('Sign Up Free'); ?></a></p>
            <p class="submit">
                <input type="submit" name="comcure-submit" id="comcure-submit" class="button button-primary button-large" value="<?php _e('Log In'); ?>" />
            </p>
        </form>
    </div>
    <?php
}

function comcure_plugin_action_links($links, $file) {
    global $page;
    if ($file == plugin_basename(dirname(__FILE__) . '/comcure.php')) {
        if ($page == 1) {
            $page = 'comcure-overview';
        }
        $links[] = '<a href="' . self_admin_url('plugins.php?page=' . $page) . '">' . __('Dashboard') . '</a>';
    }
    return $links;
}

add_filter('plugin_action_links', 'comcure_plugin_action_links', 10, 2);

function comcure_admin_menu() {
    global $page;
    if (!in_array($page, array(
                'comcure-overview',
                'comcure-backups',
                'comcure-settinfs',
            ))) {
        $page = "comcure-overview";
    }
    add_submenu_page('plugins.php', __('Cloud Backups for your Website'), __('Comcure'), 'manage_options', $page, 'comcure_init');
}

function comcure_get($get = 'page') {
    if (isset($_GET[$get])) {
        return $_GET[$get];
    }
    return false;
}