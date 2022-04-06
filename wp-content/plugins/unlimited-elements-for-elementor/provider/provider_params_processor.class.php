<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2012 Unite CMS, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');

class UniteCreatorParamsProcessor extends UniteCreatorParamsProcessorWork{
	
	private static $arrPostTypeTaxCache = array();
	
	
	/**
	 * add other image thumbs based of the platform
	 */
	protected function addOtherImageData($data, $name, $imageID){
		
		if(empty($data))
			$data = array();
		
		$imageID = trim($imageID);
		if(is_numeric($imageID) == false)
			return($data);
		
		$post = get_post($imageID);
		
		if(empty($post))
			return($data);
		
		$title = UniteFunctionsWPUC::getAttachmentPostTitle($post);
		$caption = 	$post->post_excerpt;
		$description = 	$post->post_content;
		
		$alt = UniteFunctionsWPUC::getAttachmentPostAlt($imageID);
		
		if(empty($alt))
			$alt = $title;
		
		$data["{$name}_title"] = $title;
		$data["{$name}_alt"] = $alt;
		$data["{$name}_description"] = $description;
		$data["{$name}_caption"] = $caption;
		$data["{$name}_imageid"] = $imageID;
		
		return($data);
	}
	
	
	/**
	 * add other image thumbs based of the platform
	 */
	protected function addOtherImageThumbs($data, $name, $imageID, $filterSizes = null){
		
		if(empty($data))
			$data = array();
		
		$imageID = trim($imageID);
		if(is_numeric($imageID) == false)
			return($data);
		
		$arrSizes = UniteFunctionsWPUC::getArrThumbSizes();
		
		$metaData = wp_get_attachment_metadata($imageID);
		$imageWidth = UniteFunctionsUC::getVal($metaData, "width");
		$imageHeight = UniteFunctionsUC::getVal($metaData, "height");
				
		$urlFull = UniteFunctionsWPUC::getUrlAttachmentImage($imageID);
		
		$data["{$name}_width"] = $imageWidth;
		$data["{$name}_height"] = $imageHeight;
		
		$metaSizes = UniteFunctionsUC::getVal($metaData, "sizes");
		
		foreach($arrSizes as $size => $sizeTitle){
			
			if(empty($size))
				continue;
			
			if($size == "full")
				continue;
			
			if(!empty($filterSizes) && array_search($size, $filterSizes) === false)
				continue;
			
			//change the hypen to underscore
			$thumbName = $name."_thumb_".$size;
			if($size == "medium" && empty($filterSizes))
				$thumbName = $name."_thumb";
			
			$thumbName = str_replace("-", "_", $thumbName);
			
			if(isset($data[$thumbName]))
				continue;
			
			$arrSize = UniteFunctionsUC::getVal($metaSizes, $size);
			
			$thumbWidth = UniteFunctionsUC::getVal($arrSize, "width");
			$thumbHeight = UniteFunctionsUC::getVal($arrSize, "height");
			
			$thumbWidth = trim($thumbWidth);
			
			$urlThumb = UniteFunctionsWPUC::getUrlAttachmentImage($imageID, $size);
			if(empty($urlThumb))
				$urlThumb = $urlFull;

			if(empty($thumbWidth) && $urlThumb == $urlFull){
				$thumbWidth = $imageWidth;
				$thumbHeight = $imageHeight;
			}
			
			$data[$thumbName] = $urlThumb;
			$data[$thumbName."_width"] = $thumbWidth;
			$data[$thumbName."_height"] = $thumbHeight;		
			
		}
		
		return($data);
	}
	
	
	/**
	 * get post data
	 */
	public function getPostData($postID, $arrPostAdditions = null){
		
		if(empty($postID))
			return(null);
		
		$post = get_post($postID);
		
		if(empty($post))
			return(null);
		
		try{
			
			$arrData = $this->getPostDataByObj($post, $arrPostAdditions);
			
			return($arrData);
						
		}catch(Exception $e){
			return(null);
		}
		
	}

	
	/**
	 * add custom fields to terms array
	 */
	private function addCustomFieldsToTermsArray($arrTermsOutput){
		
		if(empty($arrTermsOutput))
			return($arrTermsOutput);
		
		foreach($arrTermsOutput as $index => $term){
			
			$termID = $term["id"];
		
			$arrCustomFields = UniteFunctionsWPUC::getTermCustomFields($termID);
			
			if(empty($arrCustomFields))
				continue;
				
			$term = array_merge($term, $arrCustomFields);
			
			$arrTermsOutput[$index] = $term;
		}
		
		return($arrTermsOutput);
	}
	
	
	/**
	 * modify terms array for output
	 */
	public function modifyArrTermsForOutput($arrTerms, $taxonomy = "", $addCustomFields = false){
			
			$isWooCat = false;
			if($taxonomy == "product_cat" && UniteCreatorWooIntegrate::isWooActive())
				$isWooCat = true;
			
			if(empty($arrTerms))
				return(array());
				
			$arrOutput = array();
			
			$index = 0;
			foreach($arrTerms as $slug => $arrTerm){
				
				$item = array();
				
				$item["index"] = $index;
				$item["id"] = UniteFunctionsUC::getVal($arrTerm, "term_id");
				$item["slug"] = UniteFunctionsUC::getVal($arrTerm, "slug");
				$item["name"] = UniteFunctionsUC::getVal($arrTerm, "name");
				$item["description"] = UniteFunctionsUC::getVal($arrTerm, "description");
				$item["link"] = UniteFunctionsUC::getVal($arrTerm, "link");
				$item["parent_id"] = UniteFunctionsUC::getVal($arrTerm, "parent_id");
				$item["taxonomy"] = UniteFunctionsUC::getVal($arrTerm, "taxonomy");
				
				$index++;
				
				$current = UniteFunctionsUC::getVal($arrTerm, "iscurrent");
				
				$item["iscurrent"] = $current;
				
				$item["class_selected"] = "";
				if($current == true)
					$item["class_selected"] = "	uc-selected";
				
				if(isset($arrTerm["count"])){
					
					if($isWooCat == true){
						$item["num_posts"] = $arrTerm["count"];
						$item["num_products"] = $arrTerm["count"];
					}
					else
						$item["num_posts"] = $arrTerm["count"];
					
				}
				
				//get woo data
				if($isWooCat == true){
						
					$thumbID = get_term_meta($item["id"], 'thumbnail_id', true);
					$hasImage = !empty($thumbID);
					
					$item["has_image"] = $hasImage;
					
					if(!empty($thumbID))
						$item = $this->getProcessedParamsValue_image($item, $thumbID, array("name"=>"image"));
					
				}
								
				$arrOutput[] = $item;
			}
			
			//add custom fields
			if($addCustomFields == true)
				$arrOutput = $this->addCustomFieldsToTermsArray($arrOutput);
			
			
			return($arrOutput);
		}
	
	/**
	 * modify the meta value, process the special keywords
	 */
	private function modifyMetaValueForCompare($metaValue){
		
		switch($metaValue){
			case "{current_user_id}":
				$userID = get_current_user_id();
				if(empty($userID))
					$userID = "0";
				
				return($userID);
			break;
		}
		
		
		return($metaValue);
	}
	
	
	protected function z_______________POSTS____________(){}

	/**
	 * get post ids from post meta
	 */
	private function getPostListData_getIDsFromPostMeta($value, $name, $showDebugQuery){
		
		$postIDs = UniteFunctionsUC::getVal($value, $name."_includeby_postmeta_postid");
		
		$metaName = UniteFunctionsUC::getVal($value, $name."_includeby_postmeta_metafield");
		
		$errorMessagePrefix = "Get post ids from meta error: ";
		
		if(empty($metaName)){
			
				if($showDebugQuery == true)
					dmp($errorMessagePrefix." no meta field selected");
			
			return(null);
		}
		
		if(!empty($postIDs)){
			if(is_array($postIDs))
				$postID = $postIDs[0];
			else
				$postID = $postIDs;
		}
		else{		//current post
			
			$post = get_post();
			if(empty($post)){
				
				if($showDebugQuery == true)
					dmp($errorMessagePrefix." no post found");
				return(null);
			}
				
			$postID = $post->ID;
		}
		
		if(empty($postID)){
				
			if($showDebugQuery == true)
				dmp($errorMessagePrefix." no post found");
			
			return(null);
		}
		
		//show the post title
		if($showDebugQuery == true){
		
			$post = get_post($postID);
			$title = $post->post_title;
			$postType = $post->post_type;
			
			dmp("Getting post id's from meta fields from post: <b>$postID - $title ($postType) </b>");
		}
		
		$arrPostIDs = get_post_meta($postID, $metaName, true);
		
		if(is_array($arrPostIDs) == false){
			$arrPostIDs = explode(",", $arrPostIDs);
		}
		
		$isValidIDs = UniteFunctionsUC::isValidIDsArray($arrPostIDs);
		
		if(empty($arrPostIDs) || $isValidIDs == false){
		
			if($showDebugQuery){
				
				$metaKeys = UniteFunctionsWPUC::getPostMetaKeys($postID, null, true);
				if(empty($metaKeys))
					$metaKeys = array();
				
				dmp($errorMessagePrefix." no post ids found");
					
				if(array_search($metaName, $metaKeys) === false){
					dmp("maybe you intent to use one of those meta keys:");
					dmp($metaKeys);
				}
			}
			
			return(null);
		}
		
		if($showDebugQuery == true){
			$strPosts = implode(",", $arrPostIDs);
			dmp("Found post ids : $strPosts");
		}
		
		return($arrPostIDs);
	}
	
	
	/**
	 * get post ids from php function
	 */
	private function getPostListData_getIDsFromPHPFunction($value, $name, $showDebugQuery){
		
		$functionName = UniteFunctionsUC::getVal($value, $name."_includeby_function_name");
		
		$errorTextPrefix = "get post id's by PHP Function error: ";
				
		if(empty($functionName)){
			
			if($showDebugQuery)
				dmp($errorTextPrefix."no functon name given");
			
			return(null);
		}

		if(is_string($functionName) == false)
			return(false);
		
		if(strpos($functionName, "get") !== 0){
			
			if($showDebugQuery)
				dmp($errorTextPrefix."function <b>$functionName</b> should start with 'get'. like getMyPersonalPosts()");
			
			return(null);
		}
		
		if(function_exists($functionName) == false){
			
			if($showDebugQuery)
				dmp($errorTextPrefix."function <b>$functionName</b> not exists.");
			
			return(null);
		}
				
		$argument = UniteFunctionsUC::getVal($value, $name."_includeby_function_addparam");
		
		$arrIDs = call_user_func_array($functionName, array($argument));
		
		$isValid = UniteFunctionsUC::isValidIDsArray($arrIDs);
		
		if($isValid == false){
			
			if($showDebugQuery)
				dmp($errorTextPrefix."function <b>$functionName</b> returns invalid id's array.");
			
			return(null);
		}
		
		if($showDebugQuery == true){
			dmp("php function <b>$functionName(\"$argument\")</b> output: ");
			dmp($arrIDs);
		}
		
		return($arrIDs);
	}
		
	/**
	 * get post category taxonomy
	 */
	private function getPostCategoryTaxonomy($postType){
		
		if(isset(self::$arrPostTypeTaxCache[$postType]))
			return(self::$arrPostTypeTaxCache[$postType]);
		
		$taxonomy = "category";
		
		if($postType == "post" || $postType == "page"){
			
			self::$arrPostTypeTaxCache[$postType] = $taxonomy;
			return($taxonomy);
		}
		
		//for woo
		if($postType == "product"){
			$taxonomy = "product_cat";
			self::$arrPostTypeTaxCache[$postType] = $taxonomy;
			return($taxonomy);
		}
			
		//search in tax data
		$arrTax = UniteFunctionsWPUC::getPostTypeTaxomonies($postType);
		
		if(empty($arrTax)){
			
			self::$arrPostTypeTaxCache[$postType] = $taxonomy;
			return($taxonomy);
		}
		
		$taxonomy = null;
		foreach($arrTax as $key=>$name){
				
				$name = strtolower($name);

				if(empty($taxonomy))
					$taxonomy = $key;
				
				if($name == "category")
					$taxonomy = $key;
		}
		
		if(empty($taxonomy))
			$taxonomy = "category";
		
		self::$arrPostTypeTaxCache[$postType] = $taxonomy;
		
		return($taxonomy);
	}
	
