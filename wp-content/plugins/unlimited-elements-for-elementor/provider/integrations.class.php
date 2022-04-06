<?php

/**
 * @package Unlimited Elements
 * @author UniteCMS http://unitecms.net
 * @copyright Copyright (c) 2016 UniteCMS
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/

//no direct accees
defined ('UNLIMITED_ELEMENTS_INC') or die ('restricted aceess');

class UniteCreatorPluginIntegrations{
	
/* wp popular posts */
	
	/**
	 * return if exists wp popular posts
	 */
	public static function isWPPopularPostsExists(){
		
		$isExists = defined("WPP_VERSION") && defined("WPP_MIN_PHP_VERSION"); 
		
		return($isExists);
	}
	
	/**
	 * get popular posts
	 * args - post_type, cat, limit, range
	 */
	public function WPP_getPopularPosts($args, $addDebug = false){
		
		$isExists = self::isWPPopularPostsExists();
		
		if($isExists == false)
			return(false);
		
		$postType = UniteFunctionsUC::getVal($args, "post_type");
		
		if(is_array($postType))
			$postType = $postType[0];
		
		if(empty($postType))
			$postType = "post";
		
		$limit = UniteFunctionsUC::getVal($args, "limit", 5);
		$range = UniteFunctionsUC::getVal($args, "range", "last7days");
		$cat = UniteFunctionsUC::getVal($args, "cat", "");
		
		if(is_array($cat))
			$cat = $cat[0];
		
		$params = array();
		$params["post_type"] = $postType;
		$params["limit"] = $limit;
		$params["range"] = $range;
		
		if(!empty($cat))
			$params["cat"] = $cat;
		
		
		$query = new \WordPressPopularPosts\Query($params);
		
		$arrPosts = $query->get_posts();
		
		if(empty($arrPosts))
			$arrPosts = array();
		
		$arrPosts = UniteFunctionsUC::convertStdClassToArray($arrPosts);
		
		$strDebug = "";
		$arrPostIDs = array();
		
		if($addDebug == true){
		
			$strDebug .= "Popular posts query arguments:";
			$strDebug .= "<pre>";
			$strDebug .= print_r($params, true);
			$strDebug .= "</pre>";
	
			$numPosts = count($arrPosts);
			if(!empty($numPosts))
				$strDebug .= "Found $numPosts posts: <br>";
		}
		
		foreach($arrPosts as $index => $post){
			
			$num = $index+1;
			
			$id = UniteFunctionsUC::getVal($post, "id");
			$title = UniteFunctionsUC::getVal($post, "title");
			$pageviews = UniteFunctionsUC::getVal($post, "pageviews");
			
			if($addDebug == true)
				$strDebug .= "{$num}. $title ($id): $pageviews views <br>";
			
			$arrPostIDs[] = $id;
		}
		
		if(empty($arrPosts) && $addDebug == true)
			$strDebug .= "No popular posts found <br>";
		
		//empty the selection if not found
		if(empty($arrPostIDs))
			$arrPostIDs = array("0");
		
		$output = array();
		$output["post_ids"] = $arrPostIDs;
		$output["debug"] = $strDebug;
		
		return($output);
		
		
        // Return cached results
        /*
        if ( $this->config['tools']['cache']['active'] ) {
            $key = 'wpp_' . md5(json_encode($params));
            $query = \WordPressPopularPosts\Cache::get($key);

            if ( false === $query ) {
                $query = new Query($params);

                $time_value = $this->config['tools']['cache']['interval']['value'];
                $time_unit = $this->config['tools']['cache']['interval']['time'];

                // No popular posts found, check again in 1 minute
                if ( ! $query->get_posts() ) {
                    $time_value = 1;
                    $time_unit = 'minute';
                }

                \WordPressPopularPosts\Cache::set(
                    $key,
                    $query,
                    $time_value,
                    $time_unit
                );
            }
        } // Get real-time popular posts
        
		*/
		
        return $query;
	}
	
}