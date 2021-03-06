<?php
/**
 * FusionForge Command-line Interface
 *
 * Copyright 2005 GForge, LLC
 * http://fusionforge.org/
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FusionForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/*
* Variables passed by parent script:
* - $SOAP: Soap object to talk to the server
* - $PARAMS: parameters passed to this script
* - $LOG: object for logging of events
*/

// function to execute
// $PARAMS[0] is "project" (the name of this module) and $PARAMS[1] is the name of the function
$module_name = array_shift($PARAMS);		// Pop off module name ("project")
$function_name = array_shift($PARAMS);		// Pop off function name

$functions = array("list",
                   "mylist");

if (empty($function_name)) {
    exit_error("Please provide function name: ".implode(', ', $functions));
}
if (!in_array($function_name, $functions)) {
    exit_error("Unknown function name: ".$function_name);
}
$project_do = 'project_do_'.$function_name;
$project_do();

////////////////////////////////////////////////
/**
 * project_do_list - List of projects in the server
 */
function project_do_list() {
	global $PARAMS, $SOAP;

	if (get_parameter($PARAMS, "help")) {
		return;
	}

	$res = $SOAP->call("getPublicProjectNames");
	if (($error = $SOAP->getError())) {
		exit_error($error, $SOAP->faultcode);
	}

	show_output($res, array("Project name"));

}

/**
 * project_do_mylist - List of projects available to the logged user
 */
function project_do_mylist() {
	global $PARAMS, $SOAP;

	if (get_parameter($PARAMS, "help")) {
		return;
	}

	// Fetch the user ID from the database
	$params = array("user_ids" => array($SOAP->getSessionUser()));
	$res = $SOAP->call("getUsersByName",$params);

	if (($error = $SOAP->getError())) {
		exit_error($error, $SOAP->faultcode);
	}

	$user_id = $res[0]["user_id"];
	$params = array("user_id" => $user_id);
	$res = $SOAP->call("userGetGroups", $params);
	if (($error = $SOAP->getError())) {
		exit_error($error, $SOAP->faultcode);
	}

	show_output($res);
}
