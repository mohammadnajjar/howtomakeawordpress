<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');

class UniteCreatorFiltersProcess{

	const DEBUG_MAIN_QUERY = false;
	
	const DEBUG_FILTER = false;
	
	private static $filters = null;
	private static $arrInputFiltersCache = null;
	private static $arrFiltersAssocCache = null;
	private static $currentTermCache = null;	
	private static $isModeInit = false;
	
	private static $isScriptAdded = false;
	private static $isFilesAdded = false;
	private static $isStyleAdded = false;
	private static $isAjaxCache = null;
	private static $isModeReplace = false;
	private static $numTotalPosts;
	
	private static $showDebug = false;
	private static $originalQueryVars = null;
	private $contentWidgetsDebug = array();
	private static $lastArgs = null;	
	
	const TYPE_TERMS = "terms";
	
	
	/**
	 * check if under ajax request
	 */
	private function isUnderAjax(){
		
		$ajaxAction = UniteFunctionsUC::getPostGetVariable("ucfrontajaxaction","",UniteFunctionsUC::SANITIZE_TEXT_FIELD);
		
		if(!empty($ajaxAction))
			return(true);
		
		return(false);
	}
	
	
	/**
	 * get fitler url from the given slugs
	 */
	private function getUrlFilter_term($term, $taxonomyName){
		
		$key = "filter-term";
		
		$taxPrefix = $taxonomyName."--";
		
		if($taxonomyName == "category"){
			$taxPrefix = "";
			$key="filter-category";
		}
		
		$slug = $term->slug;

		$value = $taxPrefix.$slug;
		
		$urlAddition = "{$key}=".urlencode($value);
				
		$urlCurrent = GlobalsUC::$current_page_url;
				
		$url = UniteFunctionsUC::addUrlParams($urlCurrent, $urlAddition);
		
		return($url);
	}
	
	/**
	 * check if the term is acrive
	 */
	private function isTermActive($term, $arrActiveFilters = null){
		
		if(empty($term))
			return(false);
		
		if($arrActiveFilters === null)
			$arrActiveFilters = $this->getRequestFilters();
		
		if(empty($arrActiveFilters))
			return(false);
		
		$taxonomy = $term->taxonomy;
		
		$selectedTermID = UniteFunctionsUC::getVal($arrActiveFilters, $taxonomy);
		
		if(empty($selectedTermID))
			return(false);
			
		if($selectedTermID === $term->term_id)
			return(true);
			
		return(false);
	}
	
	/**
	 * get current term by query vars
	 */
	private function getCurrentTermByQueryVars($queryVars){
		
		if(is_array($queryVars) == false)
			return(null);
		
		if(empty($queryVars))
			return(null);
			
		if(count($queryVars) > 1)
			return(null);
		
		$postType = null;
		if(isset($queryVars["post_type"])){
			
			$postType = $queryVars["post_type"];
			unset($queryVars["post_type"]);
		}
		
		$args = array();
		if(!empty($postType))
			$args["post_type"] = $postType;
		
		if(!empty($queryVars)){
			$taxonomy = null;
			$slug = null;
	
			foreach($queryVars as $queryTax=>$querySlug){
							
				$taxonomy = $queryTax;
				$slug = $querySlug;
			}
			
			$args = array();
			$args["taxonomy"] = $taxonomy;
			$args["slug"] = $slug;			
		}
				
		$arrTerms = get_terms($args);
		
		$isError = is_wp_error($arrTerms);
		
		if($isError == true){
			if(self::$showDebug == true){
				
				dmp("error get terms");
				dmp($args);
				dmp($arrTerms);
			}
			
			UniteFunctionsUC::throwError("cannot get the terms");
		}
			
		if(empty($arrTerms))
			return(null);
			
		$term = $arrTerms[0];
		
		return($term);
	}
	
	
	/**
	 * get current term
	 */
	private function getCurrentTerm(){
		
		if(!empty(self::$currentTermCache))
			return(self::$currentTermCache);
		
		if(is_archive() == false)
			return(null);
		
		if(!empty(self::$originalQueryVars)){
			
			$currentTerm = $this->getCurrentTermByQueryVars(self::$originalQueryVars);
		}else{
			$currentTerm = get_queried_object();
			
			
			
			if($currentTerm instanceof WP_Term == false)
				$currentTerm = null;
		}
		
		self::$currentTermCache = $currentTerm;
		
		return($currentTerm);
	}
	
	private function _______PARSE_INPUT_FILTERS__________(){}
	
	/**
	 * get request array
	 */
	private function getArrRequest(){
		
		$request = $_GET;
		if(!empty($_POST))
			$request = array_merge($request, $_POST);
		
		return($request);
	}
	