	/**
	 * get post main category from the list of terms
	 */
	private function getPostMainCategory($arrTerms, $postID){
		
		
		//get term data
		
		if(count($arrTerms) == 1){		//single
			$arrTermData = UniteFunctionsUC::getArrFirstValue($arrTerms);
			return($arrTermData);
		}
		
		$yoastMainCategory = get_post_meta($postID, "_yoast_wpseo_primary_category", true);
		
		if(empty($yoastMainCategory)){
			
			unset($arrTerms["uncategorized"]);
			$arrTermData = UniteFunctionsUC::getArrFirstValue($arrTerms);			
			
			return($arrTermData);
		}
		
		foreach($arrTerms as $term){
			
			$termID = UniteFunctionsUC::getVal($term, "term_id");
			
			if($termID == $yoastMainCategory)
				return($term);			
		}
		
		unset($arrTerms["uncategorized"]);
		$arrTermData = UniteFunctionsUC::getArrFirstValue($arrTerms);			
		
		return($arrTermData);
	}
	
	
	/**
	 * get post category fields
	 * for single category
	 * choose category from list
	 */
	private function getPostCategoryFields($postID, $post){
		
		//choose right taxonomy
		$postType = $post->post_type;
		
		$taxonomy = $this->getPostCategoryTaxonomy($postType);
				
		if(empty($postID))
			return(array());
		
		$arrTerms = UniteFunctionsWPUC::getPostSingleTerms($postID, $taxonomy);
		
		//get single category
		if(empty($arrTerms))
			return(array());
		
		$arrCatsOutput = $this->modifyArrTermsForOutput($arrTerms, $taxonomy);
		
		$arrTermData = $this->getPostMainCategory($arrTerms, $postID);

		$catID = UniteFunctionsUC::getVal($arrTermData, "term_id");
		
		$urlImage = null;
		
		$arrCategory = array();
		$arrCategory["category_id"] = $catID;
		$arrCategory["category_name"] = UniteFunctionsUC::getVal($arrTermData, "name");
		$arrCategory["category_slug"] = UniteFunctionsUC::getVal($arrTermData, "slug");
		$arrCategory["category_link"] = UniteFunctionsUC::getVal($arrTermData, "link");
		
		if($taxonomy == "product_cat")
			$arrCategory["category_image"] = UniteFunctionsWPUC::getProductCatImage($catID);
		
		$arrCategory["categories"] = $arrCatsOutput;
		
		
		return($arrCategory);
	}
	
	/**
	 * get post featured images id
	 */
	private function getPostFeaturedImageID($postID, $content, $postType = null){

		$featuredImageID = UniteFunctionsWPUC::getFeaturedImageID($postID);
		
		//try to get featured image from content
		if(empty($featuredImageID)){
			
				$imageID = UniteFunctionsWPUC::getFirstImageIDFromContent($content);
				
				if(!empty($imageID))
					$featuredImageID = $imageID;				
		}
		
		
		//get first gallery image
		if(empty($featuredImageID) && $postType == "product"){
			
			$objWoo = new UniteCreatorWooIntegrate();
			$featuredImageID = $objWoo->getFirstGalleryImageID($postID);
		}
		
		
		return($featuredImageID);
	}
	
	
	/**
	 * get post data
	 */
	private function getPostDataByObj($post, $arrPostAdditions = array(), $arrImageSizes = null){
		
		try{
			
			$arrPost = (array)$post;
			$arrData = array();
			
			$postID = UniteFunctionsUC::getVal($arrPost, "ID");
			
			$arrData["id"] = $postID;
			$arrData["title"] = UniteFunctionsUC::getVal($arrPost, "post_title");
			$arrData["alias"] = UniteFunctionsUC::getVal($arrPost, "post_name");
			$arrData["author_id"] = UniteFunctionsUC::getVal($arrPost, "post_author");
			
			$content = UniteFunctionsWPUC::getPostContent($post);
			
			$arrData["content"] = $content;
			
			$arrData["link"] = UniteFunctionsWPUC::getPermalink($post);
			
			//get intro
			$intro = UniteFunctionsUC::getVal($arrPost, "post_excerpt");
			$introFull = "";
			
			if(empty($intro)){
				$intro = $arrData["content"];
			}
			
			if(!empty($intro)){
				$intro = wp_strip_all_tags($intro, true);
				$introFull = $intro;
				
				$intro = UniteFunctionsUC::truncateString($intro, 100);
			}
			
			$arrData["intro"] = $intro;			
			$arrData["intro_full"] = $introFull;
			
			//put data
			$strDate = UniteFunctionsUC::getVal($arrPost, "post_date");
			$arrData["date"] = !empty($strDate)?strtotime($strDate):"";
			
			$strDateModified = UniteFunctionsUC::getVal($arrPost, "post_modified");
			$arrData["date_modified"] = !empty($strDate)?strtotime($strDateModified):"";
			
			
			//check woo commmerce data
			$postType = UniteFunctionsUC::getVal($arrPost, "post_type");
			
			if($postType == "product"){
				
				$arrWooData = UniteCreatorWooIntegrate::getWooDataByType($postType, $postID);
								
				if(!empty($arrWooData))
					$arrData = $arrData + $arrWooData;
			}
			
			
			$featuredImageID = $this->getPostFeaturedImageID($postID, $content, $postType);
			
			
			
			if(!empty($featuredImageID)){
				
				$imageArgs = array();
				$imageArgs["name"] = "image";
				
				if(!empty($arrImageSizes)){
					$sizeDesktop = UniteFunctionsUC::getVal($arrImageSizes, "desktop");
					
					if(!empty($sizeDesktop)){
						$imageArgs["add_image_sizes"] = true;
						$imageArgs["value_size"] = $sizeDesktop;
					}
					
				}
				
				$arrData = $this->getProcessedParamsValue_image($arrData, $featuredImageID, $imageArgs);
			}
			
			if(is_array($arrPostAdditions) == false)
				$arrPostAdditions = array();
				
			//add custom fields
			foreach($arrPostAdditions as $addition){
				
				switch($addition){
					case GlobalsProviderUC::POST_ADDITION_CUSTOMFIELDS:
						
						$arrCustomFields = UniteFunctionsWPUC::getPostCustomFields($postID);
						
						$arrData = array_merge($arrData, $arrCustomFields);
					break;
					case GlobalsProviderUC::POST_ADDITION_CATEGORY:
						
						$arrCategory = $this->getPostCategoryFields($postID, $post);
							
						//HelperUC::addDebug("Get Category For Post: $postID ", $arrCategory);
						
						$arrData = array_merge($arrData, $arrCategory);
						
					break;
				}
				
			}

			
		}catch(Exception $e){
			
			$message = $e->getMessage();
			HelperUC::addDebug("Get Post Exception: ($postID) ".$message);
			
			return(null);
		}
			
		return($arrData);
	}
	
	/**
	 * run custom query
	 */
	private function getPostListData_getCustomQueryFilters($args, $value, $name, $data){
		
		if(GlobalsUC::$isProVersion == false)
			return($args);
		
		$queryID = UniteFunctionsUC::getVal($value, "{$name}_queryid");
		$queryID = trim($queryID);
		
		if(empty($queryID))
			return($args);
		
		HelperUC::addDebug("applying custom args filter: $queryID");
		
		//pass the widget data
		$widgetData = $data;
		unset($widgetData[$name]);
		
		$args = apply_filters($queryID, $args, $widgetData);
		
		HelperUC::addDebug("args after custom query", $args);
		
		return($args);
	}
	
	/**
	 * get single page query pagination
	 */
	private function getSinglePageQueryCurrentPage(){
		
		if(is_archive() == true || is_front_page() == true)
			return(false);
		
		$page = get_query_var("page", null);
		
		return($page);
	}
	
	
	/**
	 * get pagination args from the query
	 */
	private function getPostListData_getPostGetFilters_pagination($args, $value, $name, $data){
		
		//check the single page pagination
		$paginationType = UniteFunctionsUC::getVal($value, $name."_pagination_type");
		
		if(empty($paginationType))
			return($args);
		
		$objFilters = new UniteCreatorFiltersProcess();
		$isFrontAjax = $objFilters->isFrontAjaxRequest();
		
		if($isFrontAjax == false){
			
			if(is_archive() == true || is_home() == true)
				return($args);
		}
		
		$page = get_query_var("page", null);
		
		if(empty($page)){
			$page = get_query_var("paged", null);
		}

		if(empty($page))
			return($args);
		
		$postsPerPage = UniteFunctionsUC::getVal($args, "posts_per_page");
		if(empty($postsPerPage))
			return($args);
		
		$offset = ($page-1)*$postsPerPage;
		
		$args["offset"] = $offset;
		
		//save the last page for the pagination output
		GlobalsProviderUC::$lastPostQuery_page = $page;
		
		return($args);
	}
	
	
		
	/**
	 * add order by
	 */
	private function getPostListData_addOrderBy($filters, $value, $name, $isArgs = false){
		
		$keyOrderBy = "orderby";
		$keyOrderDir = "orderdir";
		$keyMeta = "meta_key";
		
		if($isArgs == true){
			$keyOrderDir = "order";
		}
		
		$orderBy = UniteFunctionsUC::getVal($value, "{$name}_orderby");
		if($orderBy == "default")
			$orderBy = null;
				
		if(!empty($orderBy))
			$filters[$keyOrderBy] = $orderBy;
		
		$orderDir = UniteFunctionsUC::getVal($value, "{$name}_orderdir1");
		if($orderDir == "default")
			$orderDir = "";
		
		if(!empty($orderDir))
			$filters[$keyOrderDir] = $orderDir;
		
		if($orderBy == UniteFunctionsWPUC::SORTBY_META_VALUE || $orderBy == UniteFunctionsWPUC::SORTBY_META_VALUE_NUM){
			$filters["meta_key"] = UniteFunctionsUC::getVal($value, "{$name}_orderby_meta_key1");
		}
		
		
		return($filters);
	}
	
