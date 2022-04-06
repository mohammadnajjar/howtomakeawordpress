<?php

/**
 * @package Unlimited Elements
 * @author UniteCMS http://unitecms.net
 * @copyright Copyright (c) 2016 UniteCMS
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

//no direct accees
defined ('UNLIMITED_ELEMENTS_INC') or die ('restricted aceess');

class UniteCreatorWpmlIntegrate{
	
	private $arrLanguages;
	private $arrShort;
	private $arrShortPrefix;
	private $isInited = false;
	private $activeLanguage;
	
	
	/**
	 * check if wpml exists
	 */
	public static function isWpmlExists(){
		
		if(defined("WPML_PLUGIN_PATH"))
			return(true);
		
		return(false);
	}
	
	
	/**
	 * init the languages
	 */
	private function init(){
		
		if($this->isInited == true)
			return(false);
		
		$this->arrLanguages = apply_filters( 'wpml_active_languages',NULL);
		
		if(empty($this->arrLanguages))
			$this->arrLanguages = array();
		
		$this->arrShort = array();
		$this->arrShortPrefix = array();
		
		$this->arrShortPrefix["__none__"] = __("Not Selected","unlimited-elements-for-elementor");
				
		//set active and short
		foreach($this->arrLanguages as $language){
			
			$code = UniteFunctionsUC::getVal($language, "code");
			$isActive = UniteFunctionsUC::getVal($language, "active");
			if($isActive == true){
				$this->activeLanguage = $code;
			}
			
			$langName = UniteFunctionsUC::getVal($language, "native_name");
			if(empty($langName))
				$langName = UniteFunctionsUC::getVal($language, "translated_name");
			
			$this->arrShort[$code] = $langName;
			$this->arrShortPrefix[$code] = $langName;
			
		}
		
		if(empty($this->activeLanguage))
			$this->activeLanguage = UniteFunctionsUC::getArrFirstValue($this->arrShortPrefix);
		
		$this->isInited = true;
		
	}
	
	
	/**
	 * get active languages
	 */
	public function getLanguagesShort($addPrefix = false){
		
		if(self::isWpmlExists() == false)
			return(array());
		
		$this->init();
		
		if($addPrefix == true)
			return($this->arrShortPrefix);
		
		return($this->arrShort);
	}
	
	/**
	 * get active language
	 */
	public function getActiveLanguage(){
		
		if(self::isWpmlExists() == false)
			return(array());
		
		$this->init();
		
		return($this->activeLanguage);
	}
	
	
	
}