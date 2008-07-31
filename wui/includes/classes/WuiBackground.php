<?php
/*****************************************************************************
 *
 * WuiBackground.php - Class for background image handling in WUI
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
class WuiBackground extends GlobalBackground {
	function WuiBackground(&$CORE, $image) {
		parent::GlobalBackground($CORE, $image);
	}
	
	/**
	* Deletes the map image
	*
	* @param	Boolean	$printErr
	* @return	Boolean	Is Check Successful?
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function deleteImage($printErr=1) {
		if($this->checkFolderWriteable($printErr) && $this->checkFileWriteable($printErr)) {
			if(unlink($this->MAINCFG->getValue('paths', 'map').$this->image)) {
				return TRUE;
			} else {
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('couldNotDeleteMapImage','IMGPATH~'.$this->MAINCFG->getValue('paths', 'map').$this->image));
				return FALSE;
			}
		}
	}
	
	/**
	* Uploads a map image
	*
	* @param	Boolean	$printErr
	* @return	Boolean	Is Check Successful?
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function uploadImage($arr, $printErr=1) {
		if(is_uploaded_file($arr['tmp_name'])) {
			$fileName = $arr['name'];
			if(preg_match('/\.png/i',$fileName)) {
				if($this->checkFolderWriteable(1)) {
					if(move_uploaded_file($arr['tmp_name'], $this->MAINCFG->getValue('paths', 'map').$fileName)) {
						// Change permissions of the map image
						chmod($this->MAINCFG->getValue('paths', 'map').$fileName,0666);
						
						return TRUE;
					} else {
						// Error handling
						$FRONTEND = new GlobalPage($this->CORE);
						$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('moveUploadedFileFailed'));
						return FALSE;
					}
				} else {
					// No need for error handling here
					return FALSE;
				}
			} else {
				// Error handling
				$FRONTEND = new GlobalPage($this->CORE);
				$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('mustBePngFile'));
				return FALSE;
			}
		} else {
			// Error handling
			$FRONTEND = new GlobalPage($this->CORE);
			$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('fileCouldNotBeUploaded'));
			return FALSE;
		}
	}
	
	/**
	* Creates a simple map image
	*
	* @param	Boolean	$printErr
	* @return	Boolean	Is Check Successful?
	* @author	Lars Michelsen <lars@vertical-visions.de>
	*/
	function createImage($color, $width, $height) {
		if(!$this->checkFileExists(0)) {
			if($this->checkFolderWriteable(1)) {
				$image = imagecreatetruecolor($width, $height);
				
				// get rgb color from hexcode
				$color = str_replace('#','',$color);
				$int = hexdec($color);
				$r = 0xFF & ($int >> 0x10);
				$g = 0xFF & ($int >> 0x8);
				$b = 0xFF & $int;
				
				$bgColor = imagecolorallocate($image, $r, $g, $b);
				imagefill($image, 0, 0, $bgColor);
				imagepng($image,$this->MAINCFG->getValue('paths', 'map').$this->image);
				imagedestroy($image);
				
				return TRUE;
			} else {
				// No need for error handling here
				return FALSE;
			}
		} else {
			// Error handling
			$FRONTEND = new GlobalPage($this->CORE);
			$FRONTEND->messageToUser('ERROR', $this->CORE->LANG->getText('imageAlreadyExists','IMAGE~'.$this->MAINCFG->getValue('paths', 'map').$this->image));
			return FALSE;
		}
	}
}
?>