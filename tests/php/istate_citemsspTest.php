<?php
use PHPUnit\Framework\TestCase;

// Test fucntions in includes/base_state_citems.inc.php
// Tests that need process isolation.

/**
  * @preserveGlobalState disabled
  * A necessary evil for tests touching UILang during TD Transition.
  * @runTestsInSeparateProcesses
  * Apparently the covers annotations are ignored whe the above necessary
  * evil is in effect. Will Add covers annotations once we get rid of
  * necessary evil.
  * @covers IPAddressCriteria
  */
class state_citemsSPTest extends TestCase {
	// Pre Test Setup.
	protected static $files;
	protected static $langs;
	protected static $UIL;
	protected static $db;

	// We are using a single TD file.
	// Share class instance as common test fixture.
	public static function setUpBeforeClass() {
		GLOBAL $BASE_path, $DBlib_path, $DBtype, $debug_mode, $alert_dbname,
			$alert_host, $alert_user, $alert_password, $alert_port,
			$db_connect_method, $db;
		$tf = __FUNCTION__;
		$ll = 'english';
		self::$langs = $ll;
		$lf = "$ll.lang.php";
		self::$files = $lf;
		$file = "$BASE_path/languages/$lf";
		if ($debug_mode > 1) {
			LogTC($tf,'language',$ll);
			LogTC($tf,'TD file',$file);
		}
		if ( class_exists('UILang') ){
			// Setup UI Language Object
			// Will throw error during TD transition.
			// Use error suppression @ symbol.
			self::assertInstanceOf('UILang',self::$UIL = @new UILang($ll),
				"Class for $ll not created."
			);
		}else{
			self::$files = $file;
		}
		// Setup DB System.
		$TRAVIS = getenv('TRAVIS');
		if (!$TRAVIS){ // Running on Local Test System.
			// Default Debian/Ubuntu location.
			$DBlib_path = '/usr/share/php/adodb';
			require('../database.php');
		}else{
			$ADO = getenv('ADODBPATH');
			if (!$ADO) {
				self::markTestIncomplete('Unable to setup ADODB');
			}else{
				$DBlib_path = "build/adodb/$ADO";
			}
			$DB = getenv('DB');
			if (!$DB){
				self::markTestIncomplete('Unable to get DB Engine.');
			}elseif ($DB == 'mysql' ){
				require('./tests/phpcommon/DB.mysql.php');
			}elseif ($DB == 'postgres' ){
				require('./tests/phpcommon/DB.pgsql.php');
			}else{
				self::markTestSkipped("CI Support unavialable for DB: $DB.");
			}
		}
		if (!isset($DBtype)){
			self::markTestIncomplete("Unable to Set DB: $DB.");
		}else{
			$alert_dbname='snort';
			// Setup DB Connection
			$db = NewBASEDBConnection($DBlib_path, $DBtype);
			// Check ADODB Sanity.
			// See: https://github.com/NathanGibbs3/BASE/issues/35
			if (ADODB_DIR != $DBlib_path ){
				self::markTestIncomplete(
					"Expected ADODB in location: $DBlib_path\n".
					"   Found ADODB in location: ".ADODB_DIR
				);
			}else{
				$db->baseDBConnect(
					$db_connect_method, $alert_dbname, $alert_host,
					$alert_port, $alert_user, $alert_password
				);
			}
			self::assertInstanceOf(
				'baseCon',
				$db,
				'DB Object Not Initialized.'
			);
			self::$db = $db;
		}
		// Issue #36 Cutout.
		// See: https://github.com/NathanGibbs3/BASE/issues/36
		$PHPV = GetPHPV();
		$PSM = getenv('SafeMode');
		if (version_compare($PHPV, '5.4', '<') && $PSM == 1){
			self::markTestSkipped();
		}
	}
	public static function tearDownAfterClass() {
		self::$UIL = null;
		self::$langs = null;
		self::$files = null;
		self::$db = null;
	}

