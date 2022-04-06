<?php
/*
* Plugin Name: Unlimited Elements for Elementor
* Plugin URI: http://unlimited-elements.com
* Description: Unlimited Elements - Huge Widgets Pack for Elementor Website Builder, with html/css/js widget creator and editor
* Author: Unlimited Elements
* Version: 1.5.4
* Author URI: http://unlimited-elements.com
* Text Domain: unlimited-elements-for-elementor
* Domain Path: /languages
* 
* Tested up to: 5.9
* Elementor tested up to: 3.5.6
* Elementor Pro tested up to: 3.6.3
*/

if(!defined("UNLIMITED_ELEMENTS_INC"))
	define("UNLIMITED_ELEMENTS_INC", true);

	if ( ! function_exists( 'unl_fs' ) ) {
    // Create a helper function for easy SDK access.
    function unl_fs() {
        global $unl_fs;

        if ( ! isset( $unl_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/provider/freemius/start.php';

            $unl_fs = fs_dynamic_init( array(
                'id'                  => '4036',
                'slug'                => 'unlimited-elements-for-elementor',
				'premium_slug'        => 'unlimited-elements-pro',            
                'type'                => 'plugin',
                'public_key'          => 'pk_719fa791fb45bf1896e3916eca491',
                'is_premium'          => false,
				'premium_suffix'      => '(Pro)',            
            	'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'has_affiliation'     => 'all',
                'menu'                => array(
                    'slug'           => 'unlimitedelements',
                    'support'        => false,
                )
            ) );
        }

        return $unl_fs;
    }
	
    // Init Freemius.
    unl_fs();
    // Signal that SDK was initiated.
    do_action( 'unl_fs_loaded' );
}	
	
$mainFilepath = __FILE__;
$currentFolder = dirname($mainFilepath);
$pathProvider = $currentFolder."/provider/";


try{
	
	if(class_exists("GlobalsUC"))
		define("UC_BOTH_VERSIONS_ACTIVE", true);
	else{
		
		$pathAltLoader = $pathProvider."provider_alt_loader.php";
		if(file_exists($pathAltLoader)){
			
			require $pathAltLoader;
		}else{
			require_once $currentFolder.'/includes.php';
			
			require_once  GlobalsUC::$pathProvider."core/provider_main_file.php";
		}
		
	}
	
}catch(Exception $e){
	$message = $e->getMessage();
	$trace = $e->getTraceAsString();
	
	echo "<br>";
	echo esc_html($message);
	echo "<pre>";
	print_r($trace);
}

