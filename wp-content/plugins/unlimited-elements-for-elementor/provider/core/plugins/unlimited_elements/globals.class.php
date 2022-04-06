<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com / Valiano
 * @copyright (C) 2012 Unite CMS, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');

class GlobalsUnlimitedElements{
	
	public static $enableInsideNotification = true;
	public static $insideNotificationText = "Unlock Access To All PRO Widgets and Features.  <a href='https://unlimited-elements.com/pricing/' target='_blank'>Upgrade Now</a> ";
	public static $insideNotificationUrl = "https://unlimited-elements.com/blackfriday/";
	
	public static $showAdminNotice = false;
	public static $arrAdminNotice = array(
		"id"=>"black_friday_sale",
		//"text"=>"Birthday Sale IS Here!",
		"banner"=>"banner-black-friday-2021.jpg",
		//"type"=>"advanced",
		"type"=>"banner",	//advanced,banner
		//"button_text"=>"Show Me More",
		"button_link"=>"https://unlimited-elements.com/blackfriday/",
		"expire"=>"",
		"free_only"=>true,
		"no-notice-wrap"=>true
	);
	
	const PLUGIN_NAME = "unlimitedelements";
   	const VIEW_ADDONS_ELEMENTOR = "addons_elementor";	
   	const VIEW_LICENSE_ELEMENTOR = "licenseelementor";
   	const VIEW_SETTINGS_ELEMENTOR = "settingselementor";
   	const VIEW_TEMPLATES_ELEMENTOR = "templates_elementor";
   	const VIEW_SECTIONS_ELEMENTOR = "sections_elementor";
   	const VIEW_CUSTOM_POST_TYPES = "custom_posttypes";
   	const VIEW_ICONS = "svg_shapes";
   	const VIEW_BACKGROUNDS = "backgrounds";
   	
   	const LINK_BUY = "https://unlimited-elements.com/pricing/";
   	
   	const SLUG_BUY_BROWSER = "page=unlimitedelements-pricing";
   	
   	const GENERAL_SETTINGS_KEY = "unlimited_elements_general_settings";
   	const ADDONSTYPE_ELEMENTOR = "elementor";
   	const ADDONSTYPE_ELEMENTOR_TEMPLATE = "elementor_template";
   	const ADDONSTYPE_CUSTOM_POSTTYPES = "posttype";
   	
	const PLUGIN_TITLE = "Unlimited Elements";
   	const POSTTYPE_ELEMENTOR_LIBRARY = "elementor_library";
   	const META_TEMPLATE_TYPE = '_elementor_template_type';
   	const META_TEMPLATE_SOURCE = "_unlimited_template_source";	//the value is unlimited
   	const META_TEMPLATE_SOURCE_NAME = "_unlimited_template_sourceid";
   	
	const POSTTYPE_UNLIMITED_ELEMENS_LIBRARY = "unelements_library";
	
   	const ALLOW_FEEDBACK_ONUNINSTALL = false;
   	const EMAIL_FEEDBACK = "support@unitecms.net";
   	
   	const FREEMIUS_PLUGIN_ID = "4036";
   	
   	const LINK_HELP_POSTSLIST = "https://unlimited-elements.helpscoutdocs.com/article/69-post-list-query-usage";
   	
   	const PREFIX_TEMPLATE_PERMALINK = "unlimited-";

   	public static $enableCPT = false;
   	public static $urlTemplatesList;
   	public static $renderingDynamicData;
   	
   	
   	/**
   	 * init globals
   	 */
   	public static function initGlobals(){
   		
   		self::$urlTemplatesList = admin_url("edit.php?post_type=elementor_library&tabs_group=library");
		
   		
   	}
   	
}


GlobalsUnlimitedElements::initGlobals();

