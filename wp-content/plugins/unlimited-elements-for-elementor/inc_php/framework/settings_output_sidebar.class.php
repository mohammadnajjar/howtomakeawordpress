<?php
/**
 * @package Unlimited Elements
 * @author unlimited-elements.com
 * @copyright (C) 2021 Unlimited Elements, All Rights Reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * */
defined('UNLIMITED_ELEMENTS_INC') or die('Restricted access');


class UniteSettingsOutputSidebarUC extends UniteCreatorSettingsOutput{
    
    private $isAccordion = true;
    private $accordionItemsSpaceBetween = 0;	//space between accoridion items
    private $accordionTitleHeight = 30;
    private $showTips = true;
    private $showSapTitle = true;
    
    
    /**
     * constuct function
     */
    public function __construct(){
        $this->isParent = true;
        self::$serial++;
        
        $this->wrapperID = "unite_settings_sidebar_output_".self::$serial;
        $this->settingsMainClass = "unite-settings-sidebar";
       
        $this->showDescAsTips = true;
        $this->setShowSaps(true, self::SAPS_TYPE_ACCORDION);
        
        $this->isSidebar = true;
        
    }
    
    
		
		/**
		 * draw wrapper end after settings
		 */
		protected function drawSettingsAfter(){
		
			?></ul><?php
		}
     
		/**
		 * get options override
		 * add accordion space
		 */
		protected function getOptions(){
			
			$arrOptions = parent::getOptions();
			$arrOptions["accordion_sap"] = $this->accordionItemsSpaceBetween;
			$arrOptions["accordion_title_height"] = $this->accordionTitleHeight;
			
			return($arrOptions);
		}
		
