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
 * Testcase for class Typo3_Bootstrap_BaseSetup
 *
 * @author Christia Kuhn <lolli@schwarbu.ch>
 * @package TYPO3
 * @subpackage tests
 */
class Typo3_Bootstrap_BaseSetupTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS because of included ressource
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * Set up testcase
	 *
	 * @return void
	 */
	public function setUp() {
		$this->createAccessibleProxyClass();
	}

	/**
	 * Create a subclass of Typo3_Bootstrap_BaseSetup with
	 * protected methods made public
	 *
	 * @return void
	 */
	protected function createAccessibleProxyClass() {
		$className = 'Typo3_Bootstrap_BaseSetupAccessibleProxy';
		if (!class_exists($className, FALSE)) {
			eval(
				'class ' . $className . ' extends Typo3_Bootstrap_BaseSetup {' .
				'  public static function getPathThisScriptCli() {' .
				'    return parent::getPathThisScriptCli();' .
				'  }' .
				'  public static function getUnifiedDirectoryNameWithTrailingSlash($absolutePath) {' .
				'    return parent::getUnifiedDirectoryNameWithTrailingSlash($absolutePath);' .
				'  }' .
				'  public static function addCorePearPathToIncludePath() {' .
				'    return parent::addCorePearPathToIncludePath();' .
				'  }' .
				'  public static function initializeGlobalVariables() {' .
				'    return parent::initializeGlobalVariables();' .
				'  }' .
				'  public static function loadDefaultTypo3ConfVars() {' .
				'    return parent::loadDefaultTypo3ConfVars();' .
				'  }' .
				'  public static function initializeGlobalTimeTrackingVariables() {' .
				'    return parent::initializeGlobalTimeTrackingVariables();' .
				'  }' .
				'  public static function initializeBasicErrorReporting() {' .
				'    return parent::initializeBasicErrorReporting();' .
				'  }' .
				'}'
			);
		}
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliReadsLocalPartFromArgv() {
		$fakedLocalPart = uniqid('Test');
		$GLOBALS['_SERVER']['argv'][0] = $fakedLocalPart;
		$this->assertStringEndsWith($fakedLocalPart, Typo3_Bootstrap_BaseSetupAccessibleProxy::getPathThisScriptCli());
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliReadsLocalPartFromEnv() {
		$fakedLocalPart = uniqid('Test');
		unset($GLOBALS['_SERVER']['argv']);
		$GLOBALS['_ENV']['_'] = $fakedLocalPart;
		$this->assertStringEndsWith($fakedLocalPart, Typo3_Bootstrap_BaseSetupAccessibleProxy::getPathThisScriptCli());
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliReadsLocalPartFromServer() {
		$fakedLocalPart = uniqid('Test');
		unset($GLOBALS['_SERVER']['argv']);
		unset($GLOBALS['_ENV']['_']);
		$GLOBALS['_SERVER']['_'] = $fakedLocalPart;
		$this->assertStringEndsWith($fakedLocalPart, Typo3_Bootstrap_BaseSetupAccessibleProxy::getPathThisScriptCli());
	}

	/**
	 * @test
	 */
	public function getPathThisScriptCliAddsCurrentWorkingDirectoryFromServerEnvironmentToLocalPathOnUnix() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}

		$GLOBALS['_SERVER']['argv'][0] = 'foo';
		$fakedAbsolutePart = '/' . uniqid('Absolute') . '/';
		$_SERVER['PWD'] = $fakedAbsolutePart;
		$this->assertStringStartsWith($fakedAbsolutePart, Typo3_Bootstrap_BaseSetupAccessibleProxy::getPathThisScriptCli());
	}

	/**
	 * @test
	 */
	public function getUnifiedDirectoryNameWithTrailingSlashReturnsCorrectPathOnUnix() {
		if (TYPO3_OS === 'WIN') {
			$this->markTestSkipped('Test not available on Windows OS.');
		}
		$input = '/foo/bar/test.php';
		$expected = '/foo/bar/';
		$actual = Typo3_Bootstrap_BaseSetupAccessibleProxy::getUnifiedDirectoryNameWithTrailingSlash($input);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function addCorePearPathToIncludePathAddsTypo3ContribPearToPathAsFirstEntry() {
		$backupPath = get_include_path();
		Typo3_Bootstrap_BaseSetupAccessibleProxy::addCorePearPathToIncludePath();
		$actualValue = get_include_path();
		set_include_path($backupPath);
		$this->assertStringStartsWith(PATH_typo3 . 'contrib/pear/' . PATH_SEPARATOR, $actualValue);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesUnsetsGlobalErrorArray() {
		$GLOBALS['error'] = 'foo';
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalVariables();
		$this->assertFalse(isset($GLOBALS['error']));
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalClientArray() {
		unset($GLOBALS['CLIENT']);
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalVariables();
		$this->assertArrayHasKey('CLIENT', $GLOBALS);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalTypo3MiscArray() {
		unset($GLOBALS['TYPO3_MISC']);
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalVariables();
		$this->assertInternalType('array', $GLOBALS['TYPO3_MISC']);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalT3VarArray() {
		unset($GLOBALS['T3_VAR']);
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalVariables();
		$this->assertInternalType('array', $GLOBALS['T3_VAR']);
	}

	/**
	 * @test
	 */
	public function initializeGlobalVariablesSetsGlobalT3ServicesArray() {
		unset($GLOBALS['T3_SERVICES']);
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalVariables();
		$this->assertInternalType('array', $GLOBALS['T3_SERVICES']);
	}

	/**
	 * @test
	 */
	public function loadDefaultTypo3ConfVarsPopulatesTypo3ConfVarsArray() {
		unset($GLOBALS['TYPO3_CONF_VARS']);
		Typo3_Bootstrap_BaseSetupAccessibleProxy::loadDefaultTypo3ConfVars();
		$this->assertInternalType('array', $GLOBALS['TYPO3_CONF_VARS']);
	}

	/**
	 * Data provider for initializeGlobalTimeTrackingVariablesSetsGlobalVariables
	 *
	 * @return array
	 */
	public function initializeGlobalTimeTrackingVariablesSetsGlobalVariablesDataProvider() {
		return array(
			'PARSETIME_START' => array('PARSETIME_START'),
			'EXEC_TIME' => array('EXEC_TIME'),
			'ACCESS_TIME' => array('ACCESS_TIME'),
			'SIM_EXEC_TIME' => array('SIM_EXEC_TIME'),
			'SIM_ACCESS_TIME' => array('SIM_ACCESS_TIME'),
		);
	}

	/**
	 * @test
	 * @dataProvider initializeGlobalTimeTrackingVariablesSetsGlobalVariablesDataProvider
	 * @param string $variable Variable to check for in $GLOBALS
	 */
	public function initializeGlobalTimeTrackingVariablesSetsGlobalVariables($variable) {
		unset($GLOBALS[$variable]);
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalTimeTrackingVariables();
		$this->assertTrue(isset($GLOBALS[$variable]));
	}

	/**
	 * @test
	 */
	public function initializeGlobalTimeTrackingVariablesSetsGlobalTypo3MiscMicrotimeStart() {
		unset($GLOBALS['TYPO3_MISC']['microtime_start']);
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalTimeTrackingVariables();
		$this->assertTrue(isset($GLOBALS['TYPO3_MISC']['microtime_start']));
	}

	/**
	 * @test
	 */
	public function initializeGlobalTimeTrackingVariablesRoundsAccessTimeToSixtySeconds() {
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalTimeTrackingVariables();
		$this->assertEquals(0, $GLOBALS['ACCESS_TIME'] % 60);
	}

	/**
	 * @test
	 */
	public function initializeGlobalTimeTrackingVariablesRoundsSimAccessTimeToSixtySeconds() {
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeGlobalTimeTrackingVariables();
		$this->assertEquals(0, $GLOBALS['SIM_ACCESS_TIME'] % 60);
	}

	/**
	 * @test
	 */
	public function initializeBasicErrorReportingExcludesStrict() {
		$backupReporting = error_reporting();
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeBasicErrorReporting();
		$actualReporting = error_reporting();
		error_reporting($backupReporting);
		$this->assertEquals(0, $actualReporting & E_STRICT);
	}

	/**
	 * @test
	 */
	public function initializeBasicErrorReportingExcludesNotice() {
		$backupReporting = error_reporting();
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeBasicErrorReporting();
		$actualReporting = error_reporting();
		error_reporting($backupReporting);
		$this->assertEquals(0, $actualReporting & E_NOTICE);
	}

	/**
	 * @test
	 */
	public function initializeBasicErrorReportingExcludesDeprecated() {
		$backupReporting = error_reporting();
		Typo3_Bootstrap_BaseSetupAccessibleProxy::initializeBasicErrorReporting();
		$actualReporting = error_reporting();
		error_reporting($backupReporting);
		$this->assertEquals(0, $actualReporting & E_DEPRECATED);
	}
}
?>