	/**
	 * parse base query
	 */
	private function parseBaseFilters($strBase){
		
		if(empty($strBase))
			return(null);
		
		$arrFilter = explode("~", $strBase);
		
		if(count($arrFilter) != 2)
			return(null);

		$term = $arrFilter[0];
		$value = $arrFilter[1];
			
		$arrBase = array();
		$arrBase[$term] = $value;
		
		return($arrBase);
	}
	
	
	/**
	 * parse filters string
	 */
	private function parseStrTerms($strFilters){
		
		$strFilters = trim($strFilters);
		
		$arrFilters = explode(";", $strFilters);
		
		//fill the terms
		$arrTerms = array();
		
		foreach($arrFilters as $strFilter){
			
			$arrFilter = explode("~", $strFilter);
			
			if(count($arrFilter) != 2)
				continue;
			
			$key = $arrFilter[0];
			$strValues = $arrFilter[1];
			
			$arrValues = explode(".", $strValues);
			
			$isTermsAnd = false;
			foreach($arrValues as $valueKey=>$value){
				if($value === "*"){
					unset($arrValues[$valueKey]);
					$isTermsAnd = true;
				}
			}
			
			if($isTermsAnd == true)
				$arrValues["relation"] = "AND";
							
			$type = self::TYPE_TERMS;
			
			switch($type){
				case self::TYPE_TERMS:
					$arrTerms[$key] = $arrValues;
				break;
			}
			
		}
		
		$arrOutput = array();
		
		if(!empty($arrTerms))
			$arrOutput[self::TYPE_TERMS] = $arrTerms;
			
		return($arrOutput);
	}
	
	
	/**
	 * get filters array from input
	 */
	private function getArrInputFilters(){
		
		if(!empty(self::$arrInputFiltersCache))
			return(self::$arrInputFiltersCache);
		
		$request = $this->getArrRequest();
		
		$strTerms = UniteFunctionsUC::getVal($request, "ucterms");
				
		$arrOutput = array();
		
		//parse filters
		
		if(!empty($strTerms)){
			if(self::$showDebug == true)
				dmp("input filters found: $strTerms");
			
			$arrOutput = $this->parseStrTerms($strTerms);
		}
		
		//page
		
		$page = UniteFunctionsUC::getVal($request, "ucpage");
		$page = (int)$page;
		
		if(!empty($page))
			$arrOutput["page"] = $page;
		
		//offset
		$offset = UniteFunctionsUC::getVal($request, "ucoffset");
		$offset = (int)$offset;
		
		if(!empty($offset))
			$arrOutput["offset"] = $offset;
		
			
		//num items
			
		$numItems = UniteFunctionsUC::getVal($request, "uccount");
		$numItems = (int)$numItems;
		
		if(!empty($numItems))
			$arrOutput["num_items"] = $numItems;
				
		self::$arrInputFiltersCache = $arrOutput;
		
		return($arrOutput);
	}
	
	
	/**
	 * get input filters in assoc mode
	 */
	private function getInputFiltersAssoc(){
		
		if(!empty(self::$arrFiltersAssocCache))
			return(self::$arrFiltersAssocCache);
		
		$arrFilters = $this->getArrInputFilters();
		
		$output = array();
		
		$terms = UniteFunctionsUC::getVal($arrFilters, "terms");
		
		if(empty($terms))
			$terms = array();
		
		foreach($terms as $taxonomy=>$arrTermSlugs){
				
			foreach($arrTermSlugs as $slug){
				
				$key = "term_{$taxonomy}_{$slug}";
				
				$output[$key] = true;
			}
			
		}
		
		self::$arrFiltersAssocCache = $output;
				
		return($output);
	}
	
	
	/**
	 * get filters arguments
	 */
	public function getRequestFilters(){
		
		if(self::$filters !== null)
			return(self::$filters);
		
		self::$filters = array();
		
		$arrInputFilters = $this->getArrInputFilters();
		
		if(empty($arrInputFilters))
			return(self::$filters);
		
		$arrTerms = UniteFunctionsUC::getVal($arrInputFilters, self::TYPE_TERMS);
		
		if(!empty($arrTerms))
			self::$filters["terms"] = $arrTerms;
		
		
		//get the page
		
		$page = UniteFunctionsUC::getVal($arrInputFilters, "page");
		
		if(!empty($page) && is_numeric($page))
			self::$filters["page"] = $page;
		
		//get the offset
		
		$offset = UniteFunctionsUC::getVal($arrInputFilters, "offset");
		
		if(!empty($offset) && is_numeric($offset))
			self::$filters["offset"] = $offset;
		
		
		//get num items
		$numItems = UniteFunctionsUC::getVal($arrInputFilters, "num_items");
		
		if(!empty($numItems) && is_numeric($numItems))
			self::$filters["num_items"] = $numItems;
		
		
		return(self::$filters);
	}
	
	
	private function _______FILTER_ARGS__________(){}
	
	
	/**
	 * get offset
	 */
	private function processRequestFilters_setPaging($args, $page, $numItems){
		
		if(empty($page))	
			return(null);
		
		$perPage = UniteFunctionsUC::getVal($args, "posts_per_page");
		
		if(empty($perPage))
			return($args);
		
		$offset = null;
		$postsPerPage = null;
		
		//set posts per page and offset
		if(!empty($numItems) && $page > 1){
			
			if($page == 2)
				$offset = $perPage;
			else if($page > 2)
				$offset = $perPage+($page-2)*$numItems;
			
			$postsPerPage = $numItems;
				
		}else{	//no num items
			$offset = ($page-1)*$perPage;
		}
			
		if(!empty($offset))
			$args["offset"] = $offset;
		
		if(!empty($postsPerPage))
			$args["posts_per_page"] = $postsPerPage;
		
		return($args);
	}
	
	/**
	 * get tax query from terms array
	 */
	private function getTaxQuery($arrTax){
		
		$arrQuery = array();
		
		foreach($arrTax as $taxonomy=>$arrTerms){
			
			$relation = UniteFunctionsUC::getVal($arrTerms, "relation");
			
			if($relation == "AND"){		//multiple
				
				unset($arrTerms["relation"]);
				
				foreach($arrTerms as $term){
					
					$item = array();
					$item["taxonomy"] = $taxonomy;
					$item["field"] = "slug";
					$item["terms"] = $term;
				
					$arrQuery[] = $item;
				}
				
			}else{		//single  (or)
				
				$item = array();
				$item["taxonomy"] = $taxonomy;
				$item["field"] = "slug";
				$item["terms"] = $arrTerms;
			
				$arrQuery[] = $item;
			}
									
		}
		
		$arrQuery["relation"] = "AND";
		
		return($arrQuery);
	}
	
