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
// $PARAMS[0] is "document" (the name of this module) and $PARAMS[1] is the name of the function
$module_name = array_shift($PARAMS);		// Pop off module name
$function_name = array_shift($PARAMS);		// Pop off function name

$functions = array("listgroups",
                   "addgroup",
                   "updategroup",
                   "listdocuments",
                   "get",
                   "adddocument",
                   "updatedocument",
                   "getstates",
                   "delete");

if (empty($function_name)) {
    exit_error("Please provide function name: ".implode(', ', $functions));
}
if (!in_array($function_name, $functions)) {
    exit_error("Unknown function name: ".$function_name);
}
$document_do = 'document_do_'.$function_name;
$document_do();

function document_do_getstates() {
	global $SOAP, $LOG;

	$res = $SOAP->call("getDocumentStates");
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	show_output($res);
}

function document_do_updategroup(){
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	if (!($doc_group = get_parameter($PARAMS, "doc_group"))) {
		exit_error("You must specify a document group id: (e.g.) --doc_group=3");
	}

	$res = $SOAP->call("getDocumentGroup", array("group_id" => $group_id,
						"doc_group"	=> $doc_group));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	if (!($new_name = get_parameter($PARAMS, "name"))) {
		$new_name=$res['groupname'];
	}

	$new_parent_group = get_parameter($PARAMS, "parent_group");

	if ($new_parent_group===false) {
		$new_parent_group=$res['parent_doc_group'];
	}

	if (empty($new_parent_group)) {
		$new_parent_group = 0;
	}

	$params = array(
			"group_id"		=> $group_id,
			"doc_group"		=> $doc_group,
			"new_groupname"		=> $new_name,
			"new_parent_doc_group"	=> $new_parent_group
			);

	$SOAP->call("updateDocumentGroup", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo "Update successful\n";
}

function document_do_adddocument(){
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	if (!($doc_group = get_parameter($PARAMS, "doc_group"))) {
		exit_error("You must specify a document group id: (e.g.) --doc_group=3");
	}
	if (!($title = get_parameter($PARAMS, "title"))) {
		exit_error("You must specify a title for the new document: (e.g.) --title=\"Title name\"");
	}
	if (!($description = get_parameter($PARAMS, "description"))) {
		exit_error("You must specify a description for the new document: (e.g.) --description=\"This is a description\"");
	}
	if (!($filename = get_parameter($PARAMS, "filename")) &&!($url = get_parameter($PARAMS, "url"))) {
		exit_error("You must specify a filename or URL for the new document: (e.g.) --filename=/home/user/file.txt or --url=/document.html");
	}

	if ($filename) {
		while (!($fh = fopen($filename, "rb"))) {
			echo "Couldn't open file ".$filename." for reading.\n";
			$filename = "";
			while (!$filename) {
				$filename = get_user_input("Please specify a new file name: ");
			}
		}
		$bin_contents = fread($fh, filesize($filename));
		$base64_contents = base64_encode($bin_contents);
		$filename = basename($filename);
		$url='';
	}else{
		$base64_contents = '';
		$filename = '';
	}

	$params = array(
			"group_id"	=> $group_id,
			"doc_group"	=> $doc_group,
			"title"		=> $title,
			"description"	=> $description,
			"base64_contents" => $base64_contents,
			"filename"	=> $filename,
			"file_url"	=> $url
			);

	$res = $SOAP->call("addDocument", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res."\n";
}

function document_do_updatedocument(){
	global $PARAMS, $SOAP, $LOG;

	if (get_parameter($PARAMS, "help")) {
		echo <<<EOF

Updates an existing Document in a Document Group.
Parameters:

		--project=<name>: Name of the project in which this document exists.
		--doc_group=<id>: Specify the ID of the document group this document belongs to.
			The function "listgroups" shows a list of available Document Groups and their
			corresponding IDs.
		--doc_id=<id>: Specify the ID of the document to update. The function "listdocuments"
			shows a list of available Documents and their corresponding IDs.
		--title=<text>: New Document title (e.g. "Coding Standards"). (optional)
		--description=<text>: New Document description (e.g. "Coding Standards for MyProject").
			(optional)
		--filename=<text>: New File to be uploaded (path included). (optional)
		--url=<text>: Url where this document can be viewed. (optional)
		--state_id=<id>: Change Document's state by setting a new state ID.
			The function "getstates" shows a list of available Document states and
			their corresponding IDs. (optional)

Note: All optional parameters, if not set, wont be changed from their original status.
EOF;
		return;
	}

	$group_id = get_working_group($PARAMS);

	if (!($doc_id = get_parameter($PARAMS, "doc_id"))) {
		exit_error("You must specify a document id: (e.g.) --doc_id=10");
	}

	if (!($doc_group = get_parameter($PARAMS, "doc_group"))) {
		$doc_group='';
	}

	if (!($title = get_parameter($PARAMS, "title"))) {
		$title='';
	}

	if (!($description = get_parameter($PARAMS, "description"))) {
		$description='';
	}

	if (!($filename = get_parameter($PARAMS, "filename"))) {
		$filename='';
	}

	if (!($url = get_parameter($PARAMS, "url"))) {
		$url='';
	}

	if (!($state_id = get_parameter($PARAMS, "state_id"))) {
		$state_id='';
	}

	if ($filename) {
		while (!($fh = fopen($filename, "rb"))) {
			echo "Couldn't open file ".$filename." for reading.\n";
			$filename = "";
			while (!$filename) {
				$filename = get_user_input("Please specify a new file name: ");
			}
		}
		$bin_contents = fread($fh, filesize($filename));
		$base64_contents = base64_encode($bin_contents);
		$filename = basename($filename);
		$url='';
	} else {
		$base64_contents = '';
		$filename = '';
	}

	$params = array(
			"group_id"	=> $group_id,
			"doc_group"	=> $doc_group,
			"doc_id"	=> $doc_id,
			"title"		=> $title,
			"description"	=> $description,
			"base64_contents" => $base64_contents,
			"filename"	=> $filename,
			"file_url"	=> $url,
			"state_id"	=> $state_id
			);

	$SOAP->call("updateDocument", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo "Update successful\n";
}

function document_do_get() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	if (!($doc_id = get_parameter($PARAMS, "doc_id"))) {
		exit_error("You must specify a document id: (e.g.) --doc_id=10");
	}

	$params = array(
			"group_id"	=> $group_id,
			"doc_id"	=> $doc_id
			);

	$res = $SOAP->call("getDocumentFiles", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	if (!is_array($res) || count($res) == 0) {
		die("No files were found for this document.");
	}

	$filename=$res[0]['filename'];
	if(strcmp($res[0]['filetype'],"URL")==0){
		die("This document can be found in the following URL: ".$filename."\n");
	}

	// Should we save the contents to a file?
	$output = get_parameter($PARAMS, "output", true);
	if ($output) {
		if (file_exists($filename)) {
			$sure = get_user_input("File $filename  already exists. Do you want to overwrite it? (y/n): ");
			if (strtolower($sure) != "y" && strtolower($sure) != "yes") {
				exit_error("Retrieval of file aborted");
			}
		}
	}

	$file = base64_decode($res[0]['data']);
	if ($output) {
		while (!($fh = @fopen($filename, "wb"))) {
			echo "Couldn't open file $filename for writing.\n";
			$filename = "";
			while (!$filename) {
				$filename = get_user_input("Please specify a new file name: ");
			}
		}

		fwrite($fh, $file, strlen($file));
		fclose($fh);

		echo "File: $filename retrieved successfully.\n";
	} else {
		echo $file;		// if not saving to a file, output to screen
	}
}

function document_do_listgroups() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$res = $SOAP->call("getDocumentGroups", array("group_id" => $group_id));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	show_output($res);
}

function document_do_group() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	if (!($doc_group = get_parameter($PARAMS, "doc_group"))) {
		exit_error("You must specify a document group id: (e.g.) --doc_group=3");
	}

	$params = array(
			"group_id"	=> $group_id,
			"doc_group"	=> $doc_group
			);

	$res = $SOAP->call("getDocumentGroup", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	show_output($res);
}

function document_do_addgroup() {
	global $PARAMS, $SOAP, $LOG;

	if (!($groupname = get_parameter($PARAMS, "name"))) {
		exit_error("You must specify a name for the group: (e.g.) --name=\"Group name\"");
	}

	if (!($parent_doc_group = get_parameter($PARAMS, "parent"))) {
		$parent_doc_group = 0;
	}

	$group_id = get_working_group($PARAMS);

	$params = array(
			"group_id"		=> $group_id,
			"groupname"		=> $groupname,
			"parent_doc_group"	=> $parent_doc_group
			);

	$res = $SOAP->call("addDocumentGroup", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res."\n";
}

function document_do_listdocuments() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	if (!($doc_group = get_parameter($PARAMS, "doc_group"))) {
		exit_error("You must specify a document group id: (e.g.) --doc_group=3");
	}

	$params = array(
			"group_id"	=> $group_id,
			"doc_group"	=> $doc_group
			);

	$res = $SOAP->call("getDocuments", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	show_output($res);
}

function document_do_delete() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	if (!($doc_id = get_parameter($PARAMS, "doc_id"))) {
		exit_error("You must specify a document id: (e.g.) --doc_id=17");
	}

	$params = array(
			"group_id"	=> $group_id,
			"doc_id"	=> $doc_id
			);

	$SOAP->call("documentDelete", $params);
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo "Document successfully deleted\n";
}
