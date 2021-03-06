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

class SVNHandler {
	/**
	 * Constructor
	 */
	var $_SOAP;		// SOAP handler
	var $_LOG;		// Log handler
	var $_scm_data;
	var $_hostname;
	var $_root;
	var $_username;


	function SVNHandler($SOAP, $LOG, $scm_data) {
		$this->_SOAP =& $SOAP;
		$this->_LOG =& $LOG;

		$this->_scm_data = $scm_data;
		$this->_hostname = $this->_scm_data["box"];
		$this->_connstring = $this->_scm_data["connection_string"];
		$this->_username = $this->_SOAP->getSessionUser();
	}

	/**
	 * Perform a checkout
	 * @param	string	$module    Name of the module to checkout
	 * @param	bool	$anonymous Whether to make an anonymous checkout or a developer checkout
	 */
	function checkout($module, $anonymous=true) {
		if ($anonymous) {
			$cmd = "checkout ".$this->_getSVNURL;
		} else {
			$cmd = "checkout --username ".$this->_username." ".$this->_connstring;
		}
		if ($module) {
			$cmd = $cmd."/".$module;
		}

		$this->_execSVN($cmd);
		echo "Success!\n";
	}

	function update() {
		$this->_execSVN("update");
		echo "Success!\n";
	}

	function commit($message) {
		$message = escapeshellarg($message);
		$this->_execSVN("commit -m \"".$message."\"");
	}

	function showFiles($module, $path) {
		if ($module) {
			$path = $path."/".$module;
		}

		$this->_execSVN("ls ".$this->_connstring."/".$path);
	}

	/**
	 * Execute a shell command
	 * @param string $cmd
	 * @param bool $output
	 * @return	array	Array that holds the return code and the output
	 */
	function _exec($cmd, $output = true) {
		if ($output) {
			passthru($cmd." 2>&1", $return_code);
		} else {
			// TODO
			die("SVNHandler::TODO");
		}

		return array("return_code" => $return_code, "output" => $output);
	}

	function _execSVN($command) {
		$cmd = "svn ".$command;

		$result = $this->_exec($cmd);
		if ($result["return_code"]) {
			exit_error("SVN program exited with error code #".$result["return_code"]);
		}
	}
}