		/**
		 * set draw options before draw
		 */
		protected function setDrawOptions(){
			
			$numSaps = $this->settings->getNumSaps();
			if($numSaps <= 1)
				$this->showSapTitle = false;
			
		}
		
		
		/**
		 * draw before settings row
		 */
		protected function drawSettings_before(){
						
		  ?>
		  	  <ul class="unite-list-settings">
		  <?php 
		}
		
		
		/**
		 * draw settings row
		 * @param $setting
		 */
		protected function drawSettingRow($setting, $mode=""){
		    
		    //set cellstyle:
		    $cellStyle = "";
		    if(isset($setting[UniteSettingsUC::PARAM_CELLSTYLE])){
		        $cellStyle .= $setting[UniteSettingsUC::PARAM_CELLSTYLE];
		    }
		    
		    if($cellStyle != "")
		        $cellStyle = "style='".$cellStyle."'";
		        
		        $textStyle = $this->drawSettingRow_getTextStyle($setting);
		        		        
		        $rowClass = $this->drawSettingRow_getRowClass($setting, "unite-setting-row");
		        
		        $text = $this->drawSettingRow_getText($setting);
		        
		        $description = UniteFunctionsUC::getVal($setting,"description");
		        $description = htmlspecialchars($description);
		        
		        $addField = UniteFunctionsUC::getVal($setting, UniteSettingsUC::PARAM_ADDFIELD);
		        
		        $toDrawText = true;
		        
		        $attribsText = UniteFunctionsUC::getVal($setting, "attrib_text");
		        if(empty($attribsText) && empty($text))
		        	$toDrawText = false;
		        		        
		        $settingID = $setting["id"];
		        
		        $textClassAdd = "";
		        if($this->showTips == true)
		            $textClassAdd = " uc-tip";
		           
		        
		        ?>
				<li id="<?php echo esc_attr($settingID)?>_row" <?php echo UniteProviderFunctionsUC::escAddParam($rowClass)?>>
					
					<?php if($toDrawText == true):?>
						<div id="<?php echo esc_attr($settingID)?>_text" class='unite-setting-text<?php echo esc_attr($textClassAdd)?>' title="<?php echo esc_attr($description)?>" <?php echo UniteProviderFunctionsUC::escAddParam($attribsText)?>><?php echo esc_html($text) ?></div>
					<?php endif?>
					
					<?php if(!empty($addHtmlBefore)):?>
						<div class="unite-setting-addhtmlbefore"><?php echo UniteProviderFunctionsUC::escAddParam($addHtmlBefore)?></div>
					<?php endif?>
					
					<div class='unite-setting-input'>
						<?php 
							$this->drawInputs($setting);
							$this->drawInputAdditions($setting);						
						?>
					</div>
					<div class="unite-clear"></div>
				</li>
						
			<?php
		}
		
		
		/**
		 * draw text row
		 * @param unknown_type $setting
		 */
		protected function drawTextRow($setting){
		    
		    //set cell style
		    $cellStyle = "";
		    if(isset($setting["padding"]))
		        $cellStyle .= "padding-left:".$setting["padding"].";";
		        
	        if(!empty($cellStyle))
	            $cellStyle="style='$cellStyle'";
		            
            //set style
            $label = UniteFunctionsUC::getVal($setting, "label");
            
            $rowClass = "";
            
            if(!empty($label))
             $rowClass = $this->drawSettingRow_getRowClass($setting);
                             
             $classAdd = UniteFunctionsUC::getVal($setting, UniteSettingsUC::PARAM_CLASSADD);
				
             if(!empty($classHidden))
				$classAdd .= $classHidden;
			
             if(!empty($classAdd))
                 $classAdd = " ".$classAdd;
                
                    $settingID = $setting["id"];
		                    ?>
		                    
    			  	<li id="<?php echo esc_attr($settingID)?>_row" <?php echo UniteProviderFunctionsUC::escAddParam($rowClass)?>>
    					
    					<?php if(!empty($label)):?>
    					<span class="unite-settings-text-label">
    						<?php echo esc_html($label)?>
    					</span>
    					<?php endif?>					
    	
    					<span class="unite-settings-static-text<?php echo esc_attr($classAdd)?>"><?php echo esc_html($setting["text"])?></span>
    					
    				</li>		                    
		                    
			<?php 
		}
		
		
		/**
		 * draw sap before override
		 * @param  $sap
		 */
		protected function drawSapBefore($sap, $key){
		   	
		    //set class
		    $class = "unite-postbox";
		    if(!empty($this->addClass))
		        $class .= " ".$this->addClass;
		    
		        //set accordion closed
		        $style = "";
		        if($this->isAccordion == false){
		            $h3Class = " unite-no-accordion";
		        }else{
		            $h3Class = "";
		            if($key>0){
		                $style = "style='display:none;'";
		                $h3Class = " unite-closed";
		            }
		        }
		        
		        //set text and icon class
		        $text = UniteFunctionsUC::getVal($sap, "text");
		        $classIcon = UniteFunctionsUC::getVal($sap, "icon");
		        $text = esc_html__($text,"unlimited-elements-for-elementor");
		    	
		        $classIcon = null;	//disable icons for now
		    	
		        //postbox style
		        $addStyle = "";
		        
		        if($key > 0)
		        	$addStyle .= "margin-top:".$this->accordionItemsSpaceBetween."px";
		        
		        
		        if(!empty($addStyle)){
		        	$addStyle = esc_attr($addStyle);
		        	$addStyle = " style='$addStyle'";
		        }
		        
		        //title style
		        $styleTitle = "";
		        $styleTitle .= "height:".$this->accordionTitleHeight."px;";
		        
		        if(!empty($styleTitle)){
		        	$styleTitle = esc_attr($styleTitle);
		        	$styleTitle = " style='$styleTitle'";
		        }
		        
		        ?>
					<div class="<?php echo esc_attr($class)?>" <?php echo UniteProviderFunctionsUC::escAddParam($addStyle)?>>
						
						<?php if($this->showSapTitle == true): ?>
						
							<div class="unite-postbox-title<?php echo esc_attr($h3Class)?>" <?php echo UniteProviderFunctionsUC::escAddParam($styleTitle)?> >
							
							<?php if(!empty($classIcon)):?>
							<i class="unite-postbox-icon <?php echo esc_attr($classIcon)?>"></i>
							<?php endif?>
							
							<?php if($this->isAccordion == true):?>
							    <div class="unite-postbox-arrow-wrapper">
									<div class="unite-postbox-arrow"></div>
								</div>
							<?php endif?>
							
								<span><?php echo esc_html($text) ?></span>
							</div>			
						<?php endif?>
						
						<div class="unite-postbox-inside" <?php echo UniteProviderFunctionsUC::escAddParam($style)?> > 
			<?php
			
		}
		
		
		/**
		 * draw sap after
		 */
		protected function drawSapAfter(){
		    ?>
						
							<div class="unite-clear"></div>
						</div>
					</div>
		        <?php 
		    
		}
		
		
		/**
		 * draw hr row
		 */
		protected function drawHrRow($setting){
			  
             $rowClass = $this->drawSettingRow_getRowClass($setting);
                                
             $settingID = $setting["id"];
				
			?>
    			  	<li id="<?php echo esc_attr($settingID)?>_row" <?php echo UniteProviderFunctionsUC::escAddParam($rowClass)?>>
    					
    					<hr id="<?php echo esc_attr($settingID)?>">
    					
    				</li>		                    
			<?php 
		}
		
	
	}
?>