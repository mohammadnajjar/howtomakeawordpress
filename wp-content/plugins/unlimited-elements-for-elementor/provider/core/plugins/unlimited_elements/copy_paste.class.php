<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');

class UniteCreatorElementorCopyPaste{
		
	
	/**
	 * add copy related scripts
	 */
	public function addCopyScripts(){
		
    	UniteProviderFunctionsUC::addjQueryInclude();
    		
	    HelperUC::addScriptAbsoluteUrl(HelperProviderCoreUC_EL::$urlCore."elementor/assets/uc_front_section_copy.js", "unlimited_elments_copy_section");
	    HelperUC::addStyleAbsoluteUrl(HelperProviderCoreUC_EL::$urlCore."elementor/assets/uc_front_section_copy.css", "unlimited_elments_copy_section_css");
	}

	/**
	 * add paste related scripts
	 */
	public function addPasteScripts(){
		
    	UniteProviderFunctionsUC::addjQueryInclude();
    		
	    HelperUC::addScriptAbsoluteUrl(HelperProviderCoreUC_EL::$urlCore."elementor/assets/uc_front_section_paste.js", "unlimited_elments_paste_section");
	    HelperUC::addStyleAbsoluteUrl(HelperProviderCoreUC_EL::$urlCore."elementor/assets/uc_front_section_paste.css", "unlimited_elments_paste_section_css");
	}
	
	
	/**
	 * print footer html
	 */
	public function onPrintFooterHtml_paste(){
		
		$currentPost = get_post();
		
		if(empty($currentPost))
			return(false);
			
		$postID = $currentPost->ID;
		
		$config = array();
		$config["ajax_url"] = GlobalsUC::$url_ajax_full;
		$config["post_id"] = $postID;
				
		$jsonConfig = UniteFunctionsUC::jsonEncodeForClientSide($config);
		
		?>
		
		<script>
		var g_ucPasteSectionConfig = <?php echo $jsonConfig?>;
		</script>
		
		<?php 
	}

	/**
	 * print footer html
	 */
	public function onPrintFooterHtml_copy(){
		
		$config = array();
		$config["ajax_url"] = HelperUC::URLtoFull(GlobalsUC::$url_ajax_full);
		
		$jsonConfig = UniteFunctionsUC::jsonEncodeForClientSide($config);
		
		?>
		
		<script>
		var g_ucCopySectionConfig = <?php echo $jsonConfig?>;
		</script>
		
		<?php 
	}
	
	
	/**
	 * get remote section zip from params
	 */
	private function pasteSection_getRemoteZip($arrParams){
		
		$postID = UniteFunctionsUC::getVal($arrParams, "post_id");
		$sectionID = UniteFunctionsUC::getVal($arrParams, "section_id");
		$urlAjax = UniteFunctionsUC::getVal($arrParams, "remote_ajax_url");
		
		$data = array();
		$data["action"] = "unlimitedelements_ajax_action";
		$data["client_action"] = "get_section_zip";
		$data["postid"] = $postID;
		$data["sectionid"] = $sectionID;
				
		try{
			
			$contentZIP = UniteFunctionsUC::getUrlContents($urlAjax, $data);
			
			if(strpos($contentZIP, "<!doctype html>") === 0)
				UniteFunctionsUC::throwError("Wrong return content");
			
		}catch(Exception $e){
			
			UniteFunctionsUC::throwError("Failed to paste section from remote site");			
		}
		
		return($contentZIP);
	}
	
	/**
	 * get paste params from params string
	 */
	private function getArrPasteParams($params){
				
		if(is_string($params) == false)
			UniteFunctionsUC::throwError("wrong paste params format");
		
		if(empty($params))
			UniteFunctionsUC::throwError("no paste params found");
					
		if(strpos($params, "---uc-section-copy") !== 0)
			UniteFunctionsUC::throwError("paste params - wrong format");
		
		$arrParams = explode("~", $params);
				
		if(count($arrParams) != 4)
			UniteFunctionsUC::throwError("wrong paste params number");
		
		$postID = $arrParams[1];
		$sectionID = $arrParams[2];
		$urlWebsite = $arrParams[3];
		
		$arrParams = array();
		$arrParams["post_id"] = $postID;
		$arrParams["section_id"] = $sectionID;
		$arrParams["remote_ajax_url"] = $urlWebsite;
		
		return($arrParams);		
	}
	
	
	/**
	 * on paste section ajax action
	 */
	public function pasteSectionAjaxAction($data){
						
		$paramsData = UniteFunctionsUC::getVal($data, "params_data");
		if(empty($paramsData))
			UniteFunctionsUC::throwError("no paste data found");
		
		$targetID = UniteFunctionsUC::getVal($data, "targetid");
		
		if(empty($targetID))
			UniteFunctionsUC::throwError("target post not given");
				
		//clean:
		$paramsData = str_replace("uesection--", "", $paramsData);
				
		//decode
		$paramsString = UniteFunctionsUC::decodeTextContent($paramsData);
		
		$arrParams = $this->getArrPasteParams($paramsString);
				
		$contentZip = $this->pasteSection_getRemoteZip($arrParams);
		
		if(empty($contentZip) || strlen($contentZip) == 0)
			UniteFunctionsUC::throwError("Failed to paste section");
		
		$objExporter = new UniteCreatorLayoutsExporterElementor();
		$objExporter->importElementorZipContentSection($contentZip, $targetID);
		
		return(true);
	}
	
	
	/**
	 * get section zip from data
	 */
	public function getSectionZipFromData($data){
		
		try{
			
			$postID = UniteFunctionsUC::getVal($data, "postid");
			$sectionID = UniteFunctionsUC::getVal($data, "sectionid");
			
			if(empty($postID))
				return(null);
				
			if(empty($sectionID))
				return(null);
							
			$objExporter = new UniteCreatorLayoutsExporterElementor();
			$arrData = $objExporter->exportElementorPost($postID, null, true,array("sectionid"=>$sectionID));
			if(empty($arrData))
				return(null);
				
			$filepath = UniteFunctionsUC::getVal($arrData, "filepath");
			
			$content = file_get_contents($filepath);
			
			echo $content;
			exit();
			
		}catch(Exception $e){
			
			dmp($e);
			
			return(null);
		}
				
	}
	
	/**
	 * init copy
	 */
	private function initCopy(){
		
		add_action('wp_print_footer_scripts', array($this, 'onPrintFooterHtml_copy'));
		
	}
	
	/**
	 * init paste
	 */
	private function initPaste(){
		
		add_action('wp_print_footer_scripts', array($this, 'onPrintFooterHtml_paste'));
		
	}
	
	/**
	 * init copy and paste, front
	 */
	public function init(){
	
		if(UniteCreatorElementorIntegrate::$enableCopySectionButton)
			$this->initCopy();

		if(UniteCreatorElementorIntegrate::$enablePasteSectionButton)
			$this->initPaste();
					
	}
	
}