	// Tests go here.
	public function testClassSignatureCriteriaFuncDescriptionReturnsNULL() {
		GLOBAL $UIL;
		if ( is_object(self::$UIL) ){
			$UIL = self::$UIL;
		}else{
			GLOBAL $BASE_installID;
			include_once(self::$files);
		}
		$db = self::$db;
		$cs = new CriteriaState('Unit_Test'); // Create Criteria State Object.
		$cst = $cs->criteria['sig']; // Porperty under test.
		$EEM = '';
		$this->assertEquals( // CSO Not Setup.
			$EEM,
			$cst->Description(''),
			'CSO Not Init Unexpected Return Value.'
		);
		// CSO Space
		$cst->criteria[0] = ' ';
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Space Unexpected Return Value.'
		);
		// CSO Blank Sig
		$cst->criteria[0] = '=';
		$cst->criteria[1] = '';
		$cst->criteria[2] = '=';
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Blank Sig Unexpected Return Value.'
		);
	}
	public function testClassSignatureCriteriaFuncDescription() {
		GLOBAL $UIL;
		if ( is_object(self::$UIL) ){
			$UIL = self::$UIL;
		}else{
			GLOBAL $BASE_installID;
			include_once(self::$files);
		}
		$db = self::$db;
		$cs = new CriteriaState('Unit_Test'); // Create Criteria State Object.
		$cst = $cs->criteria['sig']; // Porperty under test.
		// Common
		$Ts = 'Test Signature';
		$cst->criteria[1] = $Ts;
		$Pfx = 'Signature ';
		$Sfx = '&nbsp;&nbsp;<A HREF="Unit_Test?clear_criteria=sig&amp;clear_criteria_element=">...Clear...</A><br/>';
		// CSO INvalid
		$cst->criteria[0] = 'Invalid';
		$cst->criteria[2] = '=';
		$EEM = $Pfx.''.' "'.$Ts.'"'.$Sfx;
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Invalid Unexpected Return Value.'
		);
		// CSO INvalid2
		$cst->criteria[0] = '=';
		$cst->criteria[2] = 'Invalid';
		$EEM = $Pfx.''.' "'.$Ts.'"'.$Sfx;
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Invalid2 Unexpected Return Value.'
		);
		// CSO Equals
		$cst->criteria[0] = '=';
		$cst->criteria[2] = '=';
		$EEM = $Pfx.'='.' "'.$Ts.'"'.$Sfx;
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Equals Unexpected Return Value.'
		);
		// CSO Not Equals
		$cst->criteria[0] = '=';
		$cst->criteria[2] = '!=';
		$EEM = $Pfx.'!='.' "'.$Ts.'"'.$Sfx;
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Not Equals Unexpected Return Value.'
		);
		// CSO Containing
		$cst->criteria[0] = 'LIKE';
		$cst->criteria[2] = '=';
		$EEM = $Pfx.' contains '.' "'.$Ts.'"'.$Sfx;
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Contains Unexpected Return Value.'
		);
		// CSO Not containing
		$cst->criteria[0] = 'LIKE';
		$cst->criteria[2] = '!=';
		$EEM = $Pfx.' does not contain '.' "'.$Ts.'"'.$Sfx;
		$this->assertEquals(
			$EEM,
			$cst->Description(''),
			'CSO Not Contains Unexpected Return Value.'
		);
	}
	// Tests for Class IPAddressCriteria
	public function testClassIPAddressCriteriaConstruct(){
		GLOBAL $UIL;
		if ( is_object(self::$UIL) ){
			$UIL = self::$UIL;
		}else{
			GLOBAL $BASE_installID;
			include_once(self::$files);
		}
		$db = self::$db;
		$cs = 'Test';
		$URV = 'Unexpected Return Value.';
		$this->assertInstanceOf(
			'IPAddressCriteria',
			$tc = new IPAddressCriteria($db, $cs, 'Test',1),
			'Class Not Initialized.'
		);
		$this->assertEquals('Test', $tc->cs, $URV);
		$this->assertEquals('Test', $tc->export_name, $URV);
		$this->assertNull($tc->criteria, $URV);
		$this->assertNull($tc->value, $URV);
		$this->assertNull($tc->value1, $URV);
		$this->assertNull($tc->value2, $URV);
		$this->assertNull($tc->value3, $URV);
		$this->assertEquals(1, $tc->element_cnt, $URV);
		$this->assertEquals(0, $tc->criteria_cnt, $URV);
		$this->assertTrue(is_array($tc->valid_field_list), $URV);
	}
	// These functions in this class are NoOps.
	// Call them for Code Coverage purposes.
	public function testClassIPAddressCriteriaNoOpFuncs(){
		GLOBAL $UIL;
		if ( is_object(self::$UIL) ){
			$UIL = self::$UIL;
		}else{
			GLOBAL $BASE_installID;
			include_once(self::$files);
		}
		$db = self::$db;
		$cs = 'Test';
		$this->assertInstanceOf(
			'IPAddressCriteria',
			$tc = new IPAddressCriteria($db, $cs, 'Test',1),
			'Class Not Initialized.'
		);
		$tc->Clear();
		$tc->ToSQL();
	}


	// Add code to a function if needed.
	// Stop here and mark test incomplete.
	//$this->markTestIncomplete('Incomplete Test.');
}

?>
