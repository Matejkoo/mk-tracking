<?php
/*
Plugin Name: MK Tracking
Plugin URI: http://www.mateuszkolasa.pl/
Description: Plugin tracks user behaviour and save result into database.
Version: 1.0
Author: Mateusz Kolasa
Author URI: http://www.mateuszkolasa.pl/
License:
*/
add_action('admin_menu', 'mktracking_menu');
add_action('admin_init', 'mktracking_register_settings');
add_action('admin_init', 'mktracking_admin_js_init');
add_action('admin_init', 'mktracking_admin_css_init');
add_option('mktracking_json');

// Init JS files for administration panel
function mktracking_admin_js_init()
{
    wp_register_script('angular', plugins_url('assets/js/angular.min.js', __FILE__));
    wp_register_script('angular-filter', plugins_url('assets/js/angular-filter.js', __FILE__));
    wp_register_script('highcharts', plugins_url('assets/js/highcharts.js', __FILE__));
    wp_register_script('highcharts-ng', plugins_url('assets/js/highcharts-ng.js', __FILE__));
    wp_register_script('app', plugins_url('assets/js/app.js', __FILE__));
    wp_register_script('bootstrap', plugins_url('assets/js/bootstrap.min.js', __FILE__));
}

// Init CSS files for administration panel
function mktracking_admin_css_init()
{
    wp_register_style('main', plugins_url('assets/css/main.css', __FILE__));
    wp_register_style('boot', plugins_url('assets/css/bootstrap.css', __FILE__));
    wp_register_style('boottheme', plugins_url('assets/css/bootstrap-theme.css', __FILE__));
}

function mktracking_menu()
{
    $page = add_menu_page('MK Tracking', 'MK Tracking', 'administrator', 'mktracking', 'mktracking_init');
    add_action('admin_print_styles-' . $page, 'mktracking_styles');
    add_action('admin_print_scripts-' . $page, 'mktracking_scripts');
}

function mktracking_styles()
{
    wp_enqueue_style('main');
    wp_enqueue_style('boot');
    wp_enqueue_style('boottheme');
}

function mktracking_scripts()
{
    wp_enqueue_script('angular');
    wp_enqueue_script('angular-filter');
    wp_enqueue_script('highcharts');
    wp_enqueue_script('highcharts-ng');
    wp_enqueue_script('app');
    wp_enqueue_script('bootstrap');
}

function mktracking_register_settings()
{
    // do nothing...
}

// Function creates SQL table for tracking data, called once on plugin activation
function mktracking_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mktracking';
    $sql = "CREATE TABLE $table_name (
          `event_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
          `event_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `event_name` varchar(200) NOT NULL,
          `event_value` varchar(200) NOT NULL,
          `event_referral` TEXT NOT NULL,
        PRIMARY KEY (event_id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'mktracking_install');

/**
 * Add a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
function mktracking_add_dashboard_widgets()
{

    wp_add_dashboard_widget(
        'mktracking_dashboard_widget',
        'MK Tracking Widget',
        'mktracking_dashboard_widget_function'
    );
}

add_action('wp_dashboard_setup', 'mktracking_add_dashboard_widgets');

// Function hooked into theme, getting information about tracking
function mktracking_get_data()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mktracking';

    if ($_GET['endpoint'] == "input") {
        if (isset($_GET['eventName']) && isset($_GET['eventValue']) && $_SERVER['HTTP_REFERER'] != "") {
            // Save data in database
            $wpdb->insert(
                $table_name,
                array(
                    'event_name' => $_GET['eventName'],
                    'event_value' => $_GET['eventValue'],
                    'event_referral' => $_SERVER['HTTP_REFERER']
                )
            );
        }
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='); // equivalent with 1x1 gif/png image file
    }
}

// Data is convert to JSON and is available for GET/POST method
function mktracking_get_json()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'mktracking';

    $results = $wpdb->get_results("SELECT * FROM " . $table_name);
    update_option('mktracking_json', $results);
    return $results;
}

// Function displays data using AJAX
function mktracking_display_data()
{
    mktracking_get_json();
    ?>
    <div class="wrap" ng-app="mkTrackingApp">
        <h2>MK Tracking
            <small>v1.0 <strong>Beta</strong></small>
        </h2>
        <div class="container-fluid">
            <div class="row" ng-controller="listCtrl">
                <span class="label label-primary">Ordered By: {{orderByField}}</span>
                <span class="label label-primary">Reverse Sort: {{reverseSort}}</span>
                <table class="table">
                    <thead class="list-th">
                        <tr>
                            <th>
                                <a href="#" ng-click="orderByField='event_id'; reverseSort = !reverseSort">
                                    ID <span ng-show="orderByField == 'event_id'"><span ng-show="!reverseSort"></span><span
                                            ng-show="reverseSort"></span></span></a>
                            </th>
                            <th>
                                <a href="#" ng-click="orderByField='event_date'; reverseSort = !reverseSort">
                                    Date <span ng-show="orderByField == 'event_date'"><span
                                            ng-show="!reverseSort"></span><span
                                            ng-show="reverseSort"></span></span></a>
                            </th>
                            <th>
                                <a href="#" ng-click="orderByField='event_name'; reverseSort = !reverseSort">
                                    Name <span ng-show="orderByField == 'event_name'"><span
                                            ng-show="!reverseSort"></span><span
                                            ng-show="reverseSort"></span></span></a>
                            </th>
                            <th>
                                <a href="#" ng-click="orderByField='event_value'; reverseSort = !reverseSort">
                                    Value <span ng-show="orderByField == 'event_value'"><span
                                            ng-show="!reverseSort"></span><span ng-show="reverseSort"></span></span></a>
                            </th>
                            <th>
                                <a href="#" ng-click="orderByField='event_referral'; reverseSort = !reverseSort">
                                    Referral URL <span ng-show="orderByField == 'event_referral'"><span
                                            ng-show="!reverseSort"></span><span ng-show="reverseSort"></span></span></a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr ng-repeat="item in list|orderBy:orderByField:reverseSort">
                        <td>{{item.event_id}}</td>
                        <td>{{item.event_date}}</td>
                        <td>{{item.event_name}}</td>
                        <td>{{item.event_value}}</td>
                        <td>{{item.event_referral}}</td>
                    </tbody>
                </table>
            </div>
            <hr>
            <div class="row" ng-controller="listCtrl">
                <h4>Grouping and count same names:</h4>
                <ul class="list-group">
                    <li class="list-group-item" ng-repeat="(key, value) in list | groupBy: 'event_name' as result">
                        <h4>{{ key }} <span class="badge"
                                            ng-init="addCount(result[key].length)">{{result[key].length}}</span></h4>
                        <ul>
                            <li ng-repeat="element in value" ng-init="addName(element.event_name)">Value: <strong>{{
                                    element.event_value }}</strong>,
                                <br>Date: <strong>{{ element.event_date }}</strong>
                                <br>Referral URL: <strong>{{ element.event_referral }}</strong>
                                <br><br>
                            </li>
                        </ul>
                    </li>
                </ul>
                <div class="row">
                    <highchart id="chart1" config="chartConfig" class="span10"></highchart>
                </div>
            </div>
        </div>
    </div>
<?php
}

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function mktracking_dashboard_widget_function()
{
    ?> You can follow tracking <a href="<?php echo get_home_url()."/wp-admin/admin.php?page=mktracking" ?>">here</a>. <?php
}

/**
 * Create the function to output the contents of our Plugin Page.
 */
function mktracking_init()
{
    mktracking_display_data();
}

?>