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

define("CVS_AUTH_PSERVER",	1);
define("CVS_AUTH_EXT",		2);
define("CVS_AUTH_NONE",		3);

class CVSHandler {
	/**
	 * Constructor
	 */
	var $_SOAP;		// SOAP handler
	var $_LOG;		// Log handler
	var $_scm_data;
	var $_hostname;
	var $_root;
	var $_username;


	function CVSHandler($SOAP, $LOG, $scm_data) {
		$this->_SOAP =& $SOAP;
		$this->_LOG =& $LOG;

		$this->_scm_data = $scm_data;
		$this->_hostname = $this->_scm_data["box"];
		$this->_root = $this->_scm_data["root"];
		$this->_username = $this->_SOAP->getSessionUser();
	}

	/**
	 * Perform a checkout
	 * @param	string	$module    Name of the module to checkout
	 * @param	bool	$anonymous Whether to make an anonymous checkout or a developer checkout
	 */
	function checkout($module, $anonymous=true) {
		$mode = ($anonymous) ? CVS_AUTH_PSERVER : CVS_AUTH_EXT;
		$this->_execCVS("checkout ".$module, $mode);
		echo "Success!\n";
	}

	function update() {
		$this->_execCVS("update -Pd ");
		echo "Success!\n";
	}

	function commit($message) {
		$message = escapeshellarg($message);
		$this->_execCVS("commit -m \"".$message."\"");
	}

	function showFiles($module, $path) {
		if ($module) $path = $module."/".$path;
		// if anonymous access is enabled, try to connect as anonymous
		$mode = ($this->_scm_data["allow_anonymous"]) ? CVS_AUTH_PSERVER : CVS_AUTH_EXT;
		$this->_execCVS("rls -l ".$path, $mode);
	}

	/**
	 * Execute a shell command
	 * @return	array	Array that holds the return code and the output
	 *
	 * @param string $cmd
	 * @param bool $output
	 * @return array
	 */
	function _exec($cmd, $output = true) {
		if ($output) {
			passthru($cmd." 2>&1", $return_code);
		} else {
			// TODO
			die("CVSHandler::TODO");
		}

		return array("return_code" => $return_code, "output" => $output);
	}

	function _anonymousLogin() {
		$this->_LOG->add("Logging in to ".$this->_hostname." as anonymous...");
		$cmd = "cvs -d :pserver:anonymous@".$this->_hostname.":".$this->_root." login";
		$result = $this->_exec($cmd);
		if ($result["return_code"]) {
			exit_error("CVS program exited with error code #".$result["return_code"]);
		}
	}

	function _execCVS($command, $auth_mode = CVS_AUTH_NONE) {
		if ($auth_mode == CVS_AUTH_PSERVER) {
			if (!$this->_scm_data["allow_anonymous"]) {
				exit_error("This project's SCM doesn't allow anonymous access");
			}

			$this->_anonymousLogin();
			$cmd = "cvs -d :pserver:anonymous@".$this->_hostname.":".$this->_root." ".$command;
		} elseif ($auth_mode == CVS_AUTH_EXT) {
			$cmd = "CVS_RSH=\"ssh\" cvs -d :ext:".$this->_username."@".$this->_hostname.":".$this->_root." ".$command;
		} elseif ($auth_mode == CVS_AUTH_NONE) {
			$cmd = "cvs ".$command;
		}

		$result = $this->_exec($cmd);
		if ($result["return_code"]) {
			exit_error("CVS program exited with error code #".$result["return_code"]);
		}
	}
}