	/**
	 * get date query
	 */
	private function getPostListData_dateQuery($value, $name){
				
		$dateString = UniteFunctionsUC::getVal($value, "{$name}_includeby_date");
				
		if($dateString == "all")
			return(array());

		$metaField = UniteFunctionsUC::getVal($value, "{$name}_include_date_meta");
		$metaField = trim($metaField);
					
		$arrDateQuery = array();
		$arrMetaQuery = array();	
		
		$after = "";
		$before = "";
		$year = "";
		$month = "";
		
		$afterMeta = null;
		$beforeMeta = null;
		
		switch($dateString){
			case "today":
				$after = "-1 day";
				
			break;
			case "yesterday":
				$after = "-2 day";
			break;
			case "week":
				$after = '-1 week';
			break;
			case "month":
				$after = "-1 month";
			break;
			case "three_months":
				$after = "-3 months";
			break;
			case "year":
				$after = "-1 year";
			break;
			case "this_month":
				
				if(!empty($metaField)){
					
					$afterMeta = date('Ym01');
					$beforeMeta = date('Ymt');
				
				}else{
					$year = $date("Y");
					$month = date("m");
				}
				
			break;
			case "next_month":
				
				if(!empty($metaField)){
					
					$afterMeta = date('Ymd',strtotime('first day of +1 month'));
					$beforeMeta = date('Ymd',strtotime('last day of +1 month'));
				}else{
					
					$time = strtotime('first day of +1 month');
					
					$year = date("Y",$time);
					$month = date("m",$time);
				}
				
			break;
			case "custom":
				
				$before = UniteFunctionsUC::getVal($value, "{$name}_include_date_before");
				
				$after = UniteFunctionsUC::getVal($value, "{$name}_include_date_after");
				
				if(!empty($before) || !empty($after))
					$arrDateQuery['inclusive'] = true;
				
			break;
		}
		
		if(!empty($metaField)){
			
			if(!empty($after) && empty($afterMeta)){
				$afterMeta = date("Ymd", strtotime($after));
			}
			
			if(!empty($afterMeta))
				$arrMetaQuery[] = array(
		            'key'     => $metaField,
		            'compare' => '>=',
		            'value'   => $afterMeta
        		);				
			
			if(!empty($before) && empty($beforeMeta))
				$beforeMeta = date("Ymd", strtotime($before));
				
			if(!empty($beforeMeta))
				$arrMetaQuery[] = array(
		            'key'     => $metaField,
		            'compare' => '<=',
		            'value'   => $beforeMeta
        		);				
			
		}else{
			if(!empty($before))
				$arrDateQuery["before"] = $before;
			
			if(!empty($after))
				$arrDateQuery["after"] = $after;
			
			if(!empty($year))
				$arrDateQuery["year"] = $year;
			
			if(!empty($month))
				$arrDateQuery["month"] = $month;
			
		}
		
		
		$response = array();
		if(!empty($arrDateQuery))
			$response["date_query"] = $arrDateQuery;
		
		if(!empty($arrMetaQuery))
			$response["meta_query"] = $arrMetaQuery;
						
		return($response);
	}
	
	
	/**
	 * get post list data custom from filters
	 */
	private function getPostListData_custom($value, $name, $processType, $param, $data){
		
		if(empty($value))
			return(array());
		
		if(is_array($value) == false)
			return(array());
		
		$filters = array();	
		
		$showDebugQuery = UniteFunctionsUC::getVal($value, "{$name}_show_query_debug");
		$showDebugQuery = UniteFunctionsUC::strToBool($showDebugQuery);
		
		$debugType = null;
		if($showDebugQuery == true)
			$debugType = UniteFunctionsUC::getVal($value, "{$name}_query_debug_type");
		
		$source = UniteFunctionsUC::getVal($value, "{$name}_source");
		
		$isForWoo = UniteFunctionsUC::getVal($param, "for_woocommerce_products");
		$isForWoo = UniteFunctionsUC::strToBool($isForWoo);
		
		//enable filters
		$isAjax = UniteFunctionsUC::getVal($value, "{$name}_isajax");
		$isAjax = UniteFunctionsUC::strToBool($isAjax);
		
		$isAjaxSetUrl = UniteFunctionsUC::getVal($value, "{$name}_ajax_seturl");
		
		$isFilterable = $isAjax && ($isAjaxSetUrl != "ajax");
		
		$isRelatedPosts = $source == "related";
		if(is_single() == false)
			$isRelatedPosts = false;
		
		$arrMetaQuery = array();
		
		$getRelatedProducts = false;
				
				
		//get post type
		$postType = UniteFunctionsUC::getVal($value, "{$name}_posttype", "post");
		if($isForWoo)
			$postType = "product";
		
		$filters["posttype"] = $postType;
		
		$post = null;
		
		if($isRelatedPosts == true){
			
			$post = get_post();
			$postType = $post->post_type;
			
			$filters["posttype"] = $postType;		//rewrite the post type argument
			
			if($postType == "product"){
				
				$getRelatedProducts = true;
				$productID = $post->ID;
				
			}else{
				
				if($showDebugQuery == true){
					dmp("Related Posts Query");
				}
				
				//prepare terms string
				$arrTerms = UniteFunctionsWPUC::getPostTerms($post);
				
				$strTerms = "";
							
				foreach($arrTerms as $tax => $terms){
					
					if($tax == "product_type")
						continue;
					
					foreach($terms as $term){
						$termID = UniteFunctionsUC::getVal($term, "term_id");
						$strTerm = "{$tax}--{$termID}";
						
						if(!empty($strTerms))
							$strTerms .= ",";
						
						$strTerms .= $strTerm;
					}
				}
				
				//add terms
				if(!empty($strTerms)){
					$filters["category"] = $strTerms;
					$filters["category_relation"] = "OR";				
				}
				
				$filters["exclude_current_post"] = true;
			}
			
		}else{
						
			$category = UniteFunctionsUC::getVal($value, "{$name}_category");
			
			if(!empty($category))
				$filters["category"] = UniteFunctionsUC::getVal($value, "{$name}_category");
			
			$relation = UniteFunctionsUC::getVal($value, "{$name}_category_relation");
			
			if(!empty($relation) && !empty($category))
				$filters["category_relation"] = $relation;
			
			$termsIncludeChildren = UniteFunctionsUC::getVal($value, "{$name}_terms_include_children");
			$termsIncludeChildren = UniteFunctionsUC::strToBool($termsIncludeChildren);
			
			if($termsIncludeChildren === true)
				$filters["category_include_children"] = true;
		}
		
		$limit = UniteFunctionsUC::getVal($value, "{$name}_maxitems");
		
		$limit = (int)$limit;
		if($limit <= 0)
			$limit = 100;
		
		if($limit > 1000)
			$limit = 1000;

			
		//------ Exclude ---------
		
		$arrExcludeBy = UniteFunctionsUC::getVal($value, "{$name}_excludeby", array());
		if(is_string($arrExcludeBy))
			$arrExcludeBy = array($arrExcludeBy);
		
		if(is_array($arrExcludeBy) == false)
			$arrExcludeBy = array();

		$excludeProductsOnSale = false;
		$excludeSpecificPosts = false;
		$excludeByAuthors = false;
		$arrExcludeTerms = array();
		$offset = null;
		$isAvoidDuplicates = false;
		$arrExcludeIDsDynamic = null;
		
		foreach($arrExcludeBy as $excludeBy){
			
			switch($excludeBy){
				case "current_post":
					$filters["exclude_current_post"] = true;					
				break;
				case "out_of_stock":
					$arrMetaQuery[] = array(
			            'key' => '_stock_status',
			            'value' => 'instock'
					);
					$arrMetaQuery[] = array(
				            'key' => '_backorders',
				            'value' => 'no'
				    );
				break;
				case "terms":
					
					$arrTerms = UniteFunctionsUC::getVal($value, "{$name}_exclude_terms");

					$arrExcludeTerms = UniteFunctionsUC::mergeArraysUnique($arrExcludeTerms, $arrTerms);
															
					$termsExcludeChildren = UniteFunctionsUC::getVal($value, "{$name}_terms_exclude_children");
					$termsExcludeChildren = UniteFunctionsUC::strToBool($termsExcludeChildren);
					
					$filters["category_exclude_children"] = $termsExcludeChildren;
					
				break;
				case "products_on_sale":
					
					$excludeProductsOnSale = true;
				break;
				case "specific_posts":
					
					$excludeSpecificPosts = true;
				break;
				case "author":
					
					$excludeByAuthors = true;
				break;
				case "no_image":
					
					$arrMetaQuery[] = array(
						"key"=>"_thumbnail_id",
						"compare"=>"EXISTS"
					);
					
				break;
				case "current_category":
					
					if(empty($post))
						$post = get_post();
										
					$arrCatIDs = UniteFunctionsWPUC::getPostCategoriesIDs($post);
					
					$arrExcludeTerms = UniteFunctionsUC::mergeArraysUnique($arrExcludeTerms, $arrCatIDs);
				break;
				case "current_tag":
					
					if(empty($post))
						$post = get_post();
					
					$arrTagsIDs = UniteFunctionsWPUC::getPostTagsIDs($post);
					
					$arrExcludeTerms = UniteFunctionsUC::mergeArraysUnique($arrExcludeTerms, $arrTagsIDs);
				break;
				case "offset":
					
					$offset = UniteFunctionsUC::getVal($value, $name."_offset");
					$offset = (int)$offset;
					
				break;
				case "avoid_duplicates":
					
					$isAvoidDuplicates = true;
					
				break;
				case "ids_from_dynamic":
					
					$arrExcludeIDsDynamic = UniteFunctionsUC::getVal($value, $name."_exclude_dynamic_field");
					$arrExcludeIDsDynamic = UniteFunctionsUC::getIDsArray($arrExcludeIDsDynamic);
										
				break;
			}
			
		}
		
		if(!empty($arrExcludeTerms))
			$filters["exclude_category"] = $arrExcludeTerms;
		
		$filters["limit"] = $limit;
		
		$filters = $this->getPostListData_addOrderBy($filters, $value, $name);

		
		//add debug for further use
		HelperUC::addDebug("Post Filters", $filters);
				
		//run custom query if available
		$args = UniteFunctionsWPUC::getPostsArgs($filters);
						
		//exclude by authors
		
		if($excludeByAuthors == true){
			
			$arrExcludeByAuthors = UniteFunctionsUC::getVal($value, "{$name}_excludeby_authors");
			
			foreach($arrExcludeByAuthors as $key => $userID){
				
				if($userID == "uc_loggedin_user"){
					
					$userID = get_current_user_id();
					
					if(empty($userID))
						unset($arrExcludeByAuthors[$key]);
					else
						$arrExcludeByAuthors[$key] = $userID;
				}
				
			}
			
			if(!empty($arrExcludeByAuthors))
				$args["author__not_in"] = $arrExcludeByAuthors;
		}
		
		//exclude by specific posts
		
		$arrPostsNotIn = array();
		
		if($excludeProductsOnSale == true){
			
			$arrPostsNotIn = wc_get_product_ids_on_sale();
		}
		
		if($excludeSpecificPosts == true){
			
			$specificPostsToExclude = UniteFunctionsUC::getVal($value, "{$name}_exclude_specific_posts");
			
			if(!empty($specificPostsToExclude)){
				
				if(empty($arrPostsNotIn))
					$arrPostsNotIn = $specificPostsToExclude;
				else
					$arrPostsNotIn = array_merge($arrPostsNotIn, $specificPostsToExclude);
			}
			
		}
		
		//exclude from dynamic field
		
		if(!empty($arrExcludeIDsDynamic)){
			
			if(empty($arrExcludeIDsDynamic))
				$arrPostsNotIn = $arrExcludeIDsDynamic;
			else
				$arrPostsNotIn = array_merge($arrPostsNotIn, $arrExcludeIDsDynamic);
		}
		
		
		// exclude duplicates
		if($isAvoidDuplicates == true && !empty(GlobalsProviderUC::$arrFetchedPostIDs)){
			
			$arrFetchedIDs = array_keys(GlobalsProviderUC::$arrFetchedPostIDs);				
			
			if(empty($arrPostsNotIn))
				$arrPostsNotIn = $arrFetchedIDs;
			else
				$arrPostsNotIn = array_merge($arrPostsNotIn, $arrFetchedIDs);
			
		}
		
		
		//add the include by
		$arrIncludeBy = UniteFunctionsUC::getVal($value, "{$name}_includeby");
		if(empty($arrIncludeBy))
			$arrIncludeBy = array();
		
		$args["ignore_sticky_posts"] = true;
		
		$getOnlySticky = false;
		
		$product = null;
		
		$arrProductsUpSells = array();
		$arrProductsCrossSells = array();
		$arrIDsOnSale = array();
		$arrRecentProducts = array();
		$arrIDsPopular = array();
		$arrIDsPHPFunction = array();
		$arrIDsPostMeta = array();
		$arrIDsDynamicField = array();
		$arrIDsFromContent = array();
		
		
		$makePostINOrder = false;
				
		foreach($arrIncludeBy as $includeby){
						
			switch($includeby){
				case "sticky_posts":
					$args["ignore_sticky_posts"] = false;
				break;
				case "sticky_posts_only":
					$getOnlySticky = true;
				break;
				case "products_on_sale":
					
					$arrIDsOnSale = wc_get_product_ids_on_sale();
					
					if(empty($arrIDsOnSale))
						$arrIDsOnSale = array("0");
					
				break;
				case "up_sells":		//product up sells
					
					if(empty($product))
						$product = wc_get_product();
					
					if(!empty($product)){
						$arrProductsUpSells = $product->get_upsell_ids();
						if(empty($arrProductsUpSells))
							$arrProductsUpSells = array("0");
					}
										
				break;
				case "cross_sells":

					if(empty($product))
						$product = wc_get_product();
				 	
					if(!empty($product)){
						$arrProductsCrossSells = $product->get_cross_sell_ids();
						if(empty($arrProductsUpSells))
							$arrProductsCrossSells = array("0");
					}
					
				break;
				case "out_of_stock":
					
					$arrMetaQuery[] = array(
			            'key' => '_stock_status',
			            'value' => 'instock',
						'compare'=>'!='
					);
					
				break;
				case "products_from_post":		//get products from post content
					
					$objWoo = new UniteCreatorWooIntegrate();
					$arrIDsFromContent = $objWoo->getProductIDsFromCurrentPostContent();
					
				break;
				case "author":
					
					$arrIncludeByAuthors = UniteFunctionsUC::getVal($value, "{$name}_includeby_authors");
					
					//if set to current user, and no user logged in, then get no posts at all
					$authorMakeZero = false;
					foreach($arrIncludeByAuthors as $key => $userID){
						
						if($userID == "uc_loggedin_user"){
							
							$userID = get_current_user_id();
							$arrIncludeByAuthors[$key] = $userID;
							
							if(empty($userID))
								$authorMakeZero = true;
						}
						
					}
					
					if($authorMakeZero == true)
						$arrIncludeByAuthors = array("0");
					
					if(!empty($arrIncludeByAuthors))
						$args["author__in"] = $arrIncludeByAuthors;
					
				break;
				case "date":
					
					$response = $this->getPostListData_dateQuery($value, $name);
					$arrDateQuery = UniteFunctionsUC::getVal($response, "date_query");
					
					if(!empty($arrDateQuery))
						$args["date_query"] = $arrDateQuery;
					
					$arrDateMetaQuery = UniteFunctionsUC::getVal($response, "meta_query");
					if(!empty($arrDateMetaQuery))
					
					$arrMetaQuery = array_merge($arrMetaQuery, $arrDateMetaQuery);
										
				break;
				case "parent":
					
					$parent =  UniteFunctionsUC::getVal($value, "{$name}_includeby_parent");
					if(!empty($parent)){
						
						if(is_array($parent) && count($parent) == 1)
							$parent = $parent[0];
							
						if(is_array($parent))
							$args["post_parent__in"] = $parent;
						else
							$args["post_parent"] = $parent;
					}
				break;
				case "recent":
					
					if(isset($_COOKIE["woocommerce_recently_viewed"])){
						
						$strRecentProducts = $_COOKIE["woocommerce_recently_viewed"];
						$strRecentProducts = trim($strRecentProducts);
						$arrRecentProducts = explode("|", $strRecentProducts);
					}
										
				break;
				case "meta":
					
					$metaKey = UniteFunctionsUC::getVal($value, "{$name}_includeby_metakey");
					$metaCompare = UniteFunctionsUC::getVal($value, "{$name}_includeby_metacompare");
					
					$metaValue = UniteFunctionsUC::getVal($value, "{$name}_includeby_metavalue");
					$metaValue = $this->modifyMetaValueForCompare($metaValue);
					
					$metaValue2 = UniteFunctionsUC::getVal($value, "{$name}_includeby_metavalue2");
					$metaValue2 = $this->modifyMetaValueForCompare($metaValue2);
					
					$metaValue3 = UniteFunctionsUC::getVal($value, "{$name}_includeby_metavalue3");
					$metaValue3 = $this->modifyMetaValueForCompare($metaValue3);
					
					
					if(!empty($metaKey)){
						
						$arrMetaQuery[] = array(
				            'key' => $metaKey,
				            'value' => $metaValue,
							'compare'=>$metaCompare
						);
						
						
						if(!empty($metaValue2)){
							
							$arrMetaQuery[] = array(
					            'key' => $metaKey,
					            'value' => $metaValue2,
								'compare'=>$metaCompare
							);
							
							$arrMetaQuery["relation"] = "OR";
						}
							
						if(!empty($metaValue3)){
							
							$arrMetaQuery[] = array(
					            'key' => $metaKey,
					            'value' => $metaValue3,
								'compare'=>$metaCompare
							);
							
							$arrMetaQuery["relation"] = "OR";
						}
						
						
					}
					
				break;
				case "most_viewed":
					
					$isWPPPluginExists = UniteCreatorPluginIntegrations::isWPPopularPostsExists();
					
					if($showDebugQuery == true && $isWPPPluginExists == false){
						dmp("Select Most Viewed posts posible only if you install 'WordPress Popular Posts' plugin. Please install it");
					}
					
					if($isWPPPluginExists){
						
						$objIntegrations = new UniteCreatorPluginIntegrations();

						$wppRange = UniteFunctionsUC::getVal($value, "{$name}_includeby_mostviewed_range");
												
						$wpp_args = array(
							"post_type"=>$postType,
							"limit"=>$limit,
							"range"=>$wppRange
						);
						if(!empty($category))
							$wpp_args["cat"] = $category;
						
						$response = $objIntegrations->WPP_getPopularPosts($wpp_args, $showDebugQuery);
						
						$arrIDsPopular = UniteFunctionsUC::getVal($response, "post_ids");
						
						$debugWPP = UniteFunctionsUC::getVal($response, "debug");
						
						if($showDebugQuery == true && !empty($debugWPP)){
							dmp("Pupular Posts Data: ");
							dmp($debugWPP);
						}
						
					}
					
				break;
				case "php_function":
					
					$arrIDsPHPFunction = $this->getPostListData_getIDsFromPHPFunction($value, $name, $showDebugQuery);
					
				break;
				case "ids_from_meta":
					
					$arrIDsPostMeta = $this->getPostListData_getIDsFromPostMeta($value, $name, $showDebugQuery);
					
				break;
				case "ids_from_dynamic":
					
					$arrIDsDynamicField = UniteFunctionsUC::getVal($value, $name."_includeby_dynamic_field");
					
					$arrIDsDynamicField = UniteFunctionsUC::getIDsArray($arrIDsDynamicField);
										
				break;
			}
			
		}

		//include id's
		$arrPostInIDs = UniteFunctionsUC::mergeArraysUnique($arrProductsCrossSells, $arrProductsUpSells, $arrRecentProducts);
		
		if(!empty($arrIDsOnSale)){
			
			if(!empty($arrPostInIDs))		//intersect with previous id's
				$arrPostInIDs = array_intersect($arrPostInIDs, $arrIDsOnSale);
			else
				$arrPostInIDs = $arrIDsOnSale;
		}
		
		if(!empty($arrIDsPopular)){
			$makePostINOrder = true;
			$arrPostInIDs = $arrIDsPopular;
		}
		
		if(!empty($arrIDsPHPFunction)){
			$arrPostInIDs = $arrIDsPHPFunction;
			$makePostINOrder = true;
		}
		
		if(!empty($arrIDsPostMeta)){
			$arrPostInIDs = $arrIDsPostMeta;
			$makePostINOrder = true;
		}
		
		if(!empty($arrIDsDynamicField)){
			$arrPostInIDs = $arrIDsDynamicField;
			$makePostINOrder = true;
		}
		
		if(!empty($arrIDsFromContent)){
			$arrPostInIDs = $arrIDsFromContent;
			$makePostINOrder = true;
		}
		
		
		//make order as "post__id"	
			
		if($makePostINOrder == true){
			
			//set order
			$args["orderby"] = "post__in";
			
			$orderDir = UniteFunctionsUC::getVal($args, "order");
			if($orderDir == "ASC")
				$arrIDsPopular = array_reverse($arrIDsPopular);
			
			unset($args["order"]);			
		}
				
		
		if(!empty($arrPostInIDs))
			$args["post__in"] = $arrPostInIDs;
		
		
		//------ get woo  related products ------ 
		
		if($getRelatedProducts == true){
			
			if($showDebugQuery == true){
				
				$debugText = "Debug: Getting up to $limit related products";
				
				if(!empty($arrPostsNotIn)){
					$strPostsNotIn = implode(",", $arrPostsNotIn);
					$debugText = " excluding $strPostsNotIn";
				}
				
				dmp($debugText);
			}
			
			$arrRelatedProductIDs = wc_get_related_products($productID, $limit, $arrPostsNotIn);
			if(empty($arrRelatedProductIDs))
				$arrRelatedProductIDs = array("0");
			$args["post__in"] = $arrRelatedProductIDs;
		}
		
		if(!empty($arrMetaQuery))
			$args["meta_query"] = $arrMetaQuery;

		//add exclude specific posts if available
		if(!empty($arrPostsNotIn)){
			$arrPostsNotIn = array_unique($arrPostsNotIn);
			$args["post__not_in"] = $arrPostsNotIn;
		}
		
		$isWpmlExists = UniteCreatorWpmlIntegrate::isWpmlExists();
		if($isWpmlExists)
			$args["suppress_filters"] = false;
		
		//add post status
		$arrStatuses = UniteFunctionsUC::getVal($value, "{$name}_status");
				
		if(empty($arrStatuses))
			$arrStatuses = "publish";
		
		if(!empty($offset))
			$args["offset"] = $offset;
		
		if(is_array($arrStatuses) && count($arrStatuses) == 1)
			$arrStatuses = $arrStatuses[0];
			
		$args["post_status"] = $arrStatuses;
		
		//add sticky posts only
		$arrStickyPosts = array();
		
		if($getOnlySticky == true){
			
			$arrStickyPosts = get_option("sticky_posts");
				
			$args["ignore_sticky_posts"] = true;
			
			if(!empty($arrStickyPosts) && is_array($arrStickyPosts)){
				$args["post__in"] = $arrStickyPosts;
			}else{
				$args["post__in"] = array("0");		//no posts at all
			}
		}
				
		$args = $this->getPostListData_getPostGetFilters_pagination($args, $value, $name, $data);
				
		$args = $this->getPostListData_getCustomQueryFilters($args, $value, $name, $data);
		
		//update by post and get filters
		$objFiltersProcess = new UniteCreatorFiltersProcess();
		$args = $objFiltersProcess->processRequestFilters($args, $isFilterable);
		
		
		HelperUC::addDebug("Posts Query", $args);
		
		//-------- show debug query --------------
				
		if($showDebugQuery == true){
			echo "<div class='uc-debug-query-wrapper'>";	//start debug wrapper
			
			dmp("The Query Is:");
			dmp($args);
		}
		
		//remember last args
		GlobalsProviderUC::$lastQueryArgs = $args;
		
		//skip run
		if(GlobalsProviderUC::$skipRunPostQueryOnce == true){
			GlobalsProviderUC::$skipRunPostQueryOnce = false;
			return(array());
		}
		
		$query = new WP_Query($args);
				
		if($showDebugQuery == true && $debugType == "show_query"){
						
			$originalQueryVars = $query->query_vars;
			$originalQueryVars = UniteFunctionsWPUC::cleanQueryArgsForDebug($originalQueryVars);
						
			dmp("The Query Request Is:");
			dmp($query->request);
			
			dmp("The finals query vars:");
			dmp($originalQueryVars);
			
			$this->showPostsDebugCallbacks($isForWoo);
			
		}
		
		
		/*
			dmp($query->request);
			dmp("the query");
			dmp($query->query);
			dmp($query->post_count);
			dmp($query->found_posts);
		*/
		
		$arrPosts = $query->posts;
		
		if(!$arrPosts)
			$arrPosts = array();
		
		//sort sticky posts
		if($getOnlySticky == true && !empty($arrStickyPosts)){
			
			$orderby = UniteFunctionsUC::getVal($args, "orderby");
			if(empty($orderby))
				$arrPosts = UniteFunctionsWPUC::orderPostsByIDs($arrPosts, $arrStickyPosts);
		}
		
		//save last query and page
		$this->saveLastQueryAndPage($query,GlobalsProviderUC::QUERY_TYPE_CUSTOM);
		
		//remember duplicate posts
		if($isAvoidDuplicates == true){
			foreach($arrPosts as $post)
				GlobalsProviderUC::$arrFetchedPostIDs[$post->ID] = true;
		}
		
		HelperUC::addDebug("posts found: ".count($arrPosts));
		
		if($showDebugQuery == true){
			dmp("Found Posts: ".count($arrPosts));
			
			echo "</div>";
		}
		
		
		return($arrPosts);
	}
	
