<?php
/**
 * Plugin Name: Ballpark Calculator
 * Description: Clinical trial cost calculator API
 * Version: 1.0.0
 * Author: Cromos
 * Requires PHP: 7.2
 */

defined('ABSPATH') || exit;

define('BALLPARK_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'BallparkCalculator\\';
    $base_dir = BALLPARK_PLUGIN_DIR . 'src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Register REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route('ballpark/v1', '/calculate', array(
        'methods'  => 'POST',
        'callback' => 'ballpark_calculate_handler',
        'permission_callback' => '__return_true',
    ));
});

function ballpark_calculate_handler(WP_REST_Request $request) {
    $data = $request->get_json_params();
    
    if (empty($data)) {
        return new WP_Error('no_data', 'No input data provided', array('status' => 400));
    }
    
    try {
        $config = new BallparkCalculator\Config\CountryRates(
            BALLPARK_PLUGIN_DIR . 'ballpark_constants.php'
        );
        
        $calculator = new BallparkCalculator\Calculator\BallparkCalculator($config);
        
        $qualType = isset($data['qualification_visit_type']) ? $data['qualification_visit_type'] : 'on-site';
        $initType = isset($data['initiation_visit_type']) ? $data['initiation_visit_type'] : 'on-site';
        $closeType = isset($data['closeout_visit_type']) ? $data['closeout_visit_type'] : 'on-site';
        
        $project = new BallparkCalculator\Model\ProjectInput(
            isset($data['enrollment_months']) ? (int)$data['enrollment_months'] : 12,
            isset($data['treatment_months']) ? (int)$data['treatment_months'] : 0,
            isset($data['followup_months']) ? (int)$data['followup_months'] : 0,
            $qualType,
            $initType,
            $closeType,
            isset($data['sae_rate']) ? (float)$data['sae_rate'] : 0.15,
            isset($data['susars_weeks']) ? (int)$data['susars_weeks'] : 13,
            isset($data['vendors']) ? (int)$data['vendors'] : 1,
            isset($data['investigator_grant']) ? (float)$data['investigator_grant'] : null
        );
        
        $regions = array('US', 'EU_CEE', 'EU_West', 'Non_EU');
        
        foreach ($regions as $region) {
            $key = strtolower(str_replace('_', '', $region));
            $countryData = null;
            
            if (isset($data['countries'][$key])) {
                $countryData = $data['countries'][$key];
            } elseif (isset($data['countries'][$region])) {
                $countryData = $data['countries'][$region];
            }
            
            if ($countryData) {
                $sites = isset($countryData['sites']) ? (int)$countryData['sites'] : 0;
                if ($sites > 0) {
                    $project->addCountry(new BallparkCalculator\Model\CountryInput(
                        $region,
                        $sites,
                        isset($countryData['patients']) ? (int)$countryData['patients'] : 0,
                        isset($countryData['monitoring_onsite']) ? (int)$countryData['monitoring_onsite'] : 0,
                        isset($countryData['monitoring_remote']) ? (int)$countryData['monitoring_remote'] : 0,
                        isset($countryData['unblinded_visits']) ? (int)$countryData['unblinded_visits'] : 0,
                        isset($countryData['countries_in_region']) ? (int)$countryData['countries_in_region'] : null
                    ));
                }
            }
        }
        
        $results = $calculator->calculateAsArray($project);
        
        return rest_ensure_response($results);
        
    } catch (Exception $e) {
        return new WP_Error('calc_error', $e->getMessage(), array('status' => 500));
    }
}

add_action('wp_enqueue_scripts', function () {
    if (!is_page('calculator')) {
        return;
    }

    wp_enqueue_script(
        'ballpark-calc',
        plugin_dir_url(__FILE__) . 'assets/calculator.js',
        array(),
        '1.0.0',
        true
    );

    wp_localize_script('ballpark-calc', 'ballparkConfig', array(
        'apiUrl' => rest_url('ballpark/v1/calculate'),
        'nonce'  => wp_create_nonce('wp_rest'),
    ));
});