	/**
	 * set arguments tax query, merge with existing if avaliable
	 */
	private function setArgsTaxQuery($args, $arrTaxQuery){
		
		if(empty($arrTaxQuery))
			return($args);
		
		$existingTaxQuery = UniteFunctionsUC::getVal($args, "tax_query");
		
		//if replace terms mode - just delete the existing tax query
		if(self::$isModeReplace == true)
			$existingTaxQuery = null;
			
		if(empty($existingTaxQuery)){
			
			$args["tax_query"] = $arrTaxQuery;
						
			return($args);
		}
				
		$newTaxQuery = array(
			$existingTaxQuery, 
			$arrTaxQuery
		);
				
		$newTaxQuery["relation"] = "AND";
		
		
		$args["tax_query"] = $newTaxQuery;
		
		return($args);
	}
	
	
	/**
	 * process request filters
	 */
	public function processRequestFilters($args, $isFilterable, $isMainQuery = false){
		
		$isUnderAjax = $this->isUnderAjax();
		
		if($isUnderAjax == false && $isFilterable == false)
			return($args);
		
		
		$arrFilters = $this->getRequestFilters();
		
		//---- set offset and count ----
		
		$page = UniteFunctionsUC::getVal($arrFilters, "page");
		$numItems = UniteFunctionsUC::getVal($arrFilters, "num_items");
		$offset = UniteFunctionsUC::getVal($arrFilters, "offset");
		
		if(!empty($page))
			$args = $this->processRequestFilters_setPaging($args, $page, $numItems);
		
		//set paging by offset
		if(!empty($offset)){
			
			$args["offset"] = $offset;
			
			if(!empty($numItems))
				$args["posts_per_page"] = $numItems;
		}
		
		$arrTerms = UniteFunctionsUC::getVal($arrFilters, "terms");
		if(!empty($arrTerms)){
			
			//combine the tax queries
			$arrTaxQuery = $this->getTaxQuery($arrTerms);
			
			if(!empty($arrTaxQuery))
				$args = $this->setArgsTaxQuery($args, $arrTaxQuery);
			
		}
		
		if(self::DEBUG_FILTER == true){
			dmp("debug!!!");
			dmp($args);
			dmp($arrFilters);
			exit();
		}
		
		
		//skip modify clauses in case of main query
		if($isMainQuery == false)
			return($args);
		
		$postType = UniteFunctionsUC::getVal($args, "post_type");
		
		//add additional filters in post clauses in some cases
		switch($postType){
			case "product":
				self::$lastArgs = $args;
				add_filter( 'posts_clauses', array( $this, 'modifyWCQuery' ), 10, 2 );
			break;
		}
				
		return($args);
	}

	/**
	 * before get posts
	 */
	public function modifyWCQuery($arrClauses){

		
		if(empty(self::$lastArgs))
			return($arrClauses);
			
		$args = self::$lastArgs;
		
		$postType = UniteFunctionsUC::getVal($args, "post_type");
		
		if($postType != "product")
			return($arrClauses);
		
		$orderBY = UniteFunctionsUC::getVal($args, "orderby");
		$dir = UniteFunctionsUC::getVal($args, "order", "DESC");
		
		if(empty($orderBY))
			return($arrClauses);
		
		//add code filter by orderby
		
		if(class_exists("WC_Query") == false)
			return($arrClauses);
		
		$objQuery = new WC_Query();
		
		switch($orderBY){
			case "price":
				if($dir == "DESC")
					$arrClauses = $objQuery->order_by_price_desc_post_clauses($arrClauses);
				else
					$arrClauses = $objQuery->order_by_price_asc_post_clauses($arrClauses);
			break;
			case 'popularity':
				$arrClauses = $objQuery->order_by_popularity_post_clauses($arrClauses);
			break;
			case 'rating':
				$arrClauses = $objQuery->order_by_rating_post_clauses($arrClauses);
			break;
		}
		
		remove_filter( 'posts_clauses', array( $this, 'modifyWCQuery' ), 10, 2 );
		
		return($arrClauses);
	}
	

	private function _______AJAX__________(){}
	
	/**
	 * get addon post list name
	 */
	private function getAddonPostListName($addon){
		
		$paramPostList = $addon->getParamByType(UniteCreatorDialogParam::PARAM_POSTS_LIST);
				
		$postListName = UniteFunctionsUC::getVal($paramPostList, "name");
		
		return($postListName);
	}
	
	
	/**
	 * validate if the addon ajax ready
	 * if it's have post list and has option that enable ajax
	 */
	private function validateAddonAjaxReady($addon, $arrSettingsValues){
		
		$paramPostList = $addon->getParamByType(UniteCreatorDialogParam::PARAM_POSTS_LIST);
		
		if(empty($paramPostList))
			UniteFunctionsUC::throwError("Widget not ready for ajax");
		
		$postListName = UniteFunctionsUC::getVal($paramPostList, "name");
		
		$isAjaxReady = UniteFunctionsUC::getVal($arrSettingsValues, $postListName."_isajax");
		$isAjaxReady = UniteFunctionsUC::strToBool($isAjaxReady);
		
		if($isAjaxReady == false)
			UniteFunctionsUC::throwError("The ajax is not ready for this widget");
			
		return($postListName);
	}
	
	
	/**
	 * process the html output - convert all the links, remove the query part
	 */
	private function processAjaxHtmlOutput($html){

		$currentUrl = GlobalsUC::$current_page_url;
		
		$arrUrl = parse_url($currentUrl);
		
		$query = "?".UniteFunctionsUC::getVal($arrUrl, "query");
				
		$html = str_replace($query, "", $html);
		
		$query = str_replace("&", "&#038;", $query);
		
		$html = str_replace($query, "", $html);

		return($html);
	}
	