	/**
	 * show modify callbacks for debug
	 */
	private function showPostsDebugCallbacks($isForWoo = false){
				
		$arrActions = UniteFunctionsWPUC::getFilterCallbacks("posts_pre_query");
		
		dmp("Query modify callbacks ( posts_pre_query ):");
		dmp($arrActions);

		$arrActions = UniteFunctionsWPUC::getFilterCallbacks("posts_orderby");
		
		dmp("Query modify callbacks ( posts_orderby ):");
		dmp($arrActions);
		
		if($isForWoo == true){
		
			$arrActions = UniteFunctionsWPUC::getFilterCallbacks("loop_shop_per_page");
			
			dmp("Query modify callbacks ( loop_shop_per_page ):");
			dmp($arrActions);
			
			$arrActions = UniteFunctionsWPUC::getFilterCallbacks("loop_shop_columns");
			
			dmp("Query modify callbacks ( loop_shop_columns ):");
			dmp($arrActions);
			
			//products change
		}
		
	}
	
	/**
	 * save last query and page
	 */
	private function saveLastQueryAndPage($query, $type){
		
		GlobalsProviderUC::$lastPostQuery = $query;
		GlobalsProviderUC::$lastPostQuery_page = 1;
		GlobalsProviderUC::$lastPostQuery_type = $type;

		//set type for pagination, stay on current if exists
		if(GlobalsProviderUC::$lastPostQuery_paginationType != GlobalsProviderUC::QUERY_TYPE_CURRENT)
			GlobalsProviderUC::$lastPostQuery_paginationType = $type;
		
			
		$queryVars = $query->query;
		
		$perPage = UniteFunctionsUC::getVal($queryVars, "posts_per_page");
		
		if(empty($perPage))
			return(false);
		
		$offset = UniteFunctionsUC::getVal($queryVars, "offset");
		
		if(empty($offset))
			return(false);
		
		$page = ceil($offset / $perPage)+1;
		
		if(!empty($page))
			GlobalsProviderUC::$lastPostQuery_page = $page;
		
		GlobalsProviderUC::$lastPostQuery_offset = $offset;
		
	}
		
	
	/**
	 * get current posts
	 */
	private function getPostListData_currentPosts($value, $name, $data){
				
		//add debug for further use
		HelperUC::addDebug("Getting Current Posts");
		
		$orderBy = UniteFunctionsUC::getVal($value, $name."_orderby");
		$orderDir = UniteFunctionsUC::getVal($value, $name."_orderdir1");
		$orderByMetaKey = UniteFunctionsUC::getVal($value, $name."_orderby_meta_key1");
		
		//enable filters
		$isAjax = UniteFunctionsUC::getVal($value, "{$name}_isajax");
		$isAjax = UniteFunctionsUC::strToBool($isAjax);
		
		$isAjaxSetUrl = UniteFunctionsUC::getVal($value, "{$name}_ajax_seturl");
		
		$isFilterable = $isAjax && ($isAjaxSetUrl != "ajax");
		
		
		if($orderBy == "default")
			$orderBy = null;
			
		if($orderDir == "default")
			$orderDir = null;
		
		global $wp_query;
		$currentQueryVars = $wp_query->query_vars;
		
		// ----- current query settings --------
		
		//--- set posts per page --- 
		
		//--- set order --- 
		if(!empty($orderBy))
			$currentQueryVars["orderby"] = $orderBy;
		
		if($orderBy == "meta_value" || $orderBy == "meta_value_num")
			$currentQueryVars["meta_key"] = $orderByMetaKey;
		
		if(!empty($orderDir))
			$currentQueryVars["order"] = $orderDir;
		
		$currentQueryVars = apply_filters( 'elementor/theme/posts_archive/query_posts/query_vars', $currentQueryVars);
		
		//update by post and get filters
		$objFiltersProcess = new UniteCreatorFiltersProcess();
		$currentQueryVars = $objFiltersProcess->processRequestFilters($currentQueryVars, $isFilterable);
		
		
		//custom filters
		$currentQueryVars = $this->getPostListData_getCustomQueryFilters($currentQueryVars, $value, $name, $data);
		
		
		$showDebugQuery = UniteFunctionsUC::getVal($value, "{$name}_show_query_debug");
		$showDebugQuery = UniteFunctionsUC::strToBool($showDebugQuery);
		
		$debugType = null;

		$isForWoo = false;
		if($showDebugQuery == true){
			
			$postType = UniteFunctionsUC::getVal($currentQueryVars, "post_type");
			if($postType == "product")
				$isForWoo = true;
			
			echo "<div class='uc-debug-query-wrapper'>";	//start debug wrapper
			
			dmp("Current Posts. The Query Is:");
			
			$argsForDebug = UniteFunctionsWPUC::cleanQueryArgsForDebug($currentQueryVars);
			dmp($argsForDebug);
			
			$debugType = UniteFunctionsUC::getVal($value, "{$name}_query_debug_type");
			
		}
		
		$query = $wp_query;
		
		$objFilters = new UniteCreatorFiltersProcess();
		$isFrontAjax = $objFilters->isFrontAjaxRequest();
				
		
		//remember last args
		GlobalsProviderUC::$lastQueryArgs = $wp_query->query_vars;
		
		//remake the query - not inside ajax
		
		if($currentQueryVars !== $wp_query->query_vars && $isFrontAjax == false){
			
			HelperUC::addDebug("New Query", $currentQueryVars);
			
			//skip run
			GlobalsProviderUC::$lastQueryArgs = $wp_query->query_vars;
			
			if(GlobalsProviderUC::$skipRunPostQueryOnce == true){
				GlobalsProviderUC::$skipRunPostQueryOnce = false;
				return(array());
			}			
			
			$query = new WP_Query( $currentQueryVars );
					
		}
		
		
		//skip run
		if(GlobalsProviderUC::$skipRunPostQueryOnce == true){
			GlobalsProviderUC::$skipRunPostQueryOnce = false;
			return(array());
		}
		
		
		HelperUC::addDebug("Query Vars", $currentQueryVars);
		
		//save last query
		$this->saveLastQueryAndPage($query, GlobalsProviderUC::QUERY_TYPE_CURRENT);
				
		$arrPosts = $query->posts;
				
		if(empty($arrPosts))
			$arrPosts = array();
		
		if($showDebugQuery == true && $debugType == "show_query"){
			
			$originalQueryVars = $query->query_vars;
			$originalQueryVars = UniteFunctionsWPUC::cleanQueryArgsForDebug($originalQueryVars);
			
			dmp("The Query Request Is:");
			dmp($query->request);
			
			dmp("The finals query vars:");
			dmp($originalQueryVars);
			
			$this->showPostsDebugCallbacks($isForWoo);
			
		}
			
			
		if($showDebugQuery == true){
			dmp("Found Posts: ".count($arrPosts));
			
			echo "</div>";	//close query wrapper div
		}
			
		HelperUC::addDebug("Posts Found: ". count($arrPosts));
			
		return($arrPosts);
	}
	
	
	/**
	 * get manual selection
	 */
	private function getPostListData_manualSelection($value, $name, $data){
		
		$args = array();
		
		$postIDs = UniteFunctionsUC::getVal($value, $name."_manual_select_post_ids");
		
		if(empty($postIDs))
			$postIDs = array();
		
		//post id's by dynamic text field 
		
		$dynamicIDs = UniteFunctionsUC::getVal($value, $name."_manual_post_ids_dynamic");
		
		$arrDynamicIDs = UniteFunctionsUC::getIDsArray($dynamicIDs);
				
		if(!empty($arrDynamicIDs))
			$postIDs = array_merge($postIDs, $arrDynamicIDs);
		
		
		$showDebugQuery = UniteFunctionsUC::getVal($value, "{$name}_show_query_debug");
		$showDebugQuery = UniteFunctionsUC::strToBool($showDebugQuery);
		
		if(empty($postIDs)){
			
			if($showDebugQuery == true){
				
				dmp("Query Debug, Manual Selection: No Posts Selected");
				HelperUC::addDebug("No Posts Selected");
			}
			
			return(array());
		}
		
		$args["post__in"] = $postIDs;
		$args["ignore_sticky_posts"] = true;
		$args["post_type"] = "any";
		$args["post_status"] = "publish, private";
		
		$args = $this->getPostListData_addOrderBy($args, $value, $name, true);
		
		if($showDebugQuery == true){
			dmp("Manual Selection. The Query Is:");
			dmp($args);
		}
		
		GlobalsProviderUC::$lastQueryArgs = $args;
		
		$query = new WP_Query($args);
		$arrPosts = $query->posts;
		
		if(empty($arrPosts))
			$arrPosts = array();
		
		//keep original order if no orderby
		$orderby = UniteFunctionsUC::getVal($args, "orderby");
		if(empty($orderby))
			$arrPosts = UniteFunctionsWPUC::orderPostsByIDs($arrPosts, $postIDs);
		
		//save last query
		$this->saveLastQueryAndPage($query,GlobalsProviderUC::QUERY_TYPE_MANUAL);
				
		HelperUC::addDebug("posts found: ".count($arrPosts));
		
		if($showDebugQuery == true){
			dmp("Found Posts: ".count($arrPosts));
		}
		
		return($arrPosts);
		
	}
	
