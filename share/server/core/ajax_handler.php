<?PHP
/*****************************************************************************
 *
 * ajax_handler.php - Ajax handler for the NagVis frontend
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

// Include global defines
require('../../server/core/defines/global.php');
require('../../server/core/defines/matches.php');

// Include functions
require('../../server/core/functions/autoload.php');
require('../../server/core/functions/debug.php');
require("../../server/core/functions/getuser.php");
require('../../server/core/functions/oldPhpVersionFixes.php');
require('../../server/core/functions/ajaxErrorHandler.php');

// This defines whether the GlobalMessage prints HTML or ajax error messages
define('CONST_AJAX' , TRUE);

// Load the core
$CORE = GlobalCore::getInstance();

/*
 * Url: Parse the url to know later what module and
 *      action is called. The requested uri is splitted
 *      into elements for later usage.
 */

$UHANDLER = new CoreUriHandler($CORE);

/*
 * Session: Handle the user session
 */

$SHANDLER = new CoreSessionHandler($CORE->getMainCfg()->getValue('global', 'sesscookiedomain'), 
		                               $CORE->getMainCfg()->getValue('global', 'sesscookiepath'),
		                               $CORE->getMainCfg()->getValue('global', 'sesscookieduration'));

/*
 * Authentication 1: First try to use an existing session
 *                   If that fails use the configured login method
 */

$AUTH = new CoreAuthHandler($CORE, $SHANDLER, 'CoreAuthModSession');

/*
 * Authorisation 1: Collect and save the permissions when the user is logged in
 *                  and nothing other is saved yet
 */

if($AUTH->isAuthenticated()) {
	// First try to get information from session
	$AUTHORISATION = new CoreAuthorisationHandler($CORE, $AUTH, 'CoreAuthorisationModSession');
	$aPerms = $AUTHORISATION->parsePermissions();

	// When no information in session get permission and write to session
	if($aPerms === false) {
		$AUTHORISATION = new CoreAuthorisationHandler($CORE, $AUTH, $CORE->getMainCfg()->getValue('global', 'authorisationmodule'));

		// Save credentials to seession
 		$SHANDLER->set('userPermissions', $AUTHORISATION->parsePermissions());
	}
} else {
	$AUTHORISATION = null;
}

/*
 * Module handling 1: Choose modules
 */

// Load the module handler
$MHANDLER = new CoreModuleHandler($CORE);

// Register valid modules
// Unregistered modules can not be accessed
$MHANDLER->regModule('General');
$MHANDLER->regModule('Overview');
$MHANDLER->regModule('Map');
$MHANDLER->regModule('AutoMap');
$MHANDLER->regModule('Url');

// Load the module
$MODULE = $MHANDLER->loadModule($UHANDLER->get('mod'));
if($MODULE == null) {
	new GlobalMessage('ERROR', $CORE->getLang()->getText('The module [MOD] is not known', Array('MOD' => htmlentities($UHANDLER->get('mod')))));
}
$MODULE->passAuth($AUTH, $AUTHORISATION);
$MODULE->setAction($UHANDLER->get('act'));

/*
 * Authorisation 2: Check if the user is permitted to use this module/action
 *                  If not redirect to Msg/401 (Unauthorized) page
 */

// Only check modules which should have authorisation checks
// This are all modules excluded some core things
if($MODULE->actionRequiresAuthorisation()) {
	// Only proceed with authenticated users
	if($AUTH->isAuthenticated()) {
		// In some modules not only the mod and the action need to be authorized
		// The called object needs separate authorisation too (e.g. in maps)
		if($MODULE->checkForObjectAuthorisation()) {
			$sObj = $MODULE->getObject();
		} else {
			$sObj = null;
		}
		
		// Check if the user is permited to this action in the module
		if(!isset($AUTHORISATION) || !$AUTHORISATION->isPermitted($UHANDLER->get('mod'), $UHANDLER->get('act'), $sObj)) {
			new GlobalMessage('ERROR', $CORE->getLang()->getText('You are not permitted to access this page'), null, $CORE->getLang()->getText('Access denied'));
		}
	} else {
		// FIXME: Maybe make login possible via API?
		// When not authenticated show error message
		new GlobalMessage('ERROR', $CORE->getLang()->getText('You are not authenticated'), null, $CORE->getLang()->getText('Access denied'));
	}
}

/*
 * Module handling 2: Render the modules when permitted
 *                    otherwise handle other pages
 */

// Handle regular action when everything is ok
// When no matching module or action is found show the 404 error
if($MODULE !== false && $MODULE->offersAction($UHANDLER->get('act'))) {
	$MODULE->setAction($UHANDLER->get('act'));

	// Handle the given action in the module
	$sContent = $MODULE->handleAction();
} else {
	// Create instance of msg module
	new GlobalMessage('ERROR', $CORE->getLang()->getText('The given action is not valid'));
}

echo $sContent;
if (DEBUG&&DEBUGLEVEL&4) debugFinalize();
exit(1);

?>