	/**
	 * modify settings values before set to addon
	 * set pagination type to post list values
	 */
	private function modifySettingsValues($arrSettingsValues, $postListName){
		
		$paginationType = UniteFunctionsUC::getVal($arrSettingsValues, "pagination_type");
		
		if(!empty($paginationType))
			$arrSettingsValues[$postListName."_pagination_type"] = $paginationType;

		return($arrSettingsValues);			
	}
	
	/**
	 * get content element html
	 */
	private function getContentWidgetHtml($arrContent, $elementID, $isGrid = true){
		
		$arrElement = HelperProviderCoreUC_EL::getArrElementFromContent($arrContent, $elementID);
		
		if(empty($arrElement))
			UniteFunctionsUC::throwError("Elementor Widget not found");
		
		$type = UniteFunctionsUC::getVal($arrElement, "elType");
		
		if($type != "widget")
			UniteFunctionsUC::throwError("The element is not a widget");
		
		$widgetType = UniteFunctionsUC::getVal($arrElement, "widgetType");
		
		if(strpos($widgetType, "ucaddon_") === false)
			UniteFunctionsUC::throwError("Cannot output widget content");

		$arrSettingsValues = UniteFunctionsUC::getVal($arrElement, "settings");
		
		$widgetName = str_replace("ucaddon_", "", $widgetType);
				
		$addon = new UniteCreatorAddon();
		$addon->initByAlias($widgetName, GlobalsUC::ADDON_TYPE_ELEMENTOR);

		//make a check that ajax option is on in this widget
		
		if($isGrid == true){
			
			$postListName = $this->validateAddonAjaxReady($addon, $arrSettingsValues);
			
			$arrSettingsValues = $this->modifySettingsValues($arrSettingsValues, $postListName);
		}
		
		$addon->setParamsValues($arrSettingsValues);
		
		
		//------ get the html output
				
		//collect the debug html
		ob_start();
					
		$objOutput = new UniteCreatorOutput();
		$objOutput->initByAddon($addon);
		
		$htmlDebug = ob_get_contents();
		ob_end_clean();
		
		$output = array();
		
		//get only items
		if($isGrid == true){
			$htmlGridItems = $objOutput->getHtmlItems();
			$output["html"] = $htmlGridItems;
		}
		
		//get output of the html template
		if($isGrid == false){
			
			$htmlBody = $objOutput->getHtmlOnly();
			
			$htmlBody = $this->processAjaxHtmlOutput($htmlBody);
			
			$output["html"] = $htmlBody;
		}
		
		if(!empty($htmlDebug))
			$output["html_debug"] = $htmlDebug;
					
		return($output);
	}
	
	
	/**
	 * get content widgets html
	 */
	private function getContentWidgetsHTML($arrContent, $strIDs){
		
		if(empty($strIDs))
			return(null);
		
		$arrIDs = explode(",", $strIDs);
		
		$arrHTML = array();
		
		$this->contentWidgetsDebug = array();
		
		
		foreach($arrIDs as $elementID){
			
			$output = $this->getContentWidgetHtml($arrContent, $elementID, false);
			
			$htmlDebug = UniteFunctionsUC::getVal($output, "html_debug");
			
			$html = UniteFunctionsUC::getVal($output, "html");
						
			//collect the debug
			if(!empty($htmlDebug))
				$this->contentWidgetsDebug[$elementID] = $htmlDebug;
			
			$arrHTML[$elementID] = $html;
		}
		
		return($arrHTML);
	}

	/**
	 * get init filtres taxonomy request
	 */
	private function getInitFiltersTaxRequest($request, $strTestIDs){

		$posLimit = strpos($request, "LIMIT");
		
		if($posLimit){
			$request = substr($request, 0, $posLimit-1);
			$request = trim($request);
		}
		
		//remove the calc found rows
		
		$request = str_replace("SQL_CALC_FOUND_ROWS", "", $request);
		
		//wrap it in get term id's request 
		
		$prefix = UniteProviderFunctionsUC::$tablePrefix;
		
		$arrTermIDs = explode(",", $strTestIDs);
		
		if(empty($arrTermIDs))
			return(null);
			
		$selectTerms = "";
		$selectTop = "";
		
		$query = "SELECT \n";
		
		foreach($arrTermIDs as $termID){
			
			if(!empty($selectTerms)){
				$selectTerms .= ",\n";
				$selectTop .= ",\n";
			}
			
			$name = "term_$termID";
			
			$selectTerms .= "SUM(if(tt.`parent` = $termID OR tt.`term_id` = $termID, 1, 0)) AS $name";
			
			$selectTop .= "SUM(if($name > 0, 1, 0)) as $name";
			
		}
		
		$query .= $selectTerms;
		
		$sql = "
			FROM `{$prefix}posts` p
			LEFT JOIN `{$prefix}term_relationships` rl ON rl.`object_id` = p.`id`
			LEFT JOIN `{$prefix}term_taxonomy` tt ON tt.`term_taxonomy_id` = rl.`term_taxonomy_id`
			WHERE rl.`term_taxonomy_id` IS NOT NULL AND p.`id` IN \n
				({$request}) \n
			GROUP BY p.`id`";
		
		$query .= $sql;
		
		$fullQuery = "SELECT $selectTop from($query) as summary";
		
		return($fullQuery);
	}
	
	