	/**
	 * get post list data
	 */
	private function getPostListData($value, $name, $processType, $param, $data){
		
		if($processType != self::PROCESS_TYPE_OUTPUT && $processType != self::PROCESS_TYPE_OUTPUT_BACK)
			return($data);
		
		HelperUC::addDebug("getPostList values", $value);
		HelperUC::addDebug("getPostList param", $param);
		
		$source = UniteFunctionsUC::getVal($value, "{$name}_source");
		
		$arrPosts = array();
				
		switch($source){
			case "manual":
				
				$arrPosts = $this->getPostListData_manualSelection($value, $name, $data);
				
			break;
			case "current":
				
				$arrPosts = $this->getPostListData_currentPosts($value, $name, $data);
				
			break;
			default:		//custom
				
				$arrPosts = $this->getPostListData_custom($value, $name, $processType, $param, $data);
				
				$filters = array();
				$arrPostsFromFilter = UniteProviderFunctionsUC::applyFilters("uc_filter_posts_list", $arrPosts, $value, $filters);
				
				if(!empty($arrPostsFromFilter))
					$arrPosts = $arrPostsFromFilter;
				
			break;
		}
		
		
		if(empty($arrPosts))
			$arrPosts = array();
			
		$useCustomFields = UniteFunctionsUC::getVal($param, "use_custom_fields");
		$useCustomFields = UniteFunctionsUC::strToBool($useCustomFields);
		
		$useCategory = UniteFunctionsUC::getVal($param, "use_category");
		$useCategory = UniteFunctionsUC::strToBool($useCategory);
		
		$arrPostAdditions = HelperProviderUC::getPostDataAdditions($useCustomFields, $useCategory);
		
		HelperUC::addDebug("post additions", $arrPostAdditions);
		
		//image sizes
		$showImageSizes = UniteFunctionsUC::getVal($param, "show_image_sizes");
		$showImageSizes = UniteFunctionsUC::strToBool($showImageSizes);
		
		$arrImageSizes = null;
		
		if($showImageSizes == true){
			
			$imageSize = UniteFunctionsUC::getVal($value, "{$name}_imagesize","medium_large");
			$arrImageSizes["desktop"] = $imageSize;
		}
						
		$objFilters = new UniteCreatorFiltersProcess();
		$data = $objFilters->addWidgetFilterableVariables($data, $this->addon);
		
		//prepare listing output. no items prepare for the listing
		
		$useForListing = UniteFunctionsUC::getVal($param, "use_for_listing");
		$useForListing = UniteFunctionsUC::strToBool($useForListing);
		
		if($useForListing == true){
			$nameListing = UniteFunctionsUC::getVal($param, "name_listing");
			
			$data[$nameListing."_items"] = $arrPosts;
			return($data);
		}
		
		$arrData = array();
		foreach($arrPosts as $post){
			
			$arrData[] = $this->getPostDataByObj($post, $arrPostAdditions, $arrImageSizes);
		}
		
		$data[$name] = $arrData;		
		
		return($data);
	}
	
	
	protected function z_______________DYNAMIC_LOOP_GALLERY____________(){}
	
	/**
	 * get gallery item title
	 */
	private function getGalleryItem_title($source, $data, $name, $post, $item){
		
		switch($source){
			case "post_title":
				$title = $post->post_title;
			break;
			case "post_excerpt":
				$title = $post->post_excerpt;
			break;
			case "post_content":
				$title = $post->post_content;				
			break;
			case "image_title":
				$title = UniteFunctionsUC::getVal($data, $name."_title");
			break;
			case "image_alt":
				$title = UniteFunctionsUC::getVal($data, $name."_alt");				
			break;
			case "image_caption":
				$title = UniteFunctionsUC::getVal($data, $name."_caption");
			break;
			case "image_description":
				$title = UniteFunctionsUC::getVal($data, $name."_description");				
			break;
			case "item_title":
				$title = UniteFunctionsUC::getVal($item, "title");
			break;
			case "item_description":
				$title = UniteFunctionsUC::getVal($item, "description");
			break;
			default:
			case "image_auto":
				
				$title = UniteFunctionsUC::getVal($data, $name."_title");
				
				if(empty($title))
					$title = UniteFunctionsUC::getVal($data, $name."_caption");
				
				if(empty($title))
					$title = UniteFunctionsUC::getVal($data, $name."_alt");
				
			break;
		}

		
		return($title);
	}
	
	/**
	 * get gallery item data
	 */
	private function getGalleryItem_sourceItemData($item, $sourceItem){
		
		$itemType = UniteFunctionsUC::getVal($sourceItem, "item_type", "image");
		
		switch($itemType){
			case "image":
			break;
			case "youtube":
				
				$urlYoutube = UniteFunctionsUC::getVal($sourceItem, "url_youtube");
				
				$videoID = UniteFunctionsUC::getYoutubeVideoID($urlYoutube);
				
				$item["type"] = "youtube";
				$item["videoid"] = $videoID;
				
			break;
			case "html5":
				
				$urlMp4 = UniteFunctionsUC::getVal($sourceItem, "url_html5");
				
				$item["type"] = "html5video";
				$item["url_mp4"] = $urlMp4;
								
			break;
			case "vimeo":
				
				$videoID = UniteFunctionsUC::getVal($sourceItem, "vimeo_id");
				
				$videoID = UniteFunctionsUC::getVimeoIDFromUrl($videoID);
				
				$item["type"] = "vimeo";
				$item["videoid"] = $videoID;
			break;
			case "wistia":
				
				$videoID = UniteFunctionsUC::getVal($sourceItem, "wistia_id");
								
				$item["type"] = "wistia";
				$item["videoid"] = $videoID;
				
			break;
			default:
				
				dmp("wrong gallery item type: $itemType");
				dmp($sourceItem);
				
			break;
		}
		
		//get the link url
		$link = UniteFunctionsUC::getVal($sourceItem, "link");
		if(is_array($link))
			$link = UniteFunctionsUC::getVal($link, "url");
		
		if(empty($link))
			$link = "";
			
		$item["link"] = $link;
			
		
		return($item);
	}
	
	
	/**
	 * get gallery item from instagram
	 */
	private function getGalleryItem_instagram($instaItem, $isEnableVideo){
		
		$isVideo = UniteFunctionsUC::getVal($instaItem, "isvideo");
		$isVideo = UniteFunctionsUC::strToBool($isVideo);

		$item["type"] = "image";
		$item["image"] = UniteFunctionsUC::getVal($instaItem, "image");
		$item["thumb"] = UniteFunctionsUC::getVal($instaItem, "thumb");
		
		if($isVideo == true && $isEnableVideo == true){
			
			$urlVideo = UniteFunctionsUC::getVal($instaItem, "url_video");
			
			$item["type"] = "html5video";
			$item["url_mp4"] = $urlVideo;
		}
		
		$imageSize = 1080;
		
		$item["image_width"] = $imageSize;
		$item["image_height"] = $imageSize;
		$item["thumb_width"] = $imageSize;
		$item["thumb_height"] = $imageSize;
		
		$item["title"] = UniteFunctionsUC::getVal($instaItem, "caption");
		$item["description"] = "";
		$item["link"] = UniteFunctionsUC::getVal($instaItem, "link");
		$item["imageid"] = 0;
		
		return($item);
	}
	
