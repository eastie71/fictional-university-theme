<?php
class wfConfig {
	const AUTOLOAD = 'yes';
	const DONT_AUTOLOAD = 'no';
	
	public static $diskCache = array();
	private static $diskCacheDisabled = false; //enables if we detect a write fail so we don't keep calling stat()
	private static $cacheDisableCheckDone = false;
	private static $table = false;
	private static $tableExists = true;
	private static $cache = array();
	private static $DB = false;
	private static $tmpFileHeader = "<?php\n/* Wordfence temporary file security header */\necho \"Nothing to see here!\\n\"; exit(0);\n?>";
	private static $tmpDirCache = false;
	public static $defaultConfig = array(
		//All exportable boolean options
		"checkboxes" => array(
			"alertOn_critical" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"alertOn_update" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"alertOn_warnings" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"alertOn_throttle" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"alertOn_block" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"alertOn_loginLockout" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"alertOn_lostPasswdForm" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"alertOn_adminLogin" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"alertOn_firstAdminLoginOnly" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"alertOn_nonAdminLogin" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"alertOn_firstNonAdminLoginOnly" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"alertOn_wordfenceDeactivated" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"liveTrafficEnabled" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"advancedCommentScanning" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"checkSpamIP" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"spamvertizeCheck" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"liveTraf_ignorePublishers" => array('value' => true, 'autoload' => self::AUTOLOAD),
			//"perfLoggingEnabled" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"scheduledScansEnabled" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"lowResourceScansEnabled" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"scansEnabled_checkGSB" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_checkHowGetIPs" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_core" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_themes" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"scansEnabled_plugins" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"scansEnabled_coreUnknown" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_malware" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_fileContents" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_fileContentsGSB" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_checkReadableConfig" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_suspectedFiles" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_posts" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_comments" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_suspiciousOptions" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_passwds" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_diskSpace" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_options" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_wpscan_fullPathDisclosure" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_wpscan_directoryListingEnabled" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_dns" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_scanImages" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"scansEnabled_highSense" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"scansEnabled_oldVersions" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"scansEnabled_suspiciousAdminUsers" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"liveActivityPauseEnabled" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"firewallEnabled" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"blockFakeBots" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"autoBlockScanners" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"loginSecurityEnabled" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"loginSec_lockInvalidUsers" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"loginSec_maskLoginErrors" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"loginSec_blockAdminReg" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"loginSec_disableAuthorScan" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"loginSec_disableOEmbedAuthor" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"notification_updatesNeeded" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"notification_securityAlerts" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"notification_promotions" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"notification_blogHighlights" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"notification_productUpdates" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"notification_scanStatus" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"other_hideWPVersion" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"other_noAnonMemberComments" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"other_blockBadPOST" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"other_scanComments" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"other_pwStrengthOnUpdate" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"other_WFNet" => array('value' => true, 'autoload' => self::AUTOLOAD),
			"other_scanOutside" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"other_bypassLitespeedNoabort" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"deleteTablesOnDeact" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"autoUpdate" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"disableCookies" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"startScansRemotely" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"disableConfigCaching" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"addCacheComment" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"disableCodeExecutionUploads" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"allowHTTPSCaching" => array('value' => false, 'autoload' => self::AUTOLOAD),
			"debugOn" => array('value' => false, 'autoload' => self::AUTOLOAD),
			'email_summary_enabled' => array('value' => true, 'autoload' => self::AUTOLOAD),
			'email_summary_dashboard_widget_enabled' => array('value' => true, 'autoload' => self::AUTOLOAD),
			'ssl_verify' => array('value' => true, 'autoload' => self::AUTOLOAD),
			'ajaxWatcherDisabled_front' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'ajaxWatcherDisabled_admin' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'wafAlertOnAttacks' => array('value' => true, 'autoload' => self::AUTOLOAD),
			'disableWAFIPBlocking' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'showAdminBarMenu' => array('value' => true, 'autoload' => self::AUTOLOAD),
		),
		//All exportable variable type options
		"otherParams" => array(
			"scan_include_extra" => array('value' => "", 'autoload' => self::AUTOLOAD),
			"alertEmails" => array('value' => "", 'autoload' => self::AUTOLOAD), 
			"liveTraf_ignoreUsers" => array('value' => "", 'autoload' => self::AUTOLOAD), 
			"liveTraf_ignoreIPs" => array('value' => "", 'autoload' => self::AUTOLOAD), 
			"liveTraf_ignoreUA" => array('value' => "", 'autoload' => self::AUTOLOAD),  
			"apiKey" => array('value' => "", 'autoload' => self::AUTOLOAD), 
			"maxMem" => array('value' => '256', 'autoload' => self::AUTOLOAD), 
			'scan_exclude' => array('value' => '', 'autoload' => self::AUTOLOAD), 
			'scan_maxIssues' => array('value' => 1000, 'autoload' => self::AUTOLOAD), 
			'scan_maxDuration' => array('value' => '', 'autoload' => self::AUTOLOAD), 
			'whitelisted' => array('value' => '', 'autoload' => self::AUTOLOAD), 
			'bannedURLs' => array('value' => '', 'autoload' => self::AUTOLOAD), 
			'maxExecutionTime' => array('value' => '', 'autoload' => self::AUTOLOAD), 
			'howGetIPs' => array('value' => '', 'autoload' => self::AUTOLOAD), 
			'actUpdateInterval' => array('value' => '', 'autoload' => self::AUTOLOAD), 
			'alert_maxHourly' => array('value' => 0, 'autoload' => self::AUTOLOAD), 
			'loginSec_userBlacklist' => array('value' => '', 'autoload' => self::AUTOLOAD),
			'liveTraf_maxRows' => array('value' => 2000, 'autoload' => self::AUTOLOAD),
			"neverBlockBG" => array('value' => "neverBlockVerified", 'autoload' => self::AUTOLOAD),
			"loginSec_countFailMins" => array('value' => "240", 'autoload' => self::AUTOLOAD),
			"loginSec_lockoutMins" => array('value' => "240", 'autoload' => self::AUTOLOAD),
			'loginSec_strongPasswds' => array('value' => 'pubs', 'autoload' => self::AUTOLOAD),
			'loginSec_maxFailures' => array('value' => "20", 'autoload' => self::AUTOLOAD),
			'loginSec_maxForgotPasswd' => array('value' => "20", 'autoload' => self::AUTOLOAD),
			'maxGlobalRequests' => array('value' => "DISABLED", 'autoload' => self::AUTOLOAD),
			'maxGlobalRequests_action' => array('value' => "throttle", 'autoload' => self::AUTOLOAD),
			'maxRequestsCrawlers' => array('value' => "DISABLED", 'autoload' => self::AUTOLOAD),
			'maxRequestsCrawlers_action' => array('value' => "throttle", 'autoload' => self::AUTOLOAD),
			'maxRequestsHumans' => array('value' => "DISABLED", 'autoload' => self::AUTOLOAD),
			'maxRequestsHumans_action' => array('value' => "throttle", 'autoload' => self::AUTOLOAD),
			'max404Crawlers' => array('value' => "DISABLED", 'autoload' => self::AUTOLOAD),
			'max404Crawlers_action' => array('value' => "throttle", 'autoload' => self::AUTOLOAD),
			'max404Humans' => array('value' => "DISABLED", 'autoload' => self::AUTOLOAD),
			'max404Humans_action' => array('value' => "throttle", 'autoload' => self::AUTOLOAD),
			'maxScanHits' => array('value' => "DISABLED", 'autoload' => self::AUTOLOAD),
			'maxScanHits_action' => array('value' => "throttle", 'autoload' => self::AUTOLOAD),
			'blockedTime' => array('value' => "300", 'autoload' => self::AUTOLOAD),
			'email_summary_interval' => array('value' => 'weekly', 'autoload' => self::AUTOLOAD),
			'email_summary_excluded_directories' => array('value' => 'wp-content/cache,wp-content/wflogs', 'autoload' => self::AUTOLOAD),
			'allowed404s' => array('value' => "/favicon.ico\n/apple-touch-icon*.png\n/*@2x.png\n/browserconfig.xml", 'autoload' => self::AUTOLOAD),
			'wafAlertWhitelist' => array('value' => '', 'autoload' => self::AUTOLOAD),
			'wafAlertInterval' => array('value' => 600, 'autoload' => self::AUTOLOAD),
			'wafAlertThreshold' => array('value' => 100, 'autoload' => self::AUTOLOAD),
			'howGetIPs_trusted_proxies' => array('value' => '', 'autoload' => self::AUTOLOAD),
		),
		//Set as default only, not included automatically in the settings import/export or options page saving
		'defaultsOnly' => array(
			'cbl_loggedInBlocked' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'cbl_loginFormBlocked' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'cbl_restOfSiteBlocked' => array('value' => true, 'autoload' => self::AUTOLOAD),
			'isPaid' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'hasKeyConflict' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'betaThreatDefenseFeed' => array('value' => false, 'autoload' => self::AUTOLOAD),
			'cbl_action' => array('value' => 'block', 'autoload' => self::AUTOLOAD),
			'cbl_redirURL' => array('value' => '', 'autoload' => self::AUTOLOAD),
			'cbl_bypassRedirURL' => array('value' => '', 'autoload' => self::AUTOLOAD),
			'cbl_bypassRedirDest' => array('value' => '', 'autoload' => self::AUTOLOAD),
			'cbl_bypassViewURL' => array('value' => '', 'autoload' => self::AUTOLOAD),
			'cbl_countries' => array('value' => '', 'autoload' => self::AUTOLOAD),
			'timeoffset_wf_updated' => array('value' => 0, 'autoload' => self::AUTOLOAD),
			'cacheType' => array('value' => 'disabled', 'autoload' => self::AUTOLOAD),
			'detectProxyRecommendation' => array('value' => '', 'autoload' => self::DONT_AUTOLOAD),
			'loginSec_enableSeparateTwoFactor' => array('value' => false, 'autoload' => self::AUTOLOAD),
		),
	);
	public static $serializedOptions = array('lastAdminLogin', 'scanSched', 'emailedIssuesList', 'wf_summaryItems', 'adminUserList', 'twoFactorUsers', 'alertFreqTrack', 'wfStatusStartMsgs', 'vulnerabilities_plugin', 'vulnerabilities_theme', 'dashboardData', 'malwarePrefixes', 'noc1ScanSchedule', 'allScansScheduled');
	public static function setDefaults() {
		foreach (self::$defaultConfig['checkboxes'] as $key => $config) {
			$val = $config['value'];
			$autoload = $config['autoload'];
			if (self::get($key) === false) {
				self::set($key, $val ? '1' : '0', $autoload);
			}
		}
		foreach (self::$defaultConfig['otherParams'] as $key => $config) {
			$val = $config['value'];
			$autoload = $config['autoload'];
			if (self::get($key) === false) {
				self::set($key, $val, $autoload);
			}
		}
		foreach (self::$defaultConfig['defaultsOnly'] as $key => $config) {
			$val = $config['value'];
			$autoload = $config['autoload'];
			if (self::get($key) === false) {
				if ($val === false) {
					self::set($key, '0', $autoload);
				}
				else if ($val === true) {
					self::set($key, '1', $autoload);
				}
				else {
					self::set($key, $val, $autoload);
				}
			}
		}
		self::set('encKey', substr(wfUtils::bigRandomHex(), 0, 16));
		if (self::get('maxMem', false) === false) {
			self::set('maxMem', '256');
		}
		if (self::get('other_scanOutside', false) === false) {
			self::set('other_scanOutside', 0);
		}

		if (self::get('email_summary_enabled')) {
			wfActivityReport::scheduleCronJob();
		} else {
			wfActivityReport::disableCronJob();
		}
	}
	public static function loadAllOptions() {
		global $wpdb;
		
		$options = wp_cache_get('alloptions', 'wordfence');
		if (!$options) {
			$table = self::table();
			self::updateTableExists();
			$suppress = $wpdb->suppress_errors();
			if (!($rawOptions = $wpdb->get_results("SELECT name, val FROM {$table} WHERE autoload = 'yes'"))) {
				$rawOptions = $wpdb->get_results("SELECT name, val FROM {$table}");
			}
			$wpdb->suppress_errors($suppress);
			$options = array();
			foreach ((array) $rawOptions as $o) {
				if (in_array($o->name, self::$serializedOptions)) {
					$val = maybe_unserialize($o->val);
					if ($val) {
						$options[$o->name] = $val;
					}
				}
				else {
					$options[$o->name] = $o->val;
				}
			}
			
			wp_cache_add_non_persistent_groups('wordfence');
			wp_cache_add('alloptions', $options, 'wordfence');
		}
		
		return $options;
	}
	public static function updateTableExists() {
		global $wpdb;
		self::$tableExists = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME=%s
SQL
			, self::table()));
	}
	private static function updateCachedOption($name, $val) {
		$options = self::loadAllOptions();
		$options[$name] = $val;
		wp_cache_set('alloptions', $options, 'wordfence');
	}
	private static function removeCachedOption($name) {
		$options = self::loadAllOptions();
		if (isset($options[$name])) {
			unset($options[$name]);
			wp_cache_set('alloptions', $options, 'wordfence');
		}
	}
	private static function getCachedOption($name) {
		$options = self::loadAllOptions();
		if (isset($options[$name])) {
			return $options[$name];
		}
		
		$table = self::table();
		$val = self::getDB()->querySingle("SELECT val FROM {$table} WHERE name='%s'", $name);
		if ($val !== null) {
			$options[$name] = $val;
			wp_cache_set('alloptions', $options, 'wordfence');
		}
		return $val;
	}
	private static function hasCachedOption($name) {
		$options = self::loadAllOptions();
		return isset($options[$name]);
	}
	public static function getExportableOptionsKeys(){
		$ret = array();
		foreach(self::$defaultConfig['checkboxes'] as $key => $val){
			$ret[] = $key;
		}
		foreach(self::$defaultConfig['otherParams'] as $key => $val){
			if($key != 'apiKey'){
				$ret[] = $key;
			}
		}
		foreach(array('cbl_action', 'cbl_countries', 'cbl_redirURL', 'cbl_loggedInBlocked', 'cbl_loginFormBlocked', 'cbl_restOfSiteBlocked', 'cbl_bypassRedirURL', 'cbl_bypassRedirDest', 'cbl_bypassViewURL') as $key){
			$ret[] = $key;
		}
		return $ret;
	}
	public static function parseOptions($excludeOmitted = false) {
		$ret = array();
		foreach (self::$defaultConfig['checkboxes'] as $key => $val) { //value is not used. We just need the keys for validation
			if ($excludeOmitted && isset($_POST[$key])) {
				$ret[$key] = (int) $_POST[$key];
			}
			else if (!$excludeOmitted || isset($_POST[$key])) {
				$ret[$key] = isset($_POST[$key]) ? '1' : '0';
			}
		}
		foreach (self::$defaultConfig['otherParams'] as $key => $val) {
			if (!$excludeOmitted || isset($_POST[$key])) {
				if (isset($_POST[$key])) {
					$ret[$key] = stripslashes($_POST[$key]);
				}
				else {
					error_log("Missing options param \"$key\" when parsing parameters.");
				}
			}
		}
		/* for debugging only:
		foreach($_POST as $key => $val){
			if($key != 'action' && $key != 'nonce' && (! array_key_exists($key, self::$checkboxes)) && (! array_key_exists($key, self::$otherParams)) ){
				error_log("Unrecognized option: $key");
			}
		}
		*/
		return $ret;
	}
	public static function setArray($arr){
		foreach($arr as $key => $val){
			self::set($key, $val);
		}
	}
	public static function getHTML($key){
		return esc_html(self::get($key));
	}
	public static function inc($key){
		$val = self::get($key, false);
		if(! $val){
			$val = 0;
		}
		self::set($key, $val + 1);
		return $val + 1;
	}
	public static function atomicInc($key) {
		if (!self::$tableExists) {
			return false;
		}
		
		global $wpdb;
		$old_suppress_errors = $wpdb->suppress_errors(true);
		$table = self::table();
		$rowExists = false;
		do {
			if (!$rowExists && $wpdb->query($wpdb->prepare("INSERT INTO {$table} (name, val, autoload) values (%s, %s, %s)", $key, 1, self::DONT_AUTOLOAD))) {
				$val = 1;
				$successful = true;
			}
			else {
				$rowExists = true;
				$val = self::get($key, 1);
				if ($wpdb->query($wpdb->prepare("UPDATE {$table} SET val = %s WHERE name = %s AND val = %s", $val + 1, $key, $val))) {
					$val++;
					$successful = true;
				}
			}
		} while (!$successful);
		$wpdb->suppress_errors($old_suppress_errors);
		return $val;
	}
	public static function set($key, $val, $autoload = self::AUTOLOAD) {
		global $wpdb;
		
		if (is_array($val)) {
			$msg = "wfConfig::set() got an array as second param with key: $key and value: " . var_export($val, true);
			wordfence::status(1, 'error', $msg);
			return;
		}

		if (($key == 'apiKey' || $key == 'isPaid' || $key == 'other_WFNet') && wfWAF::getInstance() && !WFWAF_SUBDIRECTORY_INSTALL) {
			if ($key == 'isPaid' || $key == 'other_WFNet') {
				$val = !!$val;
			}
			
			try {
				wfWAF::getInstance()->getStorageEngine()->setConfig($key, $val);
			} catch (wfWAFStorageFileException $e) {
				error_log($e->getMessage());
			}
		}
		
		if (!self::$tableExists) {
			return;
		
		}
		$table = self::table();
		if ($wpdb->query($wpdb->prepare("INSERT INTO {$table} (name, val, autoload) values (%s, %s, %s) ON DUPLICATE KEY UPDATE val = %s, autoload = %s", $key, $val, $autoload, $val, $autoload)) !== false && $autoload != self::DONT_AUTOLOAD) {
			self::updateCachedOption($key, $val);
		}
		
		if (!WFWAF_SUBDIRECTORY_INSTALL && class_exists('wfWAFIPBlocksController') && (substr($key, 0, 4) == 'cbl_' || $key == 'blockedTime' || $key == 'disableWAFIPBlocking')) {
			wfWAFIPBlocksController::synchronizeConfigSettings();
		} 
	}
	public static function get($key, $default = false, $allowCached = true) {
		global $wpdb;
		
		if ($allowCached && self::hasCachedOption($key)) {
			return self::getCachedOption($key);
		}
		
		if (!self::$tableExists) {
			return $default;
		}
		
		$table = self::table();
		if (!($option = $wpdb->get_row($wpdb->prepare("SELECT name, val, autoload FROM {$table} WHERE name = %s", $key)))) {
			return $default;
		}
		
		if ($option->autoload != self::DONT_AUTOLOAD) {
			self::updateCachedOption($key, $option->val);
		}
		return $option->val;
	}
	
	private static function canCompressValue() {
		if (!function_exists('gzencode') || !function_exists('gzdecode')) {
			return false;
		}
		$disabled = explode(',', ini_get('disable_functions'));
		if (in_array('gzencode', $disabled) || in_array('gzdecode', $disabled)) {
			return false;
		}
		return true;
	}
	
	private static function isCompressedValue($data) {
		//Based on http://www.ietf.org/rfc/rfc1952.txt
		if (strlen($data) < 2) {
			return false;
		}
		
		$magicBytes = substr($data, 0, 2);
		if ($magicBytes !== (chr(0x1f) . chr(0x8b))) {
			return false;
		}
		
		//Small chance of false positives here -- can check the header CRC if it turns out it's needed
		return true;
	}
	
	private static function ser_chunked_key($key) {
		return 'wordfence_chunked_' . $key . '_';
	}
	
	public static function get_ser($key, $default = false, $cache = true) {
		if (self::hasCachedOption($key)) {
			return self::getCachedOption($key);
		}
		
		if (!self::$tableExists) {
			return $default;
		}
		
		//Check for a chunked value first
		$chunkedValueKey = self::ser_chunked_key($key);
		$header = self::getDB()->querySingle("select val from " . self::table() . " where name=%s", $chunkedValueKey . 'header');
		if ($header) {
			$header = unserialize($header);
			$count = $header['count'];
			$path = tempnam(sys_get_temp_dir(), $key); //Writing to a file like this saves some of PHP's in-memory copying when just appending each chunk to a string
			$fh = fopen($path, 'r+');
			$length = 0;
			for ($i = 0; $i < $count; $i++) {
				$chunk = self::getDB()->querySingle("select val from " . self::table() . " where name=%s", $chunkedValueKey . $i);
				self::getDB()->flush(); //clear cache
				if (!$chunk) {
					wordfence::status(2, 'error', "Error reassembling value for {$key}");
					return $default;
				}
				fwrite($fh, $chunk);
				$length += strlen($chunk);
				unset($chunk);
			}
			
			fseek($fh, 0);
			$serialized = fread($fh, $length);
			fclose($fh);
			unlink($path);
			
			if (self::canCompressValue() && self::isCompressedValue($serialized)) {
				$inflated = @gzdecode($serialized);
				if ($inflated !== false) {
					unset($serialized);
					if ($cache) {
						self::updateCachedOption($key, unserialize($inflated));
						return self::getCachedOption($key);
					}
					return unserialize($inflated);
				}
			}
			if ($cache) {
				self::updateCachedOption($key, unserialize($serialized));
				return self::getCachedOption($key);
			}
			return unserialize($serialized);
		}
		else {
			$serialized = self::getDB()->querySingle("select val from " . self::table() . " where name=%s", $key);
			self::getDB()->flush(); //clear cache
			if ($serialized) {
				if (self::canCompressValue() && self::isCompressedValue($serialized)) {
					$inflated = @gzdecode($serialized);
					if ($inflated !== false) {
						unset($serialized);
						return unserialize($inflated);
					}
				}
				if ($cache) {
					self::updateCachedOption($key, unserialize($serialized));
					return self::getCachedOption($key);
				}
				return unserialize($serialized);
			}
		}
		
		return $default;
	}
	
	public static function set_ser($key, $val, $allowCompression = false, $autoload = self::AUTOLOAD) {
		/*
		 * Because of the small default value for `max_allowed_packet` and `max_long_data_size`, we're stuck splitting
		 * large values into multiple chunks. To minimize memory use, the MySQLi driver is used directly when possible.
		 */
		
		global $wpdb;
		$dbh = $wpdb->dbh;
		$useMySQLi = (is_object($dbh) && $wpdb->use_mysqli);
		
		if (!self::$tableExists) {
			return;
		}
		
		self::delete_ser_chunked($key); //Ensure any old values for a chunked value are deleted first
		
		if (self::canCompressValue() && $allowCompression) {
			$data = gzencode(serialize($val));
		}
		else {
			$data = serialize($val);
		}
		
		if (!$useMySQLi) {
			$data = bin2hex($data);
		}
		
		$dataLength = strlen($data);
		$maxAllowedPacketBytes = self::getDB()->getMaxAllowedPacketBytes();
		$chunkSize = intval((($maxAllowedPacketBytes < 1024 /* MySQL minimum, probably failure to fetch it */ ? 1024 * 1024 /* MySQL default */ : $maxAllowedPacketBytes) - 50) / 1.2); //Based on max_allowed_packet + 20% for escaping and SQL
		$chunkSize = $chunkSize - ($chunkSize % 2); //Ensure it's even
		$chunkedValueKey = self::ser_chunked_key($key);
		if ($dataLength > $chunkSize) {
			$chunks = 0;
			while (($chunks * $chunkSize) < $dataLength) {
				$dataChunk = substr($data, $chunks * $chunkSize, $chunkSize);
				if ($useMySQLi) {
					$chunkKey = $chunkedValueKey . $chunks;
					$stmt = $dbh->prepare("INSERT IGNORE INTO " . self::table() . " (name, val, autoload) VALUES (?, ?, 'no')");
					if ($stmt === false) {
						wordfence::status(2, 'error', "Error writing value chunk for {$key} (MySQLi error: [{$dbh->errno}] {$dbh->error})");
						return false;
					}
					$null = NULL;
					$stmt->bind_param("sb", $chunkKey, $null);
					
					if (!$stmt->send_long_data(1, $dataChunk)) {
						wordfence::status(2, 'error', "Error writing value chunk for {$key} (MySQLi error: [{$dbh->errno}] {$dbh->error})");
						return false;
					}
					
					if (!$stmt->execute()) {
						wordfence::status(2, 'error', "Error finishing writing value for {$key} (MySQLi error: [{$dbh->errno}] {$dbh->error})");
						return false;
					}
				}
				else {
					if (!self::getDB()->queryWrite(sprintf("insert ignore into " . self::table() . " (name, val, autoload) values (%%s, X'%s', 'no')", $dataChunk), $chunkedValueKey . $chunks)) {
						$errno = mysql_errno($wpdb->dbh);
						wordfence::status(2, 'error', "Error writing value chunk for {$key} (MySQL error: [$errno] {$wpdb->last_error})");
						return false;
					}
				}
				$chunks++;
			}
			
			if (!self::getDB()->queryWrite(sprintf("insert ignore into " . self::table() . " (name, val, autoload) values (%%s, X'%s', 'no')", bin2hex(serialize(array('count' => $chunks)))), $chunkedValueKey . 'header')) {
				wordfence::status(2, 'error', "Error writing value header for {$key}");
				return false;
			}
		}
		else {
			$exists = self::getDB()->querySingle("select name from " . self::table() . " where name='%s'", $key);
			
			if ($useMySQLi) {
				if ($exists) {
					$stmt = $dbh->prepare("UPDATE " . self::table() . " SET val=? WHERE name=?");
					if ($stmt === false) {
						wordfence::status(2, 'error', "Error writing value for {$key} (MySQLi error: [{$dbh->errno}] {$dbh->error})");
						return false;
					}
					$null = NULL;
					$stmt->bind_param("bs", $null, $key);
				}
				else {
					$stmt = $dbh->prepare("INSERT IGNORE INTO " . self::table() . " (val, name, autoload) VALUES (?, ?, ?)");
					if ($stmt === false) {
						wordfence::status(2, 'error', "Error writing value for {$key} (MySQLi error: [{$dbh->errno}] {$dbh->error})");
						return false;
					}
					$null = NULL;
					$stmt->bind_param("bss", $null, $key, $autoload);
				}
				
				if (!$stmt->send_long_data(0, $data)) {
					wordfence::status(2, 'error', "Error writing value for {$key} (MySQLi error: [{$dbh->errno}] {$dbh->error})");
					return false;
				}
				
				if (!$stmt->execute()) {
					wordfence::status(2, 'error', "Error finishing writing value for {$key} (MySQLi error: [{$dbh->errno}] {$dbh->error})");
					return false;
				}
			}
			else {
				if ($exists) {
					self::getDB()->queryWrite(sprintf("update " . self::table() . " set val=X'%s' where name=%%s", $data), $key);
				}
				else {
					self::getDB()->queryWrite(sprintf("insert ignore into " . self::table() . " (name, val, autoload) values (%%s, X'%s', %%s)", $data), $key, $autoload);
				}
			}
		}
		self::getDB()->flush();
		
		if ($autoload != self::DONT_AUTOLOAD) {
			self::updateCachedOption($key, $val);
		}
		return true;
	}
	
	private static function delete_ser_chunked($key) {
		if (!self::$tableExists) {
			return;
		}
		
		self::removeCachedOption($key);
		
		$chunkedValueKey = self::ser_chunked_key($key);
		$header = self::getDB()->querySingle("select val from " . self::table() . " where name=%s", $chunkedValueKey . 'header');
		if (!$header) {
			return;
		}
		
		$header = unserialize($header);
		$count = $header['count'];
		for ($i = 0; $i < $count; $i++) {
			self::getDB()->queryWrite("delete from " . self::table() . " where name='%s'", $chunkedValueKey . $i);
		}
		self::getDB()->queryWrite("delete from " . self::table() . " where name='%s'", $chunkedValueKey . 'header');
	}
	public static function f($key){
		echo esc_attr(self::get($key));
	}
	public static function p() {
		return self::get('isPaid');
	}
	public static function cbp($key){
		if(self::get('isPaid') && self::get($key)){
			echo ' checked ';
		}
	}
	public static function cb($key){
		if(self::get($key)){
			echo ' checked ';
		}
	}
	public static function sel($key, $val, $isDefault = false){
		if((! self::get($key)) && $isDefault){ echo ' selected '; }
		if(self::get($key) == $val){ echo ' selected '; }
	}
	private static function getDB(){
		if(! self::$DB){ 
			self::$DB = new wfDB();
		}
		return self::$DB;
	}
	private static function table(){
		if(! self::$table){
			global $wpdb;
			self::$table = $wpdb->base_prefix . 'wfConfig';
		}
		return self::$table;
	}
	public static function haveAlertEmails(){
		$emails = self::getAlertEmails();
		return sizeof($emails) > 0 ? true : false;
	}
	public static function getAlertEmails(){
		$dat = explode(',', self::get('alertEmails'));
		$emails = array();
		foreach($dat as $email){
			if(preg_match('/\@/', $email)){
				$emails[] = trim($email);
			}
		}
		return $emails;
	}
	public static function getAlertLevel(){
		if(self::get('alertOn_warnings')){
			return 2;
		} else if(self::get('alertOn_critical')){
			return 1;
		} else {
			return 0;
		}
	}
	public static function liveTrafficEnabled(&$overriden = null){
		$enabled = self::get('liveTrafficEnabled');
		if (WORDFENCE_DISABLE_LIVE_TRAFFIC || function_exists('wpe_site')) {
			$enabled = false;
			if ($overriden !== null) {
				$overriden = true;
			}
		}
		return $enabled;
	}
	public static function enableAutoUpdate(){
		wfConfig::set('autoUpdate', '1');
		wp_clear_scheduled_hook('wordfence_daily_autoUpdate');
		if (is_main_site()) {
			wp_schedule_event(time(), 'daily', 'wordfence_daily_autoUpdate');
		}
	}
	public static function disableAutoUpdate(){
		wfConfig::set('autoUpdate', '0');	
		wp_clear_scheduled_hook('wordfence_daily_autoUpdate');
	}
	public static function createLock($name, $timeout = null) { //Polyfill since WP's built-in version wasn't added until 4.5
		global $wpdb;
		$oldBlogID = $wpdb->set_blog_id(0);
		
		if (function_exists('WP_Upgrader::create_lock')) {
			$result = WP_Upgrader::create_lock($name, $timeout);
			$wpdb->set_blog_id($oldBlogID);
			return $result;
		}
		
		if (!$timeout) {
			$timeout = 3600;
		}
		
		$lock_option = $name . '.lock';
		$lock_result = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `{$wpdb->options}` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, 'no') /* LOCK */", $lock_option, time()));
		
		if (!$lock_result) {
			$lock_result = get_option($lock_option);
			if (!$lock_result) {
				$wpdb->set_blog_id($oldBlogID);
				return false;
			}
			
			if ($lock_result > (time() - $timeout)) {
				$wpdb->set_blog_id($oldBlogID);
				return false;
			}
			
			self::releaseLock($name);
			$wpdb->set_blog_id($oldBlogID);
			return self::createLock($name, $timeout);
		}
		
		update_option($lock_option, time());
		$wpdb->set_blog_id($oldBlogID);
		return true;
	}
	public static function releaseLock($name) {
		global $wpdb;
		$oldBlogID = $wpdb->set_blog_id(0);
		if (function_exists('WP_Upgrader::release_lock')) {
			$result = WP_Upgrader::release_lock($name);
		}
		else {
			$result = delete_option($name . '.lock');
		}
		
		$wpdb->set_blog_id($oldBlogID);
		return $result;
	}
	public static function autoUpdate(){
		try {
			if (!wfConfig::get('other_bypassLitespeedNoabort', false) && getenv('noabort') != '1' && stristr($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false) {
				$lastEmail = self::get('lastLiteSpdEmail', false);
				if( (! $lastEmail) || (time() - (int)$lastEmail > (86400 * 30))){
					self::set('lastLiteSpdEmail', time());
					 wordfence::alert("Wordfence Upgrade not run. Please modify your .htaccess", "To preserve the integrity of your website we are not running Wordfence auto-update.\n" .
						"You are running the LiteSpeed web server which has been known to cause a problem with Wordfence auto-update.\n" .
						"Please go to your website now and make a minor change to your .htaccess to fix this.\n" .
						"You can find out how to make this change at:\n" .
						"https://docs.wordfence.com/en/LiteSpeed_aborts_Wordfence_scans_and_updates._How_do_I_prevent_that%3F\n" .
						"\nAlternatively you can disable auto-update on your website to stop receiving this message and upgrade Wordfence manually.\n",
						'127.0.0.1'
						);
				}
				return;
			}
			require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
			require_once(ABSPATH . 'wp-admin/includes/misc.php');
			/* We were creating show_message here so that WP did not write to STDOUT. This had the strange effect of throwing an error about redeclaring show_message function, but only when a crawler hit the site and triggered the cron job. Not a human. So we're now just require'ing misc.php which does generate output, but that's OK because it is a loopback cron request.  
			if(! function_exists('show_message')){ 
				function show_message($msg = 'null'){}
			}
			*/
			if(! defined('FS_METHOD')){ 
				define('FS_METHOD', 'direct'); //May be defined already and might not be 'direct' so this could cause problems. But we were getting reports of a warning that this is already defined, so this check added. 
			}
			require_once(ABSPATH . 'wp-includes/update.php');
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			
			if (!self::createLock('wfAutoUpdate')) {
				return;
			}
			
			wp_update_plugins();
			ob_start();
			$upgrader = new Plugin_Upgrader();
			$upret = $upgrader->upgrade(WORDFENCE_BASENAME);
			if($upret){
				$cont = file_get_contents(WORDFENCE_FCPATH);
				if(wfConfig::get('alertOn_update') == '1' && preg_match('/Version: (\d+\.\d+\.\d+)/', $cont, $matches) ){
					wordfence::alert("Wordfence Upgraded to version " . $matches[1], "Your Wordfence installation has been upgraded to version " . $matches[1], '127.0.0.1');
				}
			}
			$output = @ob_get_contents();
			@ob_end_clean();
		} catch(Exception $e){}
		
		self::releaseLock('wfAutoUpdate');
	}
	
	/**
	 * .htaccess file contents to disable all script execution in a given directory.
	 */
	private static $_disable_scripts_htaccess = '# BEGIN Wordfence code execution protection
<IfModule mod_php5.c>
php_flag engine 0
</IfModule>
<IfModule mod_php7.c>
php_flag engine 0
</IfModule>

AddHandler cgi-script .php .phtml .php3 .pl .py .jsp .asp .htm .shtml .sh .cgi
Options -ExecCGI
# END Wordfence code execution protection
';
	private static $_disable_scripts_regex = '/# BEGIN Wordfence code execution protection.+?# END Wordfence code execution protection/s';
	
	private static function _uploadsHtaccessFilePath() {
		$upload_dir = wp_upload_dir();
		return $upload_dir['basedir'] . '/.htaccess';
	}

	/**
	 * Add/Merge .htaccess file in the uploads directory to prevent code execution.
	 *
	 * @return bool
	 * @throws wfConfigException
	 */
	public static function disableCodeExecutionForUploads() {
		$uploads_htaccess_file_path = self::_uploadsHtaccessFilePath();
		$uploads_htaccess_has_content = false;
		if (file_exists($uploads_htaccess_file_path)) {
			$htaccess_contents = file_get_contents($uploads_htaccess_file_path);
			
			// htaccess exists and contains our htaccess code to disable script execution, nothing more to do
			if (strpos($htaccess_contents, self::$_disable_scripts_htaccess) !== false) {
				return true;
			}
			$uploads_htaccess_has_content = strlen(trim($htaccess_contents)) > 0;
		}
		if (@file_put_contents($uploads_htaccess_file_path, ($uploads_htaccess_has_content ? "\n\n" : "") . self::$_disable_scripts_htaccess, FILE_APPEND | LOCK_EX) === false) {
			throw new wfConfigException("Unable to save the .htaccess file needed to disable script execution in the uploads directory.  Please check your permissions on that directory.");
		}
		self::set('disableCodeExecutionUploadsPHP7Migrated', true);
		return true;
	}
	
	public static function migrateCodeExecutionForUploadsPHP7() {
		if (self::get('disableCodeExecutionUploads')) {
			if (!self::get('disableCodeExecutionUploadsPHP7Migrated')) {
				$uploads_htaccess_file_path = self::_uploadsHtaccessFilePath();
				if (file_exists($uploads_htaccess_file_path)) {
					$htaccess_contents = file_get_contents($uploads_htaccess_file_path);
					if (preg_match(self::$_disable_scripts_regex, $htaccess_contents)) {
						$htaccess_contents = preg_replace(self::$_disable_scripts_regex, self::$_disable_scripts_htaccess, $htaccess_contents); 
						@file_put_contents($uploads_htaccess_file_path, $htaccess_contents);
						self::set('disableCodeExecutionUploadsPHP7Migrated', true);
					}
				}
			}
		}
	}

	/**
	 * Remove script execution protections for our the .htaccess file in the uploads directory.
	 *
	 * @return bool
	 * @throws wfConfigException
	 */
	public static function removeCodeExecutionProtectionForUploads() {
		$uploads_htaccess_file_path = self::_uploadsHtaccessFilePath();
		if (file_exists($uploads_htaccess_file_path)) {
			$htaccess_contents = file_get_contents($uploads_htaccess_file_path);

			// Check that it is in the file
			if (preg_match(self::$_disable_scripts_regex, $htaccess_contents)) {
				$htaccess_contents = preg_replace(self::$_disable_scripts_regex, '', $htaccess_contents);

				$error_message = "Unable to remove code execution protections applied to the .htaccess file in the uploads directory.  Please check your permissions on that file.";
				if (strlen(trim($htaccess_contents)) === 0) {
					// empty file, remove it
					if (!@unlink($uploads_htaccess_file_path)) {
						throw new wfConfigException($error_message);
					}

				} elseif (@file_put_contents($uploads_htaccess_file_path, $htaccess_contents, LOCK_EX) === false) {
					throw new wfConfigException($error_message);
				}
			}
		}
		return true;
	}
}

class wfConfigException extends Exception {}

?>