	/**
	 * modify test term id's
	 */
	private function modifyFoundTermsIDs($arrFoundTermIDs){
		
		if(isset($arrFoundTermIDs[0]))
			$arrFoundTermIDs = $arrFoundTermIDs[0];
				
		$arrTermsAssoc = array();
		
		foreach($arrFoundTermIDs as $strID=>$count){

			$termID = str_replace("term_", "", $strID);
			
			$arrTermsAssoc[$termID] = $count;
		}
		
		return($arrTermsAssoc);
	}
	
	
	/**
	 * get widget ajax data
	 */
	private function putWidgetGridFrontAjaxData(){
				
		//validate by response code
		
		$responseCode = http_response_code();
		
		if($responseCode != 200){
			http_response_code(200);
			UniteFunctionsUC::throwError("Request not allowed, please make sure the pagination is allowed for the ajax grid");
		}
		
		//init widget by post id and element id
		
		$layoutID = UniteFunctionsUC::getPostGetVariable("layoutid","",UniteFunctionsUC::SANITIZE_KEY);
		$elementID = UniteFunctionsUC::getPostGetVariable("elid","",UniteFunctionsUC::SANITIZE_KEY);
		
		$addElIDs = UniteFunctionsUC::getPostGetVariable("addelids","",UniteFunctionsUC::SANITIZE_TEXT_FIELD);
		
		$isModeFiltersInit = UniteFunctionsUC::getPostGetVariable("modeinit","",UniteFunctionsUC::SANITIZE_TEXT_FIELD);
		$isModeFiltersInit = UniteFunctionsUC::strToBool($isModeFiltersInit);
		
		self::$isModeInit = $isModeFiltersInit;
		
		$testTermIDs = UniteFunctionsUC::getPostGetVariable("testtermids","",UniteFunctionsUC::SANITIZE_TEXT_FIELD);
		UniteFunctionsUC::validateIDsList($testTermIDs);

		
		//replace terms mode
		$isModeReplace = UniteFunctionsUC::getPostGetVariable("ucreplace","",UniteFunctionsUC::SANITIZE_TEXT_FIELD);
		$isModeReplace = UniteFunctionsUC::strToBool($isModeReplace);
		
		self::$isModeReplace = $isModeReplace;
				
		//if($isModeFiltersInit == true)
			//GlobalsProviderUC::$skipRunPostQueryOnce = true;
		
		$arrContent = HelperProviderCoreUC_EL::getElementorContentByPostID($layoutID);
		
		if(empty($arrContent))
			UniteFunctionsUC::throwError("Elementor content not found");
		
		//run the post query
		$arrHtmlWidget = $this->getContentWidgetHtml($arrContent, $elementID);
		
		self::$numTotalPosts = GlobalsProviderUC::$lastPostQuery->found_posts;
		
				
		//find the term id's for test (find or not in the current posts query)
		if(!empty($testTermIDs)){
			
			$args = GlobalsProviderUC::$lastQueryArgs;
			
			$query = new WP_Query($args);
						
			$request = $query->request;
			
			$taxRequest = $this->getInitFiltersTaxRequest($request, $testTermIDs);
						
			$db = HelperUC::getDB();
			$arrFoundTermIDs = $db->fetchSql($taxRequest);
						
			$arrFoundTermIDs = $this->modifyFoundTermsIDs($arrFoundTermIDs);
			
			//set the test term id's for the output
			GlobalsProviderUC::$arrTestTermIDs = $arrFoundTermIDs;			
		}
				
		$htmlGridItems = UniteFunctionsUC::getVal($arrHtmlWidget, "html");
		$htmlDebug = UniteFunctionsUC::getVal($arrHtmlWidget, "html_debug");
		
		$addWidgetsHTML = $this->getContentWidgetsHTML($arrContent, $addElIDs);
				
		//output the html
		$outputData = array();		
		
		if(!empty($htmlDebug))
			$outputData["html_debug"] = $htmlDebug;
			
		if($isModeFiltersInit == false)
			$outputData["html_items"] = $htmlGridItems;
		
		if(!empty($addWidgetsHTML))
			$outputData["html_widgets"] = $addWidgetsHTML;
		
		if(!empty($this->contentWidgetsDebug))
			$outputData["html_widgets_debug"] = $this->contentWidgetsDebug;
		
			
		HelperUC::ajaxResponseData($outputData);
		
	}
	
	
	private function _______WIDGET__________(){}
	
	
	/**
	 * include the filters js files
	 */
	private function includeJSFiles(){
		
		if(self::$isFilesAdded == true)
			return(false);
		
		$urlFiltersJS = GlobalsUC::$url_assets_libraries."filters/ue_filters.js";
		HelperUC::addScriptAbsoluteUrl($urlFiltersJS, "ue_filters");		
		
		
		self::$isFilesAdded = true;
	}
	
	/**
	 * put custom scripts
	 */
	private function putCustomJsScripts(){
		
		if(self::$isScriptAdded == true)
			return(false);
		
		self::$isScriptAdded = true;
		
		$arrData = $this->getFiltersJSData();
		
		$strData = UniteFunctionsUC::jsonEncodeForClientSide($arrData);
		
		$script = "//Unlimited Elements Filters \n";
		$script .= "window.g_strFiltersData = {$strData};";
		
		UniteProviderFunctionsUC::printCustomScript($script);
	}
	
	/**
	 * put custom style
	 */
	private function putCustomStyle(){
		
		if(self::$isStyleAdded == true)
			return(false);
		
		self::$isStyleAdded = true;
		
		$style = "
			.uc-ajax-loading{
				opacity:0.6;
			}
		";
		
		UniteProviderFunctionsUC::printCustomStyle($style);
	}
	
	
	/**
	 * include the client side scripts
	 */
	private function includeClientSideScripts(){
		
		$this->includeJSFiles();
		
		$this->putCustomJsScripts();
		
		$this->putCustomStyle();
		
	}
	
	
	/**
	 * get active archive terms
	 */
	private function getActiveArchiveTerms($taxonomy){
		
		if(is_archive() == false)
			return(null);

		$currentTerm = $this->getCurrentTerm();

		if(empty($currentTerm))
			return(null);
		
		if($currentTerm instanceof WP_Term == false)
			return(null);
		
		$termID = $currentTerm->term_id;
		
		$args = array();
		$args["taxonomy"] = $taxonomy;
		$args["parent"] = $termID;
		
		$arrTerms = get_terms($args);
		
		return($arrTerms);
	}
	
	
	/**
	 * put checkbox filters test
	 */
	public function putCheckboxFiltersTest($data){
				
		$arrActiveFilters = $this->getInputFiltersAssoc();
		
		$taxonomy = UniteFunctionsUC::getVal($data, "taxonomy", "category");
				
		//remove me
		$taxonomy = "product_cat";
				
		$terms = $this->getActiveArchiveTerms($taxonomy);
		
		if(empty($terms))
			return(null);
		
		$this->includeClientSideScripts();
		
		$html = $this->getHtml_termsCheckboxes($terms, $arrActiveFilters,$taxonomy);
		
		echo $html;
	}
	
		
	
	/**
	 * add widget variables
	 * uc_listing_addclass, uc_listing_attributes
	 */
	public function addWidgetFilterableVariables($data, $addon){
		
		$param = $addon->getParamByType(UniteCreatorDialogParam::PARAM_POSTS_LIST);
		
		$postListName = UniteFunctionsUC::getVal($param, "name");
		
		$dataPosts = UniteFunctionsUC::getVal($data, $postListName);
		
		//check if ajax related
		$isAjax = UniteFunctionsUC::getVal($dataPosts, $postListName."_isajax");
		$isAjax = UniteFunctionsUC::strToBool($isAjax);
				
		if($isAjax == false)
			return($data);
				
		if(empty($param))
			return($data);
		
		$filterBehavoiur = UniteFunctionsUC::getVal($dataPosts, $postListName."_ajax_seturl");
		
		//check if ajax
		$strAttributes = "";
		
		$strAttributes .= " data-ajax='true' ";
		
		if(!empty($filterBehavoiur))
			$strAttributes .= " data-filterbehave='$filterBehavoiur' ";
		
		$this->includeClientSideScripts();
		
		$data["uc_filtering_attributes"] = $strAttributes;
		$data["uc_filtering_addclass"] = " uc-filterable-grid";
		
		return($data);
	}

	
	/**
	 * get filters attributes
	 */
	private function getFiltersJSData(){
		
		$urlBase = UniteFunctionsUC::getBaseUrl(GlobalsUC::$current_page_url);
		
		//include some common url filters
		$orderby = UniteFunctionsUC::getGetVar("orderby","",UniteFunctionsUC::SANITIZE_TEXT_FIELD);

		if(!empty($orderby))
			$urlBase = UniteFunctionsUC::addUrlParams($urlBase, "orderby=$orderby");
					
		//get current filters
		
		$arrData = array();
		$arrData["urlbase"] = $urlBase;
		$arrData["urlajax"] = GlobalsUC::$url_ajax_full;
		$arrData["querybase"] = self::$originalQueryVars;
				
		
		return($arrData);
	}
	
