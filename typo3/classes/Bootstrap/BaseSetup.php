<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class to encapsulate base setup of bootstrap.
 *
 * This class contains all code that must be executed by every
 * entry script without exceptions.
 *
 * It sets up all basic pathes, constants, global variables and checks
 * the basic environment TYPO3 runs in.
 *
 * This class does not use any TYPO3 instance specific configuration, it only
 * sets up things based on the server environment and core code. Even with a
 * missing typo3conf/localconf.php this script will be successful.
 *
 * The script aborts execution with an error message if
 * some part fails or conditions are not met.
 */
class Typo3_Bootstrap_BaseSetup {

	/**
	 * Run base setup.
	 *
	 * @param string $relativePathPart Relative path of the entry script back to document root
	 * @return void
	 */
	public static function run($relativePathPart = '') {
		self::checkPhpVersionOrDie();
		self::checkGlobalsAreNotSetViaPostOrGet();
		self::defineBaseConstants();
		self::definePaths($relativePathPart);
		self::checkMainPathsExist();
		self::requireBaseClasses();
		self::handleMagicQuotesGpc();
		self::addCorePearPathToIncludePath();
		self::initializeGlobalVariables();
		self::loadDefaultTypo3ConfVars();
		self::initializeGlobalTimeTrackingVariables();
		self::initializeBasicErrorReporting();
	}

	/**
	 * Check php version requirement or exit script
	 *
	 * @return void
	 */
	protected static function checkPhpVersionOrDie() {
		if (version_compare(phpversion(), '5.3', '<')) {
			die('TYPO3 requires PHP 5.3.0 or higher.');
		}
	}