	/**
	 * get gallery item
	 */
	private function getGalleryItem($id, $url = null, $arrParams = null){
		
		$data = array();
				
		$arrFilters = UniteFunctionsUC::getVal($arrParams, "size_filters");
		
		$thumbSize = UniteFunctionsUC::getVal($arrParams, "thumb_size");
		$imageSize = UniteFunctionsUC::getVal($arrParams, "image_size");
		
		$titleSource = UniteFunctionsUC::getVal($arrParams, "title_source");
		$descriptionSource = UniteFunctionsUC::getVal($arrParams, "description_source");
		$post = UniteFunctionsUC::getVal($arrParams, "post");
		$sourceItem = UniteFunctionsUC::getVal($arrParams, "item");
		
		$isAddItemsData = UniteFunctionsUC::getVal($arrParams, "add_item_data");
		$isAddItemsData = UniteFunctionsUC::strToBool($isAddItemsData);

		$index = UniteFunctionsUC::getVal($arrParams, "index");
		
		
		$name = "image";
		
		$param = array();
		$param["name"] = $name;
		$param["size_filters"] = $arrFilters;
		$param["no_attributes"] = true;
		
		//no extra data needed
		if( strpos($titleSource,"post_") !== false && strpos($descriptionSource, "post_") !== false)
			$param["no_image_data"] = true;
		else
		if($titleSource == "item_title" && $descriptionSource == "item_description")
			$param["no_image_data"] = true;

		$value = $id;
		$isByUrl = false;
		if(empty($value)){
			$value = $url;
			$isByUrl = true;
		}
		
		$item = array();
		$item["type"] = "image";
		
		if(empty($value)){
			
			$item["image"] = GlobalsUC::$url_no_image_placeholder;
			$item["thumb"] = GlobalsUC::$url_no_image_placeholder;
			
			$item["image_width"] = 600;
			$item["image_height"] = 600;
			$item["thumb_width"] = 600;
			$item["thumb_height"] = 600;
			
			$title = $this->getGalleryItem_title($titleSource, $data, $name, $post, $sourceItem);
			$description = $this->getGalleryItem_title($descriptionSource, $data, $name, $post, $sourceItem);
			
			if(empty($title) && !empty($post))
				$title = $post->post_title;
			
			$item["title"] = $title;
			$item["description"] = $description;

			$item["link"] = "";
			
			if(!empty($post))
				$item["link"] = $post->guid;
			
			$item["imageid"] = 0;
			
			return($item);
		}
		
		$data = $this->getProcessedParamsValue_image($data, $value, $param);
		
		$arrItem = array();
		$keyThumb = "{$name}_thumb_$thumbSize";
		$keyImage = "{$name}_thumb_$imageSize";
		
		if(!isset($data[$keyThumb]))
			$keyThumb = $name;
		
		if(!isset($data[$keyImage]))
			$keyImage = $name;
		
		//add extra data
		if($isAddItemsData == true)
			$item = $this->getGalleryItem_sourceItemData($item, $sourceItem);
			
		$item["image"] = UniteFunctionsUC::getVal($data, $keyImage);
		$item["thumb"] = UniteFunctionsUC::getVal($data, $keyThumb);
		
		$item["image_width"] = UniteFunctionsUC::getVal($data, $keyImage."_width");
		$item["image_height"] = UniteFunctionsUC::getVal($data, $keyImage."_height");
		
		$item["thumb_width"] = UniteFunctionsUC::getVal($data, $keyThumb."_width");
		$item["thumb_height"] = UniteFunctionsUC::getVal($data, $keyThumb."_height");
		
		$title = $this->getGalleryItem_title($titleSource, $data, $name, $post, $sourceItem);
		$description = $this->getGalleryItem_title($descriptionSource, $data, $name, $post, $sourceItem);

		//demo item text
		if($isByUrl == true && count($data) == 1){
						
			if(empty($title))
				$title = "Demo Item {$index} Title";
			
			if(empty($description))
				$description = "Demo Item {$index} Description";
		}
		
		$item["title"] = $title;
		$item["description"] = $description;
		
		if(!isset($item["link"])){
			$item["link"] = "";
			if(!empty($post))
				$item["link"] = $post->guid;
		}
		
		$item["imageid"] = $id;
		
		return($item);
	}
	
	
	/**
	 * convert grouped data for gallery
	 * return the images data at the end
	 */
	private function getGroupedData_convertForGallery($arrItems, $source, $value, $param){
		
		$name = UniteFunctionsUC::getVal($param, "name");
		
		$thumbSize = UniteFunctionsUC::getVal($value, $name."_thumb_size");
		$imageSize = UniteFunctionsUC::getVal($value, $name."_image_size");
		
		$isEnableVideo = UniteFunctionsUC::getVal($param, "gallery_enable_video");
		$isEnableVideo = UniteFunctionsUC::strToBool($isEnableVideo);
		
		$arrFilters = array();
		if(!empty($thumbSize))
			$arrFilters[] = $thumbSize;
		
		if(!empty($imageSize))
			$arrFilters[] = $imageSize;
			
		$params = array();
		$params["thumb_size"] = $thumbSize;
		$params["image_size"] = $imageSize;
		$params["size_filters"] = $arrFilters;
		
		
		//set title and description source
		
		$titleSource = null;
		$descriptionSource = null;
		
		switch($source){
			case "posts":
				
				$titleSource = UniteFunctionsUC::getVal($value, $name."_title_source_post");
				$descriptionSource = UniteFunctionsUC::getVal($value, $name."_description_source_post");
							
			break;
			case "gallery":
				$titleSource = UniteFunctionsUC::getVal($value, $name."_title_source_gallery");
				$descriptionSource = UniteFunctionsUC::getVal($value, $name."_description_source_gallery");
			break;
			case "image_video_repeater":
				
				$titleSource = "item_title";
				$descriptionSource = "item_description";
				
			break;
		}
		
		$params["title_source"] = $titleSource;
		$params["description_source"] = $descriptionSource;
		
		$output = array();
		foreach($arrItems as $index => $item){
			
			$params["index"] = ($index+1);
			
			switch($source){
				case "products":
				case "posts":
					
					$postID = $item->ID;
					$content = $item->post_content;
					
					$featuredImageID = $this->getPostFeaturedImageID($postID, $content, $item->post_type);
					
					$params["post"] = $item;
					
					$galleryItem = $this->getGalleryItem($featuredImageID,null,$params);
					
					$galleryItem["postid"] = $postID;
										
				break;
				case "gallery":
					
					$id = UniteFunctionsUC::getVal($item, "id");
					$url = UniteFunctionsUC::getVal($item, "url");
					
					//for default items
					if(empty($id) && empty($url)){
						$url = UniteFunctionsUC::getVal($item, "image");
						
						if(!empty($url)){
							$params["item"] = $item;
							$params["title_source"] = "item_title";
						}
					}
						
					$galleryItem = $this->getGalleryItem($id, $url,$params);
					
				break;
				case "current_post_meta":
					
					//item is ID
					$galleryItem = $this->getGalleryItem($item,null,$params);
					
				break;
				case "image_video_repeater":
					
					$image = UniteFunctionsUC::getVal($item, "image");
					
					$url = UniteFunctionsUC::getVal($image, "url");
					$id = UniteFunctionsUC::getVal($image, "id");
										
					$params["add_item_data"] = true;
					$params["item"] = $item;
										
					$galleryItem = $this->getGalleryItem($id, $url, $params);
					
				break;
				case "instagram":
					
					$galleryItem = $this->getGalleryItem_instagram($item, $isEnableVideo);
					
				break;
				default:
					UniteFunctionsUC::throwError("group gallery error: unknown type: $source");
				break;
			}
			
			if(!empty($galleryItem))
				$output[] = $galleryItem;
			
		}
				
		return($output);		
	}
	
	/**
	 * get image ids from meta key
	 */
	private function getGroupedData_getArrImageIDsFromMeta($value, $name){
		
		if(is_single() == false)
			return(array());
			
		$post = get_post();
		if(empty($post))
			return(array());
		
		$postID = $post->ID;
			
		$isShowMeta = UniteFunctionsUC::getVal($value, $name."_show_metafields");
		$isShowMeta = UniteFunctionsUC::strToBool($isShowMeta);
		
		$arrMeta = array();
		
		//--- output debug
		if($isShowMeta == true){
			
			$arrMeta = UniteFunctionsWPUC::getPostMeta($postID);
			
			$arrMetaDebug = UniteFunctionsUC::modifyDataArrayForShow($arrMeta);
			
			dmp("<b>Debug Post Meta</b>, please turn it off on release");
			dmp($arrMetaDebug);			
		}
		
		//get meta key:
		
		$metaKey = UniteFunctionsUC::getVal($value, $name."_current_metakey");
		
		if(empty($metaKey)){
			
			if($isShowMeta == true)
				dmp("empty meta key, please set it");
			
			return(array());
		}

		$metaValues = get_post_meta($postID, $metaKey, true);
		
		if(empty($metaValues)){
			
			if($isShowMeta)
				dmp("no value for this meta key: $metaKey");
			
			return(array());
		}
		
		if(is_array($metaValues))
			return(false);
			
		$arrValues = explode(",", $metaValues);
		
		$arrIDs = array();
		foreach($arrValues as $value){
			$value = trim($value);
			if(is_numeric($value) == false)
				continue;
			
			$arrIDs[] = $value;
		}
		
		return($arrIDs);
	}
	
	/**
	 * try to get gallery items from addon items
	 */
	private function getGalleryItemsFromDefaultItems(){
		
		$arrItems = $this->addon->getArrItemsNonProcessed();
		
		if(empty($arrItems))
			return(array());
			
		$firstItem = $arrItems[0];
		if(isset($firstItem["image"]) == false)
			return(array());
		
		return($arrItems);
	}
	
	
	/**
	 * get remote parent type data
	 */
	private function getRemoteParentData($value, $name, $processType, $param, $data){
		
		$arrOutput = array();
		
		$isInsideEditor = UniteCreatorElementorIntegrate::$isEditMode;
		
		$isEnable = UniteFunctionsUC::getVal($value, $name."_enable");
		$isEnable = UniteFunctionsUC::strToBool($isEnable);
		
		$isDebug = UniteFunctionsUC::getVal($value, $name."_debug");
		$isDebug = UniteFunctionsUC::strToBool($isDebug);
		
		$isSync = UniteFunctionsUC::getVal($value, $name."_sync");
		$isSync = UniteFunctionsUC::strToBool($isSync);
		
		$widgetName = $this->addon->getTitle();
		
		if($isEnable == false && $isSync == false){
			
			$arrOutput["attributes"] = "";
			$arrOutput["class"] = "";
			$arrOutput["click_event"] = "click";
			
			$data[$name] = $arrOutput;
			
			return($data);
		}
		
		HelperUC::addRemoteControlsScript();
		
		$attributes = "";
		
		//get the name
		if($isEnable == true){
			
			$parentName = UniteFunctionsUC::getVal($value, $name."_name");
			
			if($parentName == "custom")
				$parentName = UniteFunctionsUC::getVal($value, $name."_custom_name");
			
			if(empty($parentName))
				$parentName = "auto";
			
			$parentName = UniteFunctionsUC::sanitizeAttr($parentName);
							
			//create attributes and classes
			
			$attributes .= " data-remoteid='$parentName'";
		}
		
		if($isDebug == true)
			$attributes .= " data-debug='true'";
		
		$widgetName = UniteFunctionsUC::sanitizeAttr($widgetName);
		
		if(!empty($widgetName))
			$attributes .= " data-widgetname='$widgetName'";			
		
		
		if($isSync == true){
			
			//get the name
			$syncParentName = UniteFunctionsUC::getVal($value, $name."_sync_name");
						
			$attributes .= " data-sync='true' data-syncid='$syncParentName'";
		}
		
		$class = " uc-remote-parent";
		
		//output
		
		$arrOutput["attributes"] = $attributes;
		$arrOutput["class"] = $class;
		$arrOutput["click_event"] = $isInsideEditor?"ucclick":"click";
		
		$data[$name] = $arrOutput;
		
		return($data);
	}
	
	/**
	 * get background data
	 */
	private function getRemoteBackgroundData($value, $name, $processType, $param, $data){
		
		$isSync = UniteFunctionsUC::getVal($value, $name."_sync");
		$isSync = UniteFunctionsUC::strToBool($isSync);
		
		if($isSync == false){
			
			$arrOutput["attributes"] = "";
			$arrOutput["class"] = "";
			
			$data[$name] = $arrOutput;
			
			return($data);
		}

		$syncParentName = UniteFunctionsUC::getVal($value, $name."_sync_name");
		$remoteParentName = UniteFunctionsUC::getVal($value, $name."_remote_name");
		
		$isDebug = UniteFunctionsUC::getVal($value, $name."_debug");
		$isDebug = UniteFunctionsUC::strToBool($isDebug);
		
		HelperUC::addRemoteControlsScript();
		
		$attributes = "";
		$attributes .= " data-sync='true' data-syncid='$syncParentName' data-remoteid='$remoteParentName'";
		
		if($isDebug == true)
			$attributes .= " data-debug='true'";
		
		$widgetName = $this->addon->getTitle();
		$widgetName = UniteFunctionsUC::sanitizeAttr($widgetName);
		
		if(!empty($widgetName))
			$attributes .= " data-widgetname='$widgetName'";			
		
		
		$class = " uc-remote-parent ";
		
		$arrOutput["attributes"] = $attributes;
		$arrOutput["class"] = $class;
		
		$data[$name] = $arrOutput;
		
		
		
		return($data);
	}
	
	
	/**
	 * add remote controller data
	 */
	private function getRemoteControllerData($value, $name, $processType, $param, $data){
		
		HelperUC::addRemoteControlsScript();
		
		$parentName = UniteFunctionsUC::getVal($value, $name."_name");
		
		if($parentName == "custom")
			$parentName = UniteFunctionsUC::getVal($value, $name."_custom_name");
		
		if(empty($name))
			$parentName = "auto";
		
		$parentName = UniteFunctionsUC::sanitizeAttr($parentName);
								
		$attributes = " data-parentid='$parentName'";
		
		$arrOutput = array();
		$arrOutput["attributes"] = $attributes;

		$data[$name] = $arrOutput;
		
		return($data);
	}
	
	/**
	 * get remote settings data
	 */
	private function getRemoteSettingsData($value, $name, $processType, $param, $data){
		
		$type = UniteFunctionsUC::getVal($param, "remote_type");
		
		switch($type){
			case "controller":
				
				$data = $this->getRemoteControllerData($value, $name, $processType, $param, $data);
				
			break;
			default:
			case "parent":
				$data = $this->getRemoteParentData($value, $name, $processType, $param, $data);
			break;
			case "background":
				
				$data = $this->getRemoteBackgroundData($value, $name, $processType, $param, $data);
				
			break;
		}

		
		return($data);
	}
	