	private function _____MODIFY_PARAMS_PROCESS_TERMS_______(){}
	
	
	/**
	 * check if term selected by request
	 */
	private function isTermSelectedByRequest($term, $selectedTerms){
		
		$taxonomy = UniteFunctionsUC::getVal($term, "taxonomy");
		
		if(empty($taxonomy))
			return(false);
			
		$arrSlugs = UniteFunctionsUC::getVal($selectedTerms, $taxonomy);
		
		if(empty($arrSlugs))
			return(false);

		$slug = UniteFunctionsUC::getVal($term, "slug");
		
		$found = in_array($slug, $arrSlugs);
			
		return($found);
	}
	
	
	/**
	 * modify selected by request
	 */
	private function modifyOutputTerms_modifySelectedByRequest($arrTerms){
		
		$selectedTerms = null;
		$selectedTermIDs = null;
		
		//if mode init - get selected id's from request
		if(self::$isModeInit == true){
			
			$strSelectedTermIDs = UniteFunctionsUC::getPostGetVariable("ucinitselectedterms","",UniteFunctionsUC::SANITIZE_TEXT_FIELD);
			
			if(empty($strSelectedTermIDs))
				return($arrTerms);
				
			UniteFunctionsUC::validateIDsList($strSelectedTermIDs,"selected terms");
			
			$selectedTermIDs = explode(",", $strSelectedTermIDs);
			
			if(empty($selectedTermIDs))
				return($arrTerms);
			
		}else{
			
			$arrRequest = $this->getRequestFilters();
			
			if(empty($arrRequest))
				return($arrTerms);
			
			$selectedTerms = UniteFunctionsUC::getVal($arrRequest, "terms");
			
			if(empty($selectedTerms))
				return($arrTerms);
			
		}
		
				
		$arrSelected = array();
				
		foreach($arrTerms as $index => $term){
			
			if(!empty($selectedTerms))
				$isSelected = $this->isTermSelectedByRequest($term, $selectedTerms);
			else{
								
				$termID = UniteFunctionsUC::getVal($term, "id");
				if(empty($termID))
					continue;
				
				$isSelected = in_array($termID, $selectedTermIDs);
			}
			
			if($isSelected == false)
				continue;
							
			$arrSelected["term_".$index] = true;
		}
		
		if(empty($arrSelected))
			return($arrTerms);
			
		//modify the selected
		
		foreach($arrTerms as $index => $term){
			
			$isSelected = UniteFunctionsUC::getVal($arrSelected, "term_".$index);
			
			if($isSelected == true){
				$term["iscurrent"] = true;
				$term["isselected"] = true;
			}else{
				
				$term["iscurrent"] = false;
				$term["isselected"] = false;
				$term["class_selected"] = "";
			}
			
			$arrTerms[$index] = $term;
		}
		
		return($arrTerms);
	}
	
	
	/**
	 * modify the selected and add first term if needed
	 */
	private function modifyOutputTerms_modifySelected($arrTerms, $data){
				
		$isSelectFirst = UniteFunctionsUC::getVal($data, "select_first");
		$isSelectFirst = UniteFunctionsUC::strToBool($isSelectFirst);
		
		$addFirst = UniteFunctionsUC::getVal($data, "add_first");
		$addFirst = UniteFunctionsUC::strToBool($addFirst);
		
		if($addFirst == true){
			
			$text = UniteFunctionsUC::getVal($data, "first_item_text", __("All","unlimited-elements-for-elementor"));
						
			$firstTerm = array();
			$firstTerm["index"] = 0;
			$firstTerm["name"] = $text;
			$firstTerm["slug"] = "";
			$firstTerm["link"] = "";
			$firstTerm["parent_id"] = "";
			$firstTerm["taxonomy"] = "";
			
			if($isSelectFirst == true){
				$firstTerm["iscurrent"] = true;
				$firstTerm["isselected"] = true;				
			}

			$firstTerm["addclass"] = " uc-item-all";
			
			if(!empty(self::$numTotalPosts))
				$firstTerm["num_posts"] = self::$numTotalPosts;
			
			array_unshift($arrTerms, $firstTerm);
		}
		
		
		//add first selected
		
		foreach($arrTerms as $index => $term){
			
			$term["index"] = $index;
			
			if($index == 0 && $isSelectFirst == true)
				$term["isselected"] = true;
			
			$arrTerms[$index] = $term;
		}
				
		
		return($arrTerms);
	}
	
	
	/**
	 * modify the terms for init after
	 */
	private function modifyOutputTerms_setNumPosts($arrTerms){
		
		if(GlobalsProviderUC::$arrTestTermIDs === null)
			return($arrTerms);
			
		$arrParentNumPosts = array();
		
		$arrPostNums = GlobalsProviderUC::$arrTestTermIDs;
			
		foreach($arrTerms as $key => $term){
			
			$termID = UniteFunctionsUC::getVal($term, "id");
			
			$termFound = array_key_exists($termID, $arrPostNums);
			
			$numPosts = 0;
			
			if($termFound){
				$numPosts = $arrPostNums[$termID];
			}
						
			//add parent id if exists
			$parentID = UniteFunctionsUC::getVal($term, "parent_id");
						
			//set the number of posts
			$term["num_posts"] = $numPosts;
			
			if(!empty($term["num_products"]))
				$term["num_products"] = $numPosts;
			
			$isHidden = !$termFound;
			
			if($numPosts == 0)
				$isHidden = true;
			
			$htmlAttributes = "";
				
			if($isHidden == true)
				$htmlAttributes = "style='display:none'";
			
			$term["hidden"] = $isHidden;
			$term["html_attributes"] = $htmlAttributes;
			
			$arrTerms[$key] = $term;			
		}
				
		return($arrTerms);
	}
	
	
	/**
	 * modify limit loaded items
	 */
	private function modifyOutputTerms_modifyLimitGrayed($arrTerms, $limitGrayedItems){
				
		if(empty($limitGrayedItems))
			return($arrTerms);
		
		$numTerms = count($arrTerms);
				
		if($numTerms < $limitGrayedItems)
			return($arrTerms);
					
		foreach($arrTerms as $index => $term){
			
			if($index < $limitGrayedItems)
				continue;
			
			$addClass = UniteFunctionsUC::getVal($term, "addclass");
			$addClass .= " uc-hide-loading-item";
			
			$term["addclass"] = $addClass;
			
			$arrTerms[$index] = $term;
		}
		
		
		return($arrTerms);		
	}
	
	/**
	 * set selected class by options
	 */
	private function modifyOutputTerms_setSelectedClass($arrTerms){
	
		foreach($arrTerms as $index => $term){
			
			$isSelected = UniteFunctionsUC::getVal($term, "isselected");
			$isSelected = UniteFunctionsUC::strToBool($isSelected);
			
			if($isSelected == false)
				continue;
			
			$class = UniteFunctionsUC::getVal($term, "addclass","");
			$class .= " uc-selected";
			
			$term["addclass"] = $class;
			
			$arrTerms[$index] = $term;
			
		}
				
		return($arrTerms);
	}
	
