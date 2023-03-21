<?php

/*
 * Plugin Name: Advanced Custom Fields: Term MultiSelect
 * Plugin URI: https://github.com/Loubal70/acf-term-multiselect
 * Description: Choose from any term
 * Version: 1.0
 * Author: Louis Boulanger
 * Author URI: https://louis-boulanger.fr/
 * GitHub Plugin URI: https://github.com/Loubal70/acf-term-multiselect
*/

load_plugin_textdomain( 'acf-term-multiselect', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );


add_action('acf/include_field_types', function($version) {
	include_once('acf_field_multiselect.php');
});

?>