	/**
	 * get listing data
	 */
	private function getListingData($value, $name, $processType, $param, $data){
		
		if($processType != self::PROCESS_TYPE_OUTPUT && $processType != self::PROCESS_TYPE_OUTPUT_BACK)
			return($data);
			
		$useFor = UniteFunctionsUC::getVal($param, "use_for");
		
		switch($useFor){
			case "remote":
				
				$data = $this->getRemoteSettingsData($value, $name, $processType, $param, $data);
				
				return($data);
			break;
			case "filter":
				
				return($data);
			break;
		}
		
		$isForGallery = ($useFor == "gallery");
		
		$source = UniteFunctionsUC::getVal($value, $name."_source");
		
		if(empty($source) && $isForGallery == true)
			$source = "gallery";
		
		$templateID = UniteFunctionsUC::getVal($value, $name."_template_templateid");
		
		$data[$name."_source"] = $source;
		$data[$name."_templateid"] = $templateID;
		
		unset($data[$name]);
		
		switch($source){
			case "posts":
				
				$paramPosts = $param;
				
				$paramPosts["name"] = $paramPosts["name"]."_posts";
				$paramPosts["name_listing"] = $name;
				$paramPosts["use_for_listing"] = true;
				
				$data = $this->getPostListData($value, $paramPosts["name"], $processType, $paramPosts, $data);
				
			break;
			case "products":
								
				$paramProducts = $param;
				
				$paramProducts["name"] = $paramProducts["name"]."_products";
				$paramProducts["name_listing"] = $name;
				$paramProducts["use_for_listing"] = true;
				$paramProducts["for_woocommerce_products"] = true;
								
				$data = $this->getPostListData($value, $paramProducts["name"], $processType, $paramProducts, $data);
				
			break;
			case "terms":
				
				dmp("get terms");
				$data[$name."_items"] = array();
				
			break;
			case "gallery":

				$arrGalleryItems = UniteFunctionsUC::getVal($value, $name."_gallery");
				
				//output defaults
				if(empty($arrGalleryItems))
					$arrGalleryItems = $this->getGalleryItemsFromDefaultItems();
				
				$data[$name."_items"] = $arrGalleryItems;
				
			break;
			case "current_post_meta":		//meta field with image id's
				
				$data[$name."_items"] = $this->getGroupedData_getArrImageIDsFromMeta($value, $name);				
				
			break;
			case "image_video_repeater":
				
				$data[$name."_items"] = UniteFunctionsUC::getVal($value, $name."_items");
				
				//do nothing, convert later
				
			break;
			case "instagram":
								
				$paramInstagram = $param;
				$paramInstagram["name"] = $paramInstagram["name"]."_instagram";
				
				$arrInstagramData = $this->getInstagramData($value, $name."_instagram", $paramInstagram);
				
				$error = UniteFunctionsUC::getVal($arrInstagramData, "error");
				if(!empty($error))
					UniteFunctionsUC::throwError($error);
								
				$arrInstagramItems = UniteFunctionsUC::getVal($arrInstagramData, "items");
				
				
				if(empty($arrInstagramItems))
					$arrInstagramItems = array();
				
				$data[$name."_items"] = $arrInstagramItems;
								
			break;
			default:
				UniteFunctionsUC::throwError("Wrong dynamic content source: $source");
			break;
		}
		
		if($isForGallery == true){
			
			$arrItems = $data[$name."_items"];
						
			$data[$name."_items"] = $this->getGroupedData_convertForGallery($arrItems, $source, $value, $param);
			
			
			return($data);
		}
		
		//modify items output
		$arrItems = UniteFunctionsUC::getVal($data, $name."_items");
		
		if(empty($arrItems))
			$arrItems = array();
		
		//convert listing items
			
		foreach($arrItems as $index => $item){
			
			$numItem = $index+1;
			
			switch($source){
				case "posts":
				case "products":
					$title = $item->post_title;
					
					$newItem = array(
						"index"=>$numItem,
						"title"=>$title,
						"object"=>$item
					);
				break;
				case "terms":
				break;
				case "gallery":
					continue(2);
				break;
				default:
					$key = $index++;
					$title = "item_{$index}";					
				break;
			}
			
			$arrItems[$index] = $newItem;
		}
		
		$data[$name."_items"] = $arrItems;
		
		return($data);
	}
	
	
	
	protected function z_______________TERMS____________(){}
	
	
	/**
	 * get woo categories data
	 */
	protected function getWooCatsData($value, $name, $processType, $param){

		$selectionType = UniteFunctionsUC::getVal($value, $name."_type");
		
		//add params
		$params = array();
		$taxonomy = "product_cat";
		
		$showDebug = UniteFunctionsUC::getVal($value, $name."_show_query_debug");
		$showDebug = UniteFunctionsUC::strToBool($showDebug);
		
		
		if($selectionType == "manual"){
		
			$includeSlugs = UniteFunctionsUC::getVal($value, $name."_include");
			
			$arrTerms = UniteFunctionsWPUC::getSpecificTerms($includeSlugs, $taxonomy);
			
		}else{
		
				$orderBy =  UniteFunctionsUC::getVal($value, $name."_orderby");
				$orderDir =  UniteFunctionsUC::getVal($value, $name."_orderdir");
				
				$hideEmpty = UniteFunctionsUC::getVal($value, $name."_hideempty");
				
				$strExclude = UniteFunctionsUC::getVal($value, $name."_exclude");
				$strExclude = trim($strExclude);
				
				$excludeUncategorized = UniteFunctionsUC::getVal($value, $name."_excludeuncat");
				
				$parent = UniteFunctionsUC::getVal($value, $name."_parent");
				$parent = trim($parent);
				
				$includeChildren = UniteFunctionsUC::getVal($value, $name."_children");
				
				$parentID = 0;
				if(!empty($parent)){
					
					$term = UniteFunctionsWPUC::getTermBySlug("product_cat", $parent);
					
					if(!empty($term))
						$parentID = $term->term_id;
				}
				
				$isHide = false;
				if($hideEmpty == "hide")
					$isHide = true;
								
				//add exclude
				$arrExcludeSlugs = null;
				
				if(!empty($strExclude))
					$arrExcludeSlugs = explode(",", $strExclude);
				
				//exclude uncategorized
				if($excludeUncategorized == "exclude"){
					if(empty($arrExcludeSlugs))
						$arrExcludeSlugs = array();
					
					$arrExcludeSlugs[] = "uncategorized";
				}			
				
				if($includeChildren == "not_include"){
					$params["parent"] = $parentID;
					
				}else{
					$params["child_of"] = $parentID;
				}
				
				
				$isWpmlExists = UniteCreatorWpmlIntegrate::isWpmlExists();
				if($isWpmlExists)
					$params["suppress_filters"] = false;
				
			if(!empty($orderBy)){
	
				$metaKey = "";
				if($orderBy == "meta_value" || $orderBy == "meta_value_num"){
					
					$metaKey = UniteFunctionsUC::getVal($value, $name."_orderby_meta_key");
					$metaKey = trim($metaKey);
									
					if(empty($metaKey))
						$orderBy = null;
					else
						$params["meta_key"] = $metaKey;
				}
			}
						
			$arrTerms = UniteFunctionsWPUC::getTerms($taxonomy, $orderBy, $orderDir, $isHide, $arrExcludeSlugs, $params);

			if($showDebug == true){
				echo "<div class='uc-div-ajax-debug'>";
					dmp("The terms query is:");
					dmp(UniteFunctionsWPUC::$arrLastTermsArgs);
					dmp("num terms found: ".count($arrTerms));
				echo "</div>";
			}
			
			
		}//not manual
		
		$arrTerms = $this->modifyArrTermsForOutput($arrTerms, $taxonomy);
				
		return($arrTerms);
	}
	
	/**
	 * add meta query
	 */
	private function addMetaQueryItem($arrMetaQuery, $metaKey, $metaValue, $metaCompare = "="){
				
		if(empty($metaKey))
			return($arrMetaQuery);
		
		if(empty($metaCompare))
			$metaCompare = "=";
		
		$isValueArray = false;
		switch($metaCompare){
			case "IN":
			case "NOT IN":
			case "BETWEEN":
			case "NOT BETWEEN":
				$isValueArray = true;
			break;
		}
		
		if($isValueArray == true){
			$arrValues = explode(",", $metaValue);
			foreach($arrValues as $key=>$value)
				$arrValues[$key] = trim($value);
			
			$value = $arrValues;
		}

		$arr = array();
		
		$arrItem = array(
		        'key'     => $metaKey,
		        'value'   => $metaValue,
		        'compare' => $metaCompare
		);
		
		$arrMetaQuery[] = $arrItem;
		
		return($arrMetaQuery);
	}
	
	
	/**
	 * get terms data
	 */
	protected function getWPTermsData($value, $name, $processType, $param){
		
		$postType = UniteFunctionsUC::getVal($value, $name."_posttype","post");
		$taxonomy =  UniteFunctionsUC::getVal($value, $name."_taxonomy","category");
		
		$orderBy =  UniteFunctionsUC::getVal($value, $name."_orderby","name");
		$orderDir =  UniteFunctionsUC::getVal($value, $name."_orderdir","ASC");
		
		$hideEmpty = UniteFunctionsUC::getVal($value, $name."_hideempty");
		
		$strExclude = UniteFunctionsUC::getVal($value, $name."_exclude");
		$excludeWithTree = UniteFunctionsUC::getVal($value, $name."_exclude_tree");
		$excludeWithTree = UniteFunctionsUC::strToBool($excludeWithTree);

		$showDebug = UniteFunctionsUC::getVal($value, $name."_show_query_debug");
		$showDebug = UniteFunctionsUC::strToBool($showDebug);
		
		
		$queryDebugType = "";
		if($showDebug == true)
			$queryDebugType = UniteFunctionsUC::getVal($value, $name."_query_debug_type");
		
		$maxTerms = UniteFunctionsUC::getVal($value, $name."_maxterms");
		$maxTerms = (int)$maxTerms;
		if(empty($maxTerms))
			$maxTerms = 100;
		
		$arrIncludeBy = UniteFunctionsUC::getVal($value, $name."_includeby");
		if(empty($arrIncludeBy))
			$arrIncludeBy = array();
		
		$arrExcludeBy = UniteFunctionsUC::getVal($value, $name."_excludeby");
		if(empty($arrExcludeBy))
			$arrExcludeBy = array();
		
		$arrExcludeIDs = array();
		
		if(is_string($strExclude))
			$strExclude = trim($strExclude);
		else{
			$arrExcludeIDs = $strExclude;
			$strExclude = null;
		}
		
		$useCustomFields = UniteFunctionsUC::getVal($param, "use_custom_fields");
		$useCustomFields = UniteFunctionsUC::strToBool($useCustomFields);
		
		$isHide = false;
		if($hideEmpty == "hide")
			$isHide = true;
		
		if(empty($postType)){
			$postType = "post";
			$taxonomy = "category";
		}
		
		
		
		
		//add exclude
		$arrExcludeSlugs = null;
		
		if(!empty($strExclude))
			$arrExcludeSlugs = explode(",", $strExclude);
		
		//includeby
		$arrIncludeTermIDs = array();
		$includeParentID = null;	
		$isDirectParent = true;
		
		$args = array();
		
		$arrMetaQuery = array();
		
		foreach($arrIncludeBy as $includeby){
			
			switch($includeby){
				case "spacific_terms":
					
					$arrIncludeTermIDs = UniteFunctionsUC::getVal($value, $name."_include_specific");
					
				break;
				case "parents":
					
					$includeParentID = UniteFunctionsUC::getVal($value, $name."_include_parent");
					if(is_array($includeParentID))
						$includeParentID = $includeParentID[0];
						
					$isDirectParent = UniteFunctionsUC::getVal($value, $name."_taxonomy_include_parent_isdirect");
					
					$isDirectParent = UniteFunctionsUC::strToBool($isDirectParent);
											
				break;
				case "search":
					
					$search = UniteFunctionsUC::getVal($value, $name."_include_search");
					$search = trim($search);
					
					if(!empty($search))
						$args["search"] = $search;
					
				break;
				case "childless":

					$args["childless"] = true;
					
				break;
				case "no_parent":
					
					$args["parent"] = "0";
					
				break;
				case "meta":
					
					$metaKey = UniteFunctionsUC::getVal($value, $name."_include_metakey");
					$metaValue = UniteFunctionsUC::getVal($value, $name."_include_metavalue");
					$metaCompare = UniteFunctionsUC::getVal($value, $name."_include_metacompare");
					
					$arrMetaQuery = $this->addMetaQueryItem($arrMetaQuery, $metaKey, $metaValue, $metaCompare);
					
				break;
				case "children_of_current":
					
					$parentTermID = UniteFunctionsWPUC::getCurrentTermID();
					
					$args["parent"] = $parentTermID;
										
				break;
				default:
					dmp("wrong include by: $includeby");
				break;
			}
			
		}
				
		foreach($arrExcludeBy as $excludeBy){
			
			switch($excludeBy){
				case "current_term":
					
					$currentTermID = UniteFunctionsWPUC::getCurrentTermID();
					if(!empty($currentTermID))
						$arrExcludeIDs[] = $currentTermID;
					
				break;
				case "hide_empty":
					$isHide = true;
				break;
			}
			
		}
		
		if(!empty($arrMetaQuery))
			$args["meta_query"] = $arrMetaQuery;
				
		//---------- get the args
		
		$args["hide_empty"] = $isHide;
		$args["taxonomy"] = $taxonomy;
		$args["count"] = true;
		$args["number"] = $maxTerms;
		
		if(!empty($orderBy)){

			$metaKey = "";
			if($orderBy == "meta_value" || $orderBy == "meta_value_num"){
				
				$metaKey = UniteFunctionsUC::getVal($value, $name."_orderby_meta_key");
				$metaKey = trim($metaKey);
								
				if(empty($metaKey))
					$orderBy = null;
			}
			
			if(!empty($orderBy)){
				
				$args["orderby"] = $orderBy;
				
				if(!empty($metaKey))
					$args["meta_key"] = $metaKey;
				
				if(empty($orderDir))
					$orderDir = self::ORDER_DIRECTION_ASC;
				
				$args["order"] = $orderDir;
			}
			
		}
		
		//exclude
		if(!empty($arrExcludeIDs)){
			
			$key = "exclude";
			if($excludeWithTree == true)
				$key = "exclude_tree";
			
			$args[$key] = $arrExcludeIDs;
		}
		
		//include specific
		if(!empty($arrIncludeTermIDs))
			$args["include"] = $arrIncludeTermIDs;
		
		if(!empty($includeParentID)){
			
			$parentKey = "parent";
			if($isDirectParent == false)
				$parentKey = "child_of";
			
			$args[$parentKey] = $includeParentID;
		}
		
		$isWpmlExists = UniteCreatorWpmlIntegrate::isWpmlExists();
		if($isWpmlExists)
			$args["suppress_filters"] = false;
		
		//------- get the terms and filter by slugs if available
		
		HelperUC::addDebug("Terms Query", $args);
		
		if($showDebug == true){
			echo "<div class='uc-div-ajax-debug'>";
			
			dmp("The terms query is:");
			dmp($args);
		}

		
		$term_query = new WP_Term_Query();
		$arrTermsObjects = $term_query->query( $args );
		
		if($showDebug == true && $queryDebugType == "show_query"){
			
			$originalQueryVars = $term_query->query_vars;
			$originalQueryVars = UniteFunctionsWPUC::cleanQueryArgsForDebug($originalQueryVars);
			
			dmp("The Query Request Is:");
			dmp($term_query->request);
			
			dmp("The finals query vars:");
			dmp($originalQueryVars);
			
			
			$arrActions = UniteFunctionsWPUC::getFilterCallbacks("get_terms_args");
			
			dmp("Query modify callbacks ( get_terms_args ):");
			dmp($arrActions);
			
			$arrActions = UniteFunctionsWPUC::getFilterCallbacks("get_terms_orderby");
			
			dmp("Query modify callbacks ( get_terms_orderby ):");
			dmp($arrActions);
			
		}
		
		if($showDebug == true)
			echo "</div>";
		
		if(!empty($arrExcludeSlugs)){
			HelperUC::addDebug("Terms Before Filter:", $arrTermsObjects);
			HelperUC::addDebug("Exclude by:", $arrExcludeSlugs);
		}
		
		if(!empty($arrExcludeSlugs) && is_array($arrExcludeSlugs))
			$arrTermsObjects = UniteFunctionsWPUC::getTerms_filterBySlugs($arrTermsObjects, $arrExcludeSlugs);
					
		$arrTerms = UniteFunctionsWPUC::getTermsObjectsData($arrTermsObjects, $taxonomy);
		
		
		$arrTerms = $this->modifyArrTermsForOutput($arrTerms, $taxonomy, $useCustomFields);

				
		
		return($arrTerms);
	}
	
	
	
	protected function z_______________USERS____________(){}
	
	
	/**
	 * modify users array for output
	 */
	public function modifyArrUsersForOutput($arrUsers, $getMeta, $getAvatar, $arrMetaKeys = null){
		
		if(empty($arrUsers))
			return(array());
		
		$arrUsersData = array();
		
		foreach($arrUsers as $objUser){
			
			$arrUser = UniteFunctionsWPUC::getUserData($objUser, $getMeta, $getAvatar, $arrMetaKeys);
			
			$arrUsersData[] = $arrUser;
		}
		
		return($arrUsersData);
	}
	
	
	/**
	 * get users data
	 */
	protected function getWPUsersData($value, $name, $processType, $param){
		
		$showDebug = UniteFunctionsUC::getVal($value, $name."_show_query_debug");
		$showDebug = UniteFunctionsUC::strToBool($showDebug);

		$selectType = UniteFunctionsUC::getVal($value, $name."_type");
			
		$args = array();
		
		if($selectType == "manual"){		//manual select
		
			$arrIncludeUsers = UniteFunctionsUC::getVal($value, $name."_include_authors");
			if(empty($arrIncludeUsers))
				$arrIncludeUsers = array("0");
			
			$args["include"] = $arrIncludeUsers;
			
		}else{

			//create the args
			$strRoles = UniteFunctionsUC::getVal($value, $name."_role");
			
			if(is_array($strRoles))
				$arrRoles = $strRoles;
			else
				$arrRoles = explode(",", $strRoles);
			
			$arrRoles = UniteFunctionsUC::arrayToAssoc($arrRoles);
			unset($arrRoles["__all__"]);
						
			if(!empty($arrRoles)){
				$arrRoles = array_values($arrRoles);
				
				$args["role__in"] = $arrRoles;
			}
			
			//add exclude roles:
			$arrRolesExclude = UniteFunctionsUC::getVal($value, $name."_role_exclude");
			
			if(!empty($strRolesExclude) && is_string($strRolesExclude))
				$arrRolesExclude = explode(",", $arrRolesExclude);
			
			if(!empty($arrRolesExclude))
				$args["role__not_in"] = $arrRolesExclude;
			
			//--- number of users
			
			$numUsers = UniteFunctionsUC::getVal($value, $name."_maxusers");
			$numUsers = (int)$numUsers;
			
			if(!empty($numUsers))
				$args["number"] = $numUsers;
			
			//--- exclude by users
			
			$arrExcludeAuthors = UniteFunctionsUC::getVal($value, $name."_exclude_authors");
			
			if(!empty($arrExcludeAuthors))
				$args["exclude"] = $arrExcludeAuthors;
			
			
		}
		
		
		//--- orderby --- 
		
		$orderby = UniteFunctionsUC::getVal($value, $name."_orderby");
		if($orderby == "default")
			$orderby = null;
		
		if(!empty($orderby))
			$args["orderby"] = $orderby;
		
		//--- order dir ----
			
		$orderdir = UniteFunctionsUC::getVal($value, $name."_orderdir");
		if($orderdir == "default")
			$orderdir = null;
		
		if(!empty($orderdir))
			$args["order"] = $orderdir;
		
		//---- debug
			
		if($showDebug == true){
			dmp("The users query is:");
			dmp($args);
		}
		
		HelperUC::addDebug("Get Users Args", $args);
		
		$arrUsers = get_users($args);
		
		HelperUC::addDebug("Num Users fetched: ".count($arrUsers));
		
		
		
		if($showDebug == true){
			dmp("Num Users fetched: ".count($arrUsers));
		}
		
		$getMeta = UniteFunctionsUC::getVal($param, "get_meta");
		$getMeta = UniteFunctionsUC::strToBool($getMeta);
		
		$getAvatar = UniteFunctionsUC::getVal($param, "get_avatar");
		$getAvatar = UniteFunctionsUC::strToBool($getAvatar);

		//add meta fields
		
		$strAddMetaKeys = UniteFunctionsUC::getVal($value, $name."_add_meta_keys");
		
		$arrMetaKeys = null;
		if(!empty($strAddMetaKeys))
			$arrMetaKeys = explode(",", $strAddMetaKeys);
		
		$arrUsers = $this->modifyArrUsersForOutput($arrUsers, $getMeta, $getAvatar, $arrMetaKeys);
		
		return($arrUsers);
	}
	
	protected function z_______________MENU____________(){}
	
	
	/**
	 * get menu output
	 */
	protected function getWPMenuData($value, $name, $param, $processType){
				
		$menuID = UniteFunctionsUC::getVal($value, $name."_id");
				
		//get first menu
		if(empty($menuID)){
			$htmlMenu = __("menu not selected","unlimited-elements-for-elementor");
			return($htmlMenu);
		}
		
		$depth = UniteFunctionsUC::getVal($value, $name."_depth");
		
		$depth = (int)$depth;
		
		//make the arguments
		$args = array();
		$args["echo"] = false;
		$args["container"] = "";
		
		if(!empty($depth) && is_numeric($depth))
			$args["depth"] = $depth;
		
		
		$args["menu"] = $menuID;
		
		$arrKeysToAdd = array(
			"menu_class",
			"before",
			"after"
		);
		
		foreach($arrKeysToAdd as $key){
			
			$value = UniteFunctionsUC::getVal($param, $key);
			if(!empty($value))
				$args[$key] = $value;
		}
				
		HelperUC::addDebug("menu arguments", $args);
		
		$htmlMenu = wp_nav_menu($args);
		
		return($htmlMenu);
	}
	
	
	protected function z_______________TEMPLATE____________(){}

	/**
	 * get template data
	 */
	private function getElementorTemplateData($value, $name, $processType, $param, $data){
		
		$templateID = UniteFunctionsUC::getVal($value, $name."_templateid");
		
		if(empty($templateID))
			return($data);
		
		if($templateID == "__none__")
			$templateID = "";
		
		if(empty($templateID))
			$shortcode = "";
		else
			$shortcode = "[elementor-template id=\"$templateID\"]";
				
		$data[$name] = $shortcode;
		$data[$name."_templateid"] = $templateID;
		
		return($data);
	}
	
	protected function z_______________POST_FILTERS____________(){}
	
	
	
	/**
	 * get post filter options
	 */
	private function modifyData_postFilterOptions($data){
		
		$objFilters = new UniteCreatorFiltersProcess();
		
		$data = $objFilters->addEditorFilterArguments($data);
		
		return($data);
	}
	
	
	/**
	 * modify data by special behaviour
	 */
	protected function modifyDataBySpecialAddonBehaviour($data){
		
		$special = $this->addon->getOption("special");
		
		if(empty($special))
			return($data);
		
		switch($special){
			case "post_filter":
				$data = $this->modifyData_postFilterOptions($data);
			break;
		}
		
		
		return($data);
	}
	
	protected function z_______________GET_PARAMS____________(){}
	
	
	/**
	 * get processe param data, function with override
	 */
	protected function getProcessedParamData($data, $value, $param, $processType){
			
		$type = UniteFunctionsUC::getVal($param, "type");
		$name = UniteFunctionsUC::getVal($param, "name");
				
		//special params
		switch($type){
			case UniteCreatorDialogParam::PARAM_POSTS_LIST:
			    $data = $this->getPostListData($value, $name, $processType, $param, $data);
			break;
			case UniteCreatorDialogParam::PARAM_LISTING:
			    $data = $this->getListingData($value, $name, $processType, $param, $data);
			break;
			case UniteCreatorDialogParam::PARAM_POST_TERMS:
				
				$data[$name] = $this->getWPTermsData($value, $name, $processType, $param);
				
			break;
			case UniteCreatorDialogParam::PARAM_WOO_CATS:
				$data[$name] = $this->getWooCatsData($value, $name, $processType, $param);
			break;
			case UniteCreatorDialogParam::PARAM_USERS:
				$data[$name] = $this->getWPUsersData($value, $name, $processType, $param);
			break;
			case UniteCreatorDialogParam::PARAM_TEMPLATE:
				$data = $this->getElementorTemplateData($value, $name, $processType, $param, $data);
			break;
			default:
				$data = parent::getProcessedParamData($data, $value, $param, $processType);
			break;
		}
		
			
		return($data);
	}
	
	/**
	 * set extra params value, add it to the param values fields
	 * like value_extra = something
	 */
	public function setExtraParamsValues($paramType, $param, $name, $arrValues){
		
	    switch($paramType){
	    	//add size param for image
	    	case UniteCreatorDialogParam::PARAM_IMAGE:
			
	    		$isAddSizes = UniteFunctionsUC::getVal($param, "add_image_sizes");
	    		$isAddSizes = UniteFunctionsUC::strToBool($isAddSizes);
	    		
	    		if($isAddSizes == true)
	    			$param["value_size"] = UniteFunctionsUC::getVal($arrValues, $name."_size");
	    		
	    	break;
	    }
				
	    return($param);
	}
	
	
	/**
	 * get param value, function for override, by type
	 * to get multiple values from one, as array
	 */
	public function getSpecialParamValue($paramType, $paramName, $value, $arrValues){
		
	    switch($paramType){
	        case UniteCreatorDialogParam::PARAM_POSTS_LIST:
	        case UniteCreatorDialogParam::PARAM_LISTING:
	        case UniteCreatorDialogParam::PARAM_POST_TERMS:
	        case UniteCreatorDialogParam::PARAM_WOO_CATS:
	        case UniteCreatorDialogParam::PARAM_USERS:
	        case UniteCreatorDialogParam::PARAM_CONTENT:
	        case UniteCreatorDialogParam::PARAM_BACKGROUND:
	        case UniteCreatorDialogParam::PARAM_MENU:
	        case UniteCreatorDialogParam::PARAM_INSTAGRAM:
	        case UniteCreatorDialogParam::PARAM_TEMPLATE:
	            
	            $paramArrValues = array();
	            $paramArrValues[$paramName] = $value;
	            
	            foreach($arrValues as $key=>$value){
	                if(strpos($key, $paramName."_") === 0)
	                    $paramArrValues[$key] = $value;
	            }
	            
	            $value = $paramArrValues;
	            	            
	        break;
	    }
	   	
	    return($value);
	}
	
	
	
}