	/**
	 * get editor filter arguments
	 */
	public function addEditorFilterArguments($data){
		
		
		//add the filter related js and css includes
		$this->includeClientSideScripts();
		
		$isInitAfter = UniteFunctionsUC::getVal($data, "init_after");
		$isInitAfter = UniteFunctionsUC::strToBool($isInitAfter);
		
		$isReplaceTerms = UniteFunctionsUC::getVal($data, "replace_terms");
		$isReplaceTerms = UniteFunctionsUC::strToBool($isReplaceTerms);
		
		$limitGrayedItems = UniteFunctionsUC::getVal($data, "load_limit_grayed");
		$limitGrayedItems = (int)$limitGrayedItems;
		
		$filterRole = UniteFunctionsUC::getVal($data, "filter_role");
		if($filterRole == "single")		
			$filterRole = null;
			
		$attributes = "";
		$style = "";
		$addClass = " uc-grid-filter";
		$addClassItem = "";
		$isFirstLoad = true;		//not in ajax, or with init after (also first load)
		
		$isInsideEditor = UniteCreatorElementorIntegrate::$isEditMode;
		
		$isUnderAjax = $this->isUnderAjax();
		
		if($isUnderAjax == true)
			$isFirstLoad = false;
				
		if($isInitAfter == true){
			
			$attributes = " data-initafter=\"true\"";
			
			if($isUnderAjax == false && $isInsideEditor == false){
				$addClassItem = " uc-filter-item-hidden";
				$addClass .= " uc-filter-initing";
			}
			
			$isFirstLoad = true;
		}
		
		//hide child filter at start
		if($filterRole == "child" && $isUnderAjax == false && $isInsideEditor == false){
			$addClass .= " uc-filter-initing uc-initing-filter-hidden";
		}
		
		if($isInsideEditor == true)
			$isFirstLoad = true;
		
		//main filter
			
		if(!empty($filterRole))
			$attributes .= " data-role=\"{$filterRole}\"";
		
		if($isReplaceTerms == true)
			$attributes .= " data-replace-mode=\"true\"";
		
		$data["filter_isajax"] = $isUnderAjax?"yes":"no";
		$data["filter_attributes"] = $attributes;
		$data["filter_style"] = $style;
		$data["filter_addclass"] = $addClass;
		$data["filter_addclass_item"] = $addClassItem;
		$data["filter_first_load"] = $isFirstLoad?"yes":"no";
		
		//modify terms
		
		$arrTerms = UniteFunctionsUC::getVal($data, "taxonomy");
		
		$arrTerms = $this->modifyOutputTerms_setNumPosts($arrTerms, $isInitAfter, $isFirstLoad);
		
		//modify the selected class
		$arrTerms = $this->modifyOutputTerms_modifySelected($arrTerms, $data);
		
		//on init mode - modify selected and grayed loader
		if($isUnderAjax == false || self::$isModeInit == true){
			
			$arrTerms = $this->modifyOutputTerms_modifySelectedByRequest($arrTerms);
		}
		
		if($isInitAfter == true && !empty($limitGrayedItems) && $isUnderAjax == false)
			$arrTerms = $this->modifyOutputTerms_modifyLimitGrayed($arrTerms, $limitGrayedItems);
		
		$arrTerms = $this->modifyOutputTerms_setSelectedClass($arrTerms);
		
		
		$data["taxonomy"] = $arrTerms;
		
		return($data);
	}
	
	
	
	
	private function _______ARCHIVE_QUERY__________(){}
	
	
	/**
	 * modify post query
	 */
	public function checkModifyMainQuery($query){
		
		if(is_single())
			return(false);
		
		self::$originalQueryVars = $query->query_vars;
		
		$query->query_vars = $this->processRequestFilters($query->query_vars, true, true);
		
		
		return($query);
	}
	
	
	/**
	 * show the main query debug
	 */
	private function showMainQueryDebug(){
		
		global $wp_query;
		
		$args = $wp_query->query_vars;
				
		$argsForDebug = UniteFunctionsWPUC::cleanQueryArgsForDebug($args);
		
		dmp("MAIN QUERY DEBUG");
		
		dmp($argsForDebug);
		
	}
	
	/**
	 * is ajax request
	 */
	public function isFrontAjaxRequest(){
		
		if(self::$isAjaxCache !== null)
			return(self::$isAjaxCache);
		
		$frontAjaxAction = UniteFunctionsUC::getPostGetVariable("ucfrontajaxaction","",UniteFunctionsUC::SANITIZE_KEY);
		
		if($frontAjaxAction == "getfiltersdata"){
			self::$isAjaxCache = true;
			return(true);
		}
		
		self::$isAjaxCache = false;
		
		return(false);
	}
	
	/**
	 * test the request filter
	 */
	public function operateAjaxResponse(){
		
		if(self::DEBUG_MAIN_QUERY == true){
			$this->showMainQueryDebug();
			exit();
		}
		
		$frontAjaxAction = UniteFunctionsUC::getPostGetVariable("ucfrontajaxaction","",UniteFunctionsUC::SANITIZE_KEY);
		
		if(empty($frontAjaxAction))
			return(false);
			
		try{
			
			switch($frontAjaxAction){
				case "getfiltersdata":
					$this->putWidgetGridFrontAjaxData();
				break;
			}
		
		}catch(Exception $e){
			
			$message = $e->getMessage();
			
			HelperUC::ajaxResponseError($message);
			
		}
		
	}
	
	
	/**
	 * init wordpress front filters
	 */
	public function initWPFrontFilters(){
				
		if(is_admin() == true)
			return(false);
					
		add_action("wp", array($this, "operateAjaxResponse"));
		
		add_action("parse_request", array($this, "checkModifyMainQuery"));
		
	}
	
	
}