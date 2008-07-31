<?php
/*****************************************************************************
 *
 * GlobalMap.php - Class for parsing the NagVis maps
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class GlobalMap {
	var $CORE;
	var $MAINCFG;
	var $MAPCFG;
	var $LANG;
	
	var $linkedMaps = Array();
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @param 	GlobalMapCfg 	$MAPCFG
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function GlobalMap(&$CORE,&$MAPCFG) {
		$this->CORE = &$CORE;
		$this->MAINCFG = &$this->CORE->MAINCFG;
		$this->LANG = &$this->CORE->LANG;
		$this->MAPCFG = &$MAPCFG;
	}
	
	/**
	 * Check if GD-Libs installed, when GD-Libs are enabled
	 *
	 * @param	Boolean $printErr
	 * @return	Boolean	Is Successful?
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkGd($printErr) {
		if($this->MAPCFG->getValue('global', 0, 'usegdlibs') == '1') {
			if(!extension_loaded('gd')) {
				if($printErr) {
					$FRONTEND = new GlobalPage($this->CORE);
					$FRONTEND->messageToUser('WARNING', $this->CORE->LANG->getText('gdLibNotFound'));
				}
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			return TRUE;
		}
	}
	
	/**
	 * Gets the background html code of the map
	 *
	 * @param	String	$src	html path
	 * @param	String	$style  css parameters
	 * @return	String HTML Code
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getBackgroundHtml($src, $style='', $attr='') {
		return '<img id="background" src="'.$src.'" style="z-index:0;'.$style.'" alt="" '.$attr.'>';
	}
}
?>
