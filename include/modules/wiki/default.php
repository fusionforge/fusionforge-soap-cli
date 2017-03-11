<?php
/**
 * FusionForge Command-line Interface
 *
 * Copyright 2015 Alcatel-Lucent
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

/**
 * Variables passed by parent script:
 * - $SOAP: Soap object to talk to the server
 * - $PARAMS: parameters passed to this script
 * - $LOG: object for logging of events
 */

// function to execute
// $PARAMS[0] is "wiki" (the name of this module) and $PARAMS[1] is the name of the function
$module_name = array_shift($PARAMS);		// Pop off module name
$function_name = array_shift($PARAMS);		// Pop off function name

$functions = array("createpage",
                   "createpagefromfile",
                   "fulltextsearch",
                   "getallpagenames",
                   "getcurrentrevision",
                   "getpage",
                   "getpagemeta",
                   "getpagerevision",
                   "getpluginsynopsis",
                   "listlinks",
                   "listplugins",
                   "listrelations",
                   "recentchanges",
                   "replacestring",
                   "titlesearch");

if (empty($function_name)) {
	exit_error("Please provide function name: ".implode(', ', $functions));
}
if (!in_array($function_name, $functions)) {
	exit_error("Unknown function name: ".$function_name);
}
$wiki_do = 'wiki_do_'.$function_name;
$wiki_do();

function wiki_do_createpage() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$pagecontent = get_parameter($PARAMS, "pagecontent", true);
	if (!$pagecontent || strlen($pagecontent) == 0) {
		exit_error("You must enter the content of the page with the --pagecontent parameter");
	}

	$res = $SOAP->call("doSavePage", array("group_id" => $group_id,
						"pagename" => $pagename,
						"content" => $pagecontent));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res;
	echo "\n";
}

function wiki_do_createpagefromfile() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$file = get_parameter($PARAMS, "file", true);
	if (!$file || strlen($file) == 0) {
		exit_error("You must enter the name of the page with the --file parameter");
	}

	if (!file_exists($file)) {
		echo "error: file $file does not exist\n";
		exit;
	}

	if (!is_readable($file)) {
		echo "error: file $file is not readable\n";
		exit;
	}

	$res = $SOAP->call("doSavePage", array("group_id" => $group_id,
						"pagename" => $pagename,
						"content" => file_get_contents($file)));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res;
	echo "\n";
}

function wiki_do_fulltextsearch() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$s = get_parameter($PARAMS, "s", true);
	if (!$s || strlen($s) == 0) {
		exit_error("You must enter the search term with the --s parameter");
	}

	$res = $SOAP->call("doFullTextSearch", array("group_id" => $group_id,
							"s" => $s));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	for ($i = 0; $i < count($res); $i++) {
		echo $res[$i]['pagename'];
		echo "\n";
	}
}

function wiki_do_getallpagenames() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$all_pages = $SOAP->call("getAllPagenames", array("group_id" => $group_id));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	for ($i = 0; $i < count($all_pages); $i++) {
		echo $all_pages[$i]['pagename'];
		echo "\n";
	}
}

function wiki_do_getcurrentrevision() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$res = $SOAP->call("getCurrentRevision", array("group_id" => $group_id,
							"pagename" => $pagename));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res;
	echo "\n";
}

function wiki_do_getpage() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$res = $SOAP->call("getPageContent", array("group_id" => $group_id,
						"pagename" => $pagename));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res;
	echo "\n";
}

function wiki_do_getpagemeta() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$pagemeta = $SOAP->call("getPageMeta", array("group_id" => $group_id,
							"pagename" => $pagename));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	foreach ($pagemeta as $key => $value) {
		echo "$key: $value\n";
	}
}

function wiki_do_getpagerevision() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$revision = get_parameter($PARAMS, "revision", true);
	if (!$revision) {
		exit_error("You must enter the the revision number of the page with the --revision parameter");
	}

	$res = $SOAP->call("getPageRevision", array("group_id" => $group_id,
							"pagename" => $pagename,
							"revision" => $revision));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res;
	echo "\n";
}

function wiki_do_getpluginsynopsis() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pluginname = get_parameter($PARAMS, "pluginname", true);
	if (!$pluginname || strlen($pluginname) == 0) {
		exit_error("You must enter the name of the plugin with the --pluginname parameter");
	}

	$res = $SOAP->call("getPluginSynopsis", array("group_id" => $group_id,
							"pluginname" => $pluginname));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res;
	echo "\n";
}

function wiki_do_listlinks() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$links = $SOAP->call("listLinks", array("group_id" => $group_id,
						"pagename" => $pagename));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	for ($i = 0; $i < count($links); $i++) {
		echo $links[$i]['pagename'];
		echo "\n";
	}
}

function wiki_do_listplugins() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$plugins = $SOAP->call("listPlugins", array("group_id" => $group_id));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	for ($i = 0; $i < count($plugins); $i++) {
		echo $plugins[$i]['pagename'];
		echo "\n";
	}
}

function wiki_do_listrelations() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$relations = $SOAP->call("listRelations", array("group_id" => $group_id));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	for ($i = 0; $i < count($relations); $i++) {
		echo $relations[$i]['pagename'];
		echo "\n";
	}
}

function wiki_do_recentchanges() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$limit = get_parameter($PARAMS, "limit", true);
	if (!$limit ) {
		$limit = 20;
	}

	$changes = $SOAP->call("getRecentChanges", array("group_id" => $group_id,
							"limit" => $limit));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	for ($i = 0; $i < count($changes); $i++) {
		echo $changes[$i]['pagename'];
		echo "\n";
	}
}

function wiki_do_replacestring() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$pagename = get_parameter($PARAMS, "pagename", true);
	if (!$pagename || strlen($pagename) == 0) {
		exit_error("You must enter the name of the page with the --pagename parameter");
	}

	$search = get_parameter($PARAMS, "search", true);
	if (!$search) {
		exit_error("You must enter the search term with the --search parameter");
	}

	$replace = get_parameter($PARAMS, "replace", true);
	if (!$replace) {
		exit_error("You must enter the replace term with the --replace parameter");
	}

	$old_content = $SOAP->call("getPageContent", array("group_id" => $group_id,
							"pagename" => $pagename));
	$new_content = str_replace($search, $replace, $old_content);
	if ($new_content != $old_content) {
	$res = $SOAP->call("doSavePage", array("group_id" => $group_id,
						"pagename" => $pagename,
						"content" => $new_content));
	} else {
	$res = "No replacement needed.";
	}

	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	echo $res;
	echo "\n";
}

function wiki_do_titlesearch() {
	global $PARAMS, $SOAP, $LOG;

	$group_id = get_working_group($PARAMS);

	$s = get_parameter($PARAMS, "s", true);
	if (!$s || strlen($s) == 0) {
		exit_error("You must enter the title to search with the --s parameter");
	}

	$res = $SOAP->call("doTitleSearch", array("group_id" => $group_id,
						"s" => $s));
	if (($error = $SOAP->getError())) {
		$LOG->add($SOAP->responseData);
		exit_error($error, $SOAP->faultcode);
	}

	for ($i = 0; $i < count($res); $i++) {
		echo $res[$i]['pagename'];
		echo "\n";
	}
	}