	/**
	 * Exit script if globals are set via post or get
	 *
	 * @return void
	 */
	protected static function checkGlobalsAreNotSetViaPostOrGet() {
		if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS'])) {
			die('You cannot set the GLOBALS array from outside the script.');
		}
	}

	/**
	 * Define all simple constants that have no dependency to local configuration
	 *
	 * @return void
	 */
	protected static function defineBaseConstants() {
			// This version, branch and copyright
		define('TYPO3_version', '6.0-dev');
		define('TYPO3_branch', '6.0');
		define('TYPO3_copyright_year', '1998-2012');

			// TYPO3 external links
		define('TYPO3_URL_GENERAL', 'http://typo3.org/');
		define('TYPO3_URL_ORG', 'http://typo3.org/');
		define('TYPO3_URL_LICENSE', 'http://typo3.org/licenses');
		define('TYPO3_URL_EXCEPTION', 'http://typo3.org/go/exception/v4/');
		define('TYPO3_URL_MAILINGLISTS', 'http://lists.typo3.org/cgi-bin/mailman/listinfo');
		define('TYPO3_URL_DOCUMENTATION', 'http://typo3.org/documentation/');
		define('TYPO3_URL_DOCUMENTATION_TSREF', 'http://typo3.org/documentation/document-library/core-documentation/doc_core_tsref/current/view/');
		define('TYPO3_URL_DOCUMENTATION_TSCONFIG', 'http://typo3.org/documentation/document-library/core-documentation/doc_core_tsconfig/current/view/');
		define('TYPO3_URL_CONSULTANCY', 'http://typo3.org/support/professional-services/');
		define('TYPO3_URL_CONTRIBUTE', 'http://typo3.org/contribute/');
		define('TYPO3_URL_SECURITY', 'http://typo3.org/teams/security/');
		define('TYPO3_URL_DOWNLOAD', 'http://typo3.org/download/');
		define('TYPO3_URL_SYSTEMREQUIREMENTS', 'http://typo3.org/about/typo3-the-cms/system-requirements/');
		define('TYPO3_URL_DONATE', 'http://typo3.org/donate/online-donation/');

			// A tabulator, a linefeed, a carriage return, a CR-LF combination
		define('TAB', chr(9));
		define('LF', chr(10));
		define('CR', chr(13));
		define('CRLF', CR . LF);

			// Security related constant: Default value of fileDenyPattern
		define('FILE_DENY_PATTERN_DEFAULT', '\.(php[3-6]?|phpsh|phtml)(\..*)?$|^\.htaccess$');

			// Security related constant: List of file extensions that should be registered as php script file extensions
		define('PHP_EXTENSIONS_DEFAULT', 'php,php3,php4,php5,php6,phpsh,inc,phtml');

			// List of extensions required to run the core
		define('REQUIRED_EXTENSIONS', 'cms,lang,sv,em,recordlist,extbase,fluid');

			// Operating system identifier
			// Either "WIN" or empty string
		define('TYPO3_OS', self::getTypo3Os());
	}

	/**
	 * Calculate all required base paths and set as constants.
	 *
	 * @param string $relativePathPart Relative path of the entry script back to document root
	 * @return void
	 */
	protected static function definePaths($relativePathPart = '') {
			// Relative path from document root to typo3/ directory
			// Hardcoded to "typo3/"
		define('TYPO3_mainDir', 'typo3/');

			// Absolute path of the entry script that was called
			// All paths are unified between Windows and Unix, so the \ of Windows is substituted to a /
			// Example "/var/www/instance-name/htdocs/typo3conf/ext/wec_map/mod1/index.php"
			// Example "c:/var/www/instance-name/htdocs/typo3/backend.php" for a path in Windows
		define('PATH_thisScript', self::getPathThisScript());

			// Absolute path of the document root of the instance with trailing slash
			// Example "/var/www/instance-name/htdocs/"
		define('PATH_site', self::getPathSite($relativePathPart));

			// Absolute path of the typo3 directory of the instance with trailing slash
			// Example "/var/www/instance-name/htdocs/typo3/"
		define('PATH_typo3', PATH_site . TYPO3_mainDir);

			// Relative path (from the PATH_typo3) to a BE module NOT using mod.php dispatcher with trailing slash
			// Example "sysext/perms/mod/" for an extension installed in typo3/sysext/
			// Example "install/" for the install tool entry script
			// Example "../typo3conf/ext/templavoila/mod2/ for an extension installed in typo3conf/ext/
		define('PATH_typo3_mod', defined('TYPO3_MOD_PATH') ? TYPO3_MOD_PATH : '');

			// Absolute path to the t3lib directory with trailing slash
			// Example "/var/www/instance-name/htdocs/t3lib/"
		define('PATH_t3lib', PATH_site . 't3lib/');

			// Absolute path to the typo3conf directory with trailing slash
			// Example "/var/www/instance-name/htdocs/typo3conf/"
		define('PATH_typo3conf', PATH_site . 'typo3conf/');

			// Absolute path to the tslib directory with trailing slash
			// Example "/var/www/instance-name/htdocs/typo3/sysext/cms/tslib/"
		define('PATH_tslib', PATH_typo3 . 'sysext/cms/tslib/');
	}

	/**
	 * Check if path and script file name calculation was successful, exit if not.
	 *
	 * @return void
	 */
	protected static function checkMainPathsExist() {
		if (!is_file(PATH_thisScript)) {
			die('Unable to determine path to entry script.');
		}
		if (!is_dir(PATH_t3lib)) {
			die('Calculated absolute path to t3lib directory does not exist.');
		}
		if (!is_dir(PATH_tslib)) {
			die('Calculated absolute path to tslib directory does not exist.');
		}
		if (!is_dir(PATH_typo3conf)) {
			die('Calculated absolute path to typo3conf directory does not exist');
		}
	}

	/**
	 * Load several base classes during bootstrap
	 *
	 * @return void
	 */
	protected static function requireBaseClasses() {
		/** @define "PATH_t3lib" "../../../t3lib/" */
		require_once(PATH_t3lib . 'class.t3lib_div.php');

		require_once(PATH_t3lib . 'class.t3lib_extmgm.php');

		require_once(PATH_t3lib . 'class.t3lib_cache.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_exception.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_nosuchcache.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invaliddata.php');
		require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_factory.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_manager.php');
		require_once(PATH_t3lib . 'cache/frontend/interfaces/interface.t3lib_cache_frontend_frontend.php');
		require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php');
		require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_stringfrontend.php');
		require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_phpfrontend.php');
		require_once(PATH_t3lib . 'cache/backend/interfaces/interface.t3lib_cache_backend_backend.php');
		require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_abstractbackend.php');
		require_once(PATH_t3lib . 'cache/backend/interfaces/interface.t3lib_cache_backend_phpcapablebackend.php');
		require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_filebackend.php');
		require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_nullbackend.php');

		require_once(PATH_t3lib . 'class.t3lib_autoloader.php');
	}

	/**
	 * Compatibility layer for magic quotes
	 *
	 * @return void
	 */
	protected static function handleMagicQuotesGpc() {
		if (!get_magic_quotes_gpc()) {
			t3lib_div::addSlashesOnArray($_GET);
			t3lib_div::addSlashesOnArray($_POST);
			$GLOBALS['HTTP_GET_VARS'] = $_GET;
			$GLOBALS['HTTP_POST_VARS'] = $_POST;
		}
	}

	/**
	 * Add typo3/contrib/pear/ as first include folder in
	 * include path, because the shipped PEAR packages use
	 * relative paths to include their files.
	 *
	 * This is required for t3lib_http_Request to work.
	 *
	 * Having the TYPO3 folder first will make sure that the
	 * shipped version is loaded before any local PEAR package,
	 * thus avoiding any incompatibilities with newer or older
	 * versions.
	 *
	 * @return void
	 */
	protected static function addCorePearPathToIncludePath() {
		set_include_path(PATH_typo3 . 'contrib/pear/' . PATH_SEPARATOR . get_include_path());
	}

	/**
	 * Set up / initialize several globals variables
	 *
	 * @return void
	 */
	protected static function initializeGlobalVariables() {
			// Unset variable(s) in global scope (security issue #13959)
		unset($GLOBALS['error']);

			// Set up base information about browser/user-agent
		$GLOBALS['CLIENT'] = t3lib_div::clientInfo();

		$GLOBALS['TYPO3_MISC'] = array();
		$GLOBALS['T3_VAR'] = array();
		$GLOBALS['T3_SERVICES'] = array();
	}

	/**
	 * Load default TYPO3_CONF_VARS to globals
	 *
	 * @return void
	 */
	protected static function loadDefaultTypo3ConfVars() {
		$GLOBALS['TYPO3_CONF_VARS'] = require(PATH_t3lib . 'stddb/DefaultSettings.php');
	}

	/**
	 * Initialize global time tracking variables.
	 * These are helpers to for example output script parsetime at the end of a script.
	 *
	 * @return void
	 */
	protected static function initializeGlobalTimeTrackingVariables() {
			// Set PARSETIME_START to the system time in milliseconds.
		$GLOBALS['PARSETIME_START'] = t3lib_div::milliseconds();
			// Microtime of (nearly) script start
		$GLOBALS['TYPO3_MISC']['microtime_start'] = microtime(TRUE);

			// EXEC_TIME is set so that the rest of the script has a common value for the script execution time
		$GLOBALS['EXEC_TIME'] = time();
			// $ACCESS_TIME is a common time in minutes for access control
		$GLOBALS['ACCESS_TIME'] = $GLOBALS['EXEC_TIME'] - ($GLOBALS['EXEC_TIME'] % 60);

			// $SIM_EXEC_TIME is set to $EXEC_TIME but can be altered later in the script if we want to
			// simulate another execution-time when selecting from eg. a database
		$GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
			// If $SIM_EXEC_TIME is changed this value must be set accordingly
		$GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
	}

	/**
	 * Initialize basic error reporting.
	 *
	 * There are a lot of extensions that have no strict / notice / deprecated free
	 * ext_localconf or ext_tables. Since the final error reporting must be set up
	 * after those extension files are read, a default configuration is needed to
	 * suppress error reporting meanwhile during further bootstrap.
	 *
	 * @return void
	 */
	protected static function initializeBasicErrorReporting() {
			// Core should be notice free at least until this point ...
		error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));
	}

	/**
	 * Determine the operating system TYPO3 is running on.
	 *
	 * @return string Either 'WIN' if running on Windows, else empty string
	 */
	protected static function getTypo3Os() {
		$typoOs = '';
		if (!stristr(PHP_OS, 'darwin') && stristr(PHP_OS, 'win')) {
			$typoOs = 'WIN';
		}
		return $typoOs;
	}

	/**
	 * Calculate PATH_thisScript
	 *
	 * First step in path calculation: Goal is to find the absolute path of the entry script
	 * that was called without resolving any links. This is important since the TYPO3 entry
	 * points are often linked to a central core location, so we can not use the php magic
	 * __FILE__ here, but resolve the called script path from given server environments.
	 *
	 * This path is important to calculate the document root (PATH_site). The strategy is to
	 * find out the script name that was called in the first place and to subtract the local
	 * part from it to find the document root.
	 *
	 * @return string Absolute path to entry script
	 */
	protected static function getPathThisScript() {
		if (defined('TYPO3_cliMode') && TYPO3_cliMode === TRUE) {
			return self::getPathThisScriptCli();
		} else {
			return self::getPathThisScriptNonCli();
		}
	}

	/**
	 * Calculate path to entry script if not in cli mode.
	 *
	 * Depending on the environment, the script path is found in different $_SERVER variables.
	 *
	 * @return string Absolute path to entry script
	 */
	protected static function getPathThisScriptNonCli() {
		$cgiPath = '';
		if (isset($_SERVER['ORIG_PATH_TRANSLATED'])) {
			$cgiPath = $_SERVER['ORIG_PATH_TRANSLATED'];
		} elseif (isset($_SERVER['PATH_TRANSLATED'])) {
			$cgiPath = $_SERVER['PATH_TRANSLATED'];
		}
		if ($cgiPath && (PHP_SAPI === 'fpm-fcgi' || PHP_SAPI === 'cgi' || PHP_SAPI === 'isapi' || PHP_SAPI === 'cgi-fcgi')) {
			$scriptPath = $cgiPath;
		} else {
			if (isset($_SERVER['ORIG_SCRIPT_FILENAME'])) {
				$scriptPath = $_SERVER['ORIG_SCRIPT_FILENAME'];
			} else {
				$scriptPath = $_SERVER['SCRIPT_FILENAME'];
			}
		}
			// Replace \ to / for Windows
		$scriptPath = str_replace('\\', '/', $scriptPath);
			// Replace double // to /
		$scriptPath = str_replace('//', '/', $scriptPath);

		return $scriptPath;
	}

	/**
	 * Calculate path to entry script if in cli mode.
	 *
	 * First argument of a cli script is the path to the script that was called. If the script does not start
	 * with / (or A:\ for Windows), the path is not absolute yet, and the current working directory is added.
	 *
	 * @return string Absolute path to entry script
	 */
	protected static function getPathThisScriptCli() {
			// Possible relative path of the called script
		if (isset($_SERVER['argv'][0])) {
			$scriptPath = $_SERVER['argv'][0];
		} elseif (isset($_ENV['_'])) {
			$scriptPath = $_ENV['_'];
		} else {
			$scriptPath = $_SERVER['_'];
		}

			// Find out if path is relative or not
		$isRelativePath = FALSE;
		if (TYPO3_OS === 'WIN') {
			if (!preg_match('/^([A-Z]:)?\\\/', $scriptPath)) {
				$isRelativePath = TRUE;
			}
		} else {
			if (substr($scriptPath, 0, 1) !== '/') {
				$isRelativePath = TRUE;
			}
		}

			// Concatenate path to current working directory with relative path and remove "/./" constructs
		if ($isRelativePath) {
			if (isset($_SERVER['PWD'])) {
				$workingDirectory = $_SERVER['PWD'];
			} else {
				$workingDirectory = getcwd();
			}
			$scriptPath = $workingDirectory . '/' . preg_replace('/\.\//', '', $scriptPath);
		}

		return $scriptPath;
	}

	/**
	 * Calculate the document root part to the instance from PATH_thisScript
	 *
	 * There are two ways to hint correct calculation:
	 * Either an explicit specified sub path or the defined constant TYPO3_MOD_PATH. Which one is
	 * used depends on which entry script was called in the first place.
	 *
	 * We have two main scenarios for entry points:
	 * - Directly called documentRoot/index.php (-> FE call or eiD include): index.php sets $relativePathPart to
	 *   empty string to hint this code that the document root is identical to the directory the script is located at.
	 * - An indirect include of typo3/init.php (-> a backend module, the install tool, or scripts like thumbs.php).
	 *   If init.php is included we distinguish two cases:
	 *   -- A backend module defines 'TYPO3_MOD_PATH': This is the case for "old" modules that are not called through
	 *      "mod.php" dispatcher, and in the install tool. The TYPO3_MOD_PATH defines the relative path to the typo3/
	 *      directory. This is taken as base to calculate the document root.
	 *   -- A script includes init.php and does not define 'TYPO3_MOD_PATH': This is the case for the mod.php dispatcher
	 *      and other entry scripts like 'cli_dispatch.phpsh' or 'thumbs.php' that are located parallel to init.php. In
	 *      this case init.php sets 'typo3/' as $relativePathPart as base to calculate the document root.
	 *
	 * This basically boils down to the following code:
	 * If TYPO3_MOD_PATH is defined, subtract this 'local' part from the entry point directory, else use
	 * $relativePathPart to subtract this from the the script entry point to find out the document root.
	 *
	 * @param string $relativePathPart Relative directory part from document root to script path if TYPO3_MOD_PATH is not used
	 * @return string Absolute path to document root of installation
	 */
	protected static function getPathSite($relativePathPart) {
			// If end of path is not "typo3/" and TYPO3_MOD_PATH is given
		if (defined('TYPO3_MOD_PATH')) {
			return self::getPathSiteByTypo3ModulePath();
		} else {
			return self::getPathSiteByRelativePathPart($relativePathPart);
		}
	}

	/**
	 * Calculate document root by TYPO3_MOD_PATH
	 *
	 * TYPO3_MOD_PATH can have the following values:
	 * - "sysext/extensionName/path/entryScript.php" -> extension is below 'docRoot'/typo3/sysext
	 * - "ext/extensionName/path/entryScript.php" -> extension is below 'docRoot'/typo3/ext
	 * - "../typo3conf/ext/extensionName/path/entryScript.php" -> extension is below 'docRoot'/typo3conf/ext
	 *- "install/index.php" -> install tool in 'docRoot'/typo3/install/
	 *
	 * The method unifies the above and subtracts the calculated path part from PATH_thisScript
	 *
	 * @return string Absolute path to document root of installation
	 */
	protected static function getPathSiteByTypo3ModulePath() {
		if (
			substr(TYPO3_MOD_PATH, 0, strlen('sysext/')) === 'sysext/'
			|| substr(TYPO3_MOD_PATH, 0, strlen('ext/')) === 'ext/'
			|| substr(TYPO3_MOD_PATH, 0, strlen('install/')) === 'install/'
		) {
			$pathPartRelativeToDocumentRoot = TYPO3_mainDir . TYPO3_MOD_PATH;
		} elseif (substr(TYPO3_MOD_PATH, 0, strlen('../typo3conf/')) === '../typo3conf/') {
			$pathPartRelativeToDocumentRoot = substr(TYPO3_MOD_PATH, 3);
		} else {
			die('Unable to determine TYPO3 document root.');
		}

		$entryScriptDirectory = self::getUnifiedDirectoryNameWithTrailingSlash(PATH_thisScript);

		return substr($entryScriptDirectory, 0, -strlen($pathPartRelativeToDocumentRoot));
	}

	/**
	 * Find out document root by subtracting $relativePathPart from PATH_thisScript
	 *
	 * @param string $relativePathPart Relative part of script from document root
	 * @return string Absolute path to document root of installation
	 */
	protected static function getPathSiteByRelativePathPart($relativePathPart) {
		$entryScriptDirectory = self::getUnifiedDirectoryNameWithTrailingSlash(PATH_thisScript);
		if (strlen($relativePathPart) > 0) {
			$pathSite = substr($entryScriptDirectory, 0, -strlen($relativePathPart));
		} else {
			$pathSite = $entryScriptDirectory;
		}
		return $pathSite;
	}

	/**
	 * Remove file name from script path and unify for Windows and Unix
	 *
	 * @param string $absolutePath Absolute path to script
	 * @return string Directory name of script file location, unified for Windows and Unix
	 */
	protected static function getUnifiedDirectoryNameWithTrailingSlash($absolutePath) {
		$directory = dirname($absolutePath);
		if (TYPO3_OS === 'WIN') {
			$directory = str_replace('\\', '/', $directory);
		}
		return $directory . '/';
	}
}
?>