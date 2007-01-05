<?php
/**
 * Class for building the Page for editing the MainCfg
 *
 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
 */
class WuiEditMainCfg extends GlobalPage {
	var $MAINCFG;
	var $LANG;
	var $FORM;
	
	/**
	 * Class Constructor
	 *
	 * @param 	$MAINCFG GlobalMainCfg
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function WuiEditMainCfg(&$MAINCFG) {
		$this->MAINCFG = &$MAINCFG;
		
		# we load the language file
		$this->LANG = new GlobalLanguage($MAINCFG,'wui:editMainCfg');
		
		$prop = Array('title'=>$MAINCFG->getValue('internal', 'title'),
					  'cssIncludes'=>Array('./includes/css/wui.css'),
					  'jsIncludes'=>Array(''),
					  'extHeader'=>Array(''),
					  'allowedUsers' => Array('EVERYONE'),
					  'languageRoot' => 'wui:editMainCfg');
		parent::GlobalPage($MAINCFG,$prop);
	}
	
	/**
	 * If enabled, the form is added to the page
	 *
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getForm() {
		$this->FORM = new GlobalForm(Array('name'=>'edit_config',
									'id'=>'edit_config',
									'method'=>'POST',
									'action'=>'./wui.function.inc.php?myaction=update_config',
									'onSubmit'=>'return update_param();',
									'cols'=>'3'));
		$this->addBodyLines($this->FORM->initForm());
		$this->addBodyLines($this->FORM->getHiddenField('properties',''));
		
		$this->addBodyLines($this->getFields());
		$this->addBodyLines($this->getSubmit());
		$this->addBodyLines($this->getHidden());
	}
	
	/**
	 * Parses the Form fields
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getFields() {
		$ret = Array();
		
		foreach($this->MAINCFG->validConfig AS $cat => $arr) {
			// don't display backend options
			if(!preg_match("/^backend/i",$cat) && !preg_match("/^internal$/i",$cat)) {
				$ret = array_merge($ret,$this->FORM->getCatLine($cat));
				
				foreach($arr AS $key2 => $prop) {
					// ignore some vars
					if(isset($this->MAINCFG->validConfig[$cat][$key2]['editable']) && $this->MAINCFG->validConfig[$cat][$key2]['editable']) {
						$val2 = $this->MAINCFG->getValue($cat,$key2,TRUE);
						
						# we add a line in the form
						$ret[] = "<tr>";
						$ret[] = "\t<td class=\"tdlabel\">".$key2."</td>";
						if(preg_match('/^TranslationNotFound:/',$this->LANG->getLabel($key2,'',FALSE)) > 0) {
							$ret[] = "\t<td class=\"tdfield\"></td>";
						} else {
							$ret[] = "\t<td class=\"tdfield\">";
							$ret[] = "\t\t<img style=\"cursor:help\" src=\"./images/internal/help_icon.png\" onclick=\"javascript:alert('".$this->LANG->getLabel($key2,'',FALSE)."')\" />";
							$ret[] = "\t</td>";
						}
						
						$ret[] = "\t<td class=\"tdfield\">";
						switch($key2) {
							case 'language':
							case 'backend':
							case 'icons':
							case 'rotatemaps':
							case 'displayheader':
							case 'checkconfig':
							case 'usegdlibs':
							case 'debug':
							case 'debugstates':
							case 'debugcheckstate':
							case 'debugfixicon':
							case 'autoupdatefreq':
								switch($key2) {
									case 'language':
										$arrOpts = $this->getLanguages();
									break;
									case 'backend':
										$arrOpts = $this->getBackends();
									break;
									case 'icons':
										$arrOpts = $this->getIconsets();
									break;
									case 'rotatemaps':
									case 'displayheader':
									case 'checkconfig':
									case 'usegdlibs':
									case 'debug':
									case 'debugstates':
									case 'debugcheckstate':
									case 'debugfixicon':
										$arrOpts = Array(Array('value'=>'1','label'=>$this->LANG->getLabel('yes')),
														 Array('value'=>'0','label'=>$this->LANG->getLabel('no')));
									break;
									case 'autoupdatefreq':
										$arrOpts = Array(Array('value'=>'0','label'=>$this->LANG->getLabel('disabled')),
														 Array('value'=>'2','label'=>'2'),
														 Array('value'=>'5','label'=>'5'),
														 Array('value'=>'10','label'=>'10'),
														 Array('value'=>'25','label'=>'25'),
														 Array('value'=>'50','label'=>'50'));
									break;
								}
								
								$ret = array_merge($ret,$this->FORM->getSelectField("conf_".$key2,$arrOpts));
							break;
							default:
								$ret = array_merge($ret,$this->FORM->getInputField("conf_".$key2,$val2));
								
								if(isset($prop['locked']) && $prop['locked'] == 1) {
									$ret[] = "<script>document.edit_config.elements['conf_".$key2."'].disabled=true;</script>";
								}
							break;
						}
						$ret[] = "\t\t<script>document.edit_config.elements['conf_".$key2."'].value='".$val2."';</script>";
						$ret[] = "\t</td>";
						$ret[] = "</tr>";
					}
				}
			}
		}
		return $ret;
	}
	
	/**
	 * Reads all aviable backends
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 * FIXME: DEPRECATED
	 */
	function getBackends() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'class'))) {
 			while (false !== ($file = readdir($handle))) {
 				if ($file != "." && $file != ".." && preg_match('/^class.GlobalBackend-/', $file)) {
					$files[] = str_replace('class.GlobalBackend-','',str_replace('.php','',$file));
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all iconsets (that habe <iconset>_ok.png)
	 *
	 * @return	Array Html
	 * @author 	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getIconsets() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'icon'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/_ok.png$/', $file)) {
					$files[] = str_replace('_ok.png','',$file);
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Reads all languages
	 *
	 * @return	Array Html
	 * @author	Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getLanguages() {
		$files = Array();
		
		if ($handle = opendir($this->MAINCFG->getValue('paths', 'language'))) {
 			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && preg_match('/.xml$/', $file)) {
					$files[] = str_replace('wui_','',str_replace('.xml','',$file));
				}				
			}
			
			if ($files) {
				natcasesort($files);
			}
		}
		closedir($handle);
		
		return $files;
	}
	
	/**
	 * Gets the submit button
	 *
	 * @return	Array Html
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getSubmit() {
		return array_merge($this->FORM->getSubmitLine($this->LANG->getLabel('check')),$this->FORM->closeForm());
	}
	
	/**
	 * Gets the hidden form
	 *
	 * @return	Array Html
	 * @author Lars Michelsen <larsi@nagios-wiki.de>
	 */
	function getHidden() {
		$ret = Array();
		$ret[] = "<script type=\"text/javascript\" language=\"JavaScript\"><!--";
		$ret[] = "// function that builds up the list of parameters/values. There are 2 kinds of parameters values :";
		$ret[] = "//	- the \"normal value\". example : \$param=\"value\";";
		$ret[] = "//	- the other one (value computed with other ones) . example : \$param=\"part1\".\$otherparam;";
		$ret[] = "function update_param() {";
		$ret[] = "	document.edit_config.properties.value='';";
		$ret[] = "	for(i=0;i<document.edit_config.elements.length;i++) {";
		$ret[] = "		if(document.edit_config.elements[i].name.substring(0,5)=='conf_') {";
		$ret[] = "			document.edit_config.properties.value=document.edit_config.properties.value+'^'+document.edit_config.elements[i].name.substring(5,document.edit_config.elements[i].name.length)+'='+document.edit_config.elements[i].value;";
		$ret[] = "		}";
		$ret[] = "	}";
		$ret[] = "	document.edit_config.properties.value=document.edit_config.properties.value.substring(1,document.edit_config.properties.value.length);";
		$ret[] = "	return true;";
		$ret[] = "}";
		$ret[] = "//--></script>";
		
		return $ret;	
	}
}
?>