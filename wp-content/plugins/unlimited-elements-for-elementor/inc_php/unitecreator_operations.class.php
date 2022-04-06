<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');


	class UCOperations extends UniteElementsBaseUC{
		
		private static $arrGeneralSettings = null;
		private static $arrLayoutsGeneralSettings = null;
		private static $arrCustomSettingsCache = array();
		private static $arrUrlThumbCache = array();
		
		
		const GENERAL_SETTINGS_OPTION = "unitecreator_general_settings";

		
		private function a_______GENERAL_SETTING________(){}
		
		
		
		/**
		 * get general settings
		 */
		public function getGeneralSettings(){
			
			if(self::$arrGeneralSettings === null){
				$objSettings = $this->getGeneralSettingsObject();
				self::$arrGeneralSettings = $objSettings->getArrValues();
			}
			
			return(self::$arrGeneralSettings);
		}
		
		
		/**
		 * get general settings values based on custom object
		 */
		public function getGeneralSettingsCustomObject($filepathSettings){
			
			$arrValues = UniteProviderFunctionsUC::getOption(self::GENERAL_SETTINGS_OPTION);
			
			$objSettings = new UniteCreatorSettings();
			$objSettings->loadXMLFile($filepathSettings);
			
			if(!empty($arrValues))
				$objSettings->setStoredValues($arrValues);
			
			return($arrValues);
		}
		
		/**
		 * get general settings key
		 */
		private function getGeneralSettingsKey($key){
			
			if($key == self::GENERAL_SETTINGS_OPTION)
				return($key);
			
			if($key == "general_settings")
				return(self::GENERAL_SETTINGS_OPTION);
			
			$key = "unite_creator_".$key;
			
			return($key);
		}
		
		
		
		
		/**
		 * get general settings object
		 */
		public function getGeneralSettingsObject(){
			
			$filepathSettings = GlobalsUC::$pathSettings."general_settings.xml";
			
			$objSettings = new UniteCreatorSettings();
			$objSettings->loadXMLFile($filepathSettings);
			
			$objSettings = UniteProviderFunctionsUC::applyFilters(UniteCreatorFilters::FILTER_MODIFY_GENERAL_SETTINGS, $objSettings);
			
			$arrValues = UniteProviderFunctionsUC::getOption(self::GENERAL_SETTINGS_OPTION);
						
			if(!empty($arrValues))
				$objSettings->setStoredValues($arrValues);
			
			return($objSettings);
		}
		
		
		/**
		 * update general settings
		 */
		public function updateGeneralSettingsFromData($data, $isValidate = true, $settingsKey = self::GENERAL_SETTINGS_OPTION){
			
			$arrValues = UniteFunctionsUC::getVal($data, "settings_values");
			
			//validations:
			if($isValidate == true)
				UniteProviderFunctionsUC::doAction(UniteCreatorFilters::ACTION_VALIDATE_GENERAL_SETTINGS, $arrValues, $settingsKey);
			
			
			$arrCurrentSettings = UniteProviderFunctionsUC::getOption($settingsKey);
			if(empty($arrCurrentSettings))
				$arrCurrentSettings = array();
			
			if(empty($arrValues))
				$arrValues = array();
				
			$arrValues = array_merge($arrCurrentSettings, $arrValues);
			
			//clear cache
			if(isset(self::$arrCustomSettingsCache[$settingsKey]))
				unset(self::$arrCustomSettingsCache[$settingsKey]);
			
			UniteProviderFunctionsUC::updateOption($settingsKey, $arrValues);
			
		}
		
		
		/**
		 * validate that there are no keys in custom settings from general
		 */
		private function validateNoGeneralSettingsKeysInCustomSettings($arrValues){
			
			if(is_array($arrValues) == false)
				return(false);
							
			$arrSettings = $this->getGeneralSettings();			
			if(is_array($arrSettings) == false)
				return(false);
				
			$arrIntersect = array_intersect_key($arrSettings, $arrValues);
			if(empty($arrIntersect))
				return(false);
								
			//----- invalid:
			
			$strIntersect = print_r($arrIntersect, true);
			UniteFunctionsUC::throwError("The custom settings should not contain general settings keys:" . $strIntersect);
						
		}
		
		/**
		 * update unlimited settings
		 */
		public function updateUnlimitedElementsGeneralSettings($arrValues){
			
			$key = "unlimited_elements_general_settings";
			$customSettingsKey = self::getGeneralSettingsKey($key);
				
			$data = array();
			$data["settings_values"] = $arrValues;
			$this->updateGeneralSettingsFromData($data, false, $customSettingsKey);
			
		}
		
		/**
		 * update custom settings from data
		 */
		public function updateCustomSettingsFromData($data){
			
			$arrValues = UniteFunctionsUC::getVal($data, "settings_values");
			$key = UniteFunctionsUC::getVal($data, "settings_key");
						
			//update general settings
			if($key == "general_settings"){
				$this->validateNoGeneralSettingsKeysInCustomSettings($arrValues);
				$this->updateGeneralSettingsFromData($data, false);
			}
			else{
				
				$customSettingsKey = self::getGeneralSettingsKey($key);
				
				$this->updateGeneralSettingsFromData($data, false, $customSettingsKey);
			}
			
		}
		
		
		/**
		 * get google fonts from general settings
		 */
		public function getGeneralSettingsGoogleFonts(){
			
			$arrSettings = $this->getGeneralSettings();
			$arrFonts = array();
			$numFonts = 4;
			for($i=0;$i<$numFonts;$i++){
				$fontName = UniteFunctionsUC::getVal($arrSettings, "google_font{$i}_name");
				$fontName = trim($fontName);
				if(empty($fontName))
					continue;
				$fontLink = UniteFunctionsUC::getVal($arrSettings, "google_font{$i}_link");
				if(empty($fontLink))
					continue;
				$fontLink = trim($fontLink);
				$fontLink = str_replace("https://fonts.googleapis.com/css?family=", "", $fontLink);
				$fontLink = str_replace("http://fonts.googleapis.com/css?family=", "", $fontLink);
				if(empty($fontLink))
					continue;
				$arrFonts[$fontName] = $fontLink;
			}
			return($arrFonts);
		}
		
		private function a_________CUSTOM_SETTINGS___________(){}
		
		/**
		 * get raw values from general settings
		 */
		public function getCustomSettingsValues($customSettingsKey){
			
			$settingsKey = self::getGeneralSettingsKey($customSettingsKey);
			
			$arrValues = UniteProviderFunctionsUC::getOption($settingsKey);
						
			return($arrValues);
		}
		
		/**
		 * get raw values from general settings
		 */
		public function getCustomSettingsObject($filepathSettings, $settingsKey){
			
			$objSettings = new UniteCreatorSettings();
			$objSettings->loadXMLFile($filepathSettings);
						
			$arrValues = $this->getCustomSettingsValues($settingsKey);
			
			if(!empty($arrValues))
				$objSettings->setStoredValues($arrValues);
						
			return($objSettings);
		}
		
		
		/**
		 * get raw values from general settings
		 */
		public function getCustomSettingsObjectValues($filepathSettings, $settingsKey){
			
			if(isset(self::$arrCustomSettingsCache[$settingsKey]))
				return(self::$arrCustomSettingsCache[$settingsKey]);
			
			$objSettings = $this->getCustomSettingsObject($filepathSettings, $settingsKey);
						
			$arrValues = $objSettings->getArrValues();
			
			self::$arrCustomSettingsCache[$settingsKey] = $arrValues;
			
			return($arrValues);
		}
		
		private function a__________OTHER_FUNCTIONS___________(){}
		
		/**
		 * check instagram update
		 */
		public function checkInstagramRenewToken(){
			
			try{
				
				//try to upgrade instagram if exists
				$objServices = new UniteServicesUC();
				$objServices->includeInstagramAPI();
				
				$isRenewed = HelperInstaUC::checkRenewAccessToken_onceInAWhile();
				
			}catch(Exception $e){}
		}
		
		/**
		 * get error message html
		 */
		public function getErrorMessageHtml($message, $trace=""){
			
			$html = '<div class="unite-error-message">';
			$html .= '<div class="unite-error-message-inner">';
			$html .= $message;
			$html .= '</div>';
			
			if(!empty($trace)){
				
				$html .= '<div class="unite-error-trace">';
				$html .= "<pre>{$trace}</pre>";
				$html .= "</div>";
			}
				
			$html.= '</div>';
			
			return($html);
		}
		
		
		
		/**
		 * put error mesage from the module
		 */
		public function putModuleErrorMessage($message, $trace = ""){
			
			echo self::getErrorMessageHtml($message,$trace);
			
		}
		
		/**
		 * get thumb width from thumb size
		 */
		protected function getThumbWidthFromSize($sizeName){
						
			switch($sizeName){
				case GlobalsUC::THUMB_SIZE_NORMAL:
					$size = GlobalsUC::THUMB_WIDTH;
				break;
				case GlobalsUC::THUMB_SIZE_LARGE:
					$size = GlobalsUC::THUMB_WIDTH_LARGE;
				break;
				default:
					$size = GlobalsUC::THUMB_WIDTH;
				break;
			}
			
			return($size);
		}
		
		
		/**
		 * create thumbs from image by url
		 * the image must be relative path to the platform base
		 */
		public function createThumbs($urlImage, $thumbSize = null){
						
			if(empty($urlImage))
				UniteFunctionsUC::throwError("empty image url");
			
			$thumbWidth = $this->getThumbWidthFromSize($thumbSize);
						
			$urlImage = HelperUC::URLtoRelative($urlImage);
			
			$info = HelperUC::getImageDetails($urlImage);
			
			//check thumbs path
			$pathThumbs = $info["path_thumbs"];
			
			if(!is_dir($pathThumbs))
				@mkdir($pathThumbs);
			
			if(!is_dir($pathThumbs))
				UniteFunctionsUC::throwError("Can't make thumb folder: {$pathThumbs}. Please check php and folder permissions");
			
			$filepathImage = $info["filepath"];
			
			$filenameThumb = $this->imageView->makeThumb($filepathImage, $pathThumbs, $thumbWidth);
			
			$urlThumb = "";
			if(!empty($filenameThumb)){
				$urlThumbs = $info["url_dir_thumbs"];
				$urlThumb = $urlThumbs.$filenameThumb;
			}
			
			return($urlThumb);
		}
		
		
		/**
		 * return thumb url from image url, return full url of the thumb
		 * if some error occured, return empty string
		 */
		public function getThumbURLFromImageUrl($urlImage, $imageID=null, $thumbSize=null){
							
			try{
				$imageID = trim($imageID);
				if(is_numeric($urlImage))
					$imageID = $urlImage;
									
				//try to get image id by url if empty
				//if(empty($imageID))
					//$imageID = UniteProviderFunctionsUC::getImageIDFromUrl($urlImage);
				
				if(!empty($imageID)){
					$urlThumb = UniteProviderFunctionsUC::getThumbUrlFromImageID($imageID, $thumbSize);
				}else{
					$urlThumb = $urlImage;
					//$urlThumb = $this->createThumbs($urlImage, $thumbSize);	
				}
								
				if(empty($urlThumb))
					return("");
									
				$urlThumb = HelperUC::URLtoFull($urlThumb);
								
				return($urlThumb);
				
			}catch(Exception $e){
				
				return("");
			}
			
			return("");			
		}
		
		
		/**
		 * get title param array
		 */
		private function getParamTitle(){
			
			$arr = array();
			
			$arr["type"] = "uc_textfield";
			$arr["title"] = "Title";
			$arr["name"] = "title";
			$arr["description"] = "";
			$arr["default_value"] = "";
			$arr["limited_edit"] = true;
			
			return($arr);
		}
		
		
		/**
		 * check that params always have item param on top
		 */
		public function checkAddParamTitle($params){
			
			if(empty($params)){
				$paramTitle = $this->getParamTitle();
				$params[] = $paramTitle;
				return($params);
			}
			
			//search for param title
			foreach($params as $param){
				$name = UniteFunctionsUC::getVal($param, "name");
				if($name == "title")
					return($params);
			}
			
			//if no title param - add it to top
			$paramTitle = $this->getParamTitle();
			array_unshift($params, $paramTitle);
			
			return($params);
		}
		
		
		/**
		* get bulk addon dialog from data
		 */
		public function getAddonBulkDialogFromData($data){
			
			require_once GlobalsUC::$pathViewsObjects."addon_view.class.php";
			$objAddonView = new UniteCreatorAddonView(false);
			
			$response = $objAddonView->getBulkDialogContents($data);
			
			return($response);
		}
		
		
		/**
		 * put debug of post custom fields
		 */
		public function putPostCustomFieldsDebug($postID){
			
			$post = get_post($postID);
			
			if(empty($post))
				return(false);
				
			$postTitle = $post->post_title;
			
			$arrCustomFields = UniteFunctionsWPUC::getPostMeta($postID);
			
			$htmlFields = HelperHtmlUC::getHtmlArrayTable($arrCustomFields, "No Meta Fields Found");
			
			echo "<br>Meta fields for post: $postTitle <br>";
			
			dmp($htmlFields);
			
		}
		
		
		private function a____________DATE____________(){}
		
		/**
		 * get nice display of date ranges, ex. 4-5 MAR 2021
		 */
		public function getDateRangeString($startTimeStamp, $endTimeStamp, $pattern = null){
			
		    $displayDate = "";
	        
		    $startDate = getDate($startTimeStamp);
	        $endDate = getDate($endTimeStamp);
		    
	        //--- check same date
	        
		    if($startDate["year"].$startDate["mon"].$startDate["mday"] == $endDate["year"].$endDate["mon"].$endDate["mday"]){
		    	
		        $displayDate = date('j M Y', $endTimeStamp);
		        return($displayDate);
		    }
		    
		    //--- check different years
	        
		    if($startDate["year"] != $endDate["year"]){		
	        	
	            $displayDate = date('j M Y', $startTimeStamp) . " - ". date('j M Y', $endTimeStamp);
	        	return($displayDate);
		    }
		        
		    //--- check same year
		    
            // diff days in the same month
		    if($startDate["mon"] == $endDate["mon"]) 
                $displayDate = date('j', $startTimeStamp) ."-". date('j M Y', $endTimeStamp);
                
            // diff months
            $displayDate = date('j M', $startTimeStamp) ." - ". date('j M Y', $endTimeStamp);
			
		    
		    return $displayDate;
		}
		
		
		private function a____________SCREENSHOTS____________(){}
		
		
		/**
		 * get filepath of layout screenshot. existing or new
		 */
		private function saveScreenshot_layout_getFilepath($objLayout, $ext){
			
			//check existing path
			$pathImage = $objLayout->getPreviewImageFilepath();
			
			if($pathImage){
				$isGenerated = UniteFunctionsUC::isPathUnderBase($pathImage, GlobalsUC::$path_images_screenshots);
				
				$info = pathinfo($pathImage);
				$extExisting = UniteFunctionsUC::getVal($info, "extension");
				if($extExisting == $ext && $isGenerated == true)
					return($pathImage);
			}
			
			
			$title = $objLayout->getTitle();
			$type = $objLayout->getLayoutType();
			
			$filename = "layout_".HelperUC::convertTitleToHandle($title);
			
			if(!empty($type))
				$filename .= "_".$type;
			
			$addition = "";
			$counter = 1;
			do{
				
				$filepath = GlobalsUC::$path_images_screenshots.$filename.$addition.".".$ext;
				$isExists = file_exists($filepath);
				
				$counter++;
				$addition = "_".$counter;
				
			}while($isExists == true);
			
			
			return($filepath);
		}
		
		
		
		/**
		 * save layout screenshot
		 */
		private function saveScreenshot_layout($layoutID, $screenshotData, $ext){
			
			$objLayout = new UniteCreatorLayout();
			$objLayout->initByID($layoutID);
			
			UniteFunctionsUC::checkCreateDir(GlobalsUC::$path_images_screenshots);
			
			//create filename
			$filepath = $this->saveScreenshot_layout_getFilepath($objLayout, $ext);
			
			//delete previous
			$pathExistingImage = $objLayout->getPreviewImageFilepath();
			$isGenerated = UniteFunctionsUC::isPathUnderBase($pathExistingImage, GlobalsUC::$path_images_screenshots);
			if($isGenerated == true && $pathExistingImage != $filepath && file_exists($pathExistingImage))
				@unlink($pathExistingImage);
			
			//write current
			UniteFunctionsUC::writeFile($screenshotData, $filepath);
			
			if(file_exists($filepath) == false){
				UniteFunctionsUC::throwError("The screenshot could not be created");
			}
			
			$urlScreenshot = HelperUC::pathToRelativeUrl($filepath);
			
			$objLayout->updateParam("preview_image", $urlScreenshot);
			$objLayout->updateParam("page_image", "");
			
			$urlScreenshotFull = HelperUC::URLtoFull($urlScreenshot);
			
			return($urlScreenshotFull);
		}
		
		
		
		/**
		 * save screenshot
		 * Enter description here ...
		 */
		public function saveScreenshotFromData($data){
			
			try{
				
				$source = UniteFunctionsUC::getVal($data, "source");
				$layoutID = UniteFunctionsUC::getVal($data, "layoutid");
				$screenshotData = UniteFunctionsUC::getVal($data, "screenshot_data");
				$ext = UniteFunctionsUC::getVal($data, "ext");
								
				UniteFunctionsUC::validateNotEmpty($layoutID, "layoutID");
								
				switch($ext){
					case "jpg":
						$screenshotData = $this->imageView->convertJPGDataToJPG($screenshotData);
					break;
					case "png":
						$screenshotData = $this->imageView->convertPngDataToPng($screenshotData);
					break;
					default:
						UniteFunctionsUC::throwError("wrong extension");
					break;
				}
				
				switch($source){
					case "layout":
						$urlScreenshot = $this->saveScreenshot_layout($layoutID, $screenshotData, $ext);
					break;
					case "addon":
						dmp("save addon screenshot");
					break;
					default:
						UniteFunctionsUC::throwError("wrong save source");
					break;
				}
				
			}catch(Exception $e){
				
				$errorMessage = $e->getMessage();
				$output = array();
				$output["error_message"] = $errorMessage;
				
				return($errorMessage);
			}
			
			$output = array();
			$output["url_screenshot"] = $urlScreenshot;
			$output["layoutid"] = $layoutID;
			
			return($output);
		}
		
		/**
		 * get post list for select
		 */
		public function getPostListForSelectFromData($data, $addNotSelected = false){
			
			dmp("getPostListForSelect: function for overide");
			exit();
		}
		
		/**
		 * get post additions
		 */
		private function getPostAttributesFromData_getPostAdditions($data){

			$arrAdditions = array();
			
			$enableCustomFields = UniteFunctionsUC::getVal($data, "enable_custom_fields");
			$enableCustomFields = UniteFunctionsUC::strToBool($enableCustomFields);
			
			$enableCategory = UniteFunctionsUC::getVal($data, "enable_category");
			$enableCategory = UniteFunctionsUC::strToBool($enableCategory);
			
			$enableWoo = UniteFunctionsUC::getVal($data, "enable_woo");
			$enableWoo = UniteFunctionsUC::strToBool($enableWoo);
						
			if($enableCustomFields == true)
				$arrAdditions[] = GlobalsProviderUC::POST_ADDITION_CUSTOMFIELDS;
			
			if($enableCategory == true)
				$arrAdditions[] = GlobalsProviderUC::POST_ADDITION_CATEGORY;
			
			if($enableWoo == true)
				$arrAdditions[] = GlobalsProviderUC::POST_ADDITION_WOO;
			
				
			return($arrAdditions);
		}
		
		
		/**
		 * get post list for select
		 */
		public function getPostAttributesFromData($data){
			
			$postID = UniteFunctionsUC::getVal($data, "postid");
						
			//UniteFunctionsUC::validateNotEmpty($postID, "post id");

			require_once GlobalsUC::$pathViewsObjects."addon_view.class.php";
			require_once GlobalsUC::$pathProvider."views/addon.php";
			
			$objAddonView = new UniteCreatorAddonViewProvider();
			
			$arrPostAdditions = $this->getPostAttributesFromData_getPostAdditions($data);
			
			
			$arrParams = $objAddonView->getChildParams_post($postID, $arrPostAdditions);
			
			$response = array();
			$response["child_params_post"] = $arrParams;
			
			return($response);
		}
		
		
		/**
		 * 
		 * modify the text from widget
		 */
		public function modifyTextFromWidget($text){
			
			//convert current page
			
			if(strpos($text, "%current_page_url%") !== false){
				
				$urlPage = UniteFunctionsWPUC::getUrlCurrentPage(true);
				
				$text = str_replace("%current_page_url%", $urlPage, $text);
			}
			
			if(strpos($text, "%current_page_title%") !== false){
				
				$post = get_post();
				if($post){
					$title = $post->post_title;
					$text = str_replace("%current_page_title%", $title, $text);
				}
			}
			
			
			return($text);
		}
		
		
		
	}

?>