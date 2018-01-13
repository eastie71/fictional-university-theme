<?php
require_once('wordfenceConstants.php');
require_once('wfScanEngine.php');
require_once('wfScan.php');
require_once('wfCrawl.php');
require_once 'Diff.php';
require_once 'Diff/Renderer/Html/SideBySide.php';
require_once 'wfAPI.php';
require_once 'wfIssues.php';
require_once('wfDB.php');
require_once('wfUtils.php');
require_once('wfLog.php');
require_once('wfConfig.php');
require_once('wfSchema.php');
require_once('wfCache.php');
require_once('wfCrypt.php');
require_once('wfMD5BloomFilter.php');
require_once 'wfView.php';
require_once 'wfHelperString.php';
require_once 'wfDirectoryIterator.php';
require_once 'wfUpdateCheck.php';
require_once 'wfActivityReport.php';
require_once 'wfHelperBin.php';
require_once 'wfDiagnostic.php';
require_once('wfStyle.php');
require_once('wfDashboard.php');
require_once('wfNotification.php');

if (class_exists('WP_REST_Users_Controller')) { //WP 4.7+
	require_once('wfRESTAPI.php');
}

class wordfence {
	public static $printStatus = false;
	public static $wordfence_wp_version = false;
	/**
	 * @var WP_Error
	 */
	public static $authError;
	private static $passwordCodePattern = '/\s+wf([a-z0-9 ]+)$/i'; 
	protected static $lastURLError = false;
	protected static $curlContent = "";
	protected static $curlDataWritten = 0;
	protected static $hasher = '';
	protected static $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	protected static $ignoreList = false;
	public static $newVisit = false;
	private static $wfLog = false;
	private static $hitID = 0;
	private static $debugOn = null;
	private static $runInstallCalled = false;
	public static $commentSpamItems = array();
	public static function installPlugin(){
		self::runInstall();
		//Used by MU code below
		update_option('wordfenceActivated', 1);
	}
	public static function uninstallPlugin(){
		//Send admin alert
		if (wfConfig::get('alertOn_wordfenceDeactivated')) {
			$currentUser = wp_get_current_user();
			$username = $currentUser->user_login;
			wordfence::alert("Wordfence Deactivated", "A user with username \"$username\" deactivated Wordfence on your WordPress site.", wfUtils::getIP());
		}
		
		//Check if caching is enabled and if it is, disable it and fix the .htaccess file.
		wfCache::removeCaching();

		//Used by MU code below
		update_option('wordfenceActivated', 0);
		wp_clear_scheduled_hook('wordfence_daily_cron');
		wp_clear_scheduled_hook('wordfence_hourly_cron');
		wp_clear_scheduled_hook('wordfence_daily_autoUpdate');

		//Remove old legacy cron job if it exists
		wp_clear_scheduled_hook('wordfence_scheduled_scan');

		//Remove all scheduled scans.
		self::unscheduleAllScans();

		// Remove cron for email summary
		wfActivityReport::clearCronJobs();

		// Remove the admin user list so it can be regenerated if Wordfence is reactivated.
		wfConfig::set_ser('adminUserList', false);

		if (!WFWAF_SUBDIRECTORY_INSTALL) {
			wfWAFConfig::set('wafDisabled', true);
		}

		if(wfConfig::get('deleteTablesOnDeact')){
			$schema = new wfSchema();
			$schema->dropAll();
			wfConfig::updateTableExists();
			foreach(array('wordfence_version', 'wordfenceActivated') as $opt){
				if (is_multisite() && function_exists('delete_network_option')) {
					delete_network_option(null, $opt);
				}
				delete_option($opt);
			}

			if (!WFWAF_SUBDIRECTORY_INSTALL) {
				try {
					if (WFWAF_AUTO_PREPEND) {
						$helper = new wfWAFAutoPrependHelper();
						if ($helper->uninstall()) {
							wfWAF::getInstance()->uninstall();
						}
					} else {
						wfWAF::getInstance()->uninstall();
					}
				} catch (wfWAFStorageFileException $e) {
					error_log($e->getMessage());
				}
			}
		}
	}
	public static function hourlyCron(){
		global $wpdb; $p = $wpdb->base_prefix;
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		
		$wfdb = new wfDB();

		if(wfConfig::get('other_WFNet')){
			$wfdb->truncate($p . "wfNet404s");

			$q2 = $wfdb->querySelect("select IP from $p"."wfVulnScanners where ctime > unix_timestamp() - 3600");
			$scanCont = "";
			foreach($q2 as $rec){
				$scanCont .= $rec['IP'];
			}
			$wfdb->truncate($p . "wfVulnScanners");

			$q3 = $wfdb->querySelect("select IP from $p"."wfLockedOut where blockedTime > unix_timestamp() - 3600");
			$lockCont = "";
			foreach($q3 as $rec){
				$lockCont .= $rec['IP'];
			}
			if(strlen($lockCont) > 0 || strlen($scanCont) > 0){
				$cont = pack('N', strlen($lockCont) / 16) . $lockCont .
						pack('N', strlen($scanCont) / 16) . $scanCont;
				try {
					$resp = $api->binCall('get_net_bad_ips', $cont);
					if($resp['code'] == 200){
						$len = strlen($resp['data']);
						$reason = "WFSN: Blocked by Wordfence Security Network";
						$wfdb->queryWrite("delete from $p"."wfBlocks where wfsn=1 and permanent=0");
						$log = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
						if($len > 0 && $len % 16 == 0){
							for($i = 0; $i < $len; $i += 16){
								$ip_bin = substr($resp['data'], $i, 16);
								$IPStr = wfUtils::inet_ntop($ip_bin);
								if(! $log->isWhitelisted($IPStr)){
									$log->blockIP($IPStr, $reason, true, false, false, 'brute');
								}
							}
						}
					}
				} catch(Exception $e){
					//Ignore
				}
			}
		}
	}
	private static function keyAlert($msg){
		self::alert($msg, $msg . " To ensure uninterrupted Premium Wordfence protection on your site,\nplease renew your API key by visiting http://www.wordfence.com/ Sign in, go to your dashboard,\nselect the key about to expire and click the button to renew that API key.", false);
	}
	public static function dailyCron() {
		$lastDailyCron = (int) wfConfig::get('lastDailyCron', 0);
		if (($lastDailyCron + 43200) > time()) { //Run no more frequently than every 12 hours
			return;
		}
		
		wfConfig::set('lastDailyCron', time());
		
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$keyData = $api->call('ping_api_key');
			if(isset($keyData['_isPaidKey']) && $keyData['_isPaidKey']){
				$keyExpDays = $keyData['_keyExpDays'];
				$keyIsExpired = $keyData['_expired'];
				if (!empty($keyData['_autoRenew'])) {
					if ($keyExpDays > 12) {
						wfConfig::set('keyAutoRenew10Sent', '');
					} else if ($keyExpDays <= 12 && $keyExpDays > 0 && !wfConfig::get('keyAutoRenew10Sent')) {
						wfConfig::set('keyAutoRenew10Sent', 1);
						$email = "Your Premium Wordfence API Key is set to auto-renew in 10 days.";
						self::alert($email, "$email To update your API key settings please visit http://www.wordfence.com/zz9/dashboard", false);
					}
				} else {
					if($keyExpDays > 15){
						wfConfig::set('keyExp15Sent', '');
						wfConfig::set('keyExp7Sent', '');
						wfConfig::set('keyExp2Sent', '');
						wfConfig::set('keyExp1Sent', '');
						wfConfig::set('keyExpFinalSent', '');
					} else if($keyExpDays <= 15 && $keyExpDays > 0){
						if($keyExpDays <= 15 && $keyExpDays >= 11 && (! wfConfig::get('keyExp15Sent'))){
							wfConfig::set('keyExp15Sent', 1);
							self::keyAlert("Your Premium Wordfence API Key expires in less than 2 weeks.");
						} else if($keyExpDays <= 7 && $keyExpDays >= 4 && (! wfConfig::get('keyExp7Sent'))){
							wfConfig::set('keyExp7Sent', 1);
							self::keyAlert("Your Premium Wordfence API Key expires in less than a week.");
						} else if($keyExpDays == 2 && (! wfConfig::get('keyExp2Sent'))){
							wfConfig::set('keyExp2Sent', 1);
							self::keyAlert("Your Premium Wordfence API Key expires in 2 days.");
						} else if($keyExpDays == 1 && (! wfConfig::get('keyExp1Sent'))){
							wfConfig::set('keyExp1Sent', 1);
							self::keyAlert("Your Premium Wordfence API Key expires in 1 day.");
						}
					} else if($keyIsExpired && (! wfConfig::get('keyExpFinalSent')) ){
						wfConfig::set('keyExpFinalSent', 1);
						self::keyAlert("Your Wordfence Premium API Key has Expired!");
					}
				}
			}
			if (isset($keyData['dashboard'])) {
				wfConfig::set('lastDashboardCheck', time());
				wfDashboard::processDashboardResponse($keyData['dashboard']);
			}
			if (isset($keyData['scanSchedule']) && is_array($keyData['scanSchedule'])) {
				wfConfig::set_ser('noc1ScanSchedule', $keyData['scanSchedule']);
				if (!wfScan::isManualScanSchedule()) {
					wordfence::scheduleScans();
				}
			}
		}
		catch(Exception $e){
			wordfence::status(4, 'error', "Could not verify Wordfence API Key: " . $e->getMessage());
		}

		$wfdb = new wfDB();
		global $wpdb; $p = $wpdb->base_prefix;
		try {
			$patData = $api->call('get_known_vuln_pattern');
			if(is_array($patData) && $patData['pat']){
				if(@preg_match($patData['pat'], 'wordfence_test_vuln_match')){
					wfConfig::set('vulnRegex', $patData['pat']);
				}
			}
		} catch(Exception $e){
			wordfence::status(4, 'error', "Could not fetch vulnerability patterns in scheduled job: " . $e->getMessage());
		}

		$wfdb->queryWrite("delete from $p"."wfLocs where ctime < unix_timestamp() - %d", WORDFENCE_MAX_IPLOC_AGE);
		$wfdb->truncate($p . "wfBadLeechers"); //only uses date that's less than 1 minute old
		$wfdb->queryWrite("delete from $p"."wfBlocks where (blockedTime + %s < unix_timestamp()) and permanent=0", wfConfig::get('blockedTime'));
		$wfdb->queryWrite("delete from $p"."wfCrawlers where lastUpdate < unix_timestamp() - (86400 * 7)");

		$wfdb->truncate($p . "wfVulnScanners"); //We only report data within the last hour in hourlyCron.
		// So if we do a once a day truncate to be safe, we'll only potentially lose the hour right before the truncate.
		// Worth it to clean out the table completely once a day.

		self::trimWfHits();
/*
		$count6 = $wfdb->querySingle("select count(*) as cnt from $p"."wfPerfLog");
		if($count6 > 20000){
			$wfdb->truncate($p . "wfPerfLog"); //So we don't slow down sites that have very large wfHits tables
		} else if($count6 > 2000){
			$wfdb->queryWrite("delete from $p"."wfPerfLog order by id asc limit %d", ($count6 - 100));
		}
*/
		$maxRows = 1000; //affects stuff further down too
		foreach(array('wfLeechers', 'wfScanners') as $table){
			//This is time based per IP so shouldn't get too big
			$wfdb->queryWrite("delete from $p"."$table where eMin < ((unix_timestamp() - (86400 * 2)) / 60)");
		}
		$wfdb->queryWrite("delete from $p"."wfLockedOut where blockedTime + %s < unix_timestamp()", wfConfig::get('loginSec_lockoutMins') * 60);
		$count2 = $wfdb->querySingle("select count(*) as cnt from $p"."wfLogins");
		if($count2 > 20000){
			$wfdb->truncate($p . "wfLogins"); //in case of Dos
		} else if($count2 > $maxRows){
			$wfdb->queryWrite("delete from $p"."wfLogins order by ctime asc limit %d", ($count2 - 100));
		}
		$wfdb->queryWrite("delete from $p"."wfReverseCache where unix_timestamp() - lastUpdate > 86400");
		$count3 = $wfdb->querySingle("select count(*) as cnt from $p"."wfThrottleLog");
		if($count3 > 20000){
			$wfdb->truncate($p . "wfThrottleLog"); //in case of DoS
		} else if($count3 > $maxRows){
			$wfdb->queryWrite("delete from $p"."wfThrottleLog order by endTime asc limit %d", ($count3 - 100));
		}
		$count4 = $wfdb->querySingle("select count(*) as cnt from $p"."wfStatus");
		if($count4 > 100000){
			$wfdb->truncate($p . "wfStatus");
		} else if($count4 > 1000){ //max status events we keep. This determines how much gets emailed to us when users sends us a debug report.
			$wfdb->queryWrite("delete from $p"."wfStatus where level != 10 order by ctime asc limit %d", ($count4 - 1000));
			$count5 = $wfdb->querySingle("select count(*) as cnt from $p"."wfStatus where level=10");
			if($count5 > 100){
				$wfdb->queryWrite("delete from $p"."wfStatus where level = 10 order by ctime asc limit %d", ($count5 - 100) );
			}
		}
		
		self::_refreshVulnerabilityCache();

		$report = new wfActivityReport();
		$report->rotateIPLog();
		self::_refreshUpdateNotification($report, true);
		
		$next = self::getNextScanStartTimestamp();
		if ($next - time() > 3600 && wfScan::shouldRunScan(wfScanEngine::SCAN_MODE_QUICK)) {
			wfScanEngine::startScan(false, wfScanEngine::SCAN_MODE_QUICK);
		}
	}
	public static function _scheduleRefreshUpdateNotification($upgrader, $options) {
		$defer = false;
		if (is_array($options) && isset($options['type']) && $options['type'] == 'core') {
			$defer = true;
			set_site_transient('wordfence_updating_notifications', true, 600);
		}
		
		if ($defer) {
			wp_schedule_single_event(time(), 'wordfence_refreshUpdateNotification');
		}
		else {
			self::_refreshUpdateNotification();
		}
	}
	public static function _refreshUpdateNotification($report = null, $useCachedValued = false) {
		if ($report === null) {
			$report = new wfActivityReport();
		}
		
		$updatesNeeded = $report->getUpdatesNeeded($useCachedValued);
		if ($updatesNeeded) {
			$items = array();
			$plural = false;
			if ($updatesNeeded['core']) {
				$items[] = 'WordPress (v' . esc_html($updatesNeeded['core']) . ')';
			}
			
			if ($updatesNeeded['plugins']) {
				$entry = count($updatesNeeded['plugins']) . ' plugin';
				if (count($updatesNeeded['plugins']) > 1) {
					$entry .= 's';
					$plural = true;
				}
				$items[] = $entry;
			}
			
			if ($updatesNeeded['themes']) {
				$entry = count($updatesNeeded['themes']) . ' theme';
				if (count($updatesNeeded['themes']) > 1) {
					$entry .= 's';
					$plural = true;
				}
				$items[] = $entry;
			}
			
			$message = 'An update is available for ';
			$plural = ($plural || (count($items) > 1));
			if ($plural) {
				$message = 'Updates are available for ';
			}
			
			for ($i = 0; $i < count($items); $i++) {
				if ($i > 0 && count($items) > 2) { $message .= ', '; }
				else if ($i > 0) { $message .= ' '; }
				if ($i > 0 && $i == count($items) - 1) { $message .= 'and '; }
				$message .= $items[$i];
			}
			
			new wfNotification(null, wfNotification::PRIORITY_HIGH_WARNING, '<a href="' . network_admin_url('update-core.php') . '">' . $message . '</a>', 'wfplugin_updates');
		}
		else {
			$n = wfNotification::getNotificationForCategory('wfplugin_updates');
			if ($n !== null) {
				$n->markAsRead();
			}
		}
		
		$i = new wfIssues();
		$i->reconcileUpgradeIssues($report, true);
		
		wp_schedule_single_event(time(), 'wordfence_completeCoreUpdateNotification');
	}
	public static function _completeCoreUpdateNotification() {
		//This approach is here because WP Core updates run in a different sequence than plugin/theme updates, so we have to defer the running of the notification update sequence by an extra page load
		delete_site_transient('wordfence_updating_notifications');
	}
	public static function runInstall(){
		if(self::$runInstallCalled){ return; }
		self::$runInstallCalled = true;
		if (function_exists('ignore_user_abort')) {
			ignore_user_abort(true);
		}
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$previous_version = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, 'wordfence_version', '0.0.0') : get_option('wordfence_version', '0.0.0'));
		if (is_multisite() && function_exists('update_network_option')) {
			update_network_option(null, 'wordfence_version', WORDFENCE_VERSION); //In case we have a fatal error we don't want to keep running install.	
		}
		else {
			update_option('wordfence_version', WORDFENCE_VERSION); //In case we have a fatal error we don't want to keep running install.
		}
		
		wordfence::status(4, 'info', 'runInstall called with previous version = ' . $previous_version);
		
		//EVERYTHING HERE MUST BE IDEMPOTENT

		//Remove old legacy cron job if exists
		wp_clear_scheduled_hook('wordfence_scheduled_scan');

		$schema = new wfSchema();
		$schema->createAll(); //if not exists
		
		/** @var wpdb $wpdb */
		global $wpdb;
		
		//6.1.15
		$configTable = "{$wpdb->base_prefix}wfConfig";
		$hasAutoload = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='autoload'
AND TABLE_NAME=%s
SQL
			, $configTable));
		if (!$hasAutoload) {
			$wpdb->query("ALTER TABLE {$configTable} ADD COLUMN autoload ENUM('no', 'yes') NOT NULL DEFAULT 'yes'");
			$wpdb->query("UPDATE {$configTable} SET autoload = 'no' WHERE name = 'wfsd_engine' OR name LIKE 'wordfence_chunked_%'");
		}
		
		wfConfig::setDefaults(); //If not set

		$restOfSite = wfConfig::get('cbl_restOfSiteBlocked', 'notset');
		if($restOfSite == 'notset'){
			wfConfig::set('cbl_restOfSiteBlocked', '1');
		}

		//Install new schedule. If schedule config is blank it will install the default 'auto' schedule.
		wordfence::scheduleScans();

		if(wfConfig::get('autoUpdate') == '1'){
			wfConfig::enableAutoUpdate(); //Sets up the cron
		}

		if(! wfConfig::get('apiKey')){
			$api = new wfAPI('', wfUtils::getWPVersion());
			try {
				$keyData = $api->call('get_anon_api_key');
				if($keyData['ok'] && $keyData['apiKey']){
					wfConfig::set('apiKey', $keyData['apiKey']);
				} else {
					throw new Exception("Could not understand the response we received from the Wordfence servers when applying for a free API key.");
				}
			} catch(Exception $e){
				error_log("Could not fetch free API key from Wordfence: " . $e->getMessage());
				return;
			}
		}
		wp_clear_scheduled_hook('wordfence_daily_cron');
		wp_clear_scheduled_hook('wordfence_hourly_cron');
		if (is_main_site()) {
			wp_schedule_event(time(), 'daily', 'wordfence_daily_cron'); //'daily'
			wp_schedule_event(time(), 'hourly', 'wordfence_hourly_cron');
		}

		$db = new wfDB();

		if($db->columnExists('wfHits', 'HTTPHeaders')){ //Upgrade from 3.0.4
			$prefix = $wpdb->base_prefix;
			$count = $db->querySingle("select count(*) as cnt from $prefix"."wfHits");
			if($count > 20000){
				$db->queryWrite("delete from $prefix"."wfHits order by id asc limit " . ($count - 20000));
			}
			$db->dropColumn('wfHits', 'HTTPHeaders');
		}

		//Upgrading from 1.5.6 or earlier needs:
		$db->createKeyIfNotExists('wfStatus', 'level', 'k2');
		if(wfConfig::get('isPaid') == 'free'){
			wfConfig::set('isPaid', '');
		}
		//End upgrade from 1.5.6
		
		$prefix = $wpdb->base_prefix;
		$db->queryWriteIgnoreError("alter table $prefix"."wfConfig modify column val longblob");
		$db->queryWriteIgnoreError("alter table $prefix"."wfBlocks add column permanent tinyint UNSIGNED default 0");
		$db->queryWriteIgnoreError("alter table $prefix"."wfStatus modify column msg varchar(1000) NOT NULL");
		//3.1.2 to 3.1.4
		$db->queryWriteIgnoreError("alter table $prefix"."wfBlocks modify column blockedTime bigint signed NOT NULL");
		//3.2.1 to 3.2.2
		$db->queryWriteIgnoreError("alter table $prefix"."wfLockedOut modify column blockedTime bigint signed NOT NULL");
		$db->queryWriteIgnoreError("drop table if exists $prefix"."wfFileQueue");
		$db->queryWriteIgnoreError("drop table if exists $prefix"."wfFileChanges");

		$result = $wpdb->get_row("SHOW FIELDS FROM {$prefix}wfStatus where field = 'id'");
		if (!$result || strtolower($result->Key) != 'pri') {
			//Adding primary key to this table because some backup apps use primary key during backup.
			$db->queryWriteIgnoreError("alter table {$prefix}wfStatus add id bigint UNSIGNED NOT NULL auto_increment PRIMARY KEY");
		}

		$optScanEnabled = $db->querySingle("select val from $prefix"."wfConfig where name='scansEnabled_options'");
		if($optScanEnabled != '0' && $optScanEnabled != '1'){
			$db->queryWrite("update $prefix"."wfConfig set val='1' where name='scansEnabled_options'");
		}

		// IPv6 schema changes for 6.0.1
		$tables_with_ips = array(
			'wfCrawlers',
			'wfBadLeechers',
			'wfBlockedIPLog',
			'wfBlocks',
			'wfHits',
			'wfLeechers',
			'wfLockedOut',
			'wfLocs',
			'wfLogins',
			'wfReverseCache',
			'wfScanners',
			'wfThrottleLog',
			'wfVulnScanners',
		);

		foreach ($tables_with_ips as $ip_table) {
			$result = $wpdb->get_row("SHOW FIELDS FROM {$prefix}{$ip_table} where field = 'IP'");
			if (!$result || strtolower($result->Type) == 'binary(16)') {
				continue;
			}

			$db->queryWriteIgnoreError("ALTER TABLE {$prefix}{$ip_table} MODIFY IP BINARY(16)");

			// Just to be sure we don't corrupt the data if the alter fails.
			$result = $wpdb->get_row("SHOW FIELDS FROM {$prefix}{$ip_table} where field = 'IP'");
			if (!$result || strtolower($result->Type) != 'binary(16)') {
				continue;
			}
			$db->queryWriteIgnoreError("UPDATE {$prefix}{$ip_table} SET IP = CONCAT(LPAD(CHAR(0xff, 0xff), 12, CHAR(0)), LPAD(
	CHAR(
		CAST(IP as UNSIGNED) >> 24 & 0xFF,
		CAST(IP as UNSIGNED) >> 16 & 0xFF,
		CAST(IP as UNSIGNED) >> 8 & 0xFF,
		CAST(IP as UNSIGNED) & 0xFF
	),
	4,
	CHAR(0)
))");
		}

		// Fix the data in the country column.
		$previousVersionHash = wfConfig::get('geoIPVersionHash', '');
		$geoIPVersion = wfUtils::geoIPVersion();
		$geoIPVersionHash = hash('sha256', implode(',', $geoIPVersion));
		if ($previousVersionHash != $geoIPVersionHash) {
			$ip_results = $wpdb->get_results("SELECT countryCode, IP FROM `{$prefix}wfBlockedIPLog` GROUP BY IP");
			if ($ip_results) {
				foreach ($ip_results as $ip_row) {
					$country = wfUtils::IP2Country(wfUtils::inet_ntop($ip_row->IP));
					if ($country != $ip_row->countryCode) {
						$wpdb->query($wpdb->prepare("UPDATE `{$prefix}wfBlockedIPLog` SET countryCode = %s WHERE IP = %s", $country, $ip_row->IP));
					}
				}
			}
			
			$ip_results = $wpdb->get_results("SELECT countryCode, IP FROM `{$prefix}wfBlockedCommentLog` GROUP BY IP");
			if ($ip_results) {
				foreach ($ip_results as $ip_row) {
					$country = wfUtils::IP2Country(wfUtils::inet_ntop($ip_row->IP));
					if ($country != $ip_row->countryCode) {
						$wpdb->query($wpdb->prepare("UPDATE `{$prefix}wfBlockedCommentLog` SET countryCode = %s WHERE IP = %s", $country, $ip_row->IP));
					}
				}
			}
			
			wfConfig::set('geoIPVersionHash', $geoIPVersionHash);
		}

		if (wfConfig::get('other_hideWPVersion')) {
			wfUtils::hideReadme();
		}

		$colsFor610 = array(
			'attackLogTime'     => '`attackLogTime` double(17,6) unsigned NOT NULL AFTER `id`',
			'statusCode'        => '`statusCode` int(11) NOT NULL DEFAULT 0 AFTER `jsRun`',
			'action'            => "`action` varchar(64) NOT NULL DEFAULT '' AFTER `UA`",
			'actionDescription' => '`actionDescription` text AFTER `action`',
			'actionData'        => '`actionData` text AFTER `actionDescription`',
		);

		$hitTable = $wpdb->base_prefix . 'wfHits';
		foreach ($colsFor610 as $col => $colDefintion) {
			$count = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME=%s
AND TABLE_NAME=%s
SQL
				, $col, $hitTable));
			if (!$count) {
				$wpdb->query("ALTER TABLE $hitTable ADD COLUMN $colDefintion");
			}
		}

		$has404 = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='is404'
AND TABLE_NAME=%s
SQL
			, $hitTable));
		if ($has404) {
			$wpdb->query(<<<SQL
UPDATE $hitTable
SET statusCode= CASE
WHEN is404=1 THEN 404
ELSE 200
END
SQL
			);

			$wpdb->query("ALTER TABLE $hitTable DROP COLUMN `is404`");
		}

		$loginsTable = "{$wpdb->base_prefix}wfLogins";
		$hasHitID = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='hitID'
AND TABLE_NAME=%s
SQL
			, $loginsTable));
		if (!$hasHitID) {
			$wpdb->query("ALTER TABLE $loginsTable ADD COLUMN hitID int(11) DEFAULT NULL AFTER `id`, ADD INDEX(hitID)");
		}

		if (!WFWAF_SUBDIRECTORY_INSTALL) {
			wfWAFConfig::set('wafDisabled', false);
		}

		// Call this before creating the index in cases where the wp-cron isn't running.
		self::trimWfHits();
		$hitsTable = "{$wpdb->base_prefix}wfHits";
		$hasAttackLogTimeIndex = $wpdb->get_var($wpdb->prepare(<<<SQL
SELECT COLUMN_KEY FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = %s
AND COLUMN_NAME = 'attackLogTime'
SQL
			, $hitsTable));

		if (!$hasAttackLogTimeIndex) {
			$wpdb->query("ALTER TABLE $hitsTable ADD INDEX `attackLogTime` (`attackLogTime`)");
		}
		
		//6.1.16
		$allowed404s = wfConfig::get('allowed404s', '');
		if (!wfConfig::get('allowed404s6116Migration', false)) {
			if (!preg_match('/(?:^|\b)browserconfig\.xml(?:\b|$)/i', $allowed404s)) {
				if (strlen($allowed404s) > 0) {
					$allowed404s .= "\n";
				}
				$allowed404s .= "/browserconfig.xml";
				wfConfig::set('allowed404s', $allowed404s);
			}
			
			wfConfig::set('allowed404s6116Migration', 1);
		}
		if (wfConfig::get('email_summary_interval') == 'biweekly') {
			wfConfig::set('email_summary_interval', 'weekly');
		}
		
		//6.2.0
		wfConfig::migrateCodeExecutionForUploadsPHP7();
		
		//6.2.3
		if (!WFWAF_SUBDIRECTORY_INSTALL && class_exists('wfWAFIPBlocksController')) {
			wfWAFIPBlocksController::synchronizeConfigSettings();
		}
		
		//6.2.8
		wfCache::removeCaching();
		
		//6.2.10
		$snipCacheTable = "{$wpdb->base_prefix}wfSNIPCache";
		$hasType = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='type'
AND TABLE_NAME=%s
SQL
			, $snipCacheTable));
		if (!$hasType) {
			$wpdb->query("ALTER TABLE `{$snipCacheTable}` ADD `type` INT  UNSIGNED  NOT NULL  DEFAULT '0'");
			$wpdb->query("ALTER TABLE `{$snipCacheTable}` ADD INDEX (`type`)");
		}
		
		//6.3.5
		$fileModsTable = wfDB::networkPrefix() . 'wfFileMods';
		$hasStoppedOn = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='stoppedOnSignature'
AND TABLE_NAME=%s
SQL
			, $fileModsTable));
		if (!$hasStoppedOn) {
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN stoppedOnSignature VARCHAR(255) NOT NULL DEFAULT ''");
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN stoppedOnPosition INT UNSIGNED NOT NULL DEFAULT '0'");
		}
		
		$blockedIPLogTable = wfDB::networkPrefix() . 'wfBlockedIPLog';
		$hasType = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='blockType'
AND TABLE_NAME=%s
SQL
			, $blockedIPLogTable));
		if (!$hasType) {
			$wpdb->query("ALTER TABLE {$blockedIPLogTable} ADD blockType VARCHAR(50) NOT NULL DEFAULT 'generic'");
			$wpdb->query("ALTER TABLE {$blockedIPLogTable} DROP PRIMARY KEY");
			$wpdb->query("ALTER TABLE {$blockedIPLogTable} ADD PRIMARY KEY (IP, unixday, blockType)");
		}
		
		//6.3.6
		if (!wfConfig::get('migration636_email_summary_excluded_directories')) {
			$excluded_directories = explode(',', (string) wfConfig::get('email_summary_excluded_directories'));
			$key = array_search('wp-content/plugins/wordfence/tmp', $excluded_directories); if ($key !== false) { unset($excluded_directories[$key]); }
			$key = array_search('wp-content/wflogs', $excluded_directories); if ($key === false) { $excluded_directories[] = 'wp-content/wflogs'; }
			wfConfig::set('email_summary_excluded_directories', implode(',', $excluded_directories));
			wfConfig::set('migration636_email_summary_excluded_directories', 1, wfConfig::DONT_AUTOLOAD);
		}
    
		$fileModsTable = wfDB::networkPrefix() . 'wfFileMods';
		$hasSHAC = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='SHAC'
AND TABLE_NAME=%s
SQL
			, $fileModsTable));
		if (!$hasSHAC) {
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN `SHAC` BINARY(32) NOT NULL DEFAULT '' AFTER `newMD5`");
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN `isSafeFile` VARCHAR(1) NOT NULL  DEFAULT '?' AFTER `stoppedOnPosition`");
		}
		
		//6.3.7
		$hooverTable = wfDB::networkPrefix() . 'wfHoover';
		$hostKeySize = $wpdb->get_var($wpdb->prepare(<<<SQL
SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='hostKey'
AND TABLE_NAME=%s
SQL
			, $hooverTable));
		if ($hostKeySize < 124) {
			$wpdb->query("ALTER TABLE {$hooverTable} CHANGE `hostKey` `hostKey` VARBINARY(124) NULL DEFAULT NULL");
		}
		
		//6.3.15
		$scanFileContents = wfConfig::get('scansEnabled_fileContents', false);
		if (!wfConfig::get('fileContentsGSB6315Migration', false)) {
			if (!$scanFileContents) {
				wfConfig::set('scansEnabled_fileContentsGSB', false);
			}
			wfConfig::set('fileContentsGSB6315Migration', 1);
		}
		
		//6.3.20
		$lastBlockAggregation = wfConfig::get('lastBlockAggregation', 0);
		if ($lastBlockAggregation == 0) {
			wfConfig::set('lastBlockAggregation', time());
		}
		
		
		//Check the How does Wordfence get IPs setting
		wfUtils::requestDetectProxyCallback();

		//Must be the final line
	}
	public static function _refreshVulnerabilityCache() {
		$update_check = new wfUpdateCheck();
		$update_check->checkAllVulnerabilities();
	}
	private static function doEarlyAccessLogging(){
		$wfLog = self::getLog();
		if($wfLog->logHitOK()){
			$request = $wfLog->getCurrentRequest();

			if(is_404()){
				if ($request) {
					$request->statusCode = 404;
				}
				$wfLog->logLeechAndBlock('404');
			} else {
				$wfLog->logLeechAndBlock('hit');
			}
		}
	}
	public static function initProtection(){ //Basic protection during WAF learning period
		if (preg_match('#/wp\-admin/admin\-ajax\.php$#i', $_SERVER['SCRIPT_FILENAME'])) {
			$gAction = isset($_GET['action']) ? $_GET['action'] : '';
			$pAction = isset($_POST['action']) ? $_POST['action'] : '';
			if (
				(($gAction == 'revslider_show_image' || $gAction == 'nopriv_revslider_show_image') && isset($_GET['img']) && preg_match('/\.php$/i', $_GET['img'])) ||
				(($pAction == 'revslider_show_image' || $pAction == 'nopriv_revslider_show_image') && isset($_POST['img']) && preg_match('/\.php$/i', $_POST['img']))
			) {
				self::getLog()->do503(86400, "URL not allowed. Slider Revolution Hack attempt detected. #2");
				exit(); //function above exits anyway
			}
			
			if (
				(
					(($gAction == 'revslider_ajax_action' || $gAction == 'nopriv_revslider_ajax_action') && isset($_GET['client_action']) && $_GET['client_action'] == 'update_plugin') ||
					(($pAction == 'revslider_ajax_action' || $pAction == 'nopriv_revslider_ajax_action') && isset($_POST['client_action']) && $_POST['client_action'] == 'update_plugin')
				) &&
				!wfUtils::isAdmin()
			) {
				self::getLog()->do503(86400, "URL not allowed. Slider Revolution Hack attempt detected. #2");
				exit(); //function above exits anyway
			}
		}
	}
	public static function install_actions(){
		register_activation_hook(WORDFENCE_FCPATH, 'wordfence::installPlugin');
		register_deactivation_hook(WORDFENCE_FCPATH, 'wordfence::uninstallPlugin');

		$versionInOptions = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, 'wordfence_version', false) : get_option('wordfence_version', false));
		if( (! $versionInOptions) || version_compare(WORDFENCE_VERSION, $versionInOptions, '>')){
			//Either there is no version in options or the version in options is greater and we need to run the upgrade
			self::runInstall();
		}
		
		self::getLog()->initLogRequest();
		
		//Fix wp_mail bug when $_SERVER['SERVER_NAME'] is undefined
		add_filter('wp_mail_from', 'wordfence::fixWPMailFromAddress');

		//These access wfConfig::get('apiKey') and will fail if runInstall hasn't executed.
		if(defined('MULTISITE') && MULTISITE === true){
			global $blog_id;
			if($blog_id == 1 && get_option('wordfenceActivated') != 1){ return; } //Because the plugin is active once installed, even before it's network activated, for site 1 (WordPress team, why?!)
		}
		//User may be logged in or not, so register both handlers
		add_action('wp_ajax_nopriv_wordfence_lh', 'wordfence::ajax_lh_callback');
		add_action('wp_ajax_nopriv_wordfence_doScan', 'wordfence::ajax_doScan_callback');
		add_action('wp_ajax_nopriv_wordfence_testAjax', 'wordfence::ajax_testAjax_callback');
		add_action('wp_ajax_nopriv_wordfence_perfLog', 'wordfence::ajax_perfLog_callback');
		if(wfUtils::hasLoginCookie()){ //may be logged in. Fast way to check. These aren't secure functions, this is just a perf optimization, along with every other use of hasLoginCookie()
			add_action('wp_ajax_wordfence_perfLog', 'wordfence::ajax_perfLog_callback');
			add_action('wp_ajax_wordfence_lh', 'wordfence::ajax_lh_callback');
			add_action('wp_ajax_wordfence_doScan', 'wordfence::ajax_doScan_callback');
			add_action('wp_ajax_wordfence_testAjax', 'wordfence::ajax_testAjax_callback');

			if (is_multisite()) {
				add_action('wp_network_dashboard_setup', 'wordfence::addDashboardWidget');
			} else {
				add_action('wp_dashboard_setup', 'wordfence::addDashboardWidget');
			}
		}


		add_action('wordfence_start_scheduled_scan', 'wordfence::wordfenceStartScheduledScan');
		add_action('wordfence_daily_cron', 'wordfence::dailyCron');
		add_action('wordfence_daily_autoUpdate', 'wfConfig::autoUpdate');
		add_action('wordfence_hourly_cron', 'wordfence::hourlyCron');
		add_action('plugins_loaded', 'wordfence::veryFirstAction');
		add_action('init', 'wordfence::initAction');
		//add_action('admin_bar_menu', 'wordfence::admin_bar_menu', 99);
		add_action('template_redirect', 'wordfence::templateRedir', 1001);
		add_action('shutdown', 'wordfence::shutdownAction');
		
		if (!wfConfig::get('ajaxWatcherDisabled_front')) {
			add_action('wp_enqueue_scripts', 'wordfence::enqueueAJAXWatcher');
		}
		if (!wfConfig::get('ajaxWatcherDisabled_admin')) {
			add_action('admin_enqueue_scripts', 'wordfence::enqueueAJAXWatcher');
		}
		
		//add_action('wp_enqueue_scripts', 'wordfence::enqueueDashboard');
		add_action('admin_enqueue_scripts', 'wordfence::enqueueDashboard');

		if(version_compare(PHP_VERSION, '5.4.0') >= 0){
			add_action('wp_authenticate','wordfence::authActionNew', 1, 2);
		} else {
			add_action('wp_authenticate','wordfence::authActionOld', 1, 2);
		}
		add_filter('authenticate', 'wordfence::authenticateFilter', 99, 3);
		if (self::isLockedOut(wfUtils::getIP())) {
			add_filter('xmlrpc_enabled', '__return_false');
		}

		add_action('login_init','wordfence::loginInitAction');
		add_action('wp_login','wordfence::loginAction');
		add_action('wp_logout','wordfence::logoutAction');
		add_action('lostpassword_post', 'wordfence::lostPasswordPost', '1');
		
		$allowSeparatePrompt = ini_get('output_buffering') > 0;
		if (wfConfig::get('loginSec_enableSeparateTwoFactor') && $allowSeparatePrompt) {
			add_action('login_form', 'wordfence::showTwoFactorField');
		}
		
		if(wfUtils::hasLoginCookie()){
			add_action('user_profile_update_errors', 'wordfence::validateProfileUpdate', 0, 3 );
			add_action('profile_update', 'wordfence::profileUpdateAction', '99', 2);
			add_action('validate_password_reset', 'wordfence::validatePassword', 10, 2 );
		}
		add_action('mobile_setup', 'wordfence::jetpackMobileSetup'); //Action called in Jetpack Mobile Theme: modules/minileven/minileven.php

		// Add actions for the email summary
		add_action('wordfence_email_activity_report', array('wfActivityReport', 'executeCronJob'));

		//For debugging
		//add_filter( 'cron_schedules', 'wordfence::cronAddSchedules' );

		add_filter('wp_redirect', 'wordfence::wpRedirectFilter', 99, 2);
		add_filter('pre_comment_approved', 'wordfence::preCommentApprovedFilter', '99', 2);
		//html|xhtml|atom|rss2|rdf|comment|export
		if(wfConfig::get('other_hideWPVersion')){
			add_filter('style_loader_src', 'wordfence::replaceVersion');
			add_filter('script_loader_src', 'wordfence::replaceVersion');

			add_action('upgrader_process_complete', 'wordfence::hideReadme');
		}
		add_filter('get_the_generator_html', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_xhtml', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_atom', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_rss2', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_rdf', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_comment', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_export', 'wordfence::genFilter', 99, 2);
		add_filter('registration_errors', 'wordfence::registrationFilter', 99, 3);
		
		if (wfConfig::get('loginSec_disableAuthorScan')) {
			add_filter('oembed_response_data', 'wordfence::oembedAuthorFilter', 99, 4);
			add_filter('rest_request_before_callbacks', 'wordfence::jsonAPIAuthorFilter', 99, 3);
			add_filter('rest_post_dispatch', 'wordfence::jsonAPIAdjustHeaders', 99, 3);
		}

		// Change GoDaddy's limit login mu-plugin since it can interfere with the two factor auth message.
		if (self::hasGDLimitLoginsMUPlugin()) {
			add_action('login_errors', array('wordfence', 'fixGDLimitLoginsErrors'), 11);
		}
		
		add_action('upgrader_process_complete', 'wordfence::_refreshVulnerabilityCache');
		add_action('upgrader_process_complete', 'wordfence::_scheduleRefreshUpdateNotification', 99, 2);
		add_action('wordfence_refreshUpdateNotification', 'wordfence::_refreshUpdateNotification', 99, 0);
		add_action('wordfence_completeCoreUpdateNotification', 'wordfence::_completeCoreUpdateNotification', 99, 0);

		if(is_admin()){
			add_action('admin_init', 'wordfence::admin_init');
			add_action('admin_head', 'wordfence::_retargetWordfenceSubmenuCallout');
			if(is_multisite()){
				if(wfUtils::isAdminPageMU()){
					add_action('network_admin_menu', 'wordfence::admin_menus');
				} //else don't show menu
			} else {
				add_action('admin_menu', 'wordfence::admin_menus');
			}
			add_filter('plugin_action_links_' . plugin_basename(realpath(dirname(__FILE__) . '/../wordfence.php')), 'wordfence::_pluginPageActionLinks');
		}

		add_action('request', 'wordfence::preventAuthorNScans');
		add_action('password_reset', 'wordfence::actionPasswordReset');

		$adminUsers = new wfAdminUserMonitor();
		if ($adminUsers->isEnabled()) {
			add_action('set_user_role', array($adminUsers, 'updateToUserRole'), 10, 3);
			add_action('grant_super_admin', array($adminUsers, 'grantSuperAdmin'), 10, 1);
			add_action('revoke_super_admin', array($adminUsers, 'revokeSuperAdmin'), 10, 1);
		} else if (wfConfig::get_ser('adminUserList', false)) {
			// reset this in the event it's disabled or the network is too large
			wfConfig::set_ser('adminUserList', false);
		}

		if (!self::getLog()->getCurrentRequest()->jsRun && wfConfig::liveTrafficEnabled()) {
			add_action('wp_head', 'wordfence::wfLogHumanHeader');
			add_action('login_head', 'wordfence::wfLogHumanHeader');
		}

		add_action('wordfence_processAttackData', 'wordfence::processAttackData');
		if (!empty($_GET['wordfence_syncAttackData']) && get_site_option('wordfence_syncingAttackData') <= time() - 60 && get_site_option('wordfence_lastSyncAttackData', 0) < time() - 4) {
			ignore_user_abort(true);
			update_site_option('wordfence_syncingAttackData', time());
			header('Content-Type: text/javascript');
			add_action('init', 'wordfence::syncAttackData', 10, 0);
			add_filter('woocommerce_unforce_ssl_checkout', '__return_false');
		}
		
		add_action('wordfence_batchReportBlockedAttempts', 'wordfence::wfsnBatchReportBlockedAttempts');
		add_action('wordfence_batchReportFailedAttempts', 'wordfence::wfsnBatchReportFailedAttempts');

		if (wfConfig::get('other_hideWPVersion')) {
			add_filter('update_feedback', 'wordfence::restoreReadmeForUpgrade');
		}

	}
	public static function _pluginPageActionLinks($links) {
		if (!wfConfig::get('isPaid')) {
			$links = array_merge(array('aWordfencePluginCallout' => '<a href="https://www.wordfence.com/zz12/wordfence-signup/" target="_blank" rel="noopener noreferrer"><strong style="color: #11967A; display: inline;">Upgrade To Premium</strong></a>'), $links);
		} 
		return $links;
	}
	public static function fixWPMailFromAddress($from_email) {
		if ($from_email == 'wordpress@') { //$_SERVER['SERVER_NAME'] is undefined so we get an incomplete email address
			wordfence::status(4, 'info', "wp_mail from address is incomplete, attempting to fix");
			$urls = array(get_site_url(), get_home_url());
			foreach ($urls as $u) {
				if (!empty($u)) {
					$u = preg_replace('#^[^/]*//+([^/]+).*$#', '\1', $u);
					if (substr($u, 0, 4) == 'www.') {
						$u = substr($u, 4);
					}
					
					if (!empty($u)) {
						wordfence::status(4, 'info', "Fixing wp_mail from address: " . $from_email . $u);
						return $from_email . $u;
					}
				}
			}
			
			//Can't fix it, return it as it was
		}
		return $from_email;
	}
	/*
  	public static function cronAddSchedules($schedules){
		$schedules['wfEachMinute'] = array(
				'interval' => 60,
				'display' => __( 'Once a Minute' )
				);
		return $schedules;
	}
	*/
	/*
	public static function addDashboardWidget(){
		wp_add_dashboard_widget('wordfenceDashboardWidget', 'Wordfence Security Status', 'wordfence::displayDashboardWidget');
	}
	public static function displayDashboardWidget(){
		require('dashboard.php');
	}
	*/
	public static function jetpackMobileSetup(){
		define('WFDONOTCACHE', true); //Don't cache jetpack mobile theme pages.
	}
	public static function wpRedirectFilter($URL, $status){
		return $URL;
	}
	public static function enqueueAJAXWatcher() {
		$wafDisabled = !WFWAF_ENABLED || (class_exists('wfWAFConfig') && wfWAFConfig::isDisabled());
		if (wfUtils::isAdmin() && !$wafDisabled) {
			wp_enqueue_style('wordfenceAJAXcss', wfUtils::getBaseURL() . 'css/wordfenceBox.css', '', WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceAJAXjs', wfUtils::getBaseURL() . 'js/admin.ajaxWatcher.js', array('jquery'), WORDFENCE_VERSION);
		}
	}
	public static function enqueueDashboard() {
		if (wfUtils::isAdmin()) {
			wp_enqueue_style('wf-adminbar', wfUtils::getBaseURL() . 'css/wf-adminbar.css', '', WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceDashboardjs', wfUtils::getBaseURL() . 'js/wfdashboard.js', array('jquery'), WORDFENCE_VERSION);
			if (wfConfig::get('showAdminBarMenu')) {
				wp_enqueue_script('wordfencePopoverjs', wfUtils::getBaseURL() . 'js/wfpopover.js', array('jquery'), WORDFENCE_VERSION);
				wp_localize_script('wordfenceDashboardjs', 'WFDashVars', array(
					'ajaxURL' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('wp-ajax'),
				));
			}
		}
	}
	public static function ajax_testAjax_callback(){
		die("WFSCANTESTOK");
	}
	public static function ajax_doScan_callback(){
		ignore_user_abort(true);
		self::$wordfence_wp_version = false;
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		//This is messy, but not sure of a better way to do this without guaranteeing we get $wp_version
		require(ABSPATH . 'wp-includes/version.php');
		self::$wordfence_wp_version = $wp_version;
		require_once('wfScan.php');
		wfScan::wfScanMain();

	} //END doScan
	public static function ajax_perfLog_callback(){
		$wfLog = self::getLog();
		$fields = array('fetchStart', 'domainLookupStart', 'domainLookupEnd', 'connectStart', 'connectEnd', 'requestStart', 'responseStart', 'responseEnd', 'domReady', 'loaded');
		foreach($fields as $f){
			if(preg_match('/^\d+$/', $_POST[$f])){
				$data[$f] = $_POST[$f];
			}
		}
		$UA = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$URL = $_POST['URL'];
		$wfLog->logPerf(wfUtils::getIP(), $UA, $URL, $data);
		die(json_encode(array('ok' => 1)));
	}
	public static function ajax_lh_callback(){
		self::getLog()->canLogHit = false;
		$UA = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$isCrawler = false;
		if ($UA) {
			if (wfCrawl::isCrawler($UA) || wfCrawl::isGoogleCrawler()) {
				$isCrawler = true;
			}
		}

		@ob_end_clean();
		if(! headers_sent()){
			header('Content-type: text/javascript');
			header("Connection: close");
			header("Content-Length: 0");
			header("X-Robots-Tag: noindex");
			if (!$isCrawler && !wfConfig::get('disableCookies')) {
				setcookie('wordfence_verifiedHuman', self::getLog()->getVerifiedHumanCookieValue($UA, wfUtils::getIP()), time() + 86400, '/', null, wfUtils::isFullSSL());
			}
		}
		flush();
		if(! $isCrawler){
			$hid = $_GET['hid'];
			$hid = wfUtils::decrypt($hid);
			if(! preg_match('/^\d+$/', $hid)){ exit(); }
			$db = new wfDB();
			global $wpdb; $p = $wpdb->base_prefix;
			$db->queryWrite("update $p"."wfHits set jsRun=1 where id=%d", $hid);
		}
		die("");
	}
	public static function ajaxReceiver(){
		if(! wfUtils::isAdmin()){
			die(json_encode(array('errorMsg' => "You appear to have logged out or you are not an admin. Please sign-out and sign-in again.")));
		}
		$func = (isset($_POST['action']) && $_POST['action']) ? $_POST['action'] : $_GET['action'];
		$nonce = (isset($_POST['nonce']) && $_POST['nonce']) ? $_POST['nonce'] : $_GET['nonce'];
		if(! wp_verify_nonce($nonce, 'wp-ajax')){
			die(json_encode(array('errorMsg' => "Your browser sent an invalid security token to Wordfence. Please try reloading this page or signing out and in again.")));
		}
		//func is e.g. wordfence_ticker so need to munge it
		$func = str_replace('wordfence_', '', $func);
		$returnArr = call_user_func('wordfence::ajax_' . $func . '_callback');
		if($returnArr === false){
			$returnArr = array('errorMsg' => "Wordfence encountered an internal error executing that request.");
		}

		if(! is_array($returnArr)){
			error_log("Function " . wp_kses($func, array()) . " did not return an array and did not generate an error.");
			$returnArr = array();
		}
		if(isset($returnArr['nonce'])){
			error_log("Wordfence ajax function return an array with 'nonce' already set. This could be a bug.");
		}
		$returnArr['nonce'] = wp_create_nonce('wp-ajax');
		die(json_encode($returnArr));
	}
	public static function validateProfileUpdate($errors, $update, $userData){
		wordfence::validatePassword($errors, $userData);
	}
	public static function validatePassword($errors, $userData){
		$password = ( isset( $_POST[ 'pass1' ] ) && trim( $_POST[ 'pass1' ] ) ) ? $_POST[ 'pass1' ] : false;
		$user_id = isset( $userData->ID ) ? $userData->ID : false;
		$username = isset( $_POST["user_login"] ) ? $_POST["user_login"] : $userData->user_login;
		if($password == false){ return $errors; }
		if($errors->get_error_data("pass") ){ return $errors; }
		$enforce = false;
		if(wfConfig::get('loginSec_strongPasswds') == 'pubs'){
			if(user_can($user_id, 'publish_posts')){
				$enforce = true;
			}
		} else if(wfConfig::get('loginSec_strongPasswds')  == 'all'){
			$enforce = true;
		}
		if($enforce){
			if(! wordfence::isStrongPasswd($password, $username)){
				$errors->add('pass', "Please choose a stronger password. Try including numbers, symbols and a mix of upper and lower case letters and remove common words.");
				return $errors;
			}
		}
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		if(preg_match(self::$passwordCodePattern, $password) && isset($twoFactorUsers) && is_array($twoFactorUsers) && sizeof($twoFactorUsers) > 0){
			$errors->add('pass', "Passwords containing a space followed by 'wf' without quotes are not allowed.");
			return $errors;
		}
		return $errors;
	}
	public static function isStrongPasswd($passwd, $username ) {
		$strength = 0;
		if(strlen( trim( $passwd ) ) < 5)
			return false;
		if(strtolower( $passwd ) == strtolower( $username ) )
			return false;
		if(preg_match('/(?:password|passwd|mypass|wordpress)/i', $passwd)){
			return false;
		}
		if($num = preg_match_all( "/\d/", $passwd, $matches) ){
			$strength += ((int)$num * 10);
		}
		if ( preg_match( "/[a-z]/", $passwd ) )
			$strength += 26;
		if ( preg_match( "/[A-Z]/", $passwd ) )
			$strength += 26;
		if ($num = preg_match_all( "/[^a-zA-Z0-9]/", $passwd, $matches)){
			$strength += (31 * (int)$num);

		}
		if($strength > 60){
			return true;
		}
	}
	public static function lostPasswordPost(){
		$IP = wfUtils::getIP();
		if ($request = self::getLog()->getCurrentRequest()) {
			$request->action = 'lostPassword';
			$request->save();
		}
		if(self::getLog()->isWhitelisted($IP)){
			return;
		}
		if(self::isLockedOut($IP)){
			require('wfLockedOut.php');
		}
		if(empty($_POST['user_login'])){ return; }
		$value = trim($_POST['user_login']);
		$user  = get_user_by('login', $value);
		if (!$user) {
			$user = get_user_by('email', $value);
		}

		if($user){
			if(wfConfig::get('alertOn_lostPasswdForm')){
				wordfence::alert("Password recovery attempted", "Someone tried to recover the password for user with email address: " . wp_kses($user->user_email, array()), $IP);
			}
		}
		if(wfConfig::get('loginSecurityEnabled')){
			$tKey = 'wffgt_' . bin2hex(wfUtils::inet_pton($IP));
			$forgotAttempts = get_transient($tKey);
			if($forgotAttempts){
				$forgotAttempts++;
			} else {
				$forgotAttempts = 1;
			}
			if($forgotAttempts >= wfConfig::get('loginSec_maxForgotPasswd')){
				self::lockOutIP($IP, "Exceeded the maximum number of tries to recover their password which is set at: " . wfConfig::get('loginSec_maxForgotPasswd') . ". The last username or email they entered before getting locked out was: '" . $_POST['user_login'] . "'");
				require('wfLockedOut.php');
			}
			set_transient($tKey, $forgotAttempts, wfConfig::get('loginSec_countFailMins') * 60);
		}
	}
	public static function lockOutIP($IP, $reason){
		//First we lock out IP
		self::getLog()->lockOutIP(wfUtils::getIP(), $reason);
		//Then we send the email because email sending takes time and we want to block the baddie asap. If we don't users can get a lot of emails about a single attacker getting locked out.
		if(wfConfig::get('alertOn_loginLockout')){
			wordfence::alert("User locked out from signing in", "A user with IP address {$IP} has been locked out from signing in or using the password recovery form for the following reason: {$reason}", $IP);
		}
	}
	public static function isLockedOut($IP){
		return self::getLog()->isIPLockedOut($IP);
	}

	public static function veryFirstAction() {
		/** @var wpdb $wpdb ; */
		global $wpdb;
		
		self::initProtection();

		$wfFunc = isset($_GET['_wfsf']) ? @$_GET['_wfsf'] : false;
		if($wfFunc == 'unlockEmail'){
			$nonceValid = wp_verify_nonce(@$_POST['nonce'], 'wf-form');
			if (!$nonceValid && method_exists(wfWAF::getInstance(), 'createNonce')) {
				$nonceValid = wfWAF::getInstance()->verifyNonce(@$_POST['nonce'], 'wf-form');
			}
			if(!$nonceValid){
				die("Sorry but your browser sent an invalid security token when trying to use this form.");
			}
			$numTries = get_transient('wordfenceUnlockTries');
			if($numTries > 10){
				echo "<html><body><h1>Please wait 3 minutes and try again</h1><p>You have used this form too much. Please wait 3 minutes and try again.</p></body></html>";
				exit();
			}
			if(! $numTries){ $numTries = 1; } else { $numTries = $numTries + 1; }
			set_transient('wordfenceUnlockTries', $numTries, 180);

			$email = trim(@$_POST['email']);
			global $wpdb;
			$ws = $wpdb->get_results($wpdb->prepare("SELECT ID, user_login FROM $wpdb->users WHERE user_email = %s", $email));
			$found = false;
			foreach($ws as $user){
				$userDat = get_userdata($user->ID);
				if(wfUtils::isAdmin($userDat)){
					if($email == $userDat->user_email){
						$found = true;
						break;
					}
				}
			}
			if(! $found){
				foreach(wfConfig::getAlertEmails() as $alertEmail){
					if($alertEmail == $email){
						$found = true;
						break;
					}
				}
			}
			if($found){
				$key = wfUtils::bigRandomHex();
				$IP = wfUtils::getIP();
				set_transient('wfunlock_' . $key, $IP, 1800);
				$content = wfUtils::tmpl('email_unlockRequest.php', array(
					'siteName' => get_bloginfo('name', 'raw'),
					'siteURL' => wfUtils::getSiteBaseURL(),
					'unlockHref' => wfUtils::getSiteBaseURL() . '?_wfsf=unlockAccess&key=' . $key,
					'key' => $key,
					'IP' => $IP
					));
				wp_mail($email, "Unlock email requested", $content, "Content-Type: text/html");
			}
			echo "<html><body><h1>Your request was received</h1><p>We received a request to email \"" . wp_kses($email, array()) . "\" instructions to unlock their access. If that is the email address of a site administrator or someone on the Wordfence alert list, they have been emailed instructions on how to regain access to this system. The instructions we sent will expire 30 minutes from now.</body></html>";
			exit();
		} else if($wfFunc == 'unlockAccess'){
			if (!preg_match('/^(?:(?:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9](?::|$)){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))$/i', get_transient('wfunlock_' . $_GET['key']))) {
				echo "Invalid key provided for authentication.";
				exit();
			}
			
			$wfLog = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
			if($_GET['func'] == 'unlockMyIP'){
				$wfLog->unblockIP(wfUtils::getIP());
				$wfLog->unlockOutIP(wfUtils::getIP());
				delete_transient('wflginfl_' . bin2hex(wfUtils::inet_pton(wfUtils::getIP()))); //Reset login failure counter
				header('Location: ' . wp_login_url());
				exit();
			} else if($_GET['func'] == 'unlockAllIPs'){
				wordfence::status(1, 'info', "Request received via unlock email link to unblock all IPs.");
				$wfLog->unblockAllIPs();
				$wfLog->unlockAllIPs();
				delete_transient('wflginfl_' . bin2hex(wfUtils::inet_pton(wfUtils::getIP()))); //Reset login failure counter
				header('Location: ' . wp_login_url());
				exit();
			} else if($_GET['func'] == 'disableRules'){
				wfConfig::set('firewallEnabled', 0);
				wfConfig::set('loginSecurityEnabled', 0);
				wordfence::status(1, 'info', "Request received via unlock email link to unblock all IPs via disabling firewall rules.");
				$wfLog->unblockAllIPs();
				$wfLog->unlockAllIPs();
				delete_transient('wflginfl_' . bin2hex(wfUtils::inet_pton(wfUtils::getIP()))); //Reset login failure counter
				wfConfig::set('cbl_countries', ''); //unblock all countries
				header('Location: ' . wp_login_url());
				exit();
			} else {
				echo "Invalid function specified. Please check the link we emailed you and make sure it was not cut-off by your email reader.";
				exit();
			}
		}
		else if ($wfFunc == 'detectProxy') {
			wfUtils::doNotCache();
			if (wfUtils::processDetectProxyCallback()) {
				self::getLog()->getCurrentRequest()->action = 'scan:detectproxy'; //Exempt a valid callback from live traffic
				echo wfConfig::get('detectProxyRecommendation', '-');
			}
			else {
				echo '0';
			}
			exit();
		}

		// Sync the WAF data with the database.
		if (!WFWAF_SUBDIRECTORY_INSTALL && $waf = wfWAF::getInstance()) {
			$homeurl = wfUtils::wpHomeURL();
			$siteurl = wfUtils::wpSiteURL();
			
			try {
				$configDefaults = array(
					'apiKey'         => wfConfig::get('apiKey'),
					'isPaid'         => !!wfConfig::get('isPaid'),
					'siteURL'        => $siteurl,
					'homeURL'        => $homeurl,
					'whitelistedIPs' => (string) wfConfig::get('whitelisted'),
					'howGetIPs'      => (string) wfConfig::get('howGetIPs'),
					'howGetIPs_trusted_proxies' => wfConfig::get('howGetIPs_trusted_proxies', ''),
					'other_WFNet'    => !!wfConfig::get('other_WFNet', true), 
					'pluginABSPATH'	 => ABSPATH,
				);
				foreach ($configDefaults as $key => $value) {
					$waf->getStorageEngine()->setConfig($key, $value);
				}
				
				if (wfConfig::get('timeoffset_wf') !== false) {
					$waf->getStorageEngine()->setConfig('timeoffset_wf', wfConfig::get('timeoffset_wf'));
				}
				else {
					$waf->getStorageEngine()->unsetConfig('timeoffset_wf');
				}
				
				if (class_exists('wfWAFIPBlocksController')) {
					wfWAFIPBlocksController::synchronizeConfigSettings();
				}

				if (empty($_GET['wordfence_syncAttackData'])) {
					$lastAttackMicroseconds = $wpdb->get_var("SELECT MAX(attackLogTime) FROM {$wpdb->base_prefix}wfHits");
					if (get_site_option('wordfence_lastSyncAttackData', 0) < time() - 4) {
						if ($waf->getStorageEngine()->hasNewerAttackData($lastAttackMicroseconds)) {
							if (get_site_option('wordfence_syncingAttackData') <= time() - 60) {
								// Could be the request to itself is not completing, add ajax to the head as a workaround
								$attempts = get_site_option('wordfence_syncAttackDataAttempts', 0);
								if ($attempts > 10) {
									add_action('wp_head', 'wordfence::addSyncAttackDataAjax');
									add_action('login_head', 'wordfence::addSyncAttackDataAjax');
									add_action('admin_head', 'wordfence::addSyncAttackDataAjax');
								} else {
									update_site_option('wordfence_syncAttackDataAttempts', ++$attempts);
									wp_remote_post(add_query_arg('wordfence_syncAttackData', microtime(true), home_url('/')), array(
										'timeout'   => 0.01,
										'blocking'  => false,
										'sslverify' => apply_filters('https_local_ssl_verify', false)
									));
								}
							}
						}
					}
				}

				if ($waf instanceof wfWAFWordPress && ($learningModeAttackException = $waf->getLearningModeAttackException())) {
					$log = self::getLog();
					$log->initLogRequest();
					$request = $log->getCurrentRequest();
					$request->action = 'learned:waf';
					$request->attackLogTime = microtime(true);

					$ruleIDs = array();
					/** @var wfWAFRule $failedRule */
					foreach ($learningModeAttackException->getFailedRules() as $failedRule) {
						$ruleIDs[] = $failedRule->getRuleID();
					}

					$actionData = array(
						'learningMode' => 1,
						'failedRules'  => $ruleIDs,
						'paramKey'     => $learningModeAttackException->getParamKey(),
						'paramValue'   => $learningModeAttackException->getParamValue(),
					);
					if ($ruleIDs && $ruleIDs[0]) {
						$rule = $waf->getRule($ruleIDs[0]);
						if ($rule) {
							$request->actionDescription = $rule->getDescription();
							$actionData['category'] = $rule->getCategory();
							$actionData['ssl'] = $waf->getRequest()->getProtocol() === 'https';
							$actionData['fullRequest'] = base64_encode($waf->getRequest());
						}
					}
					$request->actionData = wfRequestModel::serializeActionData($actionData);
					register_shutdown_function(array($request, 'save'));

					self::scheduleSendAttackData();
				}
			} catch (wfWAFStorageFileException $e) {
				// We don't have anywhere to write files in this scenario.
			}
		}

		if(wfConfig::get('firewallEnabled')){
			$wfLog = self::getLog();
			$wfLog->firewallBadIPs();

			$IP = wfUtils::getIP();
			if($wfLog->isWhitelisted($IP)){
				return;
			}
			if (wfConfig::get('neverBlockBG') == 'neverBlockUA' && wfCrawl::isGoogleCrawler()) {
				return;
			}
			if (wfConfig::get('neverBlockBG') == 'neverBlockVerified' && wfCrawl::isVerifiedGoogleCrawler()) {
				return;
			}

			if(wfConfig::get('blockFakeBots')){
				if(wfCrawl::isGooglebot() && !wfCrawl::isVerifiedGoogleCrawler()){
					$wfLog->blockIP($IP, "Fake Google crawler automatically blocked", false, false, false, 'fakegoogle');
					wordfence::status(2, 'info', "Blocking fake Googlebot at IP $IP");
					$wfLog->do503(3600, "Fake Google crawler automatically blocked.");
				}
			}
			if(wfConfig::get('bannedURLs', false)){
				$URLs = explode(',', wfConfig::get('bannedURLs'));
				foreach($URLs as $URL){
					if(preg_match(wfUtils::patternToRegex($URL, ''), $_SERVER['REQUEST_URI'])){
						$wfLog->blockIP($IP, "Accessed a banned URL.", false, false, false, 'bannedurl');
						$wfLog->do503(3600, "Accessed a banned URL.");
						//exits
					}
				}
			}

			if(wfConfig::get('other_blockBadPOST') == '1' && $_SERVER['REQUEST_METHOD'] == 'POST' && empty($_SERVER['HTTP_USER_AGENT']) && empty($_SERVER['HTTP_REFERER'])){
				$wfLog->blockIP($IP, "POST received with blank user-agent and referer", false, false, false, 'badpost');
				$wfLog->do503(3600, "POST received with blank user-agent and referer");
				//exits
			}
		}
	}

	public static function loginAction($username){
		if(sizeof($_POST) < 1){ return; } //only execute if login form is posted
		if(! $username){ return; }
		wfConfig::inc('totalLogins');
		$user = get_user_by('login', $username);
		$userID = $user ? $user->ID : 0;
		self::getLog()->logLogin('loginOK', 0, $username);
		if(wfUtils::isAdmin($user)){
			wfConfig::set_ser('lastAdminLogin', array(
				'userID' => $userID,
				'username' => $username,
				'firstName' => $user->first_name,
				'lastName' => $user->last_name,
				'time' => wfUtils::localHumanDateShort(),
				'IP' => wfUtils::getIP()
				));
		}
		
		$salt = wp_salt('logged_in');
		$cookiename = 'wf_loginalerted_' . hash_hmac('sha256', wfUtils::getIP() . '|' . $user->ID, $salt);
		$cookievalue = hash_hmac('sha256', $user->user_login, $salt);
		if(wfUtils::isAdmin($userID)){
			if(wfConfig::get('alertOn_adminLogin')){
				$shouldAlert = true;
				if (wfConfig::get('alertOn_firstAdminLoginOnly') && isset($_COOKIE[$cookiename])) {
					$shouldAlert = !hash_equals($cookievalue, $_COOKIE[$cookiename]);
				}
				
				if ($shouldAlert) {
					wordfence::alert("Admin Login", "A user with username \"$username\" who has administrator access signed in to your WordPress site.", wfUtils::getIP());
				}
			}
		} else {
			if(wfConfig::get('alertOn_nonAdminLogin')){
				$shouldAlert = true;
				if (wfConfig::get('alertOn_firstNonAdminLoginOnly') && isset($_COOKIE[$cookiename])) {
					$shouldAlert = !hash_equals($cookievalue, $_COOKIE[$cookiename]);
				}
				
				if ($shouldAlert) {
					wordfence::alert("User login", "A non-admin user with username \"$username\" signed in to your WordPress site.", wfUtils::getIP());
				}
			}
		}
		
		if (wfConfig::get('alertOn_firstAdminLoginOnly') || wfConfig::get('alertOn_firstNonAdminLoginOnly')) {
			wfUtils::setcookie($cookiename, $cookievalue, time() + (86400 * 365), '/', null, wfUtils::isFullSSL(), true);
		}
	}
	public static function registrationFilter($errors, $santizedLogin, $userEmail){
		if(wfConfig::get('loginSec_blockAdminReg') && $santizedLogin == 'admin'){
			$errors->add('user_login_error', '<strong>ERROR</strong>: You can\'t register using that username');
		}
		return $errors;
	}
	public static function oembedAuthorFilter($data, $post, $width, $height) {
		unset($data['author_name']);
		unset($data['author_url']);
		return $data;
	}
	public static function jsonAPIAuthorFilter($response, $handler, $request) {
		$route = $request->get_route();
		if (!current_user_can('list_users')) {
			$urlBase = wfWP_REST_Users_Controller::wfGetURLBase();
			if (preg_match('~' . preg_quote($urlBase, '~') . '/*$~i', $route)) {
				$error = new WP_Error('rest_user_cannot_view', __('Sorry, you are not allowed to list users.'), array('status' => rest_authorization_required_code()));
				$response = rest_ensure_response($error);
				define('WORDFENCE_REST_API_SUPPRESSED', true);
			}
			else if (preg_match('~' . preg_quote($urlBase, '~') . '/+(\d+)/*$~i', $route, $matches)) {
				$id = (int) $matches[1];
				if (get_current_user_id() !== $id) {
					$error = new WP_Error('rest_user_invalid_id', __('Invalid user ID.'), array('status' => 404));
					$response = rest_ensure_response($error);
					define('WORDFENCE_REST_API_SUPPRESSED', true);
				}
			}
		}
		return $response;
	}
	public static function jsonAPIAdjustHeaders($response, $server, $request) {
		if (defined('WORDFENCE_REST_API_SUPPRESSED')) {
			$response->header('Allow', 'GET');
		}
		
		return $response;
	}
	public static function showTwoFactorField() {
		$existingContents = ob_get_contents();
		if (!preg_match('/wftwofactornonce:([0-9]+)\/(.+?)\s/', $existingContents, $matches)) {
			return;
		}
		
		$userID = intval($matches[1]);
		$twoFactorNonce = preg_replace('/[^a-f0-9]/i', '', $matches[2]);
		if (!self::verifyTwoFactorIntermediateValues($userID, $twoFactorNonce)) {
			return;
		}
		
		//Strip out the username and password fields
		$formPosition = strrpos($existingContents, '<form');
		$formTagEnd = strpos($existingContents, '>', $formPosition);
		if ($formPosition === false || $formTagEnd === false) {
			return;
		}
		
		ob_end_clean();
		ob_start();
		echo substr($existingContents, 0, $formTagEnd + 1);
		
		//Add the 2FA field
		echo "<p>
        <label for=\"wfAuthenticationCode\">Authentication Code<br>
        <input type=\"text\" size=\"6\" class=\"input\" id=\"wordfence_authFactor\" name=\"wordfence_authFactor\" autofocus></label>
        <input type=\"hidden\" id=\"wordfence_twoFactorUser\" name=\"wordfence_twoFactorUser\" value=\"" . $userID . "\">
        <input type=\"hidden\" id=\"wordfence_twoFactorNonce\" name=\"wordfence_twoFactorNonce\" value=\"" . $twoFactorNonce . "\">
    </p>";
	}
	private static function verifyTwoFactorIntermediateValues($userID, $twoFactorNonce) {
		$user = get_user_by('ID', $userID);
		if (!$user || get_class($user) != 'WP_User') { return false; } //Check that the user exists
		
		$expectedNonce = get_user_meta($user->ID, '_wf_twoFactorNonce', true);
		$twoFactorNonceTime = get_user_meta($user->ID, '_wf_twoFactorNonceTime', true);
		if (empty($twoFactorNonce) || empty($twoFactorNonceTime)) { return false; } //Ensure the two factor nonce and time have been set
		if ($twoFactorNonce != $expectedNonce) { return false; } //Verify the nonce matches the expected
		
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		if (!$twoFactorUsers || !is_array($twoFactorUsers)) { return false; } //Make sure there are two factor users configured
		foreach ($twoFactorUsers as &$t) { //Ensure the two factor nonce hasn't expired
			if ($t[0] == $user->ID && $t[3] == 'activated') {
				if (isset($t[5]) && $t[5] == 'authenticator') { $graceTime = WORDFENCE_TWO_FACTOR_GRACE_TIME_AUTHENTICATOR; }
				else { $graceTime = WORDFENCE_TWO_FACTOR_GRACE_TIME_PHONE; }
				return ((time() - $twoFactorNonceTime) < $graceTime);
			}
		}
		return false;
	}
	public static function authenticateFilter($authUser, $username, $passwd) {
		wfConfig::inc('totalLoginHits'); //The total hits to wp-login.php including logins, logouts and just hits.
		$IP = wfUtils::getIP();
		$secEnabled = wfConfig::get('loginSecurityEnabled');
		
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		$userDat = (isset($_POST['wordfence_userDat']) ? $_POST['wordfence_userDat'] : false);
		$checkTwoFactor = $secEnabled &&
			!self::getLog()->isWhitelisted($IP) &&
			wfConfig::get('isPaid') &&
			isset($twoFactorUsers) &&
			is_array($twoFactorUsers) &&
			sizeof($twoFactorUsers) > 0 &&
			is_object($userDat) &&
			get_class($userDat) == 'WP_User';
		if ($checkTwoFactor) {
			$twoFactorRecord = false;
			$hasActivatedTwoFactorUser = false;
			foreach ($twoFactorUsers as &$t) {
				if ($t[3] == 'activated') {
					$userID = $t[0];
					$testUser = get_user_by('ID', $userID);
					if (is_object($testUser) && wfUtils::isAdmin($testUser)) {
						$hasActivatedTwoFactorUser = true;
					}
					
					if ($userID == $userDat->ID) {
						$twoFactorRecord = &$t;
					}
				}
			}
			
			if (isset($_POST['wordfence_authFactor']) && $_POST['wordfence_authFactor'] && $twoFactorRecord) { //User authenticated with name and password, 2FA code ready to check
				$userID = $userDat->ID;
				
				if (get_class($authUser) == 'WP_User' && $authUser->ID == $userID) {
					//Do nothing. This is the code path the old method of including the code in the password field will take -- since we already have a valid $authUser, skip the nonce verification portion
				}
				else if (isset($_POST['wordfence_twoFactorNonce'])) {
					$twoFactorNonce = preg_replace('/[^a-f0-9]/i', '', $_POST['wordfence_twoFactorNonce']);
					if (!self::verifyTwoFactorIntermediateValues($userID, $twoFactorNonce)) {
						self::$authError = new WP_Error('twofactor_required', __('<strong>VERIFICATION FAILED</strong>: Two factor authentication verification failed. Please try again.'));
						return self::processBruteForceAttempt(self::$authError, $username, $passwd);
					}
				}
				else { //Code path for old method, invalid password the second time
					self::$authError = $authUser;
					if (is_wp_error(self::$authError) && (self::$authError->get_error_code() == 'invalid_username' || $authUser->get_error_code() == 'invalid_email' || self::$authError->get_error_code() == 'incorrect_password' || $authUser->get_error_code() == 'authentication_failed') && wfConfig::get('loginSec_maskLoginErrors')) {
						self::$authError = new WP_Error('incorrect_password', sprintf(__('<strong>ERROR</strong>: The username or password you entered is incorrect. <a href="%2$s" title="Password Lost and Found">Lost your password</a>?'), $username, wp_lostpassword_url()));
					}
					
					return self::processBruteForceAttempt(self::$authError, $username, $passwd);
				}
				
				if (isset($twoFactorRecord[5])) { //New method TOTP
					$mode = $twoFactorRecord[5];
					$code = preg_replace('/[^a-f0-9]/i', '', $_POST['wordfence_authFactor']);
					
					$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
					try {
						$codeResult = $api->call('twoFactorTOTP_verify', array(), array('totpid' => $twoFactorRecord[6], 'code' => $code, 'mode' => $mode));
						
						if (isset($codeResult['notPaid']) && $codeResult['notPaid']) {
							//No longer a paid key, let them sign in without two factor
						}
						else if (isset($codeResult['ok']) && $codeResult['ok']) {
							//Everything's good, let the sign in continue
						} 
						else {
							if (get_class($authUser) == 'WP_User' && $authUser->ID == $userID) { //Using the old method of appending the code to the password
								if ($mode == 'authenticator') {
									self::$authError = new WP_Error('twofactor_invalid', __('<strong>INVALID CODE</strong>: Please sign in again and add a space, the letters <code>wf</code>, and the code from your authenticator app to the end of your password (e.g., <code>wf123456</code>).'));
								}
								else {
									self::$authError = new WP_Error('twofactor_invalid', __('<strong>INVALID CODE</strong>: Please sign in again and add a space, the letters <code>wf</code>, and the code sent to your phone to the end of your password (e.g., <code>wf123456</code>).'));
								}
							}
							else {
								$loginNonce = wfWAFUtils::random_bytes(20);
								if ($loginNonce === false) { //Should never happen but is technically possible
									self::$authError = new WP_Error('twofactor_required', __('<strong>AUTHENTICATION FAILURE</strong>: A temporary failure was encountered while trying to log in. Please try again.'));
									return self::$authError;
								}
								
								$loginNonce = bin2hex($loginNonce);
								update_user_meta($userDat->ID, '_wf_twoFactorNonce', $loginNonce);
								update_user_meta($userDat->ID, '_wf_twoFactorNonceTime', time());
								
								if ($mode == 'authenticator') {
									self::$authError = new WP_Error('twofactor_invalid', __('<strong>INVALID CODE</strong>: You need to enter the code generated by your authenticator app. The code should be a six digit number (e.g., 123456).') . '<!-- wftwofactornonce:' . $userDat->ID . '/' . $loginNonce . ' -->');
								}
								else {
									self::$authError = new WP_Error('twofactor_invalid', __('<strong>INVALID CODE</strong>: You need to enter the code generated sent to your phone. The code should be a six digit number (e.g., 123456).') . '<!-- wftwofactornonce:' . $userDat->ID . '/' . $loginNonce . ' -->');
								}
							}
							return self::processBruteForceAttempt(self::$authError, $username, $passwd);
						}
					}
					catch (Exception $e) {
						if (self::isDebugOn()) {
							error_log('TOTP validation error: ' . $e->getMessage());
						}
					} // Couldn't connect to noc1, let them sign in since the password was correct.
				}
				else { //Old method phone authentication
					$authFactor = $_POST['wordfence_authFactor'];
					if (strlen($authFactor) == 4) {
						$authFactor = 'wf' . $authFactor;
					}
					if ($authFactor == $twoFactorRecord[2] && $twoFactorRecord[4] > time()) { // Set this 2FA code to expire in 30 seconds (for other plugins hooking into the auth process)
						$twoFactorRecord[4] = time() + 30;
						wfConfig::set_ser('twoFactorUsers', $twoFactorUsers);
					}
					else if ($authFactor == $twoFactorRecord[2]) {
						$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
						try {
							$codeResult = $api->call('twoFactor_verification', array(), array('phone' => $twoFactorRecord[1]));
							
							if (isset($codeResult['notPaid']) && $codeResult['notPaid']) {
								//No longer a paid key, let them sign in without two factor
							} 
							else if (isset($codeResult['ok']) && $codeResult['ok']) {
								$twoFactorRecord[2] = $codeResult['code'];
								$twoFactorRecord[4] = time() + 1800; //30 minutes until code expires
								wfConfig::set_ser('twoFactorUsers', $twoFactorUsers); //save the code the user needs to enter and return an error.
								
								$loginNonce = wfWAFUtils::random_bytes(20);
								if ($loginNonce === false) { //Should never happen but is technically possible
									self::$authError = new WP_Error('twofactor_required', __('<strong>AUTHENTICATION FAILURE</strong>: A temporary failure was encountered while trying to log in. Please try again.'));
									return self::$authError;
								}
								
								$loginNonce = bin2hex($loginNonce);
								update_user_meta($userDat->ID, '_wf_twoFactorNonce', $loginNonce);
								update_user_meta($userDat->ID, '_wf_twoFactorNonceTime', time());
								
								self::$authError = new WP_Error('twofactor_required', __('<strong>CODE EXPIRED. CHECK YOUR PHONE:</strong> The code you entered has expired. Codes are only valid for 30 minutes for security reasons. We have sent you a new code. Please sign in using your username, password, and the new code we sent you.') . '<!-- wftwofactornonce:' . $userDat->ID . '/' . $loginNonce . ' -->');
								return self::$authError;
							}
							
							//else: No new code was received. Let them sign in with the expired code.
						}
						catch (Exception $e) {
							// Couldn't connect to noc1, let them sign in since the password was correct.
						} 
					}
					else { //Bad code, so cancel the login and return an error to user.
						$loginNonce = wfWAFUtils::random_bytes(20);
						if ($loginNonce === false) { //Should never happen but is technically possible
							self::$authError = new WP_Error('twofactor_required', __('<strong>AUTHENTICATION FAILURE</strong>: A temporary failure was encountered while trying to log in. Please try again.'));
							return self::$authError;
						}
						
						$loginNonce = bin2hex($loginNonce);
						update_user_meta($userDat->ID, '_wf_twoFactorNonce', $loginNonce);
						update_user_meta($userDat->ID, '_wf_twoFactorNonceTime', time());
						
						self::$authError = new WP_Error('twofactor_invalid', __('<strong>INVALID CODE</strong>: You need to enter your password and the code we sent to your phone. The code should start with \'wf\' and should be four characters (e.g., wfAB12).') . '<!-- wftwofactornonce:' . $userDat->ID . '/' . $loginNonce . ' -->');
						return self::processBruteForceAttempt(self::$authError, $username, $passwd);
					}
				}
				delete_user_meta($userDat->ID, '_wf_twoFactorNonce');
				delete_user_meta($userDat->ID, '_wf_twoFactorNonceTime');
				$authUser = $userDat; //Log in as the user we saved in the wp_authenticate action
			}
			else if (get_class($authUser) == 'WP_User') { //User authenticated with name and password, prompt for the 2FA code
				//Verify at least one administrator has 2FA enabled
				$requireAdminTwoFactor = $hasActivatedTwoFactorUser && wfConfig::get('loginSec_requireAdminTwoFactor');
				
				if ($twoFactorRecord) {
					if ($twoFactorRecord[0] == $userDat->ID && $twoFactorRecord[3] == 'activated') { //Yup, enabled, so require the code
						$loginNonce = wfWAFUtils::random_bytes(20);
						if ($loginNonce === false) { //Should never happen but is technically possible, allow login
							$requireAdminTwoFactor = false;
						}
						else {
							$loginNonce = bin2hex($loginNonce);
							update_user_meta($userDat->ID, '_wf_twoFactorNonce', $loginNonce);
							update_user_meta($userDat->ID, '_wf_twoFactorNonceTime', time());
							
							if (isset($twoFactorRecord[5])) { //New method TOTP authentication
								if ($twoFactorRecord[5] == 'authenticator') {
									if (self::hasGDLimitLoginsMUPlugin() && function_exists('limit_login_get_address')) {
										$retries = get_option('limit_login_retries', array());
										$ip = limit_login_get_address();
										
										if (!is_array($retries)) {
											$retries = array();
										}
										if (isset($retries[$ip]) && is_int($retries[$ip])) {
											$retries[$ip]--;
										}
										else {
											$retries[$ip] = 0;
										}
										update_option('limit_login_retries', $retries);
									}
									
									$allowSeparatePrompt = ini_get('output_buffering') > 0;
									if (wfConfig::get('loginSec_enableSeparateTwoFactor') && $allowSeparatePrompt) {
										self::$authError = new WP_Error('twofactor_required', __('<strong>CODE REQUIRED</strong>: Please check your authenticator app for the current code. Enter it below to sign in.') . '<!-- wftwofactornonce:' . $userDat->ID . '/' . $loginNonce . ' -->');
										return self::$authError;
									}
									else {
										self::$authError = new WP_Error('twofactor_required', __('<strong>CODE REQUIRED</strong>: Please check your authenticator app for the current code. Please sign in again and add a space, the letters <code>wf</code>, and the code to the end of your password (e.g., <code>wf123456</code>).'));
										return self::$authError;
									}
								}
								else {
									//Phone TOTP
									$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
									try {
										$codeResult = $api->call('twoFactorTOTP_sms', array(), array('totpid' => $twoFactorRecord[6]));
										if (isset($codeResult['notPaid']) && $codeResult['notPaid']) {
											$requireAdminTwoFactor = false;
											//Let them sign in without two factor if their API key has expired or they're not paid and for some reason they have this set up.
										}
										else {
											if (isset($codeResult['ok']) && $codeResult['ok']) {
												if (self::hasGDLimitLoginsMUPlugin() && function_exists('limit_login_get_address')) {
													$retries = get_option('limit_login_retries', array());
													$ip = limit_login_get_address();
													
													if (!is_array($retries)) {
														$retries = array();
													}
													if (isset($retries[$ip]) && is_int($retries[$ip])) {
														$retries[$ip]--;
													}
													else {
														$retries[$ip] = 0;
													}
													update_option('limit_login_retries', $retries);
												}
												
												$allowSeparatePrompt = ini_get('output_buffering') > 0;
												if (wfConfig::get('loginSec_enableSeparateTwoFactor') && $allowSeparatePrompt) {
													self::$authError = new WP_Error('twofactor_required', __('<strong>CHECK YOUR PHONE</strong>: A code has been sent to your phone and will arrive within 30 seconds. Enter it below to sign in.') . '<!-- wftwofactornonce:' . $userDat->ID . '/' . $loginNonce . ' -->');
													return self::$authError;
												}
												else {
													self::$authError = new WP_Error('twofactor_required', __('<strong>CHECK YOUR PHONE</strong>: A code has been sent to your phone and will arrive within 30 seconds. Please sign in again and add a space, the letters <code>wf</code>, and the code to the end of your password (e.g., <code>wf123456</code>).'));
													return self::$authError;
												}
											}
											else { //oops, our API returned an error.
												$requireAdminTwoFactor = false;
												//Let them sign in without two factor because the API is broken and we don't want to lock users out of their own systems.
											}
										}
									}
									catch (Exception $e) {
										if (self::isDebugOn()) {
											error_log('TOTP SMS error: ' . $e->getMessage());
										}
										$requireAdminTwoFactor = false;
										// Couldn't connect to noc1, let them sign in since the password was correct.
									}
								}
							}
							else { //Old method phone authentication
								$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
								try {
									$codeResult = $api->call('twoFactor_verification', array(), array('phone' => $twoFactorRecord[1]));
									if (isset($codeResult['notPaid']) && $codeResult['notPaid']) {
										$requireAdminTwoFactor = false;
										//Let them sign in without two factor if their API key has expired or they're not paid and for some reason they have this set up.
									}
									else {
										if (isset($codeResult['ok']) && $codeResult['ok']) {
											$twoFactorRecord[2] = $codeResult['code'];
											$twoFactorRecord[4] = time() + 1800; //30 minutes until code expires
											wfConfig::set_ser('twoFactorUsers', $twoFactorUsers); //save the code the user needs to enter and return an error.
											
											if (self::hasGDLimitLoginsMUPlugin() && function_exists('limit_login_get_address')) {
												$retries = get_option('limit_login_retries', array());
												$ip = limit_login_get_address();
												
												if (!is_array($retries)) {
													$retries = array();
												}
												if (isset($retries[$ip]) && is_int($retries[$ip])) {
													$retries[$ip]--;
												}
												else {
													$retries[$ip] = 0;
												}
												update_option('limit_login_retries', $retries);
											}
											
											$allowSeparatePrompt = ini_get('output_buffering') > 0;
											if (wfConfig::get('loginSec_enableSeparateTwoFactor') && $allowSeparatePrompt) {
												self::$authError = new WP_Error('twofactor_required', __('<strong>CHECK YOUR PHONE</strong>: A code has been sent to your phone and will arrive within 30 seconds. Enter it below to sign in.') . '<!-- wftwofactornonce:' . $userDat->ID . '/' . $loginNonce . ' -->');
												return self::$authError;
											}
											else {
												self::$authError = new WP_Error('twofactor_required', __('<strong>CHECK YOUR PHONE</strong>: A code has been sent to your phone and will arrive within 30 seconds. Please sign in again and add a space and the code to the end of your password (e.g., <code>wfABCD</code>).'));
												return self::$authError;
											}
										}
										else { //oops, our API returned an error.
											$requireAdminTwoFactor = false;
											//Let them sign in without two factor because the API is broken and we don't want to lock users out of their own systems.
										}
									}
								}
								catch (Exception $e) {
									$requireAdminTwoFactor = false;
									// Couldn't connect to noc1, let them sign in since the password was correct.
								}
							} //end: Old method phone authentication
						}
					}
				}
				
				if ($requireAdminTwoFactor && wfUtils::isAdmin($authUser)) {
					$username = $authUser->user_login;
					self::getLog()->logLogin('loginFailValidUsername', 1, $username);
					wordfence::alert("Admin Login Blocked", "A user with username \"$username\" who has administrator access tried to sign in to your WordPress site. Access was denied because all administrator accounts are required to have Cellphone Sign-in enabled but this account does not.", wfUtils::getIP());
					self::$authError = new WP_Error('twofactor_disabled_required', __('<strong>Cellphone Sign-in Required</strong>: Cellphone Sign-in is required for all administrator accounts. Please contact the site administrator to enable it for your account.'));
					return self::$authError;
				}
				
				//User is not configured for two factor. Sign in without two factor.
			}
		} //End: if ($checkTwoFactor)
		
		return self::processBruteForceAttempt($authUser, $username, $passwd);
	}
	
	public static function processBruteForceAttempt($authUser, $username, $passwd) {
		$IP = wfUtils::getIP();
		$secEnabled = wfConfig::get('loginSecurityEnabled');
		
		if(self::getLog()->isWhitelisted($IP)){
			return $authUser;
		}
		if(wfConfig::get('other_WFNet') && is_wp_error($authUser) && ($authUser->get_error_code() == 'invalid_username' || $authUser->get_error_code() == 'invalid_email' || $authUser->get_error_code() == 'incorrect_password' || $authUser->get_error_code() == 'twofactor_invalid' || $authUser->get_error_code() == 'authentication_failed') ){
			if($maxBlockTime = self::wfsnIsBlocked($IP, 'brute')){
				self::getLog()->blockIP($IP, "Blocked by Wordfence Security Network", true, false, $maxBlockTime, 'brute');
				self::getLog()->getCurrentRequest()->action = 'blocked:wfsn';
				self::getLog()->do503($maxBlockTime, "Blocked by Wordfence Security Network"); //exits
			}

		}
		if($secEnabled){
			if(is_wp_error($authUser) && ($authUser->get_error_code() == 'invalid_username' || $authUser->get_error_code() == 'invalid_email')){
				if($blacklist = wfConfig::get('loginSec_userBlacklist')){
					$users = explode("\n", wfUtils::cleanupOneEntryPerLine($blacklist));
					foreach($users as $user){
						if(strtolower($username) == strtolower($user)){
							self::getLog()->blockIP($IP, "Blocked by login security setting.", false, false, false, 'brute');
							$secsToGo = wfConfig::get('blockedTime');
							self::getLog()->do503($secsToGo, "Blocked by login security setting."); //exits
						}
					}
				}
				if(wfConfig::get('loginSec_lockInvalidUsers')){
					if(strlen($username) > 0 && preg_match('/[^\r\s\n\t]+/', $username)){
						self::lockOutIP($IP, "Used an invalid username '" . $username . "' to try to sign in.");
						self::getLog()->logLogin('loginFailInvalidUsername', true, $username);
					}
					require('wfLockedOut.php');
				}
			}
			$tKey = 'wflginfl_' . bin2hex(wfUtils::inet_pton($IP));
			if(is_wp_error($authUser) && ($authUser->get_error_code() == 'invalid_username' || $authUser->get_error_code() == 'invalid_email' || $authUser->get_error_code() == 'incorrect_password' || $authUser->get_error_code() == 'twofactor_invalid' || $authUser->get_error_code() == 'authentication_failed') ){
				$tries = get_transient($tKey);
				if($tries){
					$tries++;
				} else {
					$tries = 1;
				}
				if($tries >= wfConfig::get('loginSec_maxFailures')){
					self::lockOutIP($IP, "Exceeded the maximum number of login failures which is: " . wfConfig::get('loginSec_maxFailures') . ". The last username they tried to sign in with was: '" . $username . "'");
					require('wfLockedOut.php');
				}
				set_transient($tKey, $tries, wfConfig::get('loginSec_countFailMins') * 60);
			} else if(get_class($authUser) == 'WP_User'){
				delete_transient($tKey); //reset counter on success
			}
		}
		if(is_wp_error($authUser)){
			if($authUser->get_error_code() == 'invalid_username' || $authUser->get_error_code() == 'invalid_email'){
				self::getLog()->logLogin('loginFailInvalidUsername', 1, $username);
			} else {
				self::getLog()->logLogin('loginFailValidUsername', 1, $username);
			}
		}

		if(is_wp_error($authUser) && ($authUser->get_error_code() == 'invalid_username' || $authUser->get_error_code() == 'invalid_email' || $authUser->get_error_code() == 'incorrect_password') && wfConfig::get('loginSec_maskLoginErrors')){
			return new WP_Error( 'incorrect_password', sprintf( __( '<strong>ERROR</strong>: The username or password you entered is incorrect. <a href="%2$s" title="Password Lost and Found">Lost your password</a>?' ), $username, wp_lostpassword_url() ) );
		}
		
		return $authUser;
	}
	public static function wfsnBatchReportBlockedAttempts() {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$threshold = wfConfig::get('lastBruteForceDataSendTime', 0);;
		
		$wfdb = new wfDB();
		global $wpdb;
		$p = $wpdb->base_prefix;
		$rawBlocks = $wfdb->querySelect("SELECT SQL_CALC_FOUND_ROWS IP, ctime, actionData FROM {$p}wfHits WHERE ctime > %f AND action = 'blocked:wfsnrepeat' ORDER BY ctime ASC LIMIT 100", sprintf('%.6f', $threshold));
		$totalRows = $wpdb->get_var('SELECT FOUND_ROWS()');
		$ipCounts = array();
		$maxctime = 0;
		foreach ($rawBlocks as $record) {
			$maxctime = max($maxctime, $record['ctime']);
			$endpointType = 0;
			if (!empty($record['actionData'])) {
				$actionData = wfRequestModel::unserializeActionData($record['actionData']);
				if (isset($actionData['type'])) {
					$endpointType = $actionData['type'];
				}
			}
			if (isset($ipCounts[$record['IP']])) {
				$ipCounts[$record['IP']] = array();
			}
			
			if (isset($ipCounts[$record['IP']][$endpointType])) {
				$ipCounts[$record['IP']][$endpointType]++;
			}
			else {
				$ipCounts[$record['IP']][$endpointType] = 1;
			}
		}
		
		$toSend = array();
		foreach ($ipCounts as $IP => $endpoints) {
			foreach ($endpoints as $endpointType => $count) {
				$toSend[] = array('IP' => base64_encode($IP), 'count' => $count, 'blocked' => 1, 'type' => $endpointType);
			}
		}
		
		try {
			$response = wp_remote_post(WORDFENCE_HACKATTEMPT_URL . 'multipleHackAttempts/?k=' . rawurlencode(wfConfig::get('apiKey')) . '&t=brute', array(
				'timeout' => 1,
				'user-agent' => "Wordfence.com UA " . (defined('WORDFENCE_VERSION') ? WORDFENCE_VERSION : '[Unknown version]'),
				'body' => 'IPs=' . rawurlencode(json_encode($toSend)),
				'headers' => array('Referer' => false),
			));
			
			if (!is_wp_error($response)) {
				if ($totalRows > 100) {
					self::wfsnScheduleBatchReportBlockedAttempts();
				}
				
				wfConfig::set('lastBruteForceDataSendTime', $maxctime);
			}
			else {
				self::wfsnScheduleBatchReportBlockedAttempts();
			}
		} 
		catch (Exception $err) {
			//Do nothing
		}
	}
	private static function wfsnScheduleBatchReportBlockedAttempts($timeToSend = null) {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		if ($timeToSend === null) {
			$timeToSend = time() + 30;
		}
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if (!wp_next_scheduled('wordfence_batchReportBlockedAttempts')) {
			wp_schedule_single_event($timeToSend, 'wordfence_batchReportBlockedAttempts');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}
	public static function wfsnReportBlockedAttempt($IP, $type){
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		self::wfsnScheduleBatchReportBlockedAttempts();
		$endpointType = self::wfsnEndpointType();
		self::getLog()->getCurrentRequest()->actionData = wfRequestModel::serializeActionData(array('type' => $endpointType));
	}
	public static function wfsnBatchReportFailedAttempts() {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$threshold = time();
		
		$wfdb = new wfDB();
		global $wpdb;
		$p = $wpdb->base_prefix;
		$rawRecords = $wfdb->querySelect("SELECT id, IP, type, count, 1 AS failed FROM {$p}wfSNIPCache WHERE count > 0 AND expiration < FROM_UNIXTIME(%d) LIMIT 100", $threshold);
		$toSend = array();
		$toDelete = array();
		if (count($rawRecords)) {
			foreach ($rawRecords as $record) {
				$toDelete[] = $record['id'];
				unset($record['id']);
				$record['IP'] = base64_encode(filter_var($record['IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? wfUtils::inet_aton($record['IP']) : wfUtils::inet_pton($record['IP']));
				
				$key = $record['IP'] . $record['type']; //Aggregate multiple records if for some reason there are multiple for an IP/type combination
				if (!isset($toSend[$key])) {
					$toSend[$key] = $record;
				}
				else {
					$toSend[$key]['count'] += $record['count'];
				}
			}
			
			$toSend = array_values($toSend);
			
			try {
				$response = wp_remote_post(WORDFENCE_HACKATTEMPT_URL . 'multipleHackAttempts/?k=' . rawurlencode(wfConfig::get('apiKey')) . '&t=brute', array(
					'timeout' => 1,
					'user-agent' => "Wordfence.com UA " . (defined('WORDFENCE_VERSION') ? WORDFENCE_VERSION : '[Unknown version]'),
					'body' => 'IPs=' . rawurlencode(json_encode($toSend)),
					'headers' => array('Referer' => false),
				));
				
				if (is_wp_error($response)) {
					self::wfsnScheduleBatchReportFailedAttempts();
					return;
				}
			} 
			catch (Exception $err) {
				//Do nothing
			}
		}
		array_unshift($toDelete, $threshold);
		$wfdb->queryWriteIgnoreError("DELETE FROM {$p}wfSNIPCache WHERE (expiration < FROM_UNIXTIME(%d) AND count = 0)" . (count($toDelete) > 1 ? " OR id IN (" . rtrim(str_repeat('%d, ', count($toDelete) - 1), ', ') . ")" : ""), $toDelete);
		
		$remainingRows = $wfdb->querySingle("SELECT COUNT(*) FROM {$p}wfSNIPCache");
		if ($remainingRows > 0) {
			self::wfsnScheduleBatchReportFailedAttempts();
		}
	}
	private static function wfsnScheduleBatchReportFailedAttempts($timeToSend = null) {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		if ($timeToSend === null) {
			$timeToSend = time() + 30;
		}
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if (!wp_next_scheduled('wordfence_batchReportFailedAttempts')) {
			wp_schedule_single_event($timeToSend, 'wordfence_batchReportFailedAttempts');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}
	public static function wfsnIsBlocked($IP, $hitType){
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$wfdb = new wfDB();
		global $wpdb;
		$p = $wpdb->base_prefix;
		$endpointType = self::wfsnEndpointType();
		$cachedRecord = $wfdb->querySingleRec("SELECT id, body FROM {$p}wfSNIPCache WHERE IP = '%s' AND type = %d AND expiration > NOW()", $IP, $endpointType);
		if (isset($cachedRecord)) {
			$wfdb->queryWriteIgnoreError("UPDATE {$p}wfSNIPCache SET count = count + 1 WHERE id = %d", $cachedRecord['id']);
			if (preg_match('/BLOCKED:(\d+)/', $cachedRecord['body'], $matches) && (!self::getLog()->isWhitelisted($IP))) {
				return $matches[1];
			}
			return false;
		}
		
		try {
			$result = wp_remote_get(WORDFENCE_HACKATTEMPT_URL . 'hackAttempt/?k=' . rawurlencode(wfConfig::get('apiKey')) . 
																			'&IP=' . rawurlencode(filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? wfUtils::inet_aton($IP) : wfUtils::inet_pton($IP)) . 
																			'&t=' . rawurlencode($hitType) .
																			'&type=' . $endpointType, 
				array(
					'timeout' => 3,
					'user-agent' => "Wordfence.com UA " . (defined('WORDFENCE_VERSION') ? WORDFENCE_VERSION : '[Unknown version]'),
					'headers' => array('Referer' => false),
				));
			if (is_wp_error($result)) {
				return false;
			}
			$wfdb->queryWriteIgnoreError("INSERT INTO {$p}wfSNIPCache (IP, type, expiration, body) VALUES ('%s', %d, DATE_ADD(NOW(), INTERVAL %d SECOND), '%s')", $IP, $endpointType, 30, $result['body']);
			self::wfsnScheduleBatchReportFailedAttempts();
			if (preg_match('/BLOCKED:(\d+)/', $result['body'], $matches) && (!self::getLog()->isWhitelisted($IP))) {
				return $matches[1];
			}
			return false;
		} catch (Exception $err) {
			return false;
		}
	}
	public static function wfsnEndpointType() {
		$wploginPath = ABSPATH . 'wp-login.php';
		$type = 0; //Unknown
		if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
			$type = 2;
		}
		else if (isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] == $wploginPath) {
			$type = 1;
		}
		return $type;
	}
	public static function logoutAction(){
		$userID = get_current_user_id();
		$userDat = get_user_by('id', $userID);
		if(is_object($userDat)){
			self::getLog()->logLogin('logout', 0, $userDat->user_login);
		}
		// Unset the roadblock cookie
		if (!WFWAF_SUBDIRECTORY_INSTALL) {
			wfUtils::setcookie(wfWAF::getInstance()->getAuthCookieName(), ' ', time() - (86400 * 365), '/', null, wfUtils::isFullSSL(), true);
		}
	}
	public static function loginInitAction(){
		if(self::isLockedOut(wfUtils::getIP())){
			require('wfLockedOut.php');
		}
		
		self::doEarlyAccessLogging(); //Rate limiting
	}
	public static function authActionNew(&$username, &$passwd){ //As of php 5.4 we must denote passing by ref in the function definition, not the function call (as WordPress core does, which is a bug in WordPress).
		if(self::isLockedOut(wfUtils::getIP())){
			require('wfLockedOut.php');
		}
		
		if (isset($_POST['wordfence_twoFactorUser'])) { //Final stage of login -- get and verify 2fa code, make sure we load the appropriate user
			$userID = intval($_POST['wordfence_twoFactorUser']);
			$twoFactorNonce = preg_replace('/[^a-f0-9]/i', '', $_POST['wordfence_twoFactorNonce']);
			if (self::verifyTwoFactorIntermediateValues($userID, $twoFactorNonce)) {
				$user = get_user_by('ID', $userID);
				$username = $user->user_login;
				$passwd = $twoFactorNonce;
				$_POST['wordfence_userDat'] = $user;
				return;
			}
		}
		
		//Intermediate stage of login
		if(! $username){ return; }
		$userDat = get_user_by('login', $username);
		if (!$userDat) {
			$userDat = get_user_by('email', $username);
		}
		
		$_POST['wordfence_userDat'] = $userDat;
		if(preg_match(self::$passwordCodePattern, $passwd, $matches)){
			$_POST['wordfence_authFactor'] = $matches[1];
			$passwd = preg_replace('/^(.+)\s+wf([a-z0-9 ]+)$/i', '$1', $passwd);
			$_POST['pwd'] = $passwd;
		}
	}
	public static function authActionOld($username, $passwd){ //Code is identical to Newer function above except passing by ref ampersand. Some versions of PHP are throwing an error if we include the ampersand in PHP prior to 5.4.
		if(self::isLockedOut(wfUtils::getIP())){
			require('wfLockedOut.php');
		}
		
		if (isset($_POST['wordfence_twoFactorUser'])) { //Final stage of login -- get and verify 2fa code, make sure we load the appropriate user
			$userID = intval($_POST['wordfence_twoFactorUser']);
			$twoFactorNonce = preg_replace('/[^a-f0-9]/i', '', $_POST['wordfence_twoFactorNonce']);
			if (self::verifyTwoFactorIntermediateValues($userID, $twoFactorNonce)) {
				$user = get_user_by('ID', $userID);
				$username = $user->user_login;
				$passwd = $twoFactorNonce;
				$_POST['wordfence_userDat'] = $user;
				return;
			}
		}
		
		//Intermediate stage of login
		if(! $username){ return; }
		$userDat = get_user_by('login', $username);
		if (!$userDat) {
			$userDat = get_user_by('email', $username);
		}
		
		$_POST['wordfence_userDat'] = $userDat;
		if(preg_match(self::$passwordCodePattern, $passwd, $matches)){
			$_POST['wordfence_authFactor'] = $matches[1];
			$passwd = preg_replace('/^(.+)\s+wf([a-z0-9 ]+)$/i', '$1', $passwd);
			$_POST['pwd'] = $passwd;
		}
	}
	public static function getWPFileContent($file, $cType, $cName, $cVersion){
		if($cType == 'plugin'){
			if(preg_match('#^/?wp-content/plugins/[^/]+/#', $file)){
				$file = preg_replace('#^/?wp-content/plugins/[^/]+/#', '', $file);
			} else {
				//If user is using non-standard wp-content dir, then use /plugins/ in pattern to figure out what to strip off
				$file = preg_replace('#^.*[^/]+/plugins/[^/]+/#', '', $file);
			}
		} else if($cType == 'theme'){
			if(preg_match('#/?wp-content/themes/[^/]+/#', $file)){
				$file = preg_replace('#/?wp-content/themes/[^/]+/#', '', $file);
			} else {
				$file = preg_replace('#^.*[^/]+/themes/[^/]+/#', '', $file);
			}
		} else if($cType == 'core'){

		} else {
			return array('errorMsg' => "An invalid type was specified to get file.");
		}
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$contResult = $api->binCall('get_wp_file_content', array(
				'v' => wfUtils::getWPVersion(),
				'file' => $file,
				'cType' => $cType,
				'cName' => $cName,
				'cVersion' => $cVersion
				));
			if($contResult['data']){
				return array('fileContent' => $contResult['data']);
			} else {
				throw new Exception("We could not fetch a core WordPress file from the Wordfence API.");
			}
		} catch (Exception $e){
			return array('errorMsg' => wp_kses($e->getMessage(), array()));
		}
	}
	public static function ajax_sendDiagnostic_callback(){
		$inEmail = true;
		$body = "This email is the diagnostic from " . site_url() . ".\nThe IP address that requested this was: " . wfUtils::getIP() . "\nTicket Number/Forum Username: " . $_POST['ticket'];
		$sendingDiagnosticEmail = true;
		ob_start();
		require 'menu_tools_diagnostic.php';
		$body = nl2br($body) . ob_get_clean();
		$findReplace = array(
			'<th ' => '<th style="text-align:left;background-color:#222;color:#fff;"',
			'<th>' => '<th style="text-align:left;background-color:#222;color:#fff;">',
			'<td class="success"' => '<td style="font-weight:bold;color:#008c10;" class="success"',
			'<td class="error"' => '<td style="font-weight:bold;color:#d0514c;" class="error"',
			'<td class="inactive"' => '<td style="font-weight:bold;color:#666666;" class="inactive"',
		);
		$body = str_replace(array_keys($findReplace), array_values($findReplace), $body);
		$result = wfUtils::htmlEmail($_POST['email'], '[Wordfence] Diagnostic results (' . $_POST['ticket'] . ')', $body);
		return compact('result');
	}
	public static function ajax_sendTestEmail_callback(){
		$result = wp_mail($_POST['email'], "Wordfence Test Email", "This is a test email from " . site_url() . ".\nThe IP address that requested this was: " . wfUtils::getIP());
		$result = $result ? 'True' : 'False';
		return array('result' => $result);
	}
	public static function ajax_loadAvgSitePerf_callback(){
		$limit = preg_match('/^\d+$/', $_POST['limit']) ? $_POST['limit'] : 10;
		$wfdb = new wfDB();
		global $wpdb;
		$p = $wpdb->base_prefix;
		$rec = $wfdb->querySingleRec("select round(avg(domainLookupEnd),0) as domainLookupEnd, round(avg(connectEnd),0) as connectEnd, round(avg(responseStart),0) as responseStart, round(avg(responseEnd),0) as responseEnd, round(avg(domReady),0) as domReady, round(avg(loaded),0) as loaded from (select domainLookupEnd, connectEnd, responseStart, responseEnd, domReady, loaded from $p"."wfPerfLog order by ctime desc limit %d) as T", $limit);
		return $rec;
	}
	public static function ajax_addTwoFactor_callback(){
		if(! wfConfig::get('isPaid')){
			return array('errorMsg' => 'Cellphone Sign-in is only available to paid members. <a href="https://www.wordfence.com/gnl1twoFac3/wordfence-signup/" target="_blank" rel="noopener noreferrer">Click here to upgrade now.</a>');
		}
		$username = sanitize_text_field($_POST['username']);
		$phone = sanitize_text_field($_POST['phone']);
		$mode = sanitize_text_field($_POST['mode']);
		$user = get_user_by('login', $username);
		if(! $user){
			return array('errorMsg' => "The username you specified does not exist.");
		}
		
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		if (!is_array($twoFactorUsers)) {
			$twoFactorUsers = array();
		}
		for ($i = 0; $i < sizeof($twoFactorUsers); $i++) {
			if ($twoFactorUsers[$i][0] == $user->ID) {
				return array('errorMsg' => "The username you specified is already enabled.");
			}
		}
		
		if ($mode != 'phone' && $mode != 'authenticator') {
			return array('errorMsg' => "Unknown authentication mode.");
		}
		
		if ($mode == 'phone') {
			if (!preg_match('/^\+\d[\d\-]+$/', $phone)) {
				return array('errorMsg' => "The phone number you entered must start with a '+', then country code and then area code and number. It can only contain the starting plus sign and then numbers and dashes. It can not contain spaces. For example, a number in the United States with country code '1' would look like this: +1-123-555-1234");
			}
			$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
			try {
				$codeResult = $api->call('twoFactorTOTP_register', array(), array('phone' => $phone, 'mode' => $mode));
			}
			catch (Exception $e) {
				return array('errorMsg' => "Could not contact Wordfence servers to generate a verification code: " . wp_kses($e->getMessage(), array()));
			}
			
			$recoveryCodes = preg_replace('/[^a-f0-9]/i', '', $codeResult['recoveryCodes']);
			
			if (isset($codeResult['ok']) && $codeResult['ok']) {
				$secretID = $codeResult['id'];
			}
			else if (isset($codeResult['errorMsg']) && $codeResult['errorMsg']) {
				return array('errorMsg' => wp_kses($codeResult['errorMsg'], array()));
			}
			else {
				wordfence::status(4, 'info', "Could not gen verification code: " . var_export($codeResult, true));
				return array('errorMsg' => "We could not generate a verification code.");
			}
			self::twoFactorAdd($user->ID, $phone, '', 'phone', $secretID);
			return array(
				'ok' => 1,
				'userID' => $user->ID,
				'username' => $username,
				'homeurl' => preg_replace('#.*?//#', '', get_home_url()),
				'mode' => $mode,
				'phone' => $phone,
				'recoveryCodes' => $recoveryCodes,
			);
		}
		else if ($mode == 'authenticator') {
			$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
			try {
				$codeResult = $api->call('twoFactorTOTP_register', array(), array('mode' => $mode));
			}
			catch (Exception $e) {
				return array('errorMsg' => "Could not contact Wordfence servers to generate a verification code: " . wp_kses($e->getMessage(), array()));
			}
			
			/* Expected Fields:
				'ok' => 1,
				'secret' => $secret,
				'base32Secret' => $base32Secret,
				'recoveryCodes' => $codes,
				'uriQueryString' => $uriQueryString,
				'id' => $recordID,
			*/
			
			$secret = preg_replace('/[^a-f0-9]/i', '', $codeResult['secret']);
			$base32Secret = preg_replace('/[^a-z2-7]/i', '', $codeResult['base32Secret']); //Encoded in base32
			$recoveryCodes = preg_replace('/[^a-f0-9]/i', '', $codeResult['recoveryCodes']);
			$uriQueryString = preg_replace('/[^a-z0-9=&]/i', '', $codeResult['uriQueryString']);
			
			if (isset($codeResult['ok']) && $codeResult['ok']) {
				$secretID = $codeResult['id'];
			}
			else if (isset($codeResult['errorMsg']) && $codeResult['errorMsg']) {
				return array('errorMsg' => wp_kses($codeResult['errorMsg'], array()));
			}
			else {
				wordfence::status(4, 'info', "Could not gen verification code: " . var_export($codeResult, true));
				return array('errorMsg' => "We could not generate a verification code.");
			}
			self::twoFactorAdd($user->ID, '', '', 'authenticator', $secretID);
			return array(
				'ok' => 1,
				'userID' => $user->ID,
				'username' => $username,
				'homeurl' => preg_replace('#.*?//#', '', get_home_url()),
				'mode' => $mode,
				'secret' => $secret,
				'base32Secret' => $base32Secret,
				'recoveryCodes' => $recoveryCodes,
				'uriQueryString' => $uriQueryString,
			);
		}
		
		return array('errorMsg' => "Unknown two factor authentication mode.");
	}
	public static function ajax_twoFacActivate_callback() {
		$userID = sanitize_text_field($_POST['userID']);
		$code = sanitize_text_field($_POST['code']);
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		if (!is_array($twoFactorUsers)) {
			$twoFactorUsers = array();
		}
		$found = false;
		$user = false;
		for ($i = 0; $i < sizeof($twoFactorUsers); $i++) {
			if ($twoFactorUsers[$i][0] == $userID) {
				$mode = 'phone';
				if (isset($twoFactorUsers[$i][5]) && $twoFactorUsers[$i][5] == 'authenticator') {
					$mode = 'authenticator';
				}
				$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
				try {
					$codeResult = $api->call('twoFactorTOTP_verify', array(), array('totpid' => $twoFactorUsers[$i][6], 'code' => $code, 'mode' => $mode));
				}
				catch (Exception $e) {
					return array('errorMsg' => "Could not contact Wordfence servers to generate a verification code: " . wp_kses($e->getMessage(), array()));
				}
				
				if (isset($codeResult['ok']) && $codeResult['ok']) {
					$twoFactorUsers[$i][3] = 'activated';
					$twoFactorUsers[$i][4] = 0;
					$found = true;
					$user = $twoFactorUsers[$i];
					break;
				}
				else {
					return array('errorMsg' => "The code you entered is invalid. Cellphone sign-in will not be enabled for this user until you enter a valid code.");
				}
			}
		}
		if(! $found){
			return array('errorMsg' => "We could not find the user you are trying to activate. They may have been removed from the list of Cellphone Sign-in users. Please reload this page.");
		}
		wfConfig::set_ser('twoFactorUsers', $twoFactorUsers);
		$WPuser = get_userdata($userID);
		if ($mode == 'authenticator') {
			return array(
				'ok' => 1,
				'userID' => $userID,
				'username' => $WPuser->user_login,
				'status' => 'activated',
				'mode' => 'authenticator'
			);
		}
		
		return array(
			'ok' => 1,
			'userID' => $userID,
			'username' => $WPuser->user_login,
			'phone' => $user[1],
			'status' => 'activated',
			'mode' => 'phone'
			);
	}
	private static function twoFactorAdd($ID, $phone, $code, $mode = 'phone', $totpID){
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		if(! is_array($twoFactorUsers)){
			$twoFactorUsers = array();
		}
		for($i = 0; $i < sizeof($twoFactorUsers); $i++){
			if($twoFactorUsers[$i][0] == $ID || (! $twoFactorUsers[$i][0]) ){
				array_splice($twoFactorUsers, $i, 1);
				$i--;
			}
		}
		$twoFactorUsers[] = array($ID, $phone, $code /* deprecated parameter */, 'notActivated', time() + (86400 * 30) /* deprecated parameter */, $mode, $totpID); //expiry of code is 30 days in future
		wfConfig::set_ser('twoFactorUsers', $twoFactorUsers);
	}
	public static function ajax_loadTwoFactor_callback() {
		$users = wfConfig::get_ser('twoFactorUsers', array());
		$ret = array();
		foreach ($users as $user) {
			$WPuser = get_userdata($user[0]);
			if ($user) {
				if (isset($user[5]) && $user[5] == 'authenticator') { 
					$ret[] = array(
						'userID' => $user[0],
						'username' => $WPuser->user_login,
						'status' => $user[3],
						'mode' => 'authenticator'
					);
				}
				else {
					$ret[] = array(
						'userID' => $user[0],
						'username' => $WPuser->user_login,
						'phone' => $user[1],
						'status' => $user[3],
						'mode' => 'phone'
					);
				}
			}
		}
		return array('ok' => 1, 'users' => $ret);
	}
	public static function ajax_twoFacDel_callback(){
		$ID = $_POST['userID'];
		$twoFactorUsers = wfConfig::get_ser('twoFactorUsers', array());
		if(! is_array($twoFactorUsers)){
			$twoFactorUsers = array();
		}
		$deleted = false;
		for($i = 0; $i < sizeof($twoFactorUsers); $i++){
			if($twoFactorUsers[$i][0] == $ID){
				array_splice($twoFactorUsers, $i, 1);
				$deleted = true;
				$i--;
			}
		}
		wfConfig::set_ser('twoFactorUsers', $twoFactorUsers);
		if($deleted){
			return array('ok' => 1, 'userID' => $ID);
		} else {
			return array('errorMsg' => "That user has already been removed from the list.");
		}
	}
	public static function ajax_saveScanSchedule_callback(){
		if(! wfConfig::get('isPaid')){
			return array('errorMsg' => "Sorry but this feature is only available for paid customers.");
		}
		$schedDays = explode('|', $_POST['schedTxt']);
		$schedule = array();
		for($day = 0; $day <= 6; $day++){
			$schedule[$day] = explode(',', $schedDays[$day]);
		}
		$schedMode = $_POST['schedMode'];
		wfConfig::set_ser('scanSched', $schedule);
		wfConfig::set('schedMode', $schedMode);
		wordfence::scheduleScans();
		$nextTime = self::getNextScanStartTime();
		return array(
			'ok' => 1,
			'nextStart' => ($nextTime ? $nextTime : '')
			);
	}
	public static function getNextScanStartTimestamp() {
		$nextTime = false;
		$cron = _get_cron_array();
		foreach($cron as $key => $val){
			if(isset($val['wordfence_start_scheduled_scan'])){
				$nextTime = $key;
				break;
			}
		}
		return $nextTime;
	}
	public static function getNextScanStartTime($nextTime = null) {
		if ($nextTime === null) {
			$nextTime = self::getNextScanStartTimestamp();
		}
		
		if (!$nextTime) {
			return 'No scan is scheduled';
		}
		
		$difference = $nextTime - time();
		if ($difference < 1) {
			return "Next scan is starting now";
		}
		
		return 'Next scan in ' . wfUtils::makeDuration($difference) . ' (' . date('M j, Y g:i:s A', $nextTime + (3600 * get_option('gmt_offset'))) . ')';
	}
	public static function wordfenceStartScheduledScan($scheduledStartTime) {

		//If scheduled scans are not enabled in the global config option, then don't run a scheduled scan.
		if(wfConfig::get('scheduledScansEnabled') != '1'){
			return;
		}

		$minimumFrequency = (wfScan::isManualScanSchedule() ? 1800 : 43200);
		$lastScanStart = wfConfig::get('lastScheduledScanStart', 0);
		if($lastScanStart && (time() - $lastScanStart) < $minimumFrequency){
			//A scheduled scan was started in the last 30 mins (manual schedule) or 12 hours (automatic schedule), so skip this one.
			return;
		}
		wfConfig::set('originalScheduledScanStart', $scheduledStartTime);
		wfConfig::set('lastScheduledScanStart', time());
		wordfence::status(1, 'info', "Scheduled Wordfence scan starting at " . date('l jS \of F Y h:i:s A', current_time('timestamp')) );

		//We call this before the scan actually starts to advance the schedule for the next week.
		//This  ensures that if the scan crashes for some reason, the schedule will hold.
		wordfence::scheduleScans();

		wfScanEngine::startScan();
	}
	public static function scheduleScans() { //Idempotent. Deschedules everything and schedules the following week.
		self::unscheduleAllScans();
		if (wfScan::isManualScanSchedule()) {
			$sched = wfConfig::get_ser('scanSched', array());
			for ($scheduledDay = 0; $scheduledDay <= 6; $scheduledDay++) {
				//0 is sunday
				//6 is Saturday
				for ($scheduledHour = 0; $scheduledHour <= 23; $scheduledHour++) {
					if ($sched[$scheduledDay][$scheduledHour]) {
						$wpTime = current_time('timestamp');
						$currentDayOfWeek = date('w', $wpTime);
						$daysInFuture = $scheduledDay - $currentDayOfWeek; //It's monday and scheduledDay is Wed (3) then result is 2 days in future. It's Wed and sched day is monday, then result is 3 - 1 = -2
						if($daysInFuture < 0){ $daysInFuture += 7; } //Turns -2 into 5 days in future
						$currentHour = date('G', $wpTime);
						$secsOffset = ($scheduledHour - $currentHour) * 3600; //Offset from current hour, can be negative
						$secondsInFuture = ($daysInFuture * 86400) + $secsOffset; //Can be negative, so we schedule those 1 week ahead
						if($secondsInFuture < 1){
							$secondsInFuture += (86400 * 7); //Add a week
						}
						$futureTime = time() - (time() % 3600) + $secondsInFuture; //Modulo rounds down to top of the hour
						$futureTime += rand(0,3600); //Prevent a stampede of scans on our scanning server
						wordfence::status(4, 'info', "Scheduled time for day $scheduledDay hour $scheduledHour is: " . date('l jS \of F Y h:i:s A', $futureTime));
						self::scheduleSingleScan($futureTime);
					}
				}
			}
		}
		else {
			$noc1ScanSchedule = wfConfig::get_ser('noc1ScanSchedule', array());
			foreach ($noc1ScanSchedule as $timestamp) {
				$timestamp = wfUtils::denormalizedTime($timestamp);
				if ($timestamp > time()) {
					self::scheduleSingleScan($timestamp);
				}
			}
		}
	}
	public static function scheduleSingleScan($futureTime, $originalTime = false) {
		// Removed ability to activate on network site in v5.3.12
		if (is_main_site()) {
			if ($originalTime === false) {
				$originalTime = $futureTime;
			}
			wp_schedule_single_event($futureTime, 'wordfence_start_scheduled_scan', array((int) $originalTime));
			
			//Saving our own copy of the schedule because the wp-cron functions all require the args list to act
			$allScansScheduled = wfConfig::get_ser('allScansScheduled', array());
			$allScansScheduled[] = array('timestamp' => $futureTime, 'args' => array((int) $originalTime));
			wfConfig::set_ser('allScansScheduled', $allScansScheduled);
		}
	}
	private static function unscheduleAllScans() {
		$allScansScheduled = wfConfig::get_ser('allScansScheduled', array());
		foreach ($allScansScheduled as $entry) {
			wp_unschedule_event($entry['timestamp'], 'wordfence_start_scheduled_scan', $entry['args']);
		}
		wp_clear_scheduled_hook('wordfence_start_scheduled_scan');
		wfConfig::set_ser('allScansScheduled', array());
	}
	public static function ajax_saveCountryBlocking_callback(){
		if(! wfConfig::get('isPaid')){
			return array('errorMsg' => "Sorry but this feature is only available for paid customers.");
		}
		wfConfig::set('cbl_action', $_POST['blockAction']);
		wfConfig::set('cbl_countries', $_POST['codes']);
		wfConfig::set('cbl_redirURL', $_POST['redirURL']);
		wfConfig::set('cbl_loggedInBlocked', $_POST['loggedInBlocked']);
		wfConfig::set('cbl_loginFormBlocked', $_POST['loginFormBlocked']);
		wfConfig::set('cbl_restOfSiteBlocked', $_POST['restOfSiteBlocked']);
		wfConfig::set('cbl_bypassRedirURL', $_POST['bypassRedirURL']);
		wfConfig::set('cbl_bypassRedirDest', $_POST['bypassRedirDest']);
		wfConfig::set('cbl_bypassViewURL', $_POST['bypassViewURL']);
		return array('ok' => 1);
	}
	public static function ajax_sendActivityLog_callback(){
		$content = "SITE: " . site_url() . "\nPLUGIN VERSION: " . WORDFENCE_VERSION . "\nWP VERSION: " . wfUtils::getWPVersion() . "\nAPI KEY: " . wfConfig::get('apiKey') . "\nADMIN EMAIL: " . get_option('admin_email') . "\nLOG:\n\n";
		$wfdb = new wfDB();
		global $wpdb;
		$p = $wpdb->base_prefix;
		$q = $wfdb->querySelect("select ctime, level, type, msg from $p"."wfStatus order by ctime desc limit 10000");
		$timeOffset = 3600 * get_option('gmt_offset');
		foreach($q as $r){
			if($r['type'] == 'error'){
				$content .= "\n";
			}
			$content .= date(DATE_RFC822, $r['ctime'] + $timeOffset) . '::' . sprintf('%.4f', $r['ctime']) . ':' . $r['level'] . ':' . $r['type'] . '::' . wp_kses_data( (string) $r['msg']) . "\n";
		}
		$content .= "\n\n";

		ob_start();
		phpinfo();
		$phpinfo = ob_get_contents();
		ob_get_clean();

		$content .= $phpinfo;

		wp_mail($_POST['email'], "Wordfence Activity Log", $content);
		return array('ok' => 1);
	}
	public static function ajax_startTourAgain_callback(){
		wfConfig::set('tourClosed', 0);
		return array('ok' => 1);
	}
	public static function ajax_downgradeLicense_callback(){
		$api = new wfAPI('', wfUtils::getWPVersion());
		try {
			$keyData = $api->call('get_anon_api_key');
			if($keyData['ok'] && $keyData['apiKey']){
				wfConfig::set('apiKey', $keyData['apiKey']);
				wfConfig::set('isPaid', 0);
				//When downgrading we must disable all two factor authentication because it can lock an admin out if we don't.
				wfConfig::set_ser('twoFactorUsers', array());
				self::licenseStatusChanged();
				if (method_exists(wfWAF::getInstance()->getStorageEngine(), 'purgeIPBlocks')) {
					wfWAF::getInstance()->getStorageEngine()->purgeIPBlocks(wfWAFStorageInterface::IP_BLOCKS_BLACKLIST);
				}
			} else {
				throw new Exception("Could not understand the response we received from the Wordfence servers when applying for a free API key.");
			}
		} catch(Exception $e){
			return array('errorMsg' => "Could not fetch free API key from Wordfence: " . wp_kses($e->getMessage(), array()));
		}
		return array('ok' => 1);
	}
	public static function ajax_tourClosed_callback(){
		wfConfig::set('tourClosed', 1);
		return array('ok' => 1);
	}
	public static function ajax_welcomeClosed_callback(){
		wfConfig::set('welcomeClosed', 1);
		return array('ok' => 1);
	}
	public static function ajax_autoUpdateChoice_callback(){
		$choice = $_POST['choice'];
		wfConfig::set('autoUpdateChoice', '1');
		if($choice == 'yes'){
			wfConfig::set('autoUpdate', '1');
		} else {
			wfConfig::set('autoUpdate', '0');
		}
		return array('ok' => 1);
	}
	public static function ajax_adminEmailChoice_callback() {
		$choice = $_POST['choice'];
		wfConfig::set('adminEmailChoice', '1');
		if ($choice == 'mine') {
			$email = wp_get_current_user()->user_email;
			wfConfig::set('alertEmails', $email);
		}
		return array('ok' => 1);
	}
	public static function ajax_suPHPWAFUpdateChoice_callback() {
		$choice = $_POST['choice'];
		wfConfig::set('suPHPWAFUpdateChoice', '1');
		return array('ok' => 1);
	}
	public static function ajax_misconfiguredHowGetIPsChoice_callback() {
		$choice = $_POST['choice'];
		if ($choice == 'yes') {
			wfConfig::set('howGetIPs', wfConfig::get('detectProxyRecommendation', ''));
			
			if (isset($_POST['issueID'])) {
				$issueID = intval($_POST['issueID']);
				$wfIssues = new wfIssues();
				$wfIssues->updateIssue($issueID, 'delete');
				wfScanEngine::refreshScanNotification($wfIssues);
			}
		}
		else {
			wfConfig::set('misconfiguredHowGetIPsChoice' . WORDFENCE_VERSION, '1');
		}
		return array('ok' => 1);
	}
	public static function ajax_updateConfig_callback(){
		$key = $_POST['key'];
		$val = $_POST['val'];
		wfConfig::set($key, $val);
		
		if ($key == 'howGetIPs') {
			wfConfig::set('detectProxyNextCheck', false, wfConfig::DONT_AUTOLOAD);
			$ipAll = wfUtils::getIPPreview();
			$ip = wfUtils::getIP(true);
			return array('ok' => 1, 'ip' => $ip, 'ipAll' => $ipAll);
		}
		
		return array('ok' => 1);
	}
	public static function ajax_checkHtaccess_callback(){
		if(wfUtils::isNginx()){
			return array('nginx' => 1);
		}
		$file = wfCache::getHtaccessPath();
		if(! $file){
			return array('err' => "We could not find your .htaccess file to modify it.");
		}
		$fh = @fopen($file, 'r+');
		if(! $fh){
			$err = error_get_last();
			return array('err' => "We found your .htaccess file but could not open it for writing: " . $err['message']);
		}
		return array('ok' => 1);
	}
	public static function ajax_downloadHtaccess_callback(){
		$url = site_url();
		$url = preg_replace('/^https?:\/\//i', '', $url);
		$url = preg_replace('/[^a-zA-Z0-9\.]+/', '_', $url);
		$url = preg_replace('/^_+/', '', $url);
		$url = preg_replace('/_+$/', '', $url);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="htaccess_Backup_for_' . $url . '.txt"');
		$file = wfCache::getHtaccessPath();
		readfile($file);
		die();
	}
	public static function ajax_downloadLogFile_callback() {
		if (!isset($_GET['logfile'])) {
			status_header(400);
			nocache_headers();
			exit;
		}
		
		wfErrorLogHandler::outputErrorLog(stripslashes($_GET['logfile'])); //exits
	}
	public static function ajax_saveConfig_callback(){
		$reload = '';
		$opts = wfConfig::parseOptions();

		// These are now on the Diagnostics page, so they aren't sent across.
		foreach (self::$diagnosticParams as $param) {
			$opts[$param] = wfConfig::get($param);
		}

		$emails = array();
		foreach(explode(',', preg_replace('/[\r\n\s\t]+/', '', $opts['alertEmails'])) as $email){
			if(strlen($email) > 0){
				$emails[] = $email;
			}
		}
		if(sizeof($emails) > 0){
			$badEmails = array();
			foreach($emails as $email){
				if(! preg_match('/^[^@]+@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,11})$/i', $email)){
					$badEmails[] = $email;
				}
			}
			if(sizeof($badEmails) > 0){
				return array('errorMsg' => "The following emails are invalid: " . wp_kses(implode(', ', $badEmails), array()) );
			}
			$opts['alertEmails'] = implode(',', $emails);
		} else {
			$opts['alertEmails'] = '';
		}
		$opts['scan_exclude'] = wfUtils::cleanupOneEntryPerLine($opts['scan_exclude']);

		foreach (explode("\n", $opts['scan_include_extra']) as $regex) {
			if (@preg_match("/$regex/", "") === FALSE) {
				return array('errorMsg' => "\"" . esc_html($regex). "\" is not a valid regular expression");
			}
		}

		$whiteIPs = array();
		foreach(explode(',', preg_replace('/[\r\n\s\t]+/', ',', $opts['whitelisted'])) as $whiteIP){
			if(strlen($whiteIP) > 0){
				$whiteIPs[] = $whiteIP;
			}
		}
		if(sizeof($whiteIPs) > 0){
			$badWhiteIPs = array();
			$range = new wfUserIPRange();
			foreach($whiteIPs as $whiteIP){
				$range->setIPString($whiteIP);
				if (!$range->isValidRange()) {
					$badWhiteIPs[] = $whiteIP;
				}
			}
			if(sizeof($badWhiteIPs) > 0){
				return array('errorMsg' => "Please make sure you separate your IP addresses with commas. The following whitelisted IP addresses are invalid: " . wp_kses(implode(', ', $badWhiteIPs), array()) );
			}
			$opts['whitelisted'] = implode(',', $whiteIPs);
		} else {
			$opts['whitelisted'] = '';
		}
		$validUsers = array();
		$invalidUsers = array();
		foreach(explode(',', $opts['liveTraf_ignoreUsers']) as $val){
			$val = trim($val);
			if(strlen($val) > 0){
				if(get_user_by('login', $val)){
					$validUsers[] = $val;
				} else {
					$invalidUsers[] = $val;
				}
			}
		}
		$opts['loginSec_userBlacklist'] = wfUtils::cleanupOneEntryPerLine($opts['loginSec_userBlacklist']);

		$opts['apiKey'] = trim($opts['apiKey']);
		if($opts['apiKey'] && (! preg_match('/^[a-fA-F0-9]+$/', $opts['apiKey'])) ){ //User entered something but it's garbage.
			return array('errorMsg' => "You entered an API key but it is not in a valid format. It must consist only of characters A to F and 0 to 9.");
		}

		if(sizeof($invalidUsers) > 0){
			return array('errorMsg' => "The following users you selected to ignore in live traffic reports are not valid on this system: " . wp_kses(implode(', ', $invalidUsers), array()) );
		}
		if(sizeof($validUsers) > 0){
			$opts['liveTraf_ignoreUsers'] = implode(',', $validUsers);
		} else {
			$opts['liveTraf_ignoreUsers'] = '';
		}

		//liveTraf_ignoreIPs
		$validIPs = array();
		$invalidIPs = array();
		foreach(explode(',', preg_replace('/[\r\n\s\t]+/', '', $opts['liveTraf_ignoreIPs'])) as $val){
			if(strlen($val) > 0){
				if(wfUtils::isValidIP($val)){
					$validIPs[] = $val;
				} else {
					$invalidIPs[] = $val;
				}
			}
		}
		if(sizeof($invalidIPs) > 0){
			return array('errorMsg' => "The following IPs you selected to ignore in live traffic reports are not valid: " . wp_kses(implode(', ', $invalidIPs), array()) );
		}
		if(sizeof($validIPs) > 0){
			$opts['liveTraf_ignoreIPs'] = implode(',', $validIPs);
		}
		
		//howGetIPs_trusted_proxies
		$validIPs = array();
		$invalidIPs = array();
		$testIPs = preg_split('/[\r\n,]+/', $opts['howGetIPs_trusted_proxies']);
		foreach ($testIPs as $val) {
			if (strlen($val) > 0) {
				if (wfUtils::isValidIP($val) || wfUtils::isValidCIDRRange($val)) {
					$validIPs[] = $val;
				}
				else {
					$invalidIPs[] = $val;
				}
			}
		}
		if (sizeof($invalidIPs) > 0) {
			return array('errorMsg' => "The following IPs/ranges you selected to trust as proxies are not valid: " . wp_kses(implode(', ', $invalidIPs), array()) );
		}
		if (sizeof($validIPs) > 0) {
			$opts['howGetIPs_trusted_proxies'] = implode("\n", $validIPs);
		}

		//liveTraf_ignoreUA
		if(preg_match('/[a-zA-Z0-9\d]+/', $opts['liveTraf_ignoreUA'])){
			$opts['liveTraf_ignoreUA'] = trim($opts['liveTraf_ignoreUA']);
		} else {
			$opts['liveTraf_ignoreUA'] = '';
		}
		if(! $opts['other_WFNet']){
			$wfdb = new wfDB();
			global $wpdb;
			$p = $wpdb->base_prefix;
			$wfdb->queryWrite("delete from $p"."wfBlocks where wfsn=1 and permanent=0");
		}
		if($opts['howGetIPs'] != wfConfig::get('howGetIPs', '')){
			wfConfig::set('detectProxyNextCheck', false, wfConfig::DONT_AUTOLOAD);
		}
		$regenerateHtaccess = false;
		if (isset($opts['bannedURLs'])) {
			$opts['bannedURLs'] = preg_replace('/[\n\r]+/',',', $opts['bannedURLs']);
		}
		if(wfConfig::get('bannedURLs', false) != $opts['bannedURLs']){
			$regenerateHtaccess = true;
		}

		if (!is_numeric($opts['liveTraf_maxRows'])) {
			return array(
				'errorMsg' => 'Please enter a number for the amount of Live Traffic data to store.',
			);
		}


		foreach($opts as $key => $val){
			if($key != 'apiKey'){ //Don't save API key yet
				wfConfig::set($key, $val);
			}
		}

		if($opts['autoUpdate'] == '1'){
			wfConfig::enableAutoUpdate();
		} else if($opts['autoUpdate'] == '0'){
			wfConfig::disableAutoUpdate();
		}

		try {
			if ($opts['disableCodeExecutionUploads']) {
				wfConfig::disableCodeExecutionForUploads();
			} else {
				wfConfig::removeCodeExecutionProtectionForUploads();
			}
		} catch (wfConfigException $e) {
			return array('errorMsg' => $e->getMessage());
		}

		if (!empty($opts['email_summary_enabled'])) {
			wfConfig::set('email_summary_enabled', 1);
			wfConfig::set('email_summary_interval', $opts['email_summary_interval']);
			$opts['email_summary_excluded_directories'] = wfUtils::cleanupOneEntryPerLine($opts['email_summary_excluded_directories']);
			wfConfig::set('email_summary_excluded_directories', $opts['email_summary_excluded_directories']);
			wfActivityReport::scheduleCronJob();
		} else {
			wfConfig::set('email_summary_enabled', 0);
			wfActivityReport::disableCronJob();
		}

		if (wfConfig::get('other_hideWPVersion')) {
			wfUtils::hideReadme();
		} else {
			wfUtils::showReadme();
		}

		$paidKeyMsg = false;


		if(! $opts['apiKey']){ //Empty API key (after trim above), then try to get one.
			$api = new wfAPI('', wfUtils::getWPVersion());
			try {
				$keyData = $api->call('get_anon_api_key');
				if($keyData['ok'] && $keyData['apiKey']){
					wfConfig::set('apiKey', $keyData['apiKey']);
					wfConfig::set('isPaid', 0);
					$reload = 'reload';
					self::licenseStatusChanged();
				} else {
					throw new Exception("We could not understand the Wordfence server's response because it did not contain an 'ok' and 'apiKey' element.");
				}
			} catch(Exception $e){
				return array('errorMsg' => "Your options have been saved, but we encountered a problem. You left your API key blank, so we tried to get you a free API key from the Wordfence servers. However we encountered a problem fetching the free key: " . wp_kses($e->getMessage(), array()) );
			}
		} else if($opts['apiKey'] != wfConfig::get('apiKey')){
			$api = new wfAPI($opts['apiKey'], wfUtils::getWPVersion());
			try {
				$res = $api->call('check_api_key', array(), array());
				if($res['ok'] && isset($res['isPaid'])){
					wfConfig::set('apiKey', $opts['apiKey']);
					$reload = 'reload';
					wfConfig::set('isPaid', $res['isPaid']); //res['isPaid'] is boolean coming back as JSON and turned back into PHP struct. Assuming JSON to PHP handles bools.
					if($res['isPaid']){
						$paidKeyMsg = true;
					}
					self::licenseStatusChanged();
				} else {
					throw new Exception("We could not understand the Wordfence API server reply when updating your API key.");
				}
			} catch (Exception $e){
				return array('errorMsg' => "Your options have been saved. However we noticed you changed your API key and we tried to verify it with the Wordfence servers and received an error: " . wp_kses($e->getMessage(), array()) );
			}
		} else {
			$api = new wfAPI($opts['apiKey'], wfUtils::getWPVersion());
			try {
				$keyData = $api->call('ping_api_key', array(), array());
				if (isset($keyData['dashboard'])) {
					wfConfig::set('lastDashboardCheck', time());
					wfDashboard::processDashboardResponse($keyData['dashboard']);
				}
				if (isset($keyData['scanSchedule']) && is_array($keyData['scanSchedule'])) {
					wfConfig::set_ser('noc1ScanSchedule', $keyData['scanSchedule']);
					if (!wfScan::isManualScanSchedule()) {
						wordfence::scheduleScans();
					}
				}
			}
			catch (Exception $e){
				return array('errorMsg' => "Your options have been saved. However we tried to verify your API key with the Wordfence servers and received an error: " . wp_kses($e->getMessage(), array()) );
			}
		}
		
		wfNotification::reconcileNotificationsWithOptions();
		
		$ipAll = wfUtils::getIPPreview();
		$ip = wfUtils::getIP(true);
		return array('ok' => 1, 'reload' => $reload, 'paidKeyMsg' => $paidKeyMsg, 'ip' => $ip, 'ipAll' => $ipAll);
	}
	public static function ajax_savePartialConfig_callback() {
		$opts = wfConfig::parseOptions(true);
		
		if (isset($opts['alertEmails'])) {
			$emails = array();
			foreach (explode(',', preg_replace('/[\r\n\s\t]+/', '', $opts['alertEmails'])) as $email) {
				if (strlen($email) > 0) {
					$emails[] = $email;
				}
			}
			if (sizeof($emails) > 0) {
				$badEmails = array();
				foreach ($emails as $email) {
					if (!preg_match('/^[^@]+@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,11})$/i', $email)) {
						$badEmails[] = $email;
					}
				}
				if (sizeof($badEmails) > 0) { 
					return array('errorMsg' => "The following emails are invalid: " . wp_kses(implode(', ', $badEmails), array()) );
				}
				$opts['alertEmails'] = implode(',', $emails);
			}
			else {
				$opts['alertEmails'] = '';
			}
		}
		
		if (isset($opts['scan_exclude'])) {
			$opts['scan_exclude'] = wfUtils::cleanupOneEntryPerLine($opts['scan_exclude']);
		}
		
		if (isset($opts['scan_include_extra'])) {
			foreach (explode("\n", $opts['scan_include_extra']) as $regex) {
				if (@preg_match("/$regex/", "") === FALSE) {
					return array('errorMsg' => "\"" . esc_html($regex). "\" is not a valid regular expression");
				}
			}
		}
		
		if (isset($opts['whitelisted'])) {
			$whiteIPs = array();
			foreach (explode(',', preg_replace('/[\r\n\s\t]+/', ',', $opts['whitelisted'])) as $whiteIP) {
				if (strlen($whiteIP) > 0) {
					$whiteIPs[] = $whiteIP;
				}
			}
			if (sizeof($whiteIPs) > 0) {
				$badWhiteIPs = array();
				$range = new wfUserIPRange();
				foreach ($whiteIPs as $whiteIP) {
					$range->setIPString($whiteIP);
					if (!$range->isValidRange()) {
						$badWhiteIPs[] = $whiteIP;
					}
				}
				if (sizeof($badWhiteIPs) > 0) {
					return array('errorMsg' => "Please make sure you separate your IP addresses with commas. The following whitelisted IP addresses are invalid: " . wp_kses(implode(', ', $badWhiteIPs), array()) );
				}
				$opts['whitelisted'] = implode(',', $whiteIPs);
			}
			else {
				$opts['whitelisted'] = '';
			}
		}
		
		if (isset($opts['liveTraf_ignoreUsers'])) {
			$validUsers = array();
			$invalidUsers = array();
			foreach (explode(',', $opts['liveTraf_ignoreUsers']) as $val) {
				$val = trim($val);
				if (strlen($val) > 0) {
					if (get_user_by('login', $val)) {
						$validUsers[] = $val;
					}
					else {
						$invalidUsers[] = $val;
					}
				}
			}
			
			if (sizeof($invalidUsers) > 0) {
				return array('errorMsg' => "The following users you selected to ignore in live traffic reports are not valid on this system: " . wp_kses(implode(', ', $invalidUsers), array()) );
			}
			
			if (sizeof($validUsers) > 0) {
				$opts['liveTraf_ignoreUsers'] = implode(',', $validUsers);
			}
			else {
				$opts['liveTraf_ignoreUsers'] = '';
			}
		}
		
		if (isset($opts['loginSec_userBlacklist'])) {
			$opts['loginSec_userBlacklist'] = wfUtils::cleanupOneEntryPerLine($opts['loginSec_userBlacklist']);
		}
		
		if (isset($opts['liveTraf_ignoreIPs'])) {
			$validIPs = array();
			$invalidIPs = array();
			foreach (explode(',', preg_replace('/[\r\n\s\t]+/', '', $opts['liveTraf_ignoreIPs'])) as $val) {
				if (strlen($val) > 0) {
					if (wfUtils::isValidIP($val)) {
						$validIPs[] = $val;
					}
					else {
						$invalidIPs[] = $val;
					}
				}
			}
			if (sizeof($invalidIPs) > 0) {
				return array('errorMsg' => "The following IPs you selected to ignore in live traffic reports are not valid: " . wp_kses(implode(', ', $invalidIPs), array()) );
			}
			if (sizeof($validIPs) > 0) {
				$opts['liveTraf_ignoreIPs'] = implode(',', $validIPs);
			}
		}
		
		if (isset($opts['liveTraf_ignoreUA'])) {
			if (preg_match('/[a-zA-Z0-9\d]+/', $opts['liveTraf_ignoreUA'])) {
				$opts['liveTraf_ignoreUA'] = trim($opts['liveTraf_ignoreUA']);
			}
			else {
				$opts['liveTraf_ignoreUA'] = '';
			}
		}
		
		if (isset($opts['other_WFNet'])) {
			if (!$opts['other_WFNet']) {
				$wfdb = new wfDB();
				global $wpdb;
				$p = $wpdb->base_prefix;
				$wfdb->queryWrite("delete from $p"."wfBlocks where wfsn=1 and permanent=0");
			}
		}
		
		if (isset($opts['bannedURLs'])) {
			$opts['bannedURLs'] = preg_replace('/[\n\r]+/',',', $opts['bannedURLs']);
		}
		
		if (isset($opts['liveTraf_maxRows'])) {
			if (!is_numeric($opts['liveTraf_maxRows'])) {
				return array(
					'errorMsg' => 'Please enter a number for the amount of Live Traffic data to store.',
				);
			}
		}
		
		foreach($opts as $key => $val){
			wfConfig::set($key, $val);
		}

		if (isset($opts['autoUpdate'])) {
			if ($opts['autoUpdate'] == '1') {
				wfConfig::enableAutoUpdate();
			}
			else if ($opts['autoUpdate'] == '0') {
				wfConfig::disableAutoUpdate();
			}
		}
		
		if (isset($opts['disableCodeExecutionUploads'])) {
			try {
				if ($opts['disableCodeExecutionUploads']) {
					wfConfig::disableCodeExecutionForUploads();
				}
				else {
					wfConfig::removeCodeExecutionProtectionForUploads();
				}
			}
			catch (wfConfigException $e) {
				return array('errorMsg' => $e->getMessage());
			}
		}
		
		if (isset($opts['email_summary_enabled'])) {
			if (!empty($opts['email_summary_enabled'])) {
				wfConfig::set('email_summary_enabled', 1);
				wfConfig::set('email_summary_interval', $opts['email_summary_interval']);
				wfConfig::set('email_summary_excluded_directories', $opts['email_summary_excluded_directories']);
				wfActivityReport::scheduleCronJob();
			}
			else {
				wfConfig::set('email_summary_enabled', 0);
				wfActivityReport::disableCronJob();
			}
		}
		
		if (isset($opts['other_hideWPVersion'])) {
			if (wfConfig::get('other_hideWPVersion')) {
				wfUtils::hideReadme();
			}
			else {
				wfUtils::showReadme();
			}
		}
			
		return array('ok' => 1);
	}
	
	public static function ajax_updateIPPreview_callback() {
		$howGet = $_POST['howGetIPs'];
		
		$validIPs = array();
		$invalidIPs = array();
		$testIPs = preg_split('/[\r\n,]+/', $_POST['howGetIPs_trusted_proxies']);
		foreach ($testIPs as $val) {
			if (strlen($val) > 0) {
				if (wfUtils::isValidIP($val) || wfUtils::isValidCIDRRange($val)) {
					$validIPs[] = $val;
				}
				else {
					$invalidIPs[] = $val;
				}
			}
		}
		$trustedProxies = $validIPs;
		
		$ipAll = wfUtils::getIPPreview($howGet, $trustedProxies);
		$ip = wfUtils::getIPForField($howGet, $trustedProxies);
		return array('ok' => 1, 'ip' => $ip, 'ipAll' => $ipAll);
	}

	public static $diagnosticParams = array(
		'addCacheComment',
		'debugOn',
		'startScansRemotely',
		'ssl_verify',
		'disableConfigCaching',
		'betaThreatDefenseFeed',
	);

	public static function ajax_saveDebuggingConfig_callback() {
		foreach (self::$diagnosticParams as $param) {
			wfConfig::set($param, array_key_exists($param, $_POST) ? '1' : '0');
		}
		if (class_exists('wfWAFConfig')) {
			wfWAFConfig::set('betaThreatDefenseFeed', wfConfig::get('betaThreatDefenseFeed'));
		}
		return array('ok' => 1, 'reload' => false, 'paidKeyMsg' => '');
	}


	public static function ajax_hideFileHtaccess_callback(){
		$issues = new wfIssues();
		$issue  = $issues->getIssueByID($_POST['issueID']);
		if (!$issue) {
			return array('cerrorMsg' => "We could not find that issue in our database.");
		}
		
		if (!function_exists('get_home_path')) {
			include_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		$homeURL = get_home_url();
		$components = parse_url($homeURL);
		if ($components === false) {
			return array('cerrorMsg' => "An error occurred while trying to hide the file.");
		}
		
		$sitePath = '';
		if (isset($components['path'])) {
			$sitePath = trim($components['path'], '/');
		}
		
		$homePath = get_home_path();
		$file = $issue['data']['file'];
		$localFile = ABSPATH . '/' . $file; //The scanner uses ABSPATH as its base rather than get_home_path()
		$localFile = realpath($localFile);
		if (strpos($localFile, $homePath) !== 0) {
			return array('cerrorMsg' => "An invalid file was requested for hiding.");
		}
		$localFile = substr($localFile, strlen($homePath));
		$absoluteURIPath = trim($sitePath . '/' . $localFile, '/');
		$regexLocalFile = preg_replace('#/#', '/+', preg_quote($absoluteURIPath));
		$filename = basename($localFile);
		
		$htaccessContent = <<<HTACCESS
<IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_URI} ^/?{$regexLocalFile}$
        RewriteRule .* - [F,L,NC]
</IfModule>
<IfModule !mod_rewrite.c>
	<Files "{$filename}">
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		Deny from all
	</IfModule>
	</Files>
</IfModule>
HTACCESS;

		if (!wfUtils::htaccessPrepend($htaccessContent)) {
			return array('cerrorMsg' => "You don't have permission to repair .htaccess. You need to either fix the file manually using FTP or change the file permissions and ownership so that your web server has write access to repair the file.");
		}
		$issues->updateIssue($_POST['issueID'], 'delete');
		wfScanEngine::refreshScanNotification($issues);
		return array('ok' => 1);
	}
	public static function ajax_clearAllBlocked_callback(){
		$op = $_POST['op'];
		$wfLog = self::getLog();
		if($op == 'blocked'){
			wordfence::status(1, 'info', "Ajax request received to unblock All IPs including permanent blocks.");
			$wfLog->unblockAllIPs();
		} else if($op == 'locked'){
			$wfLog->unlockAllIPs();
		}
		return array('ok' => 1);
	}
	public static function ajax_unlockOutIP_callback(){
		$IP = $_POST['IP'];
		self::getLog()->unlockOutIP($IP);
		return array('ok' => 1);
	}
	public static function ajax_unblockIP_callback(){
		$IP = $_POST['IP'];
		self::getLog()->unblockIP($IP);
		return array('ok' => 1);
	}
	public static function ajax_permBlockIP_callback(){
		$IP = $_POST['IP'];
		$log = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		$log->blockIP($IP, "Manual permanent block by admin", false, true, false, 'manual');
		return array('ok' => 1);
	}
	public static function ajax_loadStaticPanel_callback(){
		$mode = $_POST['mode'];
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
		$hasMore = false;
		$wfLog = self::getLog();
		if($mode == 'topScanners' || $mode == 'topLeechers'){
			$results = $wfLog->getLeechers($mode, $offset);
		} else if($mode == 'blockedIPs'){
			$total = 0;
			$results = $wfLog->getBlockedIPs($offset, $total);
			$hasMore = ($offset + count($results)) < $total;
		} else if($mode == 'lockedOutIPs'){
			$total = 0;
			$results = $wfLog->getLockedOutIPs($offset, $total);
			$hasMore = ($offset + count($results)) < $total;
		} else if($mode == 'throttledIPs'){
			$results = $wfLog->getThrottledIPs($offset);
		}
		return array('ok' => 1, 'results' => $results, 'continuation' => ($offset > 0), 'hasMore' => $hasMore);
	}
	public static function ajax_loadBlockRanges_callback(){
		$results = self::getLog()->getRanges();
		return array('ok' => 1, 'results' => $results);
	}
	public static function ajax_unblockRange_callback(){
		$id = trim($_POST['id']);
		self::getLog()->unblockRange($id);
		return array('ok' => 1);
	}

	/**
	 * @return array
	 */
	public static function ajax_blockIPUARange_callback(){
		$ipRange = trim($_POST['ipRange']);
		$hostname = trim($_POST['hostname']);
		$uaRange = trim($_POST['uaRange']);
		$referer = trim($_POST['referer']);
		$reason = trim($_POST['reason']);
		if (preg_match('/\|+/', $ipRange . $uaRange . $referer . $hostname)) {
			return array('err' => 1, 'errorMsg' => "You are not allowed to include a pipe character \"|\" in your IP range, browser pattern or referer");
		}
		if ((!$ipRange) && wfUtils::isUABlocked($uaRange)) {
			return array('err' => 1, 'errorMsg' => "The browser pattern you specified will block you from your own website. We have not accepted this pattern to protect you from being blocked.");
		}
		if (fnmatch($referer, site_url(), FNM_CASEFOLD)) {
			return array('err' => 1, 'errorMsg' => "The referer pattern you specified matches your own website and will block visitors as they surf from one page to another on your site. You can't enter this pattern.");
		}

		if ($ipRange) {
			list($start_range, $end_range) = explode('-', $ipRange);
			if (!wfUtils::isValidIP($start_range) || !wfUtils::isValidIP($end_range)) {
				return array('err' => 1, 'errorMsg' => "The IP range you specified is not valid. Please specify an IP range like the following example: \"1.2.3.4 - 1.2.3.8\" without quotes.");
			}
			$ip1 = wfUtils::inet_pton($start_range);
			$ip2 = wfUtils::inet_pton($end_range);
			if (strcmp($ip1, $ip2) >= 0) {
				return array('err' => 1, 'errorMsg' => "The first IP address in your range must be less than the second IP address in your range.");
			}
			$clientIP = wfUtils::inet_pton(wfUtils::getIP());
			if (strcmp($ip1, $clientIP) <= 0 && strcmp($ip2, $clientIP) >= 0) {
				return array('err' => 1, 'errorMsg' => "You are trying to block yourself. Your IP address is " . wp_kses(wfUtils::getIP(), array()) . " which falls into the range " . wp_kses($ipRange, array()) . ". This blocking action has been cancelled so that you don't block yourself from your website.");
			}
			$ipRange = wfUtils::inet_ntop($ip1) . '-' . wfUtils::inet_ntop($ip2);
		}
		if ($hostname && !preg_match('/^[a-z0-9\.\*\-]+$/i', $hostname)) {
			return array('err' => 1, 'errorMsg' => 'The Hostname you specified is not valid');
		}
		$range = $ipRange . '|' . $uaRange . '|' . $referer . '|' . $hostname;
		self::getLog()->blockRange('IU', $range, $reason);
		return array('ok' => 1);
	}
	public static function ajax_whois_callback(){
		$val = trim($_POST['val']);
		$val = preg_replace('/[^a-zA-Z0-9\.\-:]+/', '', $val);
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		$result = $api->call('whois', array(), array(
			'val' => $val,
			));
		return array('ok' => 1, 'result' => $result['result']);
	}
	public static function ajax_blockIP_callback(){
		$IP = trim($_POST['IP']);
		$perm = (isset($_POST['perm']) && $_POST['perm'] == '1') ? true : false;
		$log = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		if (!wfUtils::isValidIP($IP)) {
			return array('err' => 1, 'errorMsg' => "Please enter a valid IP address to block.");
		}
		if ($IP == wfUtils::getIP()) {
			return array('err' => 1, 'errorMsg' => "You can't block your own IP address.");
		}
		$forcedWhitelistEntry = false;
		if ($log->isWhitelisted($IP, $forcedWhitelistEntry)) {
			$message = "The IP address " . wp_kses($IP, array()) . " is whitelisted and can't be blocked. You can remove this IP from the whitelist on the Wordfence options page.";
			if ($forcedWhitelistEntry) {
				$message = "The IP address " . wp_kses($IP, array()) . " is in a range of IP addresses that Wordfence does not block. The IP range may be internal or belong to a service safe to allow access for.";
			}
			return array('err' => 1, 'errorMsg' => $message);
		}
		if (wfConfig::get('neverBlockBG') != 'treatAsOtherCrawlers') { //Either neverBlockVerified or neverBlockUA is selected which means the user doesn't want to block google
			if (wfCrawl::isVerifiedGoogleCrawler($IP)) {
				return array('err' => 1, 'errorMsg' => "The IP address you're trying to block belongs to Google. Your options are currently set to not block these crawlers. Change this in Wordfence options if you want to manually block Google.");
			}
		}
		$log->blockIP($IP, $_POST['reason'], false, $perm, false, 'manual');
		return array('ok' => 1);
	}
	public static function ajax_reverseLookup_callback(){
		$ips = explode(',', $_POST['ips']);
		$res = array();
		foreach($ips as $ip){
			$res[$ip] = wfUtils::reverseLookup($ip);
		}
		return array('ok' => 1, 'ips' => $res);
	}
	public static function ajax_deleteIssue_callback(){
		$wfIssues = new wfIssues();
		$issueID = $_POST['id'];
		$wfIssues->deleteIssue($issueID);
		wfScanEngine::refreshScanNotification($wfIssues);
		return array('ok' => 1);
	}
	public static function ajax_updateAllIssues_callback(){
		$op = $_POST['op'];
		$i = new wfIssues();
		if($op == 'deleteIgnored'){
			$i->deleteIgnored();
		} else if($op == 'deleteNew'){
			$i->deleteNew();
		} else if($op == 'ignoreAllNew'){
			$i->ignoreAllNew();
		} else {
			return array('errorMsg' => "An invalid operation was called.");
		}
		wfScanEngine::refreshScanNotification($i);
		return array('ok' => 1);
	}
	public static function ajax_updateIssueStatus_callback(){
		$wfIssues = new wfIssues();
		$status = $_POST['status'];
		$issueID = $_POST['id'];
		if(! preg_match('/^(?:new|delete|ignoreP|ignoreC)$/', $status)){
			return array('errorMsg' => "An invalid status was specified when trying to update that issue.");
		}
		$wfIssues->updateIssue($issueID, $status);
		wfScanEngine::refreshScanNotification($wfIssues);
		return array('ok' => 1);
	}
	public static function ajax_killScan_callback(){
		wordfence::status(1, 'info', "Scan kill request received.");
		wordfence::status(10, 'info', "SUM_KILLED:A request was received to kill the previous scan.");
		wfUtils::clearScanLock(); //Clear the lock now because there may not be a scan running to pick up the kill request and clear the lock
		wfScanEngine::requestKill();
		return array(
			'ok' => 1,
			);
	}
	public static function ajax_loadIssues_callback(){
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
		$limit = isset($_POST['limit']) ? intval($_POST['limit']) : WORDFENCE_SCAN_ISSUES_PER_PAGE;
		
		$i = new wfIssues();
		$iss = $i->getIssues($offset, $limit);
		$counts = $i->getIssueCounts();
		return array(
			'issuesLists' => $iss,
			'issueCounts' => $counts,
			'summary' => $i->getSummaryItems(),
			'lastScanCompleted' => wfConfig::get('lastScanCompleted')
			);
	}
	public static function ajax_ticker_callback(){
		$wfdb = new wfDB();
		global $wpdb;
		$p = $wpdb->base_prefix;

		$serverTime = $wfdb->querySingle("select unix_timestamp()");
		$jsonData = array(
			'serverTime' => $serverTime,
			'serverMicrotime' => microtime(true),
			'msg' => wp_kses_data( (string) $wfdb->querySingle("SELECT msg FROM {$p}wfStatus WHERE level < 3 AND ctime > (UNIX_TIMESTAMP() - 3600) ORDER BY ctime DESC LIMIT 1"))
			);
		$events = array();
		$alsoGet = $_POST['alsoGet'];
		if(preg_match('/^logList_(404|hit|human|ruser|crawler|gCrawler|loginLogout)$/', $alsoGet, $m)){
			$type = $m[1];
			$newestEventTime = $_POST['otherParams'];
			$listType = 'hits';
			if($type == 'loginLogout'){
				$listType = 'logins';
			}
			$events = self::getLog()->getHits($listType, $type, $newestEventTime);
		} else if($alsoGet == 'perfStats'){
			$newestEventTime = $_POST['otherParams'];
			$events = self::getLog()->getPerfStats($newestEventTime);

		} else if ($alsoGet == 'liveTraffic') {
			if (get_site_option('wordfence_syncAttackDataAttempts') > 10) {
				self::syncAttackData(false);
			}
			$results = self::ajax_loadLiveTraffic_callback();
			$events = $results['data'];
			if (isset($results['sql'])) {
				$jsonData['sql'] = $results['sql'];
			}
		}
		/*
		$longest = 0;
		foreach($events as $e){
			$length = $e['domainLookupEnd'] + $e['connectEnd'] + $e['responseStart'] + $e['responseEnd'] + $e['domReady'] + $e['loaded'];
			$longest = $length > $longest ? $length : $longest;
		}
		*/
		$jsonData['events'] = $events;
		$jsonData['alsoGet'] = $alsoGet; //send it back so we don't load data if panel has changed
		//$jsonData['longestLine'] = $longest;
		return $jsonData;
	}
	public static function ajax_activityLogUpdate_callback(){
		$issues = new wfIssues();
		$scanFailed = $issues->hasScanFailed();
		$scanFailedSeconds = time() - $scanFailed;
		$scanFailedTiming = wfUtils::makeTimeAgo($scanFailedSeconds);
		
		$timeLimit = intval(wfConfig::get('scan_maxDuration'));
		if ($timeLimit < 1) {
			$timeLimit = WORDFENCE_DEFAULT_MAX_SCAN_TIME;
		}
		if ($scanFailedSeconds > $timeLimit) {
			$scanFailedTiming = 'more than ' . wfUtils::makeTimeAgo($timeLimit);
		}
		
		wfUtils::doNotCache();
		return array(
			'ok'                  => 1,
			'items'               => self::getLog()->getStatusEvents($_POST['lastctime']),
			'currentScanID'       => $issues->getScanTime(),
			'signatureUpdateTime' => wfConfig::get('signatureUpdateTime'),
			'scanFailed' 		  => ($scanFailed !== false && wfUtils::isScanRunning()),
			'scanFailedTiming'	  => $scanFailedTiming,
		);
	}
	public static function ajax_updateAlertEmail_callback(){
		$email = trim($_POST['email']);
		if(! preg_match('/[^\@]+\@[^\.]+\.[^\.]+/', $email)){
			return array( 'err' => "Invalid email address given.");
		}
		wfConfig::set('alertEmails', $email);
		return array('ok' => 1, 'email' => $email);
	}
	public static function ajax_bulkOperation_callback(){
		$op = sanitize_text_field($_POST['op']);
		if($op == 'del' || $op == 'repair'){
			$ids = $_POST['ids'];
			$filesWorkedOn = 0;
			$errors = array();
			$issues = new wfIssues();
			foreach($ids as $id){
				$id = intval($id); //Make sure input is a number.
				$issue = $issues->getIssueByID($id);
				if(! $issue){
					$errors[] = "Could not delete one of the files because we could not find the issue. Perhaps it's been resolved?";
					continue;
				}
				$file = $issue['data']['file'];
				$localFile = ABSPATH . '/' . $file;
				$localFile = realpath($localFile);
				if(strpos($localFile, ABSPATH) !== 0){
					$errors[] = "An invalid file was requested: " . wp_kses($file, array());
					continue;
				}
				if($op == 'del'){
					if(@unlink($localFile)){
						$issues->updateIssue($id, 'delete');
						$filesWorkedOn++;
					} else {
						$err = error_get_last();
						$errors[] = "Could not delete file " . wp_kses($file, array()) . ". Error was: " . wp_kses($err['message'], array());
					}
				} else if($op == 'repair'){
					$dat = $issue['data'];
					$result = self::getWPFileContent($dat['file'], $dat['cType'], $dat['cName'], $dat['cVersion']);
					if($result['cerrorMsg']){
						$errors[] = $result['cerrorMsg'];
						continue;
					} else if(! $result['fileContent']){
						$errors[] = "We could not get the original file of " . wp_kses($file, array()) . " to do a repair.";
						continue;
					}

					if(preg_match('/\.\./', $file)){
						$errors[] = "An invalid file " . wp_kses($file, array()) . " was specified for repair.";
						continue;
					}
					$fh = fopen($localFile, 'w');
					if(! $fh){
						$err = error_get_last();
						if(preg_match('/Permission denied/i', $err['message'])){
							$errMsg = "You don't have permission to repair " . wp_kses($file, array()) . ". You need to either fix the file manually using FTP or change the file permissions and ownership so that your web server has write access to repair the file.";
						} else {
							$errMsg = "We could not write to " . wp_kses($file, array()) . ". The error was: " . $err['message'];
						}
						$errors[] = $errMsg;
						continue;
					}
					flock($fh, LOCK_EX);
					$bytes = fwrite($fh, $result['fileContent']);
					flock($fh, LOCK_UN);
					fclose($fh);
					if($bytes < 1){
						$errors[] = "We could not write to " . wp_kses($file, array()) . ". ($bytes bytes written) You may not have permission to modify files on your WordPress server.";
						continue;
					}
					$filesWorkedOn++;
					$issues->updateIssue($id, 'delete');
				}
			}
			$verb = $op == 'del' ? 'Deleted' : 'Repaired';
			$verb2 = $op == 'del' ? 'delete' : 'repair';
			if($filesWorkedOn > 0 && sizeof($errors) > 0){
				$headMsg = "$verb some files with errors";
				$bodyMsg = "$verb $filesWorkedOn files but we encountered the following errors with other files: " . implode('<br />', $errors);
			} else if($filesWorkedOn > 0){
				$headMsg = "$verb $filesWorkedOn files successfully";
				$bodyMsg = "$verb $filesWorkedOn files successfully. No errors were encountered.";
			} else if(sizeof($errors) > 0){
				$headMsg = "Could not $verb2 files";
				$bodyMsg = "We could not $verb2 any of the files you selected. We encountered the following errors: " . implode('<br />', $errors);
			} else {
				$headMsg = "Nothing done";
				$bodyMsg = "We didn't $verb2 anything and no errors were found.";
			}
			
			wfScanEngine::refreshScanNotification($issues);
			return array('ok' => 1, 'bulkHeading' => $headMsg, 'bulkBody' => $bodyMsg);
		} else {
			return array('errorMsg' => "Invalid bulk operation selected");
		}
	}
	public static function ajax_deleteFile_callback($issueID = null){
		if ($issueID === null) {
			$issueID = intval($_POST['issueID']);
		}
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if(! $issue){
			return array('errorMsg' => "Could not delete file because we could not find that issue.");
		}
		if(! $issue['data']['file']){
			return array('errorMsg' => "Could not delete file because that issue does not appear to be a file related issue.");
		}
		$file = $issue['data']['file'];
		$localFile = ABSPATH . '/' . $file;
		$localFile = realpath($localFile);
		if(strpos($localFile, ABSPATH) !== 0){
			return array('errorMsg' => "An invalid file was requested for deletion.");
		}
		if ($localFile === ABSPATH . 'wp-config.php' && file_exists(ABSPATH . 'wp-config.php') && empty($_POST['forceDelete'])) {
			return array(
				'errorMsg' => "You must first download a backup copy of your <code>wp-config.php</code> prior to deleting the infected file.
				The <code>wp-config.php</code> file contains your database credentials which you will need to restore normal site operations.
				Your site will <b>NOT</b> function once the <code>wp-config.php</code> has been deleted.
				<p>
					<a class='wf-btn wf-btn-default' href='/?_wfsf=download&nonce=" . wp_create_nonce('wp-ajax') . "&file=". rawurlencode($file) ."' target='_blank' rel=\"noopener noreferrer\" onclick=\"jQuery('#wp-config-force-delete').show();\">Download a backup copy</a>
					<a style='display:none' id='wp-config-force-delete' class='wf-btn wf-btn-default' href='#' target='_blank' rel=\"noopener noreferrer\" onclick='WFAD.deleteFile($issueID, true); return false;'>Delete wp-config.php</a>
				</p>",
			);
		}

		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$adminURL = network_admin_url('admin.php?' . http_build_query(array(
				'page'               => 'Wordfence',
				'wfScanAction'       => 'promptForCredentials',
				'wfFilesystemAction' => 'deleteFile',
				'issueID'            => $issueID,
				'nonce'              => wp_create_nonce('wp-ajax'),
			)));

		if (!self::requestFilesystemCredentials($adminURL, null, true, false)) {
			return array(
				'ok'               => 1,
				'needsCredentials' => true,
				'redirect'         => $adminURL,
			);
		}

		if($wp_filesystem->delete($localFile)){
			$wfIssues->updateIssue($issueID, 'delete');
			wfScanEngine::refreshScanNotification($wfIssues);
			return array(
				'ok' => 1,
				'localFile' => $localFile,
				'file' => $file
				);
		} else {
			$err = error_get_last();
			return array('errorMsg' => "Could not delete file " . wp_kses($file, array()) . ". The error was: " . wp_kses($err['message'], array()));
		}
	}
	public static function ajax_deleteDatabaseOption_callback(){
		/** @var wpdb $wpdb */
		global $wpdb;
		$issueID = intval($_POST['issueID']);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array('errorMsg' => "Could not remove the option because we could not find that issue.");
		}
		if (empty($issue['data']['option_name'])) {
			return array('errorMsg' => "Could not remove the option because that issue does not appear to be a database related issue.");
		}
		$prefix = $wpdb->get_blog_prefix($issue['data']['site_id']);
		if ($wpdb->query($wpdb->prepare("DELETE FROM {$prefix}options WHERE option_name = %s", $issue['data']['option_name']))) {
			$wfIssues->updateIssue($issueID, 'delete');
			wfScanEngine::refreshScanNotification($wfIssues);
			return array(
				'ok'          => 1,
				'option_name' => $issue['data']['option_name'],
			);
		} else {
			return array('errorMsg' => "Could not remove the option " . esc_html($issue['data']['option_name']) . ". The error was: " . esc_html($wpdb->last_error));
		}
	}
	public static function ajax_fixFPD_callback(){
		$issues = new wfIssues();
		$issue  = $issues->getIssueByID($_POST['issueID']);
		if (!$issue) {
			return array('cerrorMsg' => "We could not find that issue in our database.");
		}

		$htaccess = ABSPATH . '/.htaccess';
		$change   = "<IfModule mod_php5.c>\n\tphp_value display_errors 0\n</IfModule>\n<IfModule mod_php7.c>\n\tphp_value display_errors 0\n</IfModule>";
		$content  = "";
		if (file_exists($htaccess)) {
			$content = file_get_contents($htaccess);
		}

		if (@file_put_contents($htaccess, trim($content . "\n" . $change), LOCK_EX) === false) {
			return array('cerrorMsg' => "You don't have permission to repair .htaccess. You need to either fix the file
				manually using FTP or change the file permissions and ownership so that your web server has write access to repair the file.");
		}
		if (wfScanEngine::testForFullPathDisclosure()) {
			// Didn't fix it, so revert the changes and return an error
			file_put_contents($htaccess, $content, LOCK_EX);
			return array(
				'cerrorMsg' => "Modifying the .htaccess file did not resolve the issue, so the original .htaccess file
				was restored. You can fix this manually by setting <code>display_errors</code> to <code>Off</code> in
				your php.ini if your site is on a VPS or dedicated server that you control.",
			);
		}
		$issues->updateIssue($_POST['issueID'], 'delete');
		wfScanEngine::refreshScanNotification($issues);
		return array('ok' => 1);
	}
	public static function ajax_restoreFile_callback($issueID = null){
		if ($issueID === null) {
			$issueID = intval($_POST['issueID']);
		}
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if(! $issue){
			return array('cerrorMsg' => "We could not find that issue in our database.");
		}

		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$adminURL = network_admin_url('admin.php?' . http_build_query(array(
				'page'               => 'Wordfence',
				'wfScanAction'       => 'promptForCredentials',
				'wfFilesystemAction' => 'restoreFile',
				'issueID'            => $issueID,
				'nonce'              => wp_create_nonce('wp-ajax'),
			)));

		if (!self::requestFilesystemCredentials($adminURL, null, true, false)) {
			return array(
				'ok'               => 1,
				'needsCredentials' => true,
				'redirect'         => $adminURL,
			);
		}

		$dat = $issue['data'];
		$result = self::getWPFileContent($dat['file'], $dat['cType'], (isset($dat['cName']) ? $dat['cName'] : ''), (isset($dat['cVersion']) ? $dat['cVersion'] : ''));
		$file = $dat['file'];
		if(isset($result['cerrorMsg']) && $result['cerrorMsg']){
			return $result;
		} else if(! $result['fileContent']){
			return array('cerrorMsg' => "We could not get the original file to do a repair.");
		}

		if(preg_match('/\.\./', $file)){
			return array('cerrorMsg' => "An invalid file was specified for repair.");
		}
		$localFile = rtrim(ABSPATH, '/') . '/' . preg_replace('/^[\.\/]+/', '', $file);
		if ($wp_filesystem->put_contents($localFile, $result['fileContent'])) {
			$wfIssues->updateIssue($issueID, 'delete');
			wfScanEngine::refreshScanNotification($wfIssues);
			return array(
				'ok'   => 1,
				'file' => $localFile,
			);
		}
		return array(
			'cerrorMsg' => "We could not write to that file. You may not have permission to modify files on your WordPress server.",
		);
	}
	public static function ajax_scan_callback(){
		self::status(4, 'info', "Ajax request received to start scan.");
		$err = wfScanEngine::startScan();
		if($err){
			return array('errorMsg' => wp_kses($err, array()));
		} else {
			return array("ok" => 1);
		}
	}
	public static function ajax_exportSettings_callback(){
		/** @var wpdb $wpdb */
		global $wpdb;

		$keys = wfConfig::getExportableOptionsKeys();
		$export = array();
		foreach($keys as $key){
			$export[$key] = wfConfig::get($key, '');
		}
		$export['scanScheduleJSON'] = json_encode(wfConfig::get_ser('scanSched', array()));
		$export['schedMode'] = wfConfig::get('schedMode', '');

		// Any user supplied blocked IPs.
		$export['_blockedIPs'] = $wpdb->get_results('SELECT *, HEX(IP) as IP FROM ' . $wpdb->base_prefix . 'wfBlocks WHERE wfsn = 0 AND permanent = 1');

		// Any advanced blocking stuff too.
		$export['_advancedBlocking'] = $wpdb->get_results('SELECT * FROM ' . $wpdb->base_prefix . 'wfBlocksAdv');

		try {
			$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
			$res = $api->call('export_options', array(), $export);
			if($res['ok'] && $res['token']){
				return array(
					'ok' => 1,
					'token' => $res['token'],
					);
			} else {
				throw new Exception("Invalid response: " . var_export($res, true));
			}
		} catch(Exception $e){
			return array('err' => "An error occurred: " . $e->getMessage());
		}
	}
	public static function importSettings($token){
		/** @var wpdb $wpdb */
		global $wpdb;

		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		$res = $api->call('import_options', array(), array('token' => $token));
		$totalSet = 0;
		if($res['ok'] && $res['options']){
			$keys = wfConfig::getExportableOptionsKeys();
			foreach($keys as $key){
				if(isset($res['options'][$key])){
					wfConfig::set($key, $res['options'][$key]);
					$totalSet++;
				}
			}
			if(isset($res['options']['scanScheduleJSON']) && isset($res['options']['schedMode'])){
				$scanSched = json_decode($res['options']['scanScheduleJSON']);
				wfConfig::set_ser('scanSched', $scanSched);
				wfConfig::set('schedMode', $res['options']['schedMode']);
				$totalSet += 2;
			}

			if (!empty($res['options']['_blockedIPs']) && is_array($res['options']['_blockedIPs'])) {
				foreach ($res['options']['_blockedIPs'] as $row) {
					if (!empty($row['IP'])) {
						$row['IP'] = pack('H*', $row['IP']);
						if (!$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->base_prefix . 'wfBlocks WHERE IP = %s', $row['IP']))) {
							$wpdb->insert($wpdb->base_prefix . 'wfBlocks', $row);
						}
					}
				}
			}

			if (!empty($res['options']['_advancedBlocking']) && is_array($res['options']['_advancedBlocking'])) {
				foreach ($res['options']['_advancedBlocking'] as $row) {
					if (!empty($row['blockString']) && !$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->base_prefix . 'wfBlocksAdv WHERE blockString = %s', $row['blockString']))) {
						unset($row['id']);
						$wpdb->insert($wpdb->base_prefix . 'wfBlocksAdv', $row);
					}
				}
			}

			return $totalSet;
		} else if($res['err']){
			throw new Exception($res['err']);
		} else {
			throw new Exception("Invalid response from Wordfence servers during import.");
		}
	}
	public static function ajax_startPasswdAudit_callback(){
		if(! wfAPI::SSLEnabled()){
			return array('errorMsg' => "Your server does not support SSL via cURL. To use this feature you need to make sure you have the PHP cURL library installed and enabled and have openSSL enabled so that you can communicate securely with our servers. This ensures that your password hashes remain secure and are double-encrypted when this feature is used. To fix this, please contact your hosting provider or site admin and ask him or her to install and enable cURL and openssl.");
		}
		if(! function_exists('openssl_public_encrypt')){
			return array('errorMsg' => "Your server does not have openssl installed. Specifically we require the openssl_public_encrypt() function to use this feature. Please ask your site admin or hosting provider to install 'openssl' and the openssl PHP libraries. We use these for public key encryption to securely send your password hashes to our server for auditing.");
		}
		global $wpdb;
		$email = $_POST['emailAddr'];
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
			return array(
				'errorMsg' => "Please enter a valid email address.",
				);
		}
		$suspended = wp_suspend_cache_addition();
		wp_suspend_cache_addition(true);
		$auditType = $_POST['auditType'];
		$symKey = wfCrypt::makeSymHexKey(32); //hex digits, so 128 bit -- 256 bit is supported in MySQL 5.7.4 but many are using older
		$admins = "";
		$users = "";
		$query = $wpdb->prepare("SELECT ID, AES_ENCRYPT(user_pass, %s) AS crypt_pass FROM " . $wpdb->users, $symKey);
		$dbh = $wpdb->dbh;
		$useMySQLi = (is_object($dbh) && $wpdb->use_mysqli);
		if ($useMySQLi) { //If direct-access MySQLi is available, we use it to minimize the memory footprint instead of letting it fetch everything into an array first
			$result = $dbh->query($query);
			if (!is_object($result)) {
				return array(
					'errorMsg' => "We were unable to generate the user list for your password audit.",
				);
			}
			while ($rec = $result->fetch_assoc()) {
				$isAdmin = wfUtils::isAdmin($rec['ID']);
				if ($isAdmin && ($auditType == 'admin' || $auditType == 'both')) {
					$admins .= $rec['ID'] . ':' . base64_encode($rec['crypt_pass']) . '|';
				}
				else if($auditType == 'user' || $auditType == 'both') {
					$users .= $rec['ID'] . ':' . base64_encode($rec['crypt_pass']) . '|';
				}
			}
		}
		else {
			$q1 = $wpdb->get_results($query, ARRAY_A);
			foreach($q1 as $rec) {
				$isAdmin = wfUtils::isAdmin($rec['ID']);
				if($isAdmin && ($auditType == 'admin' || $auditType == 'both') ) {
					$admins .= $rec['ID'] . ':' . base64_encode($rec['crypt_pass']) . '|';
				} else if($auditType == 'user' || $auditType == 'both') {
					$users .= $rec['ID'] . ':' . base64_encode($rec['crypt_pass']) . '|';
				}
			}
		}
		
		$admins = rtrim($admins,'|');
		$users = rtrim($users,'|');
		//error_log($admins);
		//error_log($users);
		wp_suspend_cache_addition($suspended);

		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$res = $api->call('password_audit', array(), array(
				'auditType' => $auditType,
				'email' => $email,
				'pubCryptSymKey' => wfCrypt::pubCrypt($symKey),
				'users' => $users,
				'admins' => $admins,
				'type' => 2,
				), true); //Force SSL
			if(is_array($res)){
				if(isset($res['ok']) && $res['ok'] == '1'){
					return array(
						'ok' => 1
					);
				} else if(isset($res['notPaid']) && $res['notPaid'] == '1'){
					return array(
						'errorMsg' => "You are not using a Premium version of Wordfence. This feature is available to Premium Wordfence members only.",
						);
				} else if(isset($res['tooManyJobs']) && $res['tooManyJobs'] == '1'){
					return array(
						'errorMsg' => "You already have a password audit running. Please wait until it finishes before starting another.",
						);
				} else {
					throw new Exception("An unrecognized response was received from the Wordfence servers.");
				}
			} else {
				return array(
					'errorMsg' => "We received an invalid response when trying to submit your password audit.",
					);
			}
		} catch(Exception $e){
			return array(
				'errMsg' => "We could not submit your password audit: " . $e,
				);
		}
	}
	public static function ajax_weakPasswordsFix_callback(){
		$mode = $_POST['mode'];
		$ids = explode(',', $_POST['ids']);
		$homeURL = home_url();
		$count = 0;
		if($mode == 'fix'){
			foreach($ids as $userID){
				$user = get_user_by('id', $userID);
				if($user){
					$passwd = wp_generate_password();
					$count++;
					wp_set_password($passwd, $userID);
					wp_mail($user->user_email, "Your Password on $homeURL Has Been Changed.", wfUtils::tmpl('email_passwdChanged.php', array(
						'siteURL' => site_url(),
						'homeURL' => home_url(),
						'loginURL' => wp_login_url(),
						'username' => $user->user_login,
						'passwd' => $passwd,
						)));
				}
			}
			return array(
				'ok' => 1,
				'title' => "Fixed $count Weak Passwords",
				'msg' => "We created new passwords for $count site members and emailed them the new password with instructions."
				);

		} else if($mode == 'email'){
			foreach($ids as $userID){
				$user = get_user_by('id', $userID);
				if($user){
					$count++;
					wp_mail($user->user_email, "Please Change Your Password on $homeURL", wfUtils::tmpl('email_pleaseChangePasswd.php', array(
						'siteURL' => site_url(),
						'homeURL' => home_url(),
						'username' => $user->user_login,
						'loginURL' => wp_login_url()
						)));
				}
			}
			return array(
				'ok' => 1,
				'title' => "Notified $count Users",
				'msg' => "We sent an email to $count site members letting them know that they have a weak password and suggesting that they sign in and change their password to a stronger one."
				);
		}
	}
	public static function ajax_passwdLoadResults_callback(){
		if(! wfAPI::SSLEnabled()){ return array('ok' => 1); } //If user hits start passwd audit they will get a helpful message. We don't want an error popping up for every ajax call if SSL is not supported.
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$res = $api->call('password_load_results', array(), array(), true);
		} catch(Exception $e){
			return array('errorMsg' => "Could not load password audit results: " . $e);
		}
		$finalResults = array();
		if(is_array($res) && isset($res['ok']) && $res['ok']){
			if(is_array($res['results'])){
				for($i = 0; $i < sizeof($res['results']); $i++){
					//$meta = get_user_meta($res['results'][$i]['userID'], 'wp_capabilities', true);
					//$res['results'][$i]['isAdmin'] = (isset($meta['administrator']) && $meta['administrator']) ? '1' : '';
					$user = new WP_User($res['results'][$i]['wpUserID']);
					if(is_object($user)){
						$passMD5 = strtoupper(md5($user->user_pass));
						if($passMD5 != $res['results'][$i]['hashMD5']){ //Password has changed, so exclude this result
							continue;
						}
						$item =  $res['results'][$i];
						$item['username'] = $user->user_login;
						$item['email'] = $user->user_email;
						$item['firstName'] = $user->first_name;
						$item['lastName'] = $user->last_name;
						$item['starredPassword'] = $res['results'][$i]['pwFirstLetter'] . str_repeat('*', $res['results'][$i]['pwLength'] - 1);
						//crackTime and crackDifficulty are fields too.
						$finalResults[] = $item;
					}
				}
			}

			return array(
				'ok' => 1,
				'results' => $finalResults,
				);
		} else {
			return array('ok' => 1); //fail silently
		}
	}
	public static function ajax_passwdLoadJobs_callback(){
		if(! wfAPI::SSLEnabled()){ return array('ok' => 1); } //If user hits start passwd audit they will get a helpful message. We don't want an error popping up for every ajax call if SSL is not supported.
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$res = $api->call('password_load_jobs', array(), array(), true);
		} catch(Exception $e){
			return array('errorMsg' => "Could not load password audit jobs: " . $e);
		}
		if(is_array($res) && isset($res['ok']) && $res['ok']){
			return array(
				'ok' => 1,
				'results' => $res['results'],
				);
		} else {
			return array('ok' => 1); //fail silently
		}
	}
	public static function ajax_killPasswdAudit_callback(){
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$res = $api->call('password_kill_job', array(), array( 'jobID' => $_POST['jobID'] ), true);
		} catch(Exception $e){
			return array('errorMsg' => "Could not stop requested audit: " . $e);
		}
		if(is_array($res) && isset($res['ok']) && $res['ok']){
			return array(
				'ok' => 1,
				);
		} else {
			return array('errorMsg' => "We could not stop the requested password audit."); //fail silently
		}
	}
	public static function ajax_deletePasswdAudit_callback(){
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$res = $api->call('password_delete_job', array(), array( 'jobID' => $_POST['jobID']));
		} catch(Exception $e){
			return array('errorMsg' => "Could not delete the job you specified: " . $e);
		}
		return array('ok' => 1);
	}
	public static function ajax_importSettings_callback(){
		$token = $_POST['token'];
		try {
			$totalSet = self::importSettings($token);
			return array(
				'ok' => 1,
				'totalSet' => $totalSet,
				);
		} catch(Exception $e){
			return array('err' => "An error occurred: " . $e->getMessage());
		}
	}
	public static function ajax_dismissNotification_callback() {
		$id = $_POST['id'];
		$n = wfNotification::getNotificationForID($id);
		if ($n !== null) {
			$n->markAsRead();
		}
		return array(
			'ok' => 1,
		);
	}
	public static function ajax_utilityScanForBlacklisted_callback() {
		if (wfUtils::isScanRunning()) {
			return array('wait' => 2); //Can't run while a scan is running since the URL hoover is currently implemented like a singleton
		}
		
		$pageURL = stripslashes($_POST['url']);
		$source = stripslashes($_POST['source']);
		$apiKey = wfConfig::get('apiKey');
		$wp_version = wfUtils::getWPVersion();
		$h = new wordfenceURLHoover($apiKey, $wp_version);
		$h->hoover(1, $source);
		$hooverResults = $h->getBaddies();
		if ($h->errorMsg) {
			$h->cleanup();
			return array('wait' => 3, 'errorMsg' => $h->errorMsg); //Unable to contact noc1 to verify
		} 
		$h->cleanup();
		if (sizeof($hooverResults) > 0 && isset($hooverResults[1])) {
			$hresults = $hooverResults[1];
			$count = count($hresults);
			if ($count > 0) {
				new wfNotification(null, wfNotification::PRIORITY_HIGH_WARNING, "Page contains {$count} malware URL" . ($count == 1 ? '' : 's') . ': ' . esc_html($pageURL), 'wfplugin_malwareurl_' . md5($pageURL), null, array(array('link' => network_admin_url('admin.php?page=WordfenceScan'), 'label' => 'Run a Scan')));
				return array('bad' => $count);
			}
		}
		return array('ok' => 1);
	}
	public static function ajax_dashboardShowMore_callback() {
		$grouping = $_POST['grouping'];
		$period = $_POST['period'];
		
		$dashboard = new wfDashboard();
		if ($grouping == 'ips') {
			$data = null;
			if ($period == '24h') { $data = $dashboard->ips24h; }
			else if ($period == '7d') { $data = $dashboard->ips7d; }
			else if ($period == '30d') { $data = $dashboard->ips30d; }
			
			if ($data !== null) {
				foreach ($data as &$d) {
					$d['IP'] = esc_html(wfUtils::inet_ntop($d['IP']));
					$d['blockCount'] = esc_html(number_format_i18n($d['blockCount']));
					$d['countryFlag'] = esc_attr(wfUtils::getBaseURL() . 'images/flags/' . esc_attr(strtolower($d['countryCode'])) . '.png');
					$d['countryName'] = esc_html($d['countryName']);
				}
				return array('ok' => 1, 'data' => $data);
			}
		}
		else if ($grouping == 'logins') {
			$data = null;
			if ($period == 'success') { $data = $dashboard->loginsSuccess; }
			else if ($period == 'fail') { $data = $dashboard->loginsFail; }
			
			if ($data !== null) {
				$data = array_slice($data, 0, 100);
				foreach ($data as &$d) {
					$d['ip'] = esc_html($d['ip']);
					$d['name'] = esc_html($d['name']);
					if (time() - $d['t'] < 86400) {
						$d['t'] = esc_html(wfUtils::makeTimeAgo(time() - $d['t']) . ' ago');
					}
					else {
						$d['t'] = esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), (int) $d['t']));
					}
				}
				return array('ok' => 1, 'data' => $data);
			}
		}
		
		return array('error' => 'Unknown dashboard data set.');
	}
	public static function startScan(){
		wfScanEngine::startScan();
	}
	public static function templateRedir(){
		if (!empty($_GET['wordfence_lh'])) {
			self::ajax_lh_callback();
			exit;
		}

		$wfFunc = !empty($_GET['_wfsf']) && is_string($_GET['_wfsf']) ? $_GET['_wfsf'] : '';

		//Logging
		self::doEarlyAccessLogging();
		//End logging


		if(! ($wfFunc == 'diff' || $wfFunc == 'view' || $wfFunc == 'viewOption' || $wfFunc == 'sysinfo' || $wfFunc == 'cronview' || $wfFunc == 'dbview' || $wfFunc == 'conntest' || $wfFunc == 'unknownFiles' || $wfFunc == 'IPTraf' || $wfFunc == 'viewActivityLog' || $wfFunc == 'testmem' || $wfFunc == 'testtime' || $wfFunc == 'download' || $wfFunc == 'blockedIPs' || ($wfFunc == 'debugWAF' && WFWAF_DEBUG))){
			return;
		}
		if(! wfUtils::isAdmin()){
			return;
		}

		$nonce = $_GET['nonce'];
		if(! wp_verify_nonce($nonce, 'wp-ajax')){
			echo "Bad security token. It may have been more than 12 hours since you reloaded the page you came from. Try reloading the page you came from. If that doesn't work, please sign out and sign-in again.";
			exit(0);
		}
		if($wfFunc == 'diff'){
			self::wfFunc_diff();
		} else if($wfFunc == 'view'){
			self::wfFunc_view();
		} else if($wfFunc == 'viewOption'){
			self::wfFunc_viewOption();
		} else if($wfFunc == 'sysinfo') {
			require( 'sysinfo.php' );
		} else if($wfFunc == 'dbview'){
				require('dbview.php');
		} else if($wfFunc == 'cronview') {
			require('cronview.php');
		} else if($wfFunc == 'conntest'){
			require('conntest.php');
		} else if($wfFunc == 'unknownFiles'){
			require('unknownFiles.php');
		} else if($wfFunc == 'IPTraf'){
			self::wfFunc_IPTraf();
		} else if($wfFunc == 'viewActivityLog'){
			self::wfFunc_viewActivityLog();
		} else if($wfFunc == 'testmem'){
			self::wfFunc_testmem();
		} else if($wfFunc == 'testtime'){
			self::wfFunc_testtime();
		} else if($wfFunc == 'download'){
			self::wfFunc_download();
		} else if($wfFunc == 'blockedIPs'){
			self::wfFunc_blockedIPs();
		} else if($wfFunc == 'debugWAF' && WFWAF_DEBUG){
			self::wfFunc_debugWAF();
		}
		exit(0);
	}
	public static function memtest_error_handler($errno, $errstr, $errfile, $errline){
		echo "Error received: $errstr\n";
	}
	private static function wfFunc_testtime(){
		header('Content-Type: text/plain');
		@error_reporting(E_ALL);
		wfUtils::iniSet('display_errors','On');
		set_error_handler('wordfence::memtest_error_handler', E_ALL);

		echo "Wordfence process duration benchmarking utility version " . WORDFENCE_VERSION . ".\n";
		echo "This utility tests how long your WordPress host allows a process to run.\n\n--Starting test--\n";
		echo "Starting timed test. This will take at least three minutes. Seconds elapsed are printed below.\nAn error after this line is not unusual. Read it and the elapsed seconds to determine max process running time on your host.\n";
		for($i = 1; $i <= 180; $i++){
			echo "\n$i:";
			for($j = 0; $j < 1000; $j++){
				echo '.';
			}
			flush();
			sleep(1);
		}
		echo "\n--Test complete.--\n\nCongratulations, your web host allows your PHP processes to run at least 3 minutes.\n";
		exit();
	}
	private static function wfFunc_testmem(){
		header('Content-Type: text/plain');
		@error_reporting(E_ALL);
		wfUtils::iniSet('display_errors','On');
		set_error_handler('wordfence::memtest_error_handler', E_ALL);
		
		$maxMemory = ini_get('memory_limit');
		$last = strtolower(substr($maxMemory, -1));
		$maxMemory = (int) $maxMemory;
		
		$configuredMax = wfConfig::get('maxMem', 0);
		if ($configuredMax <= 0) {
			if ($last == 'g') { $configuredMax = $maxMemory * 1024; }
			else if ($last == 'm') { $configuredMax = $maxMemory; }
			else if ($last == 'k') { $configuredMax = $maxMemory / 1024; }
			$configuredMax = floor($configuredMax);
		}
		
		$stepSize = 5242880; //5 MB

		echo "Wordfence Memory benchmarking utility version " . WORDFENCE_VERSION . ".\n";
		echo "This utility tests if your WordPress host respects the maximum memory configured\nin their php.ini file, or if they are using other methods to limit your access to memory.\n\n--Starting test--\n";
		echo "Current maximum memory configured in php.ini: " . ini_get('memory_limit') . "\n";
		echo "Current memory usage: " . sprintf('%.2f', memory_get_usage(true) / (1024 * 1024)) . "M\n";
		echo "Attempting to set max memory to {$configuredMax}M.\n";
		wfUtils::iniSet('memory_limit', ($configuredMax + 1) . 'M'); //Allow a little extra for testing overhead
		echo "Starting memory benchmark. Seeing an error after this line is not unusual. Read the error carefully\nto determine how much memory your host allows. We have requested {$configuredMax} megabytes.\n";
		
		if (memory_get_usage(true) < 1) {
			echo "Exiting test because memory_get_usage() returned a negative number\n";
			exit();
		}
		if (memory_get_usage(true) > (1024 * 1024 * 1024)) {
			echo "Exiting because current memory usage is greater than a gigabyte.\n";
			exit();
		}
		
		//256 bytes
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ012345678900000000000000000000000000000000000000000000000000000000000000000000000000000000000000011111111111111111222222222222222222233333333333333334444444444444444444444444555555555555666666666666666666";
		
		$currentUsage = memory_get_usage(true);
		$tenMB = 10 * 1024 * 1024;
		$start = ceil($currentUsage / $tenMB) * $tenMB - $currentUsage; //Start at the closest 10 MB increment to the current usage
		$configuredMax = $configuredMax * 1048576; //Bytes
		$testLimit = $configuredMax - memory_get_usage(true);
		$finalUsage = '0';
		while ($start <= $testLimit) {
			$accumulatedMemory = str_repeat($chars, $start / 256);
			
			$finalUsage = sprintf('%.2f', (memory_get_usage(true) / 1024 / 1024));
			echo "Tested up to " . $finalUsage . " megabytes.\n";
			if ($start == $testLimit) { break; }
			$start = min($start + $stepSize, $testLimit);
			
			if (memory_get_usage(true) > $configuredMax) { break; }
			
			unset($accumulatedMemory);
		}
		echo "--Test complete.--\n\nYour web host allows you to use at least {$finalUsage} megabytes of memory for each PHP process hosting your WordPress site.\n";
		exit();
	}
	public static function wfLogPerfHeader(){
		$ajaxURL = admin_url('admin-ajax.php');
		$ajaxURL = preg_replace('/^https?:/i', '', $ajaxURL);
		$scriptURL = wfUtils::getBaseURL() . '/js/perf.js?v=' . WORDFENCE_VERSION;
		$scriptURL = preg_replace('/^https?:/i', '', $scriptURL);
		#Load as external script async so we don't slow page down.
		echo <<<EOL
<script type="text/javascript">
window['wordfenceAjaxURL'] = "$ajaxURL";
(function(url){
if(/(?:Chrome\/26\.0\.1410\.63 Safari\/537\.31|WordfenceTestMonBot)/.test(navigator.userAgent)){ return; }
var wfscr = document.createElement('script');
wfscr.type = 'text/javascript';
wfscr.async = true;
wfscr.src = url;
(document.getElementsByTagName('head')[0]||document.getElementsByTagName('body')[0]).appendChild(wfscr);
})('$scriptURL');
</script>
EOL;
	}
	public static function wfLogHumanHeader(){
		//Final check in case this was added as an action before the request was fully initialized
		if (self::getLog()->getCurrentRequest()->jsRun || !wfConfig::liveTrafficEnabled()) {
			return;
		}
		
		self::$hitID = self::getLog()->logHit();
		if (self::$hitID) {
			$URL = home_url('/?wordfence_lh=1&hid=' . wfUtils::encrypt(self::$hitID));
			$URL = addslashes(preg_replace('/^https?:/i', '', $URL));
			#Load as external script async so we don't slow page down.
			echo <<<HTML
<script type="text/javascript">
(function(url){
	if(/(?:Chrome\/26\.0\.1410\.63 Safari\/537\.31|WordfenceTestMonBot)/.test(navigator.userAgent)){ return; }
	var addEvent = function(evt, handler) {
		if (window.addEventListener) {
			document.addEventListener(evt, handler, false);
		} else if (window.attachEvent) {
			document.attachEvent('on' + evt, handler);
		}
	};
	var removeEvent = function(evt, handler) {
		if (window.removeEventListener) {
			document.removeEventListener(evt, handler, false);
		} else if (window.detachEvent) {
			document.detachEvent('on' + evt, handler);
		}
	};
	var evts = 'contextmenu dblclick drag dragend dragenter dragleave dragover dragstart drop keydown keypress keyup mousedown mousemove mouseout mouseover mouseup mousewheel scroll'.split(' ');
	var logHuman = function() {
		var wfscr = document.createElement('script');
		wfscr.type = 'text/javascript';
		wfscr.async = true;
		wfscr.src = url + '&r=' + Math.random();
		(document.getElementsByTagName('head')[0]||document.getElementsByTagName('body')[0]).appendChild(wfscr);
		for (var i = 0; i < evts.length; i++) {
			removeEvent(evts[i], logHuman);
		}
	};
	for (var i = 0; i < evts.length; i++) {
		addEvent(evts[i], logHuman);
	}
})('$URL');
</script>
HTML;
		}
	}
	public static function shutdownAction(){
	}
	public static function wfFunc_viewActivityLog(){
		require('viewFullActivityLog.php');
		exit(0);
	}
	public static function wfFunc_IPTraf(){
		$IP = $_GET['IP'];
		if(!wfUtils::isValidIP($IP)){
			echo "An invalid IP address was specified.";
			exit(0);
		}
		$reverseLookup = wfUtils::reverseLookup($IP);
		$wfLog = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		$results = array_merge(
			$wfLog->getHits('hits', 'hit', 0, 10000, $IP),
			$wfLog->getHits('hits', '404', 0, 10000, $IP)
			);
		usort($results, 'wordfence::iptrafsort');
		for($i = 0; $i < sizeof($results); $i++){
			if(array_key_exists($i + 1, $results)){
				$results[$i]['timeSinceLastHit'] = sprintf('%.4f', $results[$i]['ctime'] - $results[$i + 1]['ctime']);
			} else {
				$results[$i]['timeSinceLastHit'] = '';
			}
		}
		require('IPTraf.php');
		exit(0);
	}
	public static function iptrafsort($b, $a){
		if($a['ctime'] == $b['ctime']){ return 0; }
		return ($a['ctime'] < $b['ctime']) ? -1 : 1;
	}

	public static function wfFunc_viewOption() {
		/** @var wpdb $wpdb */
		global $wpdb;
		$site_id = !empty($_GET['site_id']) ? absint($_GET['site_id']) : get_current_blog_id();
		$option_name = !empty($_GET['option']) ? $_GET['option'] : false;

		$prefix = $wpdb->get_blog_prefix($site_id);

		$option_value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$prefix}options WHERE option_name = %s", $option_name));

		header('Content-type: text/plain');
		exit($option_value);
	}

	public static function wfFunc_view(){
		wfUtils::doNotCache();
		if (WORDFENCE_DISABLE_FILE_VIEWER) {
			echo "File access blocked. (WORDFENCE_DISABLE_FILE_VIEWER is true)";
			exit();
		}
		$localFile = ABSPATH . preg_replace('/^(?:\.\.|[\/]+)/', '', sanitize_text_field($_GET['file']));
		if(strpos($localFile, '..') !== false){
			echo "Invalid file requested. (Relative paths not allowed)";
			exit();
		}
		if(preg_match('/[\'\"<>\!\{\}\(\)\&\@\%\$\*\+\[\]\?]+/', $localFile)){
			echo "File contains illegal characters.";
			exit();
		}
		$cont = @file_get_contents($localFile);
		$isEmpty = false;
		if(! $cont){
			if(file_exists($localFile) && filesize($localFile) === 0){ //There's a remote possibility that very large files on 32 bit systems will return 0 here, but it's about 1 in 2 billion
				$isEmpty = true;
			} else {
				$err = error_get_last();
				echo "We could not open the requested file for reading. The error was: " . $err['message'];
				exit(0);
			}
		}
		$fileMTime = @filemtime($localFile);
		$fileMTime = date('l jS \of F Y h:i:s A', $fileMTime);
		try {
			if(wfUtils::fileOver2Gigs($localFile)){
				$fileSize = "Greater than 2 Gigs";
			} else {
				$fileSize = @filesize($localFile); //Checked if over 2 gigs above
				$fileSize = number_format($fileSize, 0, '', ',') . ' bytes';
			}
		} catch(Exception $e){ $fileSize = 'Unknown file size.'; }

		require 'wfViewResult.php';
		exit(0);
	}
	public static function wfFunc_diff(){
		wfUtils::doNotCache();
		if (WORDFENCE_DISABLE_FILE_VIEWER) {
			echo "File access blocked. (WORDFENCE_DISABLE_FILE_VIEWER is true)";
			exit();
		}
		if(preg_match('/[\'\"<>\!\{\}\(\)\&\@\%\$\*\+\[\]\?]+/', $_GET['file'])){
			echo "File contains illegal characters.";
			exit();
		}

		$result = self::getWPFileContent($_GET['file'], $_GET['cType'], $_GET['cName'], $_GET['cVersion']);
		if( isset( $result['errorMsg'] ) && $result['errorMsg']){
			echo wp_kses($result['errorMsg'], array());
			exit(0);
		} else if(! $result['fileContent']){
			echo "We could not get the contents of the original file to do a comparison.";
			exit(0);
		}

		$localFile = realpath(ABSPATH . '/' . preg_replace('/^[\.\/]+/', '', $_GET['file']));
		$localContents = file_get_contents($localFile);
		if($localContents == $result['fileContent']){
			$diffResult = '';
		} else {
			$diff = new Diff(
				//Treat DOS and Unix files the same
				preg_split("/(?:\r\n|\n)/", $result['fileContent']),
				preg_split("/(?:\r\n|\n)/", $localContents),
				array()
				);
			$renderer = new Diff_Renderer_Html_SideBySide;
			$diffResult = $diff->Render($renderer);
		}
		require 'diffResult.php';
		exit(0);
	}

	public static function wfFunc_download() {
		wfUtils::doNotCache();
		if (WORDFENCE_DISABLE_FILE_VIEWER) {
			echo "File access blocked. (WORDFENCE_DISABLE_FILE_VIEWER is true)";
			exit();
		}
		$localFile = ABSPATH . preg_replace('/^(?:\.\.|[\/]+)/', '', sanitize_text_field($_GET['file']));
		if (strpos($localFile, '..') !== false) {
			echo "Invalid file requested. (Relative paths not allowed)";
			exit();
		}
		if (preg_match('/[\'\"<>\!\{\}\(\)\&\@\%\$\*\+\[\]\?]+/', $localFile)) {
			echo "File contains illegal characters.";
			exit();
		}
		if (!file_exists($localFile)) {
			exit('File does not exist.');
		}

		$filename = basename($localFile);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . filesize($localFile));
		readfile($localFile);
		exit;
	}
	
	public static function wfFunc_blockedIPs() {
		$wfLog = self::getLog();
		$blocks = $wfLog->getBlockedIPs();
		
		$output = '';
		if (is_array($blocks)) {
			foreach ($blocks as $entry) {
				$output .= $entry['IP'] . "\n";
			}
		}		
				
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . get_bloginfo('name', 'raw') . ' - Blocked IPs.txt"');
		header('Content-Length: ' . strlen($output));
		
		echo $output;
		exit;
	}

	/**
	 *
	 */
	public static function wfFunc_debugWAF() {
		$data = array();
		if (!empty($_GET['hitid'])) {
			$data['hit'] = new wfRequestModel($_GET['hitid']);
			if ($data['hit']->actionData) {
				$data['hitData'] = (object) wfRequestModel::unserializeActionData($data['hit']->actionData);
			}
			echo wfView::create('waf/debug', $data);
		}
	}

	public static function initAction(){
		if(wfConfig::liveTrafficEnabled() && (! wfConfig::get('disableCookies', false)) ){
			self::setCookie();
		}
		// This is more of a hurdle, but might stop an automated process.
		// if (current_user_can('administrator')) {
		// 	$adminUsers = new wfAdminUserMonitor();
		// 	if ($adminUsers->isEnabled() && !$adminUsers->isAdminUserLogged(get_current_user_id())) {
		// 		define('DISALLOW_FILE_MODS', true);
		// 	}
		// }

		$currentUserID = get_current_user_id();
		$role = wordfence::getCurrentUserRole();
		if (!WFWAF_SUBDIRECTORY_INSTALL) {
			try {
				$authCookie = wfWAF::getInstance()->parseAuthCookie();
				if (is_user_logged_in() &&
					(
						!$authCookie ||
						(int) $currentUserID !== (int) $authCookie['userID'] ||
						$role !== $authCookie['role']
					)
				) {
					wfUtils::setcookie(wfWAF::getInstance()->getAuthCookieName(),
						$currentUserID . '|' . $role . '|' .
						wfWAF::getInstance()->getAuthCookieValue($currentUserID, $role),
						time() + 43200, COOKIEPATH, COOKIE_DOMAIN, wfUtils::isFullSSL(), true);
				}
			} catch (wfWAFStorageFileException $e) {
				error_log($e->getMessage());
			}
		}

		if (wfConfig::get('other_hideWPVersion')) {

			global $wp_version;
			global $wp_styles;

			if (!($wp_styles instanceof WP_Styles)) {
				$wp_styles = new WP_Styles();
			}
			if ($wp_styles->default_version === $wp_version) {
				$wp_styles->default_version = wp_hash($wp_styles->default_version);
			}

			foreach ($wp_styles->registered as $key => $val) {
				if ($wp_styles->registered[$key]->ver === $wp_version) {
					$wp_styles->registered[$key]->ver = wp_hash($wp_styles->registered[$key]->ver);
				}
			}

			global $wp_scripts;
			if (!($wp_scripts instanceof WP_Scripts)) {
				$wp_scripts = new WP_Scripts();
			}
			if ($wp_scripts->default_version === $wp_version) {
				$wp_scripts->default_version = wp_hash($wp_scripts->default_version);
			}

			foreach ($wp_scripts->registered as $key => $val) {
				if ($wp_scripts->registered[$key]->ver === $wp_version) {
					$wp_scripts->registered[$key]->ver = wp_hash($wp_scripts->registered[$key]->ver);
				}
			}
		}
	}
	private static function setCookie(){
		$cookieName = 'wfvt_' . crc32(site_url());
		$c = isset($_COOKIE[$cookieName]) ? isset($_COOKIE[$cookieName]) : false;
		if($c){
			self::$newVisit = false;
		} else {
			self::$newVisit = true;
		}
		wfUtils::setcookie($cookieName, uniqid(), time() + 1800, '/', null, wfUtils::isFullSSL(), true);
	}
	public static function admin_init(){
		if(! wfUtils::isAdmin()){ return; }
		foreach(array(
			'activate', 'scan', 'updateAlertEmail', 'sendActivityLog', 'restoreFile', 'startPasswdAudit',
			'deletePasswdAudit', 'weakPasswordsFix', 'passwdLoadResults', 'killPasswdAudit', 'passwdLoadJobs',
			'exportSettings', 'importSettings', 'bulkOperation', 'deleteFile', 'deleteDatabaseOption', 'removeExclusion',
			'activityLogUpdate', 'ticker', 'loadIssues', 'updateIssueStatus', 'deleteIssue', 'updateAllIssues',
			'reverseLookup', 'unlockOutIP', 'loadBlockRanges', 'unblockRange', 'blockIPUARange', 'whois', 'unblockIP',
			'blockIP', 'permBlockIP', 'loadStaticPanel', 'saveConfig', 'savePartialConfig', 'updateIPPreview', 'downloadHtaccess', 'downloadLogFile', 'checkHtaccess',
			'updateConfig', 'autoUpdateChoice', 'adminEmailChoice', 'suPHPWAFUpdateChoice', 'misconfiguredHowGetIPsChoice',
			'clearAllBlocked', 'killScan', 'saveCountryBlocking', 'saveScanSchedule', 'tourClosed',
			'welcomeClosed', 'startTourAgain', 'downgradeLicense', 'addTwoFactor', 'twoFacActivate', 'twoFacDel',
			'loadTwoFactor', 'loadAvgSitePerf', 'sendTestEmail',
			'email_summary_email_address_debug', 'unblockNetwork', 'permanentlyBlockAllIPs',
			'sendDiagnostic', 'saveWAFConfig', 'updateWAFRules', 'loadLiveTraffic', 'whitelistWAFParamKey',
			'disableDirectoryListing', 'fixFPD', 'deleteAdminUser', 'revokeAdminUser',
			'hideFileHtaccess', 'saveDebuggingConfig', 'wafConfigureAutoPrepend',
			'whitelistBulkDelete', 'whitelistBulkEnable', 'whitelistBulkDisable',
			'dismissNotification', 'utilityScanForBlacklisted', 'dashboardShowMore',
		) as $func){
			add_action('wp_ajax_wordfence_' . $func, 'wordfence::ajaxReceiver');
		}

		if(isset($_GET['page']) && preg_match('/^Wordfence/', @$_GET['page']) ){
			wp_enqueue_style('wp-pointer');
			wp_enqueue_script('wp-pointer');
			wp_enqueue_style('wordfence-main-style', wfUtils::getBaseURL() . 'css/main.css', '', WORDFENCE_VERSION);
			wp_enqueue_style('wordfence-ionicons-style', wfUtils::getBaseURL() . 'css/wf-ionicons.css', '', WORDFENCE_VERSION);
			wp_enqueue_style('wordfence-colorbox-style', wfUtils::getBaseURL() . 'css/colorbox.css', '', WORDFENCE_VERSION);

			wp_enqueue_script('json2');
			wp_enqueue_script('jquery.wftmpl', wfUtils::getBaseURL() . 'js/jquery.tmpl.min.js', array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('jquery.wfcolorbox', wfUtils::getBaseURL() . 'js/jquery.colorbox-min.js', array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('jquery.wfdataTables', wfUtils::getBaseURL() . 'js/jquery.dataTables.min.js', array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('jquery.qrcode', wfUtils::getBaseURL() . 'js/jquery.qrcode.min.js', array('jquery'), WORDFENCE_VERSION);
			//wp_enqueue_script('jquery.tools', wfUtils::getBaseURL() . 'js/jquery.tools.min.js', array('jquery'));
			wp_enqueue_script('wordfenceAdminjs', wfUtils::getBaseURL() . 'js/admin.js', array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceAdminExtjs', wfUtils::getBaseURL() . 'js/tourTip.js', array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceDropdownjs', wfUtils::getBaseURL() . 'js/wfdropdown.js', array('jquery'), WORDFENCE_VERSION);
			self::setupAdminVars();
		} else {
			wp_enqueue_style('wp-pointer');
			wp_enqueue_script('wp-pointer');
			wp_enqueue_script('wordfenceAdminExtjs', wfUtils::getBaseURL() . 'js/tourTip.js', array('jquery'), WORDFENCE_VERSION);
			self::setupAdminVars();
		}
		
		if (is_admin()) { //Back end only
			wfUtils::refreshCachedHomeURL();
			wfUtils::refreshCachedSiteURL();
		}

		if (!WFWAF_AUTO_PREPEND || WFWAF_SUBDIRECTORY_INSTALL) {
			if (empty($_GET['wafAction']) && !wfConfig::get('dismissAutoPrependNotice')) {
				if (is_multisite()) {
					add_action('network_admin_notices', 'wordfence::wafAutoPrependNotice');
				} else {
					add_action('admin_notices', 'wordfence::wafAutoPrependNotice');
				}
			}

			if (!empty($_GET['wafAction'])) {
				switch ($_GET['wafAction']) {
					case 'configureAutoPrepend':
						if (isset($_REQUEST['serverConfiguration'])) {
							check_admin_referer('wfWAFAutoPrepend', 'wfnonce');
							$helper = new wfWAFAutoPrependHelper($_REQUEST['serverConfiguration']);
							if (!empty($_REQUEST['downloadBackup'])) {
								$helper->downloadBackups(!empty($_REQUEST['backupIndex']) ? absint($_REQUEST['backupIndex']) : 0);
							}

//							$adminURL = network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend');
//							request_filesystem_credentials($adminURL);
						}
						break;
				}
			}
		}
		else {
			if (!(!empty($_REQUEST['wafAction']) && $_REQUEST['wafAction'] == 'updateSuPHPConfig') && !wfConfig::get('suPHPWAFUpdateChoice')) {
				if (is_multisite()) {
					add_action('network_admin_notices', 'wordfence::wafCheckOutdatedConfiguration');
				} else {
					add_action('admin_notices', 'wordfence::wafCheckOutdatedConfiguration');
				}
			}
			
			if (!empty($_GET['wafAction'])) {
				switch ($_GET['wafAction']) {
					case 'updateSuPHPConfig':
						if (isset($_REQUEST['updateReady'])) {
							check_admin_referer('wfWAFUpdateSuPHPConfig', 'wfnonce');
							$helper = new wfWAFAutoPrependHelper('apache-suphp');
							if (!empty($_REQUEST['downloadBackup'])) {
								$helper->downloadBackups(!empty($_REQUEST['backupIndex']) ? absint($_REQUEST['backupIndex']) : 0);
							}
						}
						break;
					case 'removeAutoPrepend':
						if (isset($_REQUEST['serverConfiguration'])) {
							check_admin_referer('wfWAFRemoveAutoPrepend', 'wfnonce');
							$helper = new wfWAFAutoPrependHelper($_REQUEST['serverConfiguration']);
							if (!empty($_REQUEST['downloadBackup'])) {
								$helper->downloadBackups(!empty($_REQUEST['backupIndex']) ? absint($_REQUEST['backupIndex']) : 0);
							}
						}
						break;
				}
			}
		}

		if (!empty($_REQUEST['wafVerify']) && wp_verify_nonce($_REQUEST['wafVerify'], 'wfWAFAutoPrepend')) {
			if (is_multisite()) {
				add_action('network_admin_notices', 'wordfence::wafAutoPrependVerify');
			} else {
				add_action('admin_notices', 'wordfence::wafAutoPrependVerify');
			}
		}
		
		if (!empty($_REQUEST['wafRemoved']) && wp_verify_nonce($_REQUEST['wafRemoved'], 'wfWAFRemoveAutoPrepend')) {
			if (is_multisite()) {
				add_action('network_admin_notices', 'wordfence::wafAutoPrependRemoved');
			} else {
				add_action('admin_notices', 'wordfence::wafAutoPrependRemoved');
			}
		}
		
		if (!empty($_REQUEST['updateComplete']) && wp_verify_nonce($_REQUEST['updateComplete'], 'wfWAFUpdateComplete')) {
			if (is_multisite()) {
				add_action('network_admin_notices', 'wordfence::wafUpdateSuccessful');
			} else {
				add_action('admin_notices', 'wordfence::wafUpdateSuccessful');
			}
		}
	}
	private static function setupAdminVars(){
		$updateInt = wfConfig::get('actUpdateInterval', 2);
		if(! preg_match('/^\d+$/', $updateInt)){
			$updateInt = 2;
		}
		$updateInt *= 1000;

		wp_localize_script('wordfenceAdminExtjs', 'WordfenceAdminVars', array(
			'ajaxURL' => admin_url('admin-ajax.php'),
			'firstNonce' => wp_create_nonce('wp-ajax'),
			'siteBaseURL' => wfUtils::getSiteBaseURL(),
			'debugOn' => wfConfig::get('debugOn', 0),
			'actUpdateInterval' => $updateInt,
			'tourClosed' => wfConfig::get('tourClosed', 0),
			'welcomeClosed' => wfConfig::get('welcomeClosed', 0),
			'cacheType' => wfConfig::get('cacheType'),
			'liveTrafficEnabled' => wfConfig::liveTrafficEnabled(),
			'scanIssuesPerPage' => WORDFENCE_SCAN_ISSUES_PER_PAGE,
			'allowsPausing' => wfConfig::get('liveActivityPauseEnabled'),
			));
	}
	public static function activation_warning(){
		$activationError = get_option('wf_plugin_act_error', '');
		if(strlen($activationError) > 400){
			$activationError = substr($activationError, 0, 400) . '...[output truncated]';
		}
		if($activationError){
			echo '<div id="wordfenceConfigWarning" class="updated fade"><p><strong>Wordfence generated an error on activation. The output we received during activation was:</strong> ' . wp_kses($activationError, array()) . '</p></div>';
		}
		delete_option('wf_plugin_act_error');
	}
	public static function noKeyError(){
		echo '<div id="wordfenceConfigWarning" class="fade error"><p><strong>Wordfence could not get an API key from the Wordfence scanning servers when it activated.</strong> You can try to fix this by going to the Wordfence "options" page and hitting "Save Changes". This will cause Wordfence to retry fetching an API key for you. If you keep seeing this error it usually means your WordPress server can\'t connect to our scanning servers. You can try asking your WordPress host to allow your WordPress server to connect to noc1.wordfence.com.</p></div>';
	}
	public static function adminEmailWarning(){
		$url = network_admin_url('admin.php?page=WordfenceSecOpt&wafAction=useMineForAdminEmailAlerts');
		$dismissURL = network_admin_url('admin.php?page=WordfenceSecOpt&wafAction=dismissAdminEmailNotice&nonce=' .
			rawurlencode(wp_create_nonce('wfDismissAdminEmailWarning')));
		echo '<div id="wordfenceAdminEmailWarning" class="fade error inline wf-admin-notice"><p><strong>You have not set an administrator email address to receive alerts for Wordfence.</strong> Please <a href="' . self::getMyOptionsURL() . '">click here to go to the Wordfence Options Page</a> and set an email address where you will receive security alerts from this site.</p><p><a class="wf-btn wf-btn-default wf-btn-sm" href="#" onclick="wordfenceExt.adminEmailChoice(\'mine\'); return false;"">Use My Email Address</a>
		<a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#" onclick="wordfenceExt.adminEmailChoice(\'no\'); return false;">Dismiss</a></p></div>';
	}
	public static function wafReadOnlyNotice() {
		echo '<div id="wordfenceWAFReadOnlyNotice" class="fade error"><p><strong>The Wordfence Web Application Firewall is in read-only mode.</strong> PHP is currently running as a command line user and to avoid file permission issues, the WAF is running in read-only mode. It will automatically resume normal operation when run normally by a web server. <a class="wfhelp" target="_blank" rel="noopener noreferrer" href="https://docs.wordfence.com/en/Web_Application_Firewall_FAQ#What_is_read-only_mode.3F"></a></p></div>';
	}
	public static function misconfiguredHowGetIPsNotice() {
		$url = network_admin_url('admin.php?page=WordfenceSecOpt');
		$existing = wfConfig::get('howGetIPs', '');
		$recommendation = wfConfig::get('detectProxyRecommendation', '');
		if (empty($existing) || empty($recommendation) || $recommendation == 'UNKNOWN' || $recommendation == 'DEFERRED' || $existing == $recommendation) {
			return;
		}
		$existingMsg = '';
		if ($existing == 'REMOTE_ADDR') {
			$existingMsg = 'This site is currently using PHP\'s built in REMOTE_ADDR.';
		}
		else if ($existing == 'HTTP_X_FORWARDED_FOR') {
			$existingMsg = 'This site is currently using the X-Forwarded-For HTTP header, which should only be used when the site is behind a front-end proxy that outputs this header.';
		}
		else if ($existing == 'HTTP_X_REAL_IP') {
			$existingMsg = 'This site is currently using the X-Real-IP HTTP header, which should only be used when the site is behind a front-end proxy that outputs this header.';
		}
		else if ($existing == 'HTTP_CF_CONNECTING_IP') {
			$existingMsg = 'This site is currently using the Cloudflare "CF-Connecting-IP" HTTP header, which should only be used when the site is behind Cloudflare.';
		}
		
		$recommendationMsg = '';
		if ($recommendation == 'REMOTE_ADDR') {
			$recommendationMsg = 'For maximum security use PHP\'s built in REMOTE_ADDR.';
		}
		else if ($recommendation == 'HTTP_X_FORWARDED_FOR') {
			$recommendationMsg = 'This site appears to be behind a front-end proxy, so using the X-Forwarded-For HTTP header will resolve to the correct IPs.';
		}
		else if ($recommendation == 'HTTP_X_REAL_IP') {
			$recommendationMsg = 'This site appears to be behind a front-end proxy, so using the X-Real-IP HTTP header will resolve to the correct IPs.';
		}
		else if ($recommendation == 'HTTP_CF_CONNECTING_IP') {
			$recommendationMsg = 'This site appears to be behind Cloudflare, so using the Cloudflare "CF-Connecting-IP" HTTP header will resolve to the correct IPs.';
		}
		echo '<div id="wordfenceMisconfiguredHowGetIPsNotice" class="fade error"><p><strong>Your \'How does Wordfence get IPs\' setting is misconfigured.</strong> ' . $existingMsg . ' ' . $recommendationMsg . ' <a href="#" onclick="wordfenceExt.misconfiguredHowGetIPsChoice(\'yes\'); return false;">Click here to use the recommended setting</a> or <a href="' . $url . '">visit the options page</a> to manually update it.</p><p>
		<a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#" onclick="wordfenceExt.misconfiguredHowGetIPsChoice(\'no\'); return false;">Dismiss</a> <a class="wfhelp" target="_blank" rel="noopener noreferrer" href="https://docs.wordfence.com/en/Misconfigured_how_get_IPs_notice"></a></p></div>'; 
	}
	public static function autoUpdateNotice(){
		echo '<div id="wordfenceAutoUpdateChoice" class="fade error"><p><strong>Do you want Wordfence to stay up-to-date automatically?</strong>&nbsp;&nbsp;&nbsp;<a href="#" onclick="wordfenceExt.autoUpdateChoice(\'yes\'); return false;">Yes, enable auto-update.</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" onclick="wordfenceExt.autoUpdateChoice(\'no\'); return false;">No thanks.</a></p></div>';
	}
	public static function admin_menus(){
		if(! wfUtils::isAdmin()){ return; }
		$warningAdded = false;
		if(get_option('wf_plugin_act_error', false)){
			if(wfUtils::isAdminPageMU()){
				add_action('network_admin_notices', 'wordfence::activation_warning');
			} else {
				add_action('admin_notices', 'wordfence::activation_warning');
			}
			$warningAdded = true;
		}
		if(! wfConfig::get('apiKey')){
			if(wfUtils::isAdminPageMU()){
				add_action('network_admin_notices', 'wordfence::noKeyError');
			} else {
				add_action('admin_notices', 'wordfence::noKeyError');
			}
			$warningAdded = true;
		}
		
		if (!wfConfig::get('misconfiguredHowGetIPsChoice' . WORDFENCE_VERSION) && !(defined('WORDFENCE_DISABLE_MISCONFIGURED_HOWGETIPS') && WORDFENCE_DISABLE_MISCONFIGURED_HOWGETIPS)) {
			$warningAdded = true;
			if(wfUtils::isAdminPageMU()){
				add_action('network_admin_notices', 'wordfence::misconfiguredHowGetIPsNotice');
			} else {
				add_action('admin_notices', 'wordfence::misconfiguredHowGetIPsNotice');
			}
		}
		if (method_exists(wfWAF::getInstance(), 'isReadOnly') && wfWAF::getInstance()->isReadOnly()) {
			$warningAdded = true;
			if (wfUtils::isAdminPageMU()) {
				add_action('network_admin_notices', 'wordfence::wafReadOnlyNotice');
			}
			else {
				add_action('admin_notices', 'wordfence::wafReadOnlyNotice');
			}
		}
		if(! $warningAdded){
			if(wfConfig::get('tourClosed') == '1' && (! wfConfig::get('autoUpdate')) && (! wfConfig::get('autoUpdateChoice'))){
				$warningAdded = true;
				if(wfUtils::isAdminPageMU()){
					add_action('network_admin_notices', 'wordfence::autoUpdateNotice');
				} else {
					add_action('admin_notices', 'wordfence::autoUpdateNotice');
				}
			}
		}
		if(! $warningAdded){
			if(wfConfig::get('tourClosed') == '1' && (!wfConfig::get('alertEmails') && (!wfConfig::get('adminEmailChoice')))){
				$warningAdded = true;
				if(wfUtils::isAdminPageMU()){
					add_action('network_admin_notices', 'wordfence::adminEmailWarning');
				} else {
					add_action('admin_notices', 'wordfence::adminEmailWarning');
				}
			}
		}

		if (!empty($_GET['page']) && $_GET['page'] === 'WordfenceWAF' && !empty($_GET['wafconfigrebuild']) && !WFWAF_SUBDIRECTORY_INSTALL) {
			check_admin_referer('wafconfigrebuild', 'waf-nonce');

			$storage = wfWAF::getInstance()->getStorageEngine();
			if ($storage instanceof wfWAFStorageFile) {
				$configFile = $storage->getConfigFile();
				if (@unlink($configFile)) {
					if (function_exists('network_admin_url') && is_multisite()) {
						$wafMenuURL = network_admin_url('admin.php?page=WordfenceWAF');
					} else {
						$wafMenuURL = admin_url('admin.php?page=WordfenceWAF');
					}
					wp_redirect($wafMenuURL);
					exit;
				}
			}
		}
		
		$notificationCount = count(wfNotification::notifications());
		$updatingNotifications = get_site_transient('wordfence_updating_notifications');
		$hidden = ($notificationCount == 0 || $updatingNotifications ? ' wf-hidden' : '');
		$formattedCount = number_format_i18n($notificationCount);
		$dashboardExtra = " <span class='update-plugins wf-menu-badge wf-notification-count-container{$hidden}' title='{$notificationCount}'><span class='update-count wf-notification-count-value'>{$formattedCount}</span></span>";

		add_menu_page('Wordfence', "Wordfence{$dashboardExtra}", 'activate_plugins', 'Wordfence', 'wordfence::menu_dashboard', wfUtils::getBaseURL() . 'images/wordfence-logo-16x16.png'); 
		add_submenu_page("Wordfence", "Dashboard", "Dashboard", "activate_plugins", "Wordfence", 'wordfence::menu_dashboard');
		add_submenu_page("Wordfence", "Scan", "Scan", "activate_plugins", "WordfenceScan", 'wordfence::menu_scan'); 
		add_submenu_page("Wordfence", "Firewall", "Firewall", "activate_plugins", "WordfenceWAF", 'wordfence::menu_firewall');
		add_submenu_page("Wordfence", "Blocking", "Blocking", "activate_plugins", "WordfenceBlocking", 'wordfence::menu_blocking');
		add_submenu_page("Wordfence", "Live Traffic", "Live Traffic", "activate_plugins", "WordfenceActivity", 'wordfence::menu_activity');

		add_submenu_page('Wordfence', 'Tools', 'Tools', 'activate_plugins', 'WordfenceTools', 'wordfence::menu_tools');
		add_submenu_page("Wordfence", "Options", "Options", "activate_plugins", "WordfenceSecOpt", 'wordfence::menu_options');
		
		if (wfConfig::get('isPaid')) { 
			add_submenu_page("Wordfence", "Protect More Sites", "<strong id=\"wfMenuCallout\" style=\"color: #FCB214;\">Protect More Sites</strong>", "activate_plugins", "WordfenceProtectMoreSites", 'wordfence::menu_diagnostic');
		}
		else {
			add_submenu_page("Wordfence", "Upgrade To Premium", "<strong id=\"wfMenuCallout\" style=\"color: #FCB214;\">Upgrade To Premium</strong>", "activate_plugins", "WordfenceUpgradeToPremium", 'wordfence::menu_diagnostic');
		}
		add_filter('clean_url', 'wordfence::_patchWordfenceSubmenuCallout', 10, 3);
	}
	public static function _patchWordfenceSubmenuCallout($url, $original_url, $_context){
		if (preg_match('/(?:WordfenceUpgradeToPremium)$/i', $url)) {
			remove_filter('clean_url', 'wordfence::_patchWordfenceSubmenuCallout', 10);
			return 'https://www.wordfence.com/zz11/wordfence-signup/';
		}
		else if (preg_match('/(?:WordfenceProtectMoreSites)$/i', $url)) {
			remove_filter('clean_url', 'wordfence::_patchWordfenceSubmenuCallout', 10);
			return 'https://www.wordfence.com/zz10/sign-in/';
		}
		return $url;
	}
	public static function _retargetWordfenceSubmenuCallout() {
		echo <<<JQUERY
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#wfMenuCallout').closest('a').attr('target', '_blank').attr('rel', 'noopener noreferrer');
});
</script>
JQUERY;

	}
	public static function admin_bar_menu() {
		global $wp_admin_bar;
		
		if (wfUtils::isAdmin() && wfConfig::get('showAdminBarMenu')) {
			$title = '<div id="wf-adminbar-icon" class="ab-item"></div>';
			$count = count(wfNotification::notifications());
			$sinceCount = count(wfNotification::notifications((int) get_user_meta(get_current_user_id(), 'wordfence-notifications', true)));
			if ($sinceCount > 0) {
				$counter = '<span id="wf-notification-popover" data-toggle="popover" data-trigger="focus" data-content="You have ' . $sinceCount . ' new Wordfence notification' . ($sinceCount == 1 ? '' : 's') . '." data-container="body" data-placement="wf-bottom">&nbsp;</span>';
				update_user_meta(get_current_user_id(), 'wordfence-notifications', time());
			}
			else {
				$counter = ' ';
			}
			$badge = '<div class="wp-core-ui wp-ui-notification wf-notification-counter wf-notification-count-container' . ($count == 0 ? ' wf-hidden' : '') . '"><span class="wf-count wf-notification-count-value">' . $count . '</span></div>';
			$counter .= $badge;
			
			$wp_admin_bar->add_menu( array(
				'id'    => 'wordfence-menu',
				'title' => $title . $counter,
				'href'  => network_admin_url('admin.php?page=Wordfence'),
			));
			$wp_admin_bar->add_menu( array(
				'parent' => 'wordfence-menu',
				'id'     => 'wordfence-notifications',
				'title'  => '<div id="wordfence-notifications-display" class="wf-adminbar-submenu-title">Notifications</div>' . $badge,
				'href'   => network_admin_url('admin.php?page=Wordfence'),
			));
			$wp_admin_bar->add_menu( array(
				'parent' => 'wordfence-menu',
				'id'     => 'wordfence-javascripterror',
				'title'  => '<div id="wordfence-javascripterror-display" class="wf-adminbar-submenu-title">Javascript Errors</div><div class="wf-adminbar-status wf-adminbar-status-good">&bullet;</div>',
				'href'   => 'javascript:void(0)',
			));
			$wp_admin_bar->add_menu( array(
				'parent' => 'wordfence-menu',
				'id'     => 'wordfence-malwareurl',
				'title'  => '<div id="wordfence-malwareurl-display' . (is_admin() ? '-skip' : '') . '" class="wf-adminbar-submenu-title">Malware URLs</div><div class="wf-adminbar-status wf-adminbar-status-neutral">&bullet;</div>',
				'href'   => network_admin_url('admin.php?page=WordfenceScan'),
			));
		}
	}
	public static function menu_options(){
		require 'menu_options.php';
	}
	public static function menu_tools() {
		$emailForm = true;
		require 'menu_tools.php';
	}
	
	public static function menu_blocking() {
		require 'menu_blocking.php';
	}

	public static function menu_firewall() {
		global $wp_filesystem;

		wp_enqueue_style('wordfence-jquery-ui-css', wfUtils::getBaseURL() . 'css/jquery-ui.min.css', array(), WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-jquery-ui-structure-css', wfUtils::getBaseURL() . 'css/jquery-ui.structure.min.css', array(), WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-jquery-ui-theme-css', wfUtils::getBaseURL() . 'css/jquery-ui.theme.min.css', array(), WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-jquery-ui-timepicker-css', wfUtils::getBaseURL() . 'css/jquery-ui-timepicker-addon.css', array(), WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-select2-css', wfUtils::getBaseURL() . 'css/select2.min.css', array(), WORDFENCE_VERSION);

		wp_enqueue_script('wordfence-timepicker-js', wfUtils::getBaseURL() . 'js/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-datepicker', 'jquery-ui-slider'), WORDFENCE_VERSION);
		wp_enqueue_script('wordfence-select2-js', wfUtils::getBaseURL() . 'js/select2.min.js', array('jquery'), WORDFENCE_VERSION);

		try {
			$wafData = self::_getWAFData();
		} catch (wfWAFStorageFileConfigException $e) {
			// We don't have anywhere to write files in this scenario. Let's notify the user to update the permissions.
			$wafData = array();
			$logPath = str_replace(ABSPATH, '~/', WFWAF_LOG_PATH);
			if (function_exists('network_admin_url') && is_multisite()) {
				$wafMenuURL = network_admin_url('admin.php?page=WordfenceWAF&wafconfigrebuild=1');
			} else {
				$wafMenuURL = admin_url('admin.php?page=WordfenceWAF&wafconfigrebuild=1');
			}
			$wafMenuURL = add_query_arg(array(
				'waf-nonce' => wp_create_nonce('wafconfigrebuild'),
			), $wafMenuURL);
			$storageExceptionMessage = $e->getMessage() . ' <a href="' . esc_url($wafMenuURL) . '">Click here</a> to rebuild the configuration file.';
		} catch (wfWAFStorageFileException $e) {
			// We don't have anywhere to write files in this scenario. Let's notify the user to update the permissions.
			$wafData = array();
			$logPath = str_replace(ABSPATH, '~/', WFWAF_LOG_PATH);
			$storageExceptionMessage = 'We were unable to write to ' . $logPath . ' which the WAF uses for storage. Please
			update permissions on the parent directory so the web server can write to it.';
		}

		$hideBar = false;
		if (!empty($_GET['wafAction'])) {
			switch ($_GET['wafAction']) {
				case 'dismissAutoPrependNotice':
					check_admin_referer('wfDismissAutoPrependNotice', 'nonce');
					wfConfig::set('dismissAutoPrependNotice', 1);

					break;
				
				case 'updateSuPHPConfig':
					$hideBar = true;
					if (!isset($_REQUEST['updateComplete']) || !$_REQUEST['updateComplete']) {
						$wfnonce = wp_create_nonce('wfWAFUpdateSuPHPConfig');
						$adminURL = network_admin_url('admin.php?page=WordfenceWAF&wafAction=updateSuPHPConfig');
						$helper = new wfWAFAutoPrependHelper('apache-suphp');
						if (!isset($_REQUEST['confirmedBackup'])) {
							if (($backups = $helper->getFilesNeededForBackup()) && empty($_REQUEST['confirmedBackup'])) {
								$wafActionContent = '
	<h3>Wordfence Web Application Firewall Update</h3>
	<p>The Wordfence Web Application Firewall for Apache suPHP servers has been improved, and your configuration needs to be updated to continue receiving the best protection. Please download a backup copy of the following files before we make the necessary changes:</p>';
								$wafActionContent .= '<ul>';
								foreach ($backups as $index => $backup) {
									$wafActionContent .= '<li><a class="wf-btn wf-btn-default" onclick="wfWAFConfirmBackup(' . $index . ');" href="' .
										esc_url(add_query_arg(array(
											'downloadBackup'      => 1,
											'updateReady'		  => 1,
											'backupIndex'         => $index,
											'wfnonce'             => $wfnonce,
										), $adminURL)) . '">Download ' . esc_html(basename($backup)) . '</a></li>';
								}
								$jsonBackups = json_encode(array_map('basename', $backups));
								$adminURL = esc_url($adminURL);
								$wafActionContent .= "</ul>
	<form action='$adminURL' method='post'>
	<input type='hidden' name='wfnonce' value='$wfnonce'>
	<input type='hidden' value='1' name='confirmedBackup'>
	<button id='confirmed-backups' disabled class='wf-btn wf-btn-primary' type='submit'>Continue</button>
	</form>
	<script>
	var wfWAFBackups = $jsonBackups;
	var wfWAFConfirmedBackups = [];
	function wfWAFConfirmBackup(index) {
		wfWAFBackups[index] = false;
		var confirmed = true;
		for (var i = 0; i < wfWAFBackups.length; i++) {
			if (wfWAFBackups[i] !== false) {
				confirmed = false;
			}
		}
		if (confirmed) {
			document.getElementById('confirmed-backups').disabled = false;
		}
	}
	</script>";
								$htaccessPath = $helper->getHtaccessPath();
								$htaccess = @file_get_contents($htaccessPath);
								if ($htaccess && preg_match('/(# Wordfence WAF.*?# END Wordfence WAF)/is', $htaccess, $matches)) {
									$wafSection = $matches[1];
									$wafSection = preg_replace('#(<IfModule\s+mod_suphp\.c>\s+suPHP_ConfigPath\s+\S+\s+</IfModule>)#i', '', $wafSection);
									$wafActionContent .= "<br>
	<h3>Alternate method:</h3>
	<p>We've also included instructions to manually perform the change if you are unable to do it automatically, or if file permissions prevent this change.</p>
	<p>You will need to replace the equivalent section from your .htaccess with the following:</p>
	<pre class='wf-pre'>" . htmlentities($wafSection) . "</pre>";
								}
								
								break;
							}
						}
						else {
							check_admin_referer('wfWAFUpdateSuPHPConfig', 'wfnonce');
							$allow_relaxed_file_ownership = true;
							
							ob_start();
							if (false === ($credentials = request_filesystem_credentials($adminURL, '', false, ABSPATH,
									array('version', 'locale'), $allow_relaxed_file_ownership))
							) {
								$wafActionContent = ob_get_clean();
								break;
							}
							
							if (!WP_Filesystem($credentials, ABSPATH, $allow_relaxed_file_ownership)) {
								// Failed to connect, Error and request again
								request_filesystem_credentials($adminURL, '', true, ABSPATH, array('version', 'locale'),
									$allow_relaxed_file_ownership);
								$wafActionContent = ob_get_clean();
								break;
							}
							
							if ($wp_filesystem->errors->get_error_code()) {
								foreach ($wp_filesystem->errors->get_error_messages() as $message)
									show_message($message);
								$wafActionContent = ob_get_clean();
								break;
							}
							ob_end_clean();
							
							$htaccessPath = $helper->getHtaccessPath();
							$htaccess = @file_get_contents($htaccessPath);
							if ($htaccess && preg_match('/(# Wordfence WAF.*?# END Wordfence WAF)/is', $htaccess, $matches)) {
								$wafSection = $matches[1];
								$wafSection = preg_replace('#(<IfModule\s+mod_suphp\.c>\s+suPHP_ConfigPath\s+\S+\s+</IfModule>)#i', '', $wafSection);
								$htaccess = preg_replace('/# Wordfence WAF.*?# END Wordfence WAF/is', $wafSection, $htaccess);
								if (!$wp_filesystem->put_contents($htaccessPath, $htaccess)) {
									$wafActionContent = '<p>We were unable to make changes to the .htaccess file. It\'s
					possible WordPress cannot write to the .htaccess file because of file permissions, which may have been
					set by another security plugin, or you may have set them manually. Please verify the permissions allow
					the web server to write to the file, and retry the update.</p>';
									break;
								}
								
								$adminURL = json_encode(esc_url_raw(network_admin_url('admin.php?page=WordfenceWAF&wafAction=updateSuPHPConfig&updateComplete=' . wp_create_nonce('wfWAFUpdateComplete'))));
								$wafActionContent = "<script>
	document.location.href=$adminURL;
	</script>";
								break;
							}
							
							$wafActionContent = '<p>We were unable to make changes to the .htaccess file. It\'s
					possible WordPress cannot write to the .htaccess file because of file permissions, which may have been
					set by another security plugin, or you may have set them manually. Please verify the permissions allow
					the web server to write to the file, and retry the update.</p>';
						}
					}
					break;

				case 'configureAutoPrepend':
					if (WFWAF_AUTO_PREPEND && !WFWAF_SUBDIRECTORY_INSTALL) {
						break;
					}
					$hideBar = true;
					$wfnonce = wp_create_nonce('wfWAFAutoPrepend');

					$currentAutoPrependFile = ini_get('auto_prepend_file');
					$currentAutoPrepend = !empty($_REQUEST['currentAutoPrepend']) ? $_REQUEST['currentAutoPrepend'] : null;
					$adminURL = network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend&currentAutoPrepend=' . $currentAutoPrepend);
					if ($currentAutoPrependFile &&
						is_file($currentAutoPrependFile) &&
						empty($currentAutoPrepend) &&
						!WFWAF_SUBDIRECTORY_INSTALL
					) {
						$wafActionContent = sprintf("<p>The Wordfence Web Application Firewall is designed
to run via a PHP ini setting called <code>auto_prepend_file</code> in order to ensure it runs before any potentially
vulnerable code runs. This PHP setting is currently in use, and is including this file:</p>

<pre class='wf-pre'>%s</pre>

<p>If you don't recognize this file, please <a href='https://wordpress.org/support/plugin/wordfence'>contact us on the
WordPress support forums</a> before proceeding.</p>

<p>You can proceed with the installation and we will include this from within our <code>wordfence-waf.php</code> file
which should maintain compatibility with your site, or you can opt to override the existing PHP setting.</p>

<p>
<a class='wf-btn wf-btn-primary' href='%s'>Include this file (Recommended)</a>
<a class='wf-btn wf-btn-default' href='%s'>Override this value</a>
</p>
",
							esc_html($currentAutoPrependFile),
							esc_url(network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend&currentAutoPrepend=include')),
							esc_url(network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend&currentAutoPrepend=override'))
						);
						break;
					} else if (isset($_REQUEST['serverConfiguration'])) {
						check_admin_referer('wfWAFAutoPrepend', 'wfnonce');
						$allow_relaxed_file_ownership = true;
						$helper = new wfWAFAutoPrependHelper($_REQUEST['serverConfiguration'],
							$currentAutoPrepend === 'override' ? null : $currentAutoPrependFile);
						if (($backups = $helper->getFilesNeededForBackup()) && empty($_REQUEST['confirmedBackup'])) {
							$wafActionContent = '<p>Please download a backup copy of the following files before we make the necessary changes:</p>';
							$wafActionContent .= '<ul>';
							foreach ($backups as $index => $backup) {
								$wafActionContent .= '<li><a class="wf-btn wf-btn-default" onclick="wfWAFConfirmBackup(' . $index . ');" href="' .
									esc_url(add_query_arg(array(
										'downloadBackup'      => 1,
										'backupIndex'         => $index,
										'serverConfiguration' => $helper->getServerConfig(),
										'wfnonce'             => $wfnonce,
									), $adminURL)) . '">Download ' . esc_html(basename($backup)) . '</a></li>';
							}
							$serverConfig = esc_attr($helper->getServerConfig());
							$jsonBackups = json_encode(array_map('basename', $backups));
							$adminURL = esc_url($adminURL);
							$wafActionContent .= "</ul>
<form action='$adminURL' method='post'>
<input type='hidden' name='wfnonce' value='$wfnonce'>
<input type='hidden' value='$serverConfig' name='serverConfiguration'>
<input type='hidden' value='1' name='confirmedBackup'>
<button id='confirmed-backups' disabled class='wf-btn wf-btn-primary' type='submit'>Continue</button>
</form>
<script>
var wfWAFBackups = $jsonBackups;
var wfWAFConfirmedBackups = [];
function wfWAFConfirmBackup(index) {
	wfWAFBackups[index] = false;
	var confirmed = true;
	for (var i = 0; i < wfWAFBackups.length; i++) {
		if (wfWAFBackups[i] !== false) {
			confirmed = false;
		}
	}
	if (confirmed) {
		document.getElementById('confirmed-backups').disabled = false;
	}
}
</script>";
							break;
						}

						ob_start();
						if (false === ($credentials = request_filesystem_credentials($adminURL, '', false, ABSPATH,
								array('version', 'locale'), $allow_relaxed_file_ownership))
						) {
							$wafActionContent = ob_get_clean();
							break;
						}

						if (!WP_Filesystem($credentials, ABSPATH, $allow_relaxed_file_ownership)) {
							// Failed to connect, Error and request again
							request_filesystem_credentials($adminURL, '', true, ABSPATH, array('version', 'locale'),
								$allow_relaxed_file_ownership);
							$wafActionContent = ob_get_clean();
							break;
						}

						if ($wp_filesystem->errors->get_error_code()) {
							foreach ($wp_filesystem->errors->get_error_messages() as $message)
								show_message($message);
							$wafActionContent = ob_get_clean();
							break;
						}
						ob_end_clean();

						try {
							$helper->performInstallation($wp_filesystem);

							$adminURL = json_encode(esc_url_raw(network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend&wafVerify='
								. $wfnonce . '&currentAutoPrepend=' . $currentAutoPrepend)));
							$wafActionContent = "<script>
document.location.href=$adminURL;
</script>";
							break;
						} catch (wfWAFAutoPrependHelperException $e) {
							$wafActionContent = "<p>" . $e->getMessage() . "</p>";
							break;
						}
					}


					$bootstrap = self::getWAFBootstrapPath();

					// Auto populate drop down with server configuration
					// If no preconfiguration routine exists, output instructions for manual configuration
					$serverInfo = wfWebServerInfo::createFromEnvironment();

					$dropdown = array(
						array("apache-mod_php", 'Apache + mod_php', $serverInfo->isApacheModPHP()),
						array("apache-suphp", 'Apache + suPHP', $serverInfo->isApacheSuPHP()),
						array("cgi", 'Apache + CGI/FastCGI', $serverInfo->isApache() &&
							!$serverInfo->isApacheSuPHP() &&
							($serverInfo->isCGI() || $serverInfo->isFastCGI())),
						array("litespeed", 'LiteSpeed/lsapi', $serverInfo->isLiteSpeed()),
						array("nginx", 'NGINX', $serverInfo->isNGINX()),
						array("iis", 'Windows (IIS)', $serverInfo->isIIS()),
					);
					$wafActionContent = '<p>To be as secure as possible, the Wordfence Web Application Firewall is designed
to run via a PHP ini setting called <code>auto_prepend_file</code> in order to ensure it runs before any potentially
vulnerable code runs.</p>

<div class="wf-notice"><strong>NOTE:</strong> If you have separate WordPress installations with Wordfence installed within a subdirectory of
this site, it is recommended that you perform the Firewall installation procedure on those sites before this one.</div>
';
					$hasRecommendedOption = false;
					$wafPrependOptions = '';
					foreach ($dropdown as $option) {
						list($optionValue, $optionText, $selected) = $option;
						$wafPrependOptions .= "<option value=\"$optionValue\"" . ($selected ? ' selected' : '')
							. ">$optionText" . ($selected ? ' (recommended based on our tests)' : '') . "</option>\n";
						if ($selected) {
							$hasRecommendedOption = true;
						}
					}

					if (!$hasRecommendedOption) {
						$wafActionContent .= "<p>If you know your web server's configuration, please select it from the
list below:</p>";
					} else {
						$wafActionContent .= "<p>We've preselected your server configuration based on our tests, but if
you know your web server's configuration, please select it now.</p>";
					}

					$userIni = ini_get('user_ini.filename');
					$nginxIniWarning = '';
					if ($userIni) {
						$nginxIniWarning = "<div class='wf-notice wf-nginx-waf-config'>
Part of the Firewall configuration procedure for NGINX depends on creating a <code>" . esc_html($userIni) . "</code> file
in the root of your WordPress installation. This file can contain sensitive information and public access to it should
be restricted. We have
<a href='https://docs.wordfence.com/en/Web_Application_Firewall_FAQ#NGINX'>instructions on our documentation site</a> on what
directives to put in your nginx.conf to fix this.
";
					}

					$adminURL = esc_url($adminURL);
					$wafActionContent .= "
<form action='$adminURL' method='post' class='wf-form-inline'>
<div class='wf-form-group'>
<input type='hidden' name='wfnonce' value='$wfnonce'>
<select class='wf-form-control' name='serverConfiguration' id='wf-waf-server-config'>
$wafPrependOptions
</select>
</div>
<button class='wf-btn wf-btn-primary' type='submit'>Continue</button>
</form>
$nginxIniWarning
</div>
<script>
(function($) {
	var nginxNotice = $('.wf-nginx-waf-config').hide();
	$('#wf-waf-server-config').on('change', function() {
		var el = $(this);
		if (el.val() == 'nginx') {
			nginxNotice.fadeIn();
		} else {
			nginxNotice.fadeOut();
		}
	}).triggerHandler('change');
})(jQuery);
</script>
";

					$wafActionContent .= "
<h3>Alternate method:</h3>
<p>We've also included instructions to manually perform the change if you are using a web server other than what is listed in the drop-down, or if file permissions prevent this change.</p>";

					$additionally = 'You';
					if (!self::checkAndCreateBootstrap()) {
						$wafActionContent .= "<p>You will need create the following file in your WordPress root:</p>
<pre class='wf-pre'>" . esc_html(self::getWAFBootstrapPath()) . "</pre>
<p>You can create the file and set the permissions to allow WordPress to write to it, or you can add the code yourself:</p>
<pre class='wf-pre'>" . esc_textarea(self::getWAFBootstrapContent()) . "</pre>";

						$additionally = 'Additionally, you';
					}
					$wafActionContent .= "<p>{$additionally} will need to append the following code to your <code>php.ini</code>:</p>
<pre class='wf-pre'>auto_prepend_file = '" . esc_textarea($bootstrap) . "'</pre>";


					$wafActionContent = sprintf('<div style="margin: 20px 0;">%s</div>', $wafActionContent);
					break;
				
				case 'removeAutoPrepend':
					$hideBar = true;
					$installedHere = !(!WFWAF_AUTO_PREPEND && !WFWAF_SUBDIRECTORY_INSTALL);
					$wfnonce = wp_create_nonce('wfWAFRemoveAutoPrepend');
					
					$currentAutoPrependFile = ini_get('auto_prepend_file');
					$adminURL = network_admin_url('admin.php?page=WordfenceWAF&wafAction=removeAutoPrepend');
					if (!$currentAutoPrependFile && $installedHere) {
						//This should never happen but covering the possibility anyway
						$wafActionContent = "<p>Extended Protection Mode of the Wordfence Web Application Firewall uses the PHP ini setting called <code>auto_prepend_file</code> in order to ensure it runs before any potentially
vulnerable code runs. This PHP setting is not currently configured.</p>";
						break;
					}
					else if ($currentAutoPrependFile &&
						is_file($currentAutoPrependFile) &&
						!isset($_REQUEST['serverConfiguration']) &&
						!isset($_REQUEST['iniModified']) &&
						$installedHere &&
						!WFWAF_SUBDIRECTORY_INSTALL
					) {
						$contents = file_get_contents($currentAutoPrependFile);
						$refersToWAF = preg_match('/define\s*\(\s*(["\'])WFWAF_LOG_PATH\1\s*,\s*(["\']).+?\2\s*\)\s*/', $contents);
						
						if ($refersToWAF) {
							$wafActionContent = sprintf("<p>Extended Protection Mode of the Wordfence Web Application Firewall uses the PHP ini setting called <code>auto_prepend_file</code> in order to ensure it runs before any potentially
vulnerable code runs. This PHP setting currently refers to the Wordfence file at:</p>

<pre class='wf-pre'>%s</pre>

<p>Before this file can be deleted, the configuration for the <code>auto_prepend_file</code> setting needs to be removed.</p>
",
								esc_html($currentAutoPrependFile)
							);
							
							// Auto populate drop down with server configuration
							// If no preconfiguration routine exists, output instructions for manual configuration
							$serverInfo = wfWebServerInfo::createFromEnvironment();
							
							$dropdown = array(
								array("apache-mod_php", 'Apache + mod_php', $serverInfo->isApacheModPHP()),
								array("apache-suphp", 'Apache + suPHP', $serverInfo->isApacheSuPHP()),
								array("cgi", 'Apache + CGI/FastCGI', $serverInfo->isApache() &&
									!$serverInfo->isApacheSuPHP() &&
									($serverInfo->isCGI() || $serverInfo->isFastCGI())),
								array("litespeed", 'LiteSpeed/lsapi', $serverInfo->isLiteSpeed()),
								array("nginx", 'NGINX', $serverInfo->isNGINX()),
								array("iis", 'Windows (IIS)', $serverInfo->isIIS()),
							);
							
							$hasRecommendedOption = false;
							$wafPrependOptions = '';
							foreach ($dropdown as $option) {
								list($optionValue, $optionText, $selected) = $option;
								$wafPrependOptions .= "<option value=\"$optionValue\"" . ($selected ? ' selected' : '')
									. ">$optionText" . ($selected ? ' (recommended based on our tests)' : '') . "</option>\n";
								if ($selected) {
									$hasRecommendedOption = true;
								}
							}
							
							if (!$hasRecommendedOption) {
								$wafActionContent .= "<p>If you know your web server's configuration, please select it from the
list below:</p>";
							}
							else {
								$wafActionContent .= "<p>We've preselected your server configuration based on our tests, but if
you know your web server's configuration, please select it now.</p>";
							}
							
							$adminURL = esc_url($adminURL);
							$wafActionContent .= "
<form action='$adminURL' method='post' class='wf-form-inline'>
<div class='wf-form-group'>
<input type='hidden' name='wfnonce' value='$wfnonce'>
<select class='wf-form-control' name='serverConfiguration' id='wf-waf-server-config'>
$wafPrependOptions
</select>
</div>
<button class='wf-btn wf-btn-primary' type='submit'>Continue</button>
</form>
";
						}
						else {
							//This point should only be reached if the wordfence-waf.php file format changes enough for the detection to fail and this isn't updated to match
							$wafActionContent = sprintf("<p>Extended Protection Mode of the Wordfence Web Application Firewall uses the PHP ini setting called <code>auto_prepend_file</code> in order to ensure it runs before any potentially
vulnerable code runs. This PHP setting currently refers to an unknown file at:</p>

<pre class='wf-pre'>%s</pre>

<p>Automatic uninstallation cannot be completed, but you may still be able to <a href='%s' target='_blank' rel=\"noopener noreferrer\">manually uninstall extended protection</a>.</p>
",
								esc_html($currentAutoPrependFile),
								esc_url('https://docs.wordfence.com/en/Web_Application_Firewall_FAQ#How_can_I_remove_the_firewall_setup_manually.3F')
							);
							break;
						}
					}
					else if (isset($_REQUEST['serverConfiguration'])) {
						check_admin_referer('wfWAFRemoveAutoPrepend', 'wfnonce');
						$allow_relaxed_file_ownership = true;
						$helper = new wfWAFAutoPrependHelper($_REQUEST['serverConfiguration'], null);
						$serverConfig = esc_attr($helper->getServerConfig());
						if ($installedHere && empty($_REQUEST['iniModified']) && ($backups = $helper->getFilesNeededForBackup()) && empty($_REQUEST['confirmedBackup'])) {
							$wafActionContent = '<p>Please download a backup copy of the following files before we make the necessary changes:</p>';
							$wafActionContent .= '<ul>';
							foreach ($backups as $index => $backup) {
								$wafActionContent .= '<li><a class="wf-btn wf-btn-default" onclick="wfWAFConfirmBackup(' . $index . ');" href="' .
									esc_url(add_query_arg(array(
										'downloadBackup'      => 1,
										'backupIndex'         => $index,
										'serverConfiguration' => $helper->getServerConfig(),
										'wfnonce'             => $wfnonce,
									), $adminURL)) . '">Download ' . esc_html(basename($backup)) . '</a></li>';
							}
							$jsonBackups = json_encode(array_map('basename', $backups));
							$adminURL = esc_url($adminURL);
							$wafActionContent .= "</ul>
<form action='$adminURL' method='post'>
<input type='hidden' name='wfnonce' value='$wfnonce'>
<input type='hidden' value='$serverConfig' name='serverConfiguration'>
<input type='hidden' value='1' name='confirmedBackup'>
<button id='confirmed-backups' disabled class='wf-btn wf-btn-primary' type='submit'>Continue</button>
</form>
<script>
var wfWAFBackups = $jsonBackups;
var wfWAFConfirmedBackups = [];
function wfWAFConfirmBackup(index) {
	wfWAFBackups[index] = false;
	var confirmed = true;
	for (var i = 0; i < wfWAFBackups.length; i++) {
		if (wfWAFBackups[i] !== false) {
			confirmed = false;
		}
	}
	if (confirmed) {
		document.getElementById('confirmed-backups').disabled = false;
	}
}
</script>";
							break;
						}
						
						ob_start();
						if (false === ($credentials = request_filesystem_credentials($adminURL, '', false, ABSPATH,
								array('version', 'locale'), $allow_relaxed_file_ownership))
						) {
							$wafActionContent = ob_get_clean();
							break;
						}
						
						if (!WP_Filesystem($credentials, ABSPATH, $allow_relaxed_file_ownership)) {
							// Failed to connect, Error and request again
							request_filesystem_credentials($adminURL, '', true, ABSPATH, array('version', 'locale'),
								$allow_relaxed_file_ownership);
							$wafActionContent = ob_get_clean();
							break;
						}
						
						if ($wp_filesystem->errors->get_error_code()) {
							foreach ($wp_filesystem->errors->get_error_messages() as $message)
								show_message($message);
							$wafActionContent = ob_get_clean();
							break;
						}
						ob_end_clean();
						
						try {
							if (isset($_REQUEST['iniModified'])) {
								$usesUserIni = $helper->usesUserIni();
								if ((!WFWAF_AUTO_PREPEND || WFWAF_SUBDIRECTORY_INSTALL) && (!$usesUserIni || ($usesUserIni && isset($_REQUEST['iniTTLWaited'])))) { //WFWAF_AUTO_PREPEND can be false for a brief time when using .user.ini, so make sure we've waited the TTL before removing the wordfence-waf.php file
									$helper->performAutoPrependFileRemoval($wp_filesystem);
									
									$adminURL = json_encode(esc_url_raw(network_admin_url('admin.php?page=WordfenceWAF&wafRemoved=' . $wfnonce)));
									$wafActionContent = "<p>Removing firewall files...</p><script>
document.location.href=$adminURL;
</script>";
								}
								else { //Using a .user.ini where there's a delay before taking effect
									$iniTTL = intval(ini_get('user_ini.cache_ttl'));
									if ($iniTTL == 0) {
										$iniTTL = 300; //The PHP default
									}
									$timeout = max(30000, ($iniTTL + 1) * 1000);
									
									if ($timeout < 60000) { $timeoutString = floor($timeout / 1000) . ' second' . ($timeout == 1000 ? '' : 's'); }
									else { $timeoutString = floor($timeout / 60000) . ' minute' . (floor($timeout / 60000) == 1 ? '' : 's'); }
									
									$wafActionContent = "<h3>Finishing Removal</h3>";
									
									if (isset($_REQUEST['iniTTLWaited'])) {
										$wafActionContent .= "<p class='wf-error'>Extended Protection Mode has not been disabled. This may be because <code>auto_prepend_file</code> is configured somewhere else or the value is still cached by PHP.</p>";
									}
									else {
										$wafActionContent .= "<p>The <code>auto_prepend_file</code> setting has been successfully removed from <code>.htaccess</code> and <code>.user.ini</code>. Once this change takes effect, Extended Protection Mode will be disabled.</p>\n";
										if ($_REQUEST['manualAutoPrependReenable']) {
											$wafActionContent .= "<p>Any previous value for <code>auto_prepend_file</code> will need to be re-enabled manually if needed.</p>";
										}
										$wafActionContent .= "<p class='wordfence-waiting'><img src='" . wfUtils::getBaseURL() . "images/loading_large.gif' alt='Loading indicator'>&nbsp;&nbsp;<span>Waiting for it to take effect. This may take up to {$timeoutString}.</span></p>";
									}
									
									$adminURL = json_encode(esc_url_raw(network_admin_url('admin.php?page=WordfenceWAF&wafAction=removeAutoPrepend&wfnonce='
										. $wfnonce . '&serverConfiguration=' . $serverConfig . '&iniModified=1&iniTTLWaited=1')));
									$wafActionContent .= "<script>
setTimeout(function() { document.location.href={$adminURL}; }, {$timeout});
</script>"; 
								}
							}
							else if ($installedHere) {
								$hasCommentedAutoPrepend = $helper->performIniRemoval($wp_filesystem);
								
								$adminURL = json_encode(esc_url_raw(network_admin_url('admin.php?page=WordfenceWAF&wafAction=removeAutoPrepend&wfnonce='
									. $wfnonce . '&serverConfiguration=' . $serverConfig . '&iniModified=1&manualAutoPrependReenable=' . ($hasCommentedAutoPrepend ? 1 : 0))));
								$wafActionContent = "<script>
document.location.href={$adminURL};
</script>";
							}
							break;
						}
						catch (wfWAFAutoPrependHelperException $e) {
							$wafActionContent = "<p>" . $e->getMessage() . "</p>";
							break;
						}
					}
					
					$bootstrap = self::getWAFBootstrapPath();
					
					$wafActionContent .= "<h3 class=\"wf-add-top\">Alternate method:</h3>
<p>We've also included instructions to manually perform the change if you are using a web server other than what is listed in the drop-down, or if file permissions prevent this change.</p>";
					
					
					$wafActionContent .= "<p>You will need to remove the following code from your <code>php.ini</code>, <code>.user.ini</code>, or <code>.htaccess</code> file:</p>
<pre class='wf-pre'>auto_prepend_file = '" . esc_textarea($currentAutoPrependFile) . "'</pre>
<p>Once the change takes effect, you will need remove the following file in your WordPress root:</p>
<pre class='wf-pre'>" . esc_html(self::getWAFBootstrapPath()) . "</pre>";
					
					$wafActionContent = sprintf('<div class="wf-add-top wf-add-bottom">%s</div>', $wafActionContent);
					break;

				case '':
					break;
			}
		}
		require 'menu_firewall.php';
	}

	public static function liveTrafficW3TCWarning() {
		echo self::cachingWarning("W3 Total Cache");
	}
	public static function liveTrafficSuperCacheWarning(){
		echo self::cachingWarning("WP Super Cache");
	}
	public static function cachingWarning($plugin){
		return '<div id="wordfenceConfigWarning" class="error fade"><p><strong>The Wordfence Live Traffic feature has been disabled because you have ' . $plugin . ' active which is not compatible with Wordfence Live Traffic.</strong> If you want to reenable Wordfence Live Traffic, you need to deactivate ' . $plugin . ' and then go to the Wordfence options page and reenable Live Traffic there. Wordfence does work with ' . $plugin . ', however Live Traffic will be disabled and the Wordfence firewall will also count less hits per visitor because of the ' . $plugin . ' caching function. All other functions should work correctly.</p></div>';
	}
	public static function menu_dashboard() {
		wp_enqueue_style('font-awesome4', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), '4.7.0'); //GoDaddy enqueues its own ancient copy under 'font-awesome', so we have to use a different slug to ensure ours gets included
		wp_enqueue_script('chart-js', wfUtils::getBaseURL() . 'js/Chart.bundle.min.js', array('jquery'), '2.4.0');
		require('menu_dashboard.php');
	}
	public static function menu_activity() {
		wp_enqueue_style('wordfence-jquery-ui-css', wfUtils::getBaseURL() . 'css/jquery-ui.min.css', array(), WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-jquery-ui-structure-css', wfUtils::getBaseURL() . 'css/jquery-ui.structure.min.css', array(), WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-jquery-ui-theme-css', wfUtils::getBaseURL() . 'css/jquery-ui.theme.min.css', array(), WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-jquery-ui-timepicker-css', wfUtils::getBaseURL() . 'css/jquery-ui-timepicker-addon.css', array(), WORDFENCE_VERSION);

		wp_enqueue_script('wordfence-timepicker-js', wfUtils::getBaseURL() . 'js/jquery-ui-timepicker-addon.js', array('jquery', 'jquery-ui-datepicker', 'jquery-ui-slider'), WORDFENCE_VERSION);
		wp_enqueue_script('wordfence-knockout-js', wfUtils::getBaseURL() . 'js/knockout-3.3.0.js', array(), WORDFENCE_VERSION);
		wp_enqueue_script('wordfence-live-traffic-js', wfUtils::getBaseURL() . 'js/admin.liveTraffic.js', array('jquery'), WORDFENCE_VERSION);

		require 'menu_activity.php';
	}
	public static function menu_scan(){
		$scanAction = filter_input(INPUT_GET, 'wfScanAction', FILTER_SANITIZE_STRING);
		if ($scanAction == 'promptForCredentials') {
			$fsAction = filter_input(INPUT_GET, 'wfFilesystemAction', FILTER_SANITIZE_STRING);
			$promptForCredentials = true;
			$filesystemCredentialsAdminURL = network_admin_url('admin.php?' . http_build_query(array(
					'page'               => 'Wordfence',
					'wfScanAction'       => 'promptForCredentials',
					'wfFilesystemAction' => $fsAction,
					'issueID'            => filter_input(INPUT_GET, 'issueID', FILTER_SANITIZE_NUMBER_INT),
					'nonce'              => wp_create_nonce('wp-ajax'),
				)));

			switch ($fsAction) {
				case 'restoreFile':
					$wpFilesystemActionCallback = array('wordfence', 'fsActionRestoreFileCallback');
					break;
				case 'deleteFile':
					$wpFilesystemActionCallback = array('wordfence', 'fsActionDeleteFileCallback');
					break;
			}
		}

		require 'menu_scan.php';
	}

	public static function fsActionRestoreFileCallback() {
		$issueID = filter_input(INPUT_GET, 'issueID', FILTER_SANITIZE_NUMBER_INT);
		$response = self::ajax_restoreFile_callback($issueID);
		if (!empty($response['ok'])) {
			$result = sprintf('<p>The file <code>%s</code> was restored successfully.</p>',
				esc_html(strpos($response['file'], ABSPATH) === 0 ? substr($response['file'], strlen(ABSPATH) + 1) : $response['file']));
		} else if (!empty($response['cerrorMessage'])) {
			$result = sprintf('<div class="wfSummaryErr">%s</div>', esc_html($response['cerrorMessage']));
		} else {
			$result = '<div class="wfSummaryErr">There was an error restoring the file.</div>';
		}
		printf(<<<HTML
<br>
%s
<p><a href="%s">Return to scan results</a></p>
HTML
			,
			$result,
			esc_url(network_admin_url('admin.php?page=WordfenceScan'))
		);
		wfScanEngine::refreshScanNotification();
	}

	public static function fsActionDeleteFileCallback() {
		$issueID = filter_input(INPUT_GET, 'issueID', FILTER_SANITIZE_NUMBER_INT);
		$response = self::ajax_deleteFile_callback($issueID);
		if (!empty($response['ok'])) {
			$result = sprintf('<p>The file <code>%s</code> was deleted successfully.</p>', esc_html($response['file']));
		} else if (!empty($response['errorMessage'])) {
			$result = sprintf('<div class="wfSummaryErr">%s</div>', esc_html($response['errorMessage']));
		} else {
			$result = '<div class="wfSummaryErr">There was an error deleting the file.</div>';
		}
		printf(<<<HTML
<br>
%s
<p><a href="%s">Return to scan results</a></p>
HTML
			,
			$result,
			esc_url(network_admin_url('admin.php?page=WordfenceScan'))
		);
		wfScanEngine::refreshScanNotification();
	}

	public static function status($level /* 1 has highest visibility */, $type /* info|error */, $msg){
		if($level > 3 && $level < 10 && (! self::isDebugOn())){ //level 10 and higher is for summary messages
			return false;
		}
		if($type != 'info' && $type != 'error'){ error_log("Invalid status type: $type"); return; }
		if(self::$printStatus){
			echo "STATUS: $level : $type : $msg\n";
		} else {
			self::getLog()->addStatus($level, $type, $msg);
		}
	}
	public static function profileUpdateAction($userID, $newDat = false){
		if(! $newDat){ return; }
		if(wfConfig::get('other_pwStrengthOnUpdate')){
			$oldDat = get_userdata($userID);
			if($newDat->user_pass != $oldDat->user_pass){
				$wf = new wfScanEngine();
				$wf->scanUserPassword($userID);
				$wf->emailNewIssues();
			}
		}
	}

	public static function replaceVersion($url) {
		return preg_replace_callback("/([&;\?]ver)=(.+?)(&|$)/", "wordfence::replaceVersionCallback", $url);
	}

	public static function replaceVersionCallback($matches) {
		global $wp_version;
		return $matches[1] . '=' . ($wp_version === $matches[2] ? wp_hash($matches[2]) : $matches[2]) . $matches[3];
	}

	public static function genFilter($gen, $type){
		if(wfConfig::get('other_hideWPVersion')){
			return '';
		} else {
			return $gen;
		}
	}
	public static function pushCommentSpamIP($m){
		if(wfUtils::isValidIP($m[1]) && strpos($m[1], '127.0.0') !== 0 ){
			self::$commentSpamItems[] = trim($m[1]);
		}
	}
	public static function pushCommentSpamHost($m){
		self::$commentSpamItems[] = trim($m[1]);
	}
	public static function preCommentApprovedFilter($approved, $cData){
		if( $approved == 1 && (! is_user_logged_in()) && wfConfig::get('other_noAnonMemberComments') ){
			$user = get_user_by('email', trim($cData['comment_author_email']));
			if($user){
				wfConfig::inc('totalSpamStopped');
				wfActivityReport::logBlockedComment(wfUtils::getIP(), 'anon');
				return 0; //hold for moderation if the user is not signed in but used a members email
			}
		}

		if(($approved == 1 || $approved == 0) && wfConfig::get('other_scanComments')){
			$wf = new wfScanEngine();
			try {
				if($wf->isBadComment($cData['comment_author'], $cData['comment_author_email'], $cData['comment_author_url'],  $cData['comment_author_IP'], $cData['comment_content'])){
					wfConfig::inc('totalSpamStopped');
					wfActivityReport::logBlockedComment(wfUtils::getIP(), 'gsb');
					return 'spam';
				}
			} catch(Exception $e){
				//This will most likely be an API exception because we can't contact the API, so we ignore it and let the normal comment mechanisms run.
			}
		}
		if(wfConfig::get('isPaid') && ($approved == 1 || $approved == 0) && wfConfig::get('advancedCommentScanning')){
			self::$commentSpamItems = array();
			preg_replace_callback('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', 'wordfence::pushCommentSpamIP', $cData['comment_content']);
			$IPs =  self::$commentSpamItems;
			self::$commentSpamItems = array();
			preg_replace_callback('/https?:\/\/([a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+[a-zA-Z0-9])/i', 'wordfence::pushCommentSpamHost', $cData['comment_content']);
			$hosts = self::$commentSpamItems;
			self::$commentSpamItems = array();
			try {
				$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
				$res = $api->call('advanced_comment_scan', array(), array(
					'author' => $cData['comment_author'],
					'email' =>  $cData['comment_author_email'],
					'URL' => $cData['comment_author_url'],
					'commentIP' => $cData['comment_author_IP'],
					'wfIP' => wfUtils::getIP(),
					'hosts' => (sizeof($hosts) > 0 ? implode(',', $hosts) : ''),
					'IPs' => (sizeof($IPs) > 0 ? implode(',', $IPs) : '')
					));
				if(is_array($res) && isset($res['spam']) && $res['spam'] == 1){
					wfConfig::inc('totalSpamStopped');
					wfActivityReport::logBlockedComment(wfUtils::getIP(), 'reputation');
					return 'spam';
				}
			} catch(Exception $e){
				//API server is probably down
			}
		}
		wfConfig::inc('totalCommentsFiltered');
		return $approved;
	}
	public static function getMyHomeURL(){
		return network_admin_url('admin.php?page=Wordfence', 'http');
	}
	public static function getMyOptionsURL(){
		return network_admin_url('admin.php?page=WordfenceSecOpt', 'http');
	}

	public static function alert($subject, $alertMsg, $IP){
		wfConfig::inc('totalAlertsSent');
		$emails = wfConfig::getAlertEmails();
		if(sizeof($emails) < 1){ return; }

		$IPMsg = "";
		if($IP){
			$IPMsg = "User IP: $IP\n";
			$reverse = wfUtils::reverseLookup($IP);
			if($reverse){
				$IPMsg .= "User hostname: " . $reverse . "\n";
			}
			$userLoc = wfUtils::getIPGeo($IP);
			if($userLoc){
				$IPMsg .= "User location: ";
				if($userLoc['city']){
					$IPMsg .= $userLoc['city'] . ', ';
				}
				$IPMsg .= $userLoc['countryName'] . "\n";
			}
		}
		$content = wfUtils::tmpl('email_genericAlert.php', array(
			'isPaid' => wfConfig::get('isPaid'),
			'subject' => $subject,
			'blogName' => get_bloginfo('name', 'raw'),
			'adminURL' => get_admin_url(),
			'alertMsg' => $alertMsg,
			'IPMsg' => $IPMsg,
			'date' => wfUtils::localHumanDate(),
			'myHomeURL' => self::getMyHomeURL(),
			'myOptionsURL' => self::getMyOptionsURL()
			));
		$shortSiteURL = preg_replace('/^https?:\/\//i', '', site_url());
		$subject = "[Wordfence Alert] $shortSiteURL " . $subject;

		$sendMax = wfConfig::get('alert_maxHourly', 0);
		if($sendMax > 0){
			$sendArr = wfConfig::get_ser('alertFreqTrack', array());
			if(! is_array($sendArr)){
				$sendArr = array();
			}
			$minuteTime = floor(time() / 60);
			$totalSent = 0;
			for($i = $minuteTime; $i > $minuteTime - 60; $i--){
				$totalSent += isset($sendArr[$i]) ? $sendArr[$i] : 0;
			}
			if($totalSent >= $sendMax){
				return;
			}
			$sendArr[$minuteTime] = isset($sendArr[$minuteTime]) ? $sendArr[$minuteTime] + 1 : 1;
			wfConfig::set_ser('alertFreqTrack', $sendArr);
		}
		//Prevent duplicate emails within 1 hour:
		$hash = md5(implode(',', $emails) . ':' . $subject . ':' . $alertMsg . ':' . $IP); //Hex
		$lastHash = wfConfig::get('lastEmailHash', false);
		if($lastHash){
			$lastHashDat = explode(':', $lastHash); //[time, hash]
			if(time() - $lastHashDat[0] < 3600){
				if($lastHashDat[1] == $hash){
					return; //Don't send because this email is identical to the previous email which was sent within the last hour.
				}
			}
		}
		wfConfig::set('lastEmailHash', time() . ':' . $hash);
		wp_mail(implode(',', $emails), $subject, $content);
	}
	public static function getLog(){
		if(! self::$wfLog){
			$wfLog = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
			self::$wfLog = $wfLog;
		}
		return self::$wfLog;
	}
	public static function wfSchemaExists(){
		global $wpdb;
		$exists = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME=%s
SQL
			, $wpdb->base_prefix . 'wfConfig'));
		return $exists ? true : false;
	}
	public static function isDebugOn(){
		if(is_null(self::$debugOn)){
			if(wfConfig::get('debugOn')){
				self::$debugOn = true;
			} else {
				self::$debugOn = false;
			}
		}
		return self::$debugOn;
	}
	//PUBLIC API
	public static function doNotCache(){ //Call this to prevent Wordfence from caching the current page.
		wfCache::doNotCache();
		return true;
	}
	public static function whitelistIP($IP){ //IP as a string in dotted quad notation e.g. '10.11.12.13'
		$IP = trim($IP);
		$user_range = new wfUserIPRange($IP);
		if (!$user_range->isValidRange()) {
			throw new Exception("The IP you provided must be in dotted quad notation or use ranges with square brackets. e.g. 10.11.12.13 or 10.11.12.[1-50]");
		}
		$whites = wfConfig::get('whitelisted', '');
		$arr = explode(',', $whites);
		$arr2 = array();
		foreach($arr as $e){
			if($e == $IP){
				return false;
			}
			$arr2[] = trim($e);
		}
		$arr2[] = $IP;
		wfConfig::set('whitelisted', implode(',', $arr2));
		return true;
	}

	public static function ajax_email_summary_email_address_debug_callback() {
		$email = !empty($_REQUEST['email']) ? $_REQUEST['email'] : null;
		$report = new wfActivityReport();
		return $report->sendReportViaEmail($email) ?
			array('ok' => 1, 'result' => 'Test email sent successfully') :
			array('err' => "Test email failed to send.");
	}

	public static function addDashboardWidget() {
		if (wfUtils::isAdmin() && (is_network_admin() || !is_multisite()) && wfConfig::get('email_summary_dashboard_widget_enabled')) {
			wp_enqueue_style('wordfence-activity-report-widget', wfUtils::getBaseURL() . 'css/activity-report-widget.css', '', WORDFENCE_VERSION);
			$report_date_range = 'week';
			switch (wfConfig::get('email_summary_interval')) {
				case 'daily':
					$report_date_range = 'day';
					break;

				case 'monthly':
					$report_date_range = 'month';
					break;
			}
			wp_add_dashboard_widget(
				'wordfence_activity_report_widget',
				'Wordfence activity in the past ' . $report_date_range,
				array('wfActivityReport', 'outputDashboardWidget')
			);
		}
	}

	/**
	 * @return bool
	 */
	public static function hasGDLimitLoginsMUPlugin() {
		return defined('GD_SYSTEM_PLUGIN_DIR') && file_exists(GD_SYSTEM_PLUGIN_DIR . 'limit-login-attempts/limit-login-attempts.php')
			&& defined('LIMIT_LOGIN_DIRECT_ADDR');
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public static function fixGDLimitLoginsErrors($content) {
		if (self::$authError) {
			$content = str_replace(__('<strong>ERROR</strong>: Incorrect username or password.', 'limit-login-attempts') . "<br />\n", '', $content);
			$content .= '<br />' . self::$authError->get_error_message();
		}
		return $content;
	}

	/**
	 * Permanently blocks all temporarily locked out IPs.
	 */
	public static function ajax_permanentlyBlockAllIPs_callback() {
		/** @var wpdb $wpdb */
		global $wpdb;
		$IPs = array();
		$type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : null;
		$reason = !empty($_REQUEST['reason']) ? $_REQUEST['reason'] : 'Manual block by administrator';
		switch ($type) {
			case 'throttled':
				$IPs = $wpdb->get_col('SELECT DISTINCT IP FROM ' . $wpdb->base_prefix . 'wfThrottleLog');
				break;
			case 'lockedOut':
				$lockoutSecs = wfConfig::get('loginSec_lockoutMins') * 60;
				$IPs = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT IP FROM ' . $wpdb->base_prefix . 'wfLockedOut
				WHERE blockedTime + %d > UNIX_TIMESTAMP()', $lockoutSecs));
				break;
			case 'blocked':
				$blockedTime = wfConfig::get('blockedTime');
				$IPs = $wpdb->get_col($wpdb->prepare('SELECT DISTINCT IP FROM ' . $wpdb->base_prefix . 'wfBlocks
				WHERE wfsn = 0
				AND permanent = 0
				AND blockedTime + %d > UNIX_TIMESTAMP()', $blockedTime));
				break;
		}
		$log = new wfLog(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		if ($IPs && is_array($IPs)) {
			foreach ($IPs as $IP) {
				$log->blockIP(wfUtils::inet_ntop($IP), $reason, false, true, false, 'manual');
			}
		}
		switch ($type) {
			case 'lockedOut':
				if ($IPs) {
					foreach ($IPs as &$IP) {
						$IP = $wpdb->prepare('%s', $IP);
					}
					$wpdb->query('DELETE FROM ' . $wpdb->base_prefix . 'wfLockedOut WHERE IP IN ('. join(', ', $IPs).')');
				}
				break;
		}
		return array('ok' => 1);
	}

	/**
	 * @return array
	 */
	public static function ajax_deleteAdminUser_callback() {
		/** @var wpdb $wpdb */
		global $wpdb;
		$issueID = absint(!empty($_POST['issueID']) ? $_POST['issueID'] : 0);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array('errorMsg' => "We could not find that issue in our database.");
		}
		$data = $issue['data'];
		if (empty($data['userID'])) {
			return array('errorMsg' => "We could not find that user in the database.");
		}
		$user = new WP_User($data['userID']);
		if (!$user->exists()) {
			return array('errorMsg' => "We could not find that user in the database.");
		}
		$userLogin = $user->user_login;
		if (is_multisite() && strcasecmp($user->user_email, get_site_option('admin_email')) === 0) {
			return array('errorMsg' => "This user's email is the network admin email. It will need to be changed before deleting this user.");
		}
		if (is_multisite()) {
			revoke_super_admin($data['userID']);
		}
		wp_delete_user($data['userID']);
		if (is_multisite()) {
			$wpdb->delete($wpdb->users, array('ID' => $data['userID']));
		}
		$wfIssues->deleteIssue($issueID);
		wfScanEngine::refreshScanNotification($wfIssues);

		return array(
			'ok'         => 1,
			'user_login' => $userLogin,
		);
	}

	public static function ajax_revokeAdminUser_callback() {
		$issueID = absint(!empty($_POST['issueID']) ? $_POST['issueID'] : 0);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array('errorMsg' => "We could not find that issue in our database.");
		}
		$data = $issue['data'];
		if (empty($data['userID'])) {
			return array('errorMsg' => "We could not find that user in the database.");
		}
		$user = new WP_User($data['userID']);
		$userLogin = $user->user_login;
		wp_revoke_user($data['userID']);
		if (is_multisite()) {
			revoke_super_admin($data['userID']);
		}

		$wfIssues->deleteIssue($issueID);
		wfScanEngine::refreshScanNotification($wfIssues);

		return array(
			'ok'         => 1,
			'user_login' => $userLogin,
		);
	}

	/**
	 *
	 */
	public static function ajax_disableDirectoryListing_callback() {
		$issueID = absint($_POST['issueID']);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array(
				'err'      => 1,
				'errorMsg' => "We could not find that issue in our database.",
			);
		}
		$wfIssues->deleteIssue($issueID);

		$htaccessPath = wfCache::getHtaccessPath();
		if (!$htaccessPath) {
			return array(
				'err'      => 1,
				'errorMsg' => "Wordfence could not find your .htaccess file.",
			);
		}

		$fileContents = file_get_contents($htaccessPath);
		if (file_put_contents($htaccessPath, "# Added by Wordfence " . date('r') . "\nOptions -Indexes\n\n" . $fileContents, LOCK_EX)) {
			$uploadPaths = wp_upload_dir();
			if (!wfScanEngine::isDirectoryListingEnabled($uploadPaths['baseurl'])) {
				return array(
					'ok' => 1,
				);
			} else {
				// Revert any changes done to .htaccess
				file_put_contents($htaccessPath, $fileContents, LOCK_EX);
				return array(
					'err'      => 1,
					'errorMsg' => "Updating the .htaccess did not fix the issue. You may need to add <code>Options -Indexes</code>
to your httpd.conf if using Apache, or find documentation on how to disable directory listing for your web server.",
				);
			}
		}
		return array(
			'err'      => 1,
			'errorMsg' => "There was an error writing to your .htaccess file.",
		);
	}

	/**
	 * Modify the query to prevent username enumeration.
	 *
	 * @param array $query_vars
	 * @return array
	 */
	public static function preventAuthorNScans($query_vars) {
		if (wfConfig::get('loginSec_disableAuthorScan') && !is_admin() &&
			!empty($query_vars['author']) && is_numeric(preg_replace('/[^0-9]/', '', $query_vars['author'])) &&
			(
				(isset($_GET['author']) && is_numeric(preg_replace('/[^0-9]/', '', $_GET['author']))) ||
				(isset($_POST['author']) && is_numeric(preg_replace('/[^0-9]/', '', $_POST['author'])))
			)
		) {
			status_header(404);
			nocache_headers();
			
			$template = get_404_template();
			if ($template && file_exists($template)) {
				include($template);
			}
			
			exit;
		}
		return $query_vars;
	}

	/**
	 * @param WP_Upgrader $updater
	 * @param array $hook_extra
	 */
	public static function hideReadme($updater, $hook_extra = null) {
		if (wfConfig::get('other_hideWPVersion')) {
			wfUtils::hideReadme();
		}
	}

	public static function ajax_saveWAFConfig_callback() {
		if (isset($_POST['wafConfigAction'])) {
			$waf = wfWAF::getInstance();
			if (method_exists($waf, 'isReadOnly') && $waf->isReadOnly()) {
				return array(
					'err'      => 1,
					'errorMsg' => "The WAF is currently in read-only mode and will not save any configuration changes.",
				);
			}
			
			switch ($_POST['wafConfigAction']) {
				case 'config':
					if (!empty($_POST['wafStatus'])) {
						if ($_POST['wafStatus'] == 'learning-mode' && !empty($_POST['learningModeGracePeriodEnabled'])) {
							$gracePeriodEnd = strtotime(isset($_POST['learningModeGracePeriod']) ? $_POST['learningModeGracePeriod'] : '');
							if ($gracePeriodEnd > time()) {
								wfWAF::getInstance()->getStorageEngine()->setConfig('learningModeGracePeriodEnabled', 1);
								wfWAF::getInstance()->getStorageEngine()->setConfig('learningModeGracePeriod', $gracePeriodEnd);
							} else {
								return array(
									'err'      => 1,
									'errorMsg' => "The grace period end time must be in the future.",
								);
							}
						} else {
							wfWAF::getInstance()->getStorageEngine()->setConfig('learningModeGracePeriodEnabled', 0);
							wfWAF::getInstance()->getStorageEngine()->unsetConfig('learningModeGracePeriod');
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('wafStatus', $_POST['wafStatus']);
					}

					break;

				case 'addWhitelist':
					if (isset($_POST['whitelistedPath']) && isset($_POST['whitelistedParam'])) {
						$path = stripslashes($_POST['whitelistedPath']);
						$paramKey = stripslashes($_POST['whitelistedParam']);
						if (!$path || !$paramKey) {
							break;
						}
						$data = array(
							'timestamp'   => time(),
							'description' => 'Whitelisted via Firewall Options page',
							'ip'          => wfUtils::getIP(),
							'disabled'    => empty($_POST['whitelistedEnabled']),
						);
						if (function_exists('get_current_user_id')) {
							$data['userID'] = get_current_user_id();
						}
						wfWAF::getInstance()->whitelistRuleForParam($path, $paramKey, 'all', $data);
					}
					break;

				case 'replaceWhitelist':
					if (
						!empty($_POST['oldWhitelistedPath']) && !empty($_POST['oldWhitelistedParam']) &&
						!empty($_POST['newWhitelistedPath']) && !empty($_POST['newWhitelistedParam'])
					) {
						$oldWhitelistedPath = stripslashes($_POST['oldWhitelistedPath']);
						$oldWhitelistedParam = stripslashes($_POST['oldWhitelistedParam']);

						$newWhitelistedPath = stripslashes($_POST['newWhitelistedPath']);
						$newWhitelistedParam = stripslashes($_POST['newWhitelistedParam']);

						$savedWhitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams');
						// These are already base64'd
						$oldKey = $oldWhitelistedPath . '|' . $oldWhitelistedParam;
						$newKey = base64_encode($newWhitelistedPath) . '|' . base64_encode($newWhitelistedParam);
						try {
							$savedWhitelistedURLParams = wfUtils::arrayReplaceKey($savedWhitelistedURLParams, $oldKey, $newKey);
						} catch (Exception $e) {
							error_log("Caught exception from 'wfUtils::arrayReplaceKey' with message: " . $e->getMessage());
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('whitelistedURLParams', $savedWhitelistedURLParams);
					}
					break;

				case 'deleteWhitelist':
					if (
						isset($_POST['deletedWhitelistedPath']) && is_string($_POST['deletedWhitelistedPath']) &&
						isset($_POST['deletedWhitelistedParam']) && is_string($_POST['deletedWhitelistedParam'])
					) {
						$deletedWhitelistedPath = stripslashes($_POST['deletedWhitelistedPath']);
						$deletedWhitelistedParam = stripslashes($_POST['deletedWhitelistedParam']);
						$savedWhitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams');
						$key = $deletedWhitelistedPath . '|' . $deletedWhitelistedParam;
						unset($savedWhitelistedURLParams[$key]);
						wfWAF::getInstance()->getStorageEngine()->setConfig('whitelistedURLParams', $savedWhitelistedURLParams);
					}
					break;

				case 'enableWhitelist':
					if (isset($_POST['whitelistedPath']) && isset($_POST['whitelistedParam'])) {
						$path = stripslashes($_POST['whitelistedPath']);
						$paramKey = stripslashes($_POST['whitelistedParam']);
						if (!$path || !$paramKey) {
							break;
						}
						$enabled = !empty($_POST['whitelistedEnabled']);

						$savedWhitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams');
						$key = $path . '|' . $paramKey;
						if (array_key_exists($key, $savedWhitelistedURLParams) && is_array($savedWhitelistedURLParams[$key])) {
							foreach ($savedWhitelistedURLParams[$key] as $ruleID => $data) {
								$savedWhitelistedURLParams[$key][$ruleID]['disabled'] = !$enabled;
							}
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('whitelistedURLParams', $savedWhitelistedURLParams);
					}
					break;

				case 'enableRule':
					$ruleEnabled = !empty($_POST['ruleEnabled']);
					$ruleID = !empty($_POST['ruleID']) ? (int) $_POST['ruleID'] : false;
					if ($ruleID) {
						$disabledRules = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('disabledRules');
						if ($ruleEnabled) {
							unset($disabledRules[$ruleID]);
						} else {
							$disabledRules[$ruleID] = true;
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('disabledRules', $disabledRules);
					}
					break;
				case 'disableWAFBlacklistBlocking':
					if (isset($_POST['disableWAFBlacklistBlocking'])) {
						$disableWAFBlacklistBlocking = (int) $_POST['disableWAFBlacklistBlocking'];
						wfWAF::getInstance()->getStorageEngine()->setConfig('disableWAFBlacklistBlocking', $disableWAFBlacklistBlocking);
						if (method_exists(wfWAF::getInstance()->getStorageEngine(), 'purgeIPBlocks')) {
							wfWAF::getInstance()->getStorageEngine()->purgeIPBlocks(wfWAFStorageInterface::IP_BLOCKS_BLACKLIST);
						}
					}
					break;
			}
		}

		return array(
			'success' => true,
			'data'    => self::_getWAFData(),
		);
	}

	public static function ajax_updateWAFRules_callback() {
		$event = new wfWAFCronFetchRulesEvent(time() - 2);
		$event->setWaf(wfWAF::getInstance());
		$success = $event->fire();

		return self::_getWAFData($success);
	}

	public static function ajax_loadLiveTraffic_callback() {
		$return = array();

		$filters = new wfLiveTrafficQueryFilterCollection();
		$query = new wfLiveTrafficQuery(self::getLog());
		$query->setFilters($filters);
		if (array_key_exists('groupby', $_REQUEST)) {
			$param = $_REQUEST['groupby'];
			if ($param === 'type') {
				$param = 'jsRun';
			}
			$query->setGroupBy(new wfLiveTrafficQueryGroupBy($query, $param));
		}
		$query->setLimit(isset($_REQUEST['limit']) ? absint($_REQUEST['limit']) : 20);
		$query->setOffset(isset($_REQUEST['offset']) ? absint($_REQUEST['offset']) : 0);

		if (!empty($_REQUEST['since'])) {
			$query->setStartDate($_REQUEST['since']);
		} else if (!empty($_REQUEST['startDate'])) {
			$query->setStartDate(is_numeric($_REQUEST['startDate']) ? $_REQUEST['startDate'] : strtotime($_REQUEST['startDate']));
		}

		if (!empty($_REQUEST['endDate'])) {
			$query->setEndDate(is_numeric($_REQUEST['endDate']) ? $_REQUEST['endDate'] : strtotime($_REQUEST['endDate']));
		}

		if (
			array_key_exists('param', $_REQUEST) && is_array($_REQUEST['param']) &&
			array_key_exists('operator', $_REQUEST) && is_array($_REQUEST['operator']) &&
			array_key_exists('value', $_REQUEST) && is_array($_REQUEST['value'])
		) {
			for ($i = 0; $i < count($_REQUEST['param']); $i++) {
				if (
					array_key_exists($i, $_REQUEST['param']) &&
					array_key_exists($i, $_REQUEST['operator']) &&
					array_key_exists($i, $_REQUEST['value'])
				) {
					$param = $_REQUEST['param'][$i];
					$operator = $_REQUEST['operator'][$i];
					$value = $_REQUEST['value'][$i];

					switch (strtolower($param)) {
						case 'type':
							$param = 'jsRun';
							$value = strtolower($value) === 'human' ? 1 : 0;
							break;
						case 'ip':
							$ip = $value;
							
							if (strpos($ip, '*') !== false && ($operator == '=' || $operator == '!=')) { //If the IP contains a *, treat it as a wildcard for that segment and silently adjust the rule
								if (preg_match('/^(?:(?:\d{1,3}|\*)(?:\.|$)){2,4}/', $ip)) { //IPv4
									$value = array('00', '00', '00', '00', '00', '00', '00', '00', '00', '00', 'FF', 'FF');
									$octets = explode('.', $ip);
									foreach ($octets as $o)
									{
										if (strpos($o, '*') !== false) {
											$value[] = '..';
										}
										else {
											$value[] = strtoupper(str_pad(dechex($o), 2, '0', STR_PAD_LEFT));
										}
									}
									$value = '^' . implode('', array_pad($value, 16, '..')) . '$';
									$operator = ($operator == '=' ? 'hregexp' : 'hnotregexp');
								}
								else if (!empty($ip) && preg_match('/^((?:[\da-f*]{1,4}(?::|)){0,8})(::)?((?:[\da-f*]{1,4}(?::|)){0,8})$/i', $ip)) { //IPv6
									if ($ip === '::') {
										$value = '^' . str_repeat('00', 16) . '$';
									}
									else {
										$colon_count = substr_count($ip, ':');
										$dbl_colon_pos = strpos($ip, '::');
										if ($dbl_colon_pos !== false) {
											$ip = str_replace('::', str_repeat(':0000', (($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($ip) - 2) ? 9 : 8) - $colon_count) . ':', $ip);
											$ip = trim($ip, ':');
										}

										$ip_groups = explode(':', $ip);
										$value = array();
										foreach ($ip_groups as $ip_group) {
											if (strpos($ip_group, '*') !== false) {
												$value[] = '..';
												$value[] = '..';
											}
											else {
												$ip_group = strtoupper(str_pad($ip_group, 4, '0', STR_PAD_LEFT));
												$value[] = substr($ip_group, 0, 2);
												$value[] = substr($ip_group, -2);
											}
										}

										$value = '^' . implode('', array_pad($value, 16, '..')) . '$';
									}
									$operator = ($operator == '=' ? 'hregexp' : 'hnotregexp');
								}
								else if (preg_match('/^((?:0{1,4}(?::|)){0,5})(::)?ffff:((?:\d{1,3}(?:\.|$)){4})$/i', $ip, $matches)) { //IPv4 mapped IPv6
									$value = array('00', '00', '00', '00', '00', '00', '00', '00', '00', '00', 'FF', 'FF');
									$octets = explode('.', $matches[3]);
									foreach ($octets as $o)
									{
										if (strpos($o, '*') !== false) {
											$value[] = '..';
										}
										else {
											$value[] = strtoupper(str_pad(dechex($o), 2, '0', STR_PAD_LEFT));
										}
									}
									$value = '^' . implode('', array_pad($value, 16, '.')) . '$';
									$operator = ($operator == '=' ? 'hregexp' : 'hnotregexp');
								}
								else {
									$value = false;
								}
							}
							else {
								$value = wfUtils::inet_pton($ip);
							}
							break;
						case 'userid':
							$value = absint($value);
							break;
					}
					if ($operator === 'match' && $param !== 'ip') {
						$value = str_replace('*', '%', $value);
					}
					$filters->addFilter(new wfLiveTrafficQueryFilter($query, $param, $operator, $value));
				}
			}
		}

		try {
			$return['data'] = $query->execute();
			/*if (defined('WP_DEBUG') && WP_DEBUG) {
				$return['sql'] = $query->buildQuery();
			}*/
		} catch (wfLiveTrafficQueryException $e) {
			$return['data'] = array();
			$return['sql'] = $e->getMessage();
		}

		$return['success'] = true;

		return $return;
	}

	public static function ajax_whitelistWAFParamKey_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (isset($_POST['path']) && isset($_POST['paramKey']) && isset($_POST['failedRules'])) {
				$data = array(
					'timestamp'   => time(),
					'description' => 'Whitelisted via Live Traffic',
					'ip'          => wfUtils::getIP(),
				);
				if (function_exists('get_current_user_id')) {
					$data['userID'] = get_current_user_id();
				}
				$waf->whitelistRuleForParam(base64_decode($_POST['path']), base64_decode($_POST['paramKey']),
					$_POST['failedRules'], $data);

				return array(
					'success' => true,
				);
			}
		}
		return false;
	}

	public static function ajax_whitelistBulkDelete_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (!empty($_POST['items']) && ($items = json_decode(stripslashes($_POST['items']), true)) !== false) {
				$whitelist = $waf->getStorageEngine()->getConfig('whitelistedURLParams');
				if (!is_array($whitelist)) {
					$whitelist = array();
				}
				foreach ($items as $key) {
					list($path, $paramKey, ) = $key;
					$whitelistKey = $path . '|' . $paramKey;
					if (array_key_exists($whitelistKey, $whitelist)) {
						unset($whitelist[$whitelistKey]);
					}
				}
				$waf->getStorageEngine()->setConfig('whitelistedURLParams', $whitelist);
				return array(
					'data'    => self::_getWAFData(),
					'success' => true,
				);
			}
		}
		return false;
	}

	public static function ajax_whitelistBulkEnable_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (!empty($_POST['items']) && ($items = json_decode(stripslashes($_POST['items']), true)) !== false) {
				self::_whitelistBulkToggle($items, true);
				return array(
					'data'    => self::_getWAFData(),
					'success' => true,
				);
			}
		}
		return false;
	}

	public static function ajax_whitelistBulkDisable_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (!empty($_POST['items']) && ($items = json_decode(stripslashes($_POST['items']), true)) !== false) {
				self::_whitelistBulkToggle($items, false);
				return array(
					'data'    => self::_getWAFData(),
					'success' => true,
				);
			}
		}
		return false;
	}

	private static function _whitelistBulkToggle($items, $enabled) {
		$waf = wfWAF::getInstance();
		$whitelist = $waf->getStorageEngine()->getConfig('whitelistedURLParams');
		if (!is_array($whitelist)) {
			$whitelist = array();
		}
		foreach ($items as $key) {
			list($path, $paramKey, ) = $key;
			$whitelistKey = $path . '|' . $paramKey;
			if (array_key_exists($whitelistKey, $whitelist) && is_array($whitelist[$whitelistKey])) {
				foreach ($whitelist[$whitelistKey] as $ruleID => $data) {
					$whitelist[$whitelistKey][$ruleID]['disabled'] = !$enabled;
				}
			}
		}
		$waf->getStorageEngine()->setConfig('whitelistedURLParams', $whitelist);
	}

	private static function _getWAFData($updated = null) {
		$data['learningMode'] = wfWAF::getInstance()->isInLearningMode();
		$data['rules'] = wfWAF::getInstance()->getRules();
		/** @var wfWAFRule $rule */
		foreach ($data['rules'] as $ruleID => $rule) {
			$data['rules'][$ruleID] = $rule->toArray();
		}

		$whitelistedURLParams = wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams', array());
		$data['whitelistedURLParams'] = array();
		foreach ($whitelistedURLParams as $urlParamKey => $rules) {
			list($path, $paramKey) = explode('|', $urlParamKey);
			$whitelistData = null;
			foreach ($rules as $ruleID => $whitelistedData) {
				if ($whitelistData === null) {
					$whitelistData = $whitelistedData;
					continue;
				}
				if ($ruleID === 'all') {
					$whitelistData = $whitelistedData;
					break;
				}
			}

			if (is_array($whitelistData) && array_key_exists('userID', $whitelistData) && function_exists('get_user_by')) {
				$user = get_user_by('id', $whitelistData['userID']);
				if ($user) {
					$whitelistData['username'] = $user->user_login;
				}
			}

			$data['whitelistedURLParams'][] = array(
				'path'     => $path,
				'paramKey' => $paramKey,
				'ruleID'   => array_keys($rules),
				'data'     => $whitelistData,
			);
		}

		$data['disabledRules'] = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('disabledRules');
		if ($lastUpdated = wfWAF::getInstance()->getStorageEngine()->getConfig('rulesLastUpdated')) {
			$data['rulesLastUpdated'] = $lastUpdated;
		}
		$data['isPaid'] = (bool) wfConfig::get('isPaid', 0);
		if ($updated !== null) {
			$data['updated'] = (bool) $updated;
		}
		return $data;
	}

	public static function actionUserRegistration($user_id) {
		if (wfUtils::isAdmin($user_id) && ($request = self::getLog()->getCurrentRequest())) {
			//self::getLog()->canLogHit = true;
			$request->action = 'user:adminCreate';
			$request->save();
		}
	}

	public static function actionPasswordReset($user = null, $new_pass = null) {
		if ($request = self::getLog()->getCurrentRequest()) {
			//self::getLog()->canLogHit = true;
			$request->action = 'user:passwordReset';
			$request->save();
		}
	}

	public static function trimWfHits() {
		global $wpdb;
		$p = $wpdb->base_prefix;
		$wfdb = new wfDB();
		$lastAggregation = wfConfig::get('lastBlockAggregation', 0);
		$count = $wfdb->querySingle("select count(*) as cnt from $p"."wfHits");
		$liveTrafficMaxRows = absint(wfConfig::get('liveTraf_maxRows', 2000));
		if ($count > $liveTrafficMaxRows * 10) {
			self::_aggregateBlockStats($lastAggregation);
			$wfdb->truncate($p . "wfHits"); //So we don't slow down sites that have very large wfHits tables
		}
		else if ($count > $liveTrafficMaxRows) {
			self::_aggregateBlockStats($lastAggregation);
			$wfdb->queryWrite("delete from $p" . "wfHits order by id asc limit %d", ($count - $liveTrafficMaxRows) + ($liveTrafficMaxRows * .2));
		}
		else if ($lastAggregation < (time() - 86400)) {
			self::_aggregateBlockStats($lastAggregation);
		}
	}
	
	private static function _aggregateBlockStats($since = false) {
		global $wpdb;
		
		if (!wfConfig::get('other_WFNet', true)) {
			return;
		}
		
		if ($since === false) {
			$since = wfConfig::get('lastBlockAggregation', 0);
		}
		
		$hitsTable = wfDB::networkPrefix() . 'wfHits';
		$query = $wpdb->prepare("SELECT COUNT(*) AS cnt, CASE WHEN (jsRun = 1 OR userID > 0) THEN 1 ELSE 0 END AS isHuman, statusCode FROM {$hitsTable} WHERE ctime > %d GROUP BY isHuman, statusCode", $since);
		$rows = $wpdb->get_results($query, ARRAY_A);
		if (count($rows)) {
			try {
				$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
				$api->call('aggregate_stats', array(), array('stats' => json_encode($rows)));
			}
			catch (Exception $e) {
				// Do nothing
			}
		}
		
		wfConfig::set('lastBlockAggregation', time());
	}

	private static function scheduleSendAttackData($timeToSend = null) {
		if ($timeToSend === null) {
			$timeToSend = time() + (60 * 5);
		}
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if (!wp_next_scheduled('wordfence_processAttackData')) {
			wp_schedule_single_event($timeToSend, 'wordfence_processAttackData');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}

	/**
	 *
	 */
	public static function processAttackData() {
		global $wpdb;
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		
		$waf = wfWAF::getInstance();
		if ($waf->getStorageEngine()->getConfig('attackDataKey', false) === false) {
			$waf->getStorageEngine()->setConfig('attackDataKey', mt_rand(0, 0xfff));
		}
		
		//Send alert email if needed
		if (wfConfig::get('wafAlertOnAttacks')) {
			$alertInterval = wfConfig::get('wafAlertInterval', 0);
			$cutoffTime = max(time() - $alertInterval, wfConfig::get('wafAlertLastSendTime'));
			$wafAlertWhitelist = wfConfig::get('wafAlertWhitelist', '');
			$wafAlertWhitelist = preg_split("/[,\r\n]+/", $wafAlertWhitelist);
			foreach ($wafAlertWhitelist as $index => &$entry) {
				$entry = trim($entry);
				if (empty($entry) || (!preg_match('/^(?:\d{1,3}(?:\.|$)){4}/', $entry) && !preg_match('/^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i', $entry))) {
					unset($wafAlertWhitelist[$index]);
					continue;
				}
				
				$packed = @wfUtils::inet_pton($entry);
				if ($packed === false) {
					unset($wafAlertWhitelist[$index]);
					continue;
				}
				$entry = bin2hex($packed);
			}
			$wafAlertWhitelist = array_filter($wafAlertWhitelist);
			$attackData = $wpdb->get_results($wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->base_prefix}wfHits
	WHERE action = 'blocked:waf' " .
	(count($wafAlertWhitelist) ? "AND HEX(IP) NOT IN (" . implode(", ", array_fill(0, count($wafAlertWhitelist), '%s')) . ")" : "") 
	. "AND attackLogTime > %f
	ORDER BY attackLogTime DESC
	LIMIT 10", array_merge($wafAlertWhitelist, array(sprintf('%.6f', $cutoffTime)))));
			$attackCount = $wpdb->get_var('SELECT FOUND_ROWS()');
			$threshold = (int) wfConfig::get('wafAlertThreshold');
			if ($threshold < 1) {
				$threshold = 100;
			}
			if ($attackCount >= $threshold) {
				$durationMessage = wfUtils::makeDuration($alertInterval);
				$message = <<<ALERTMSG
The Wordfence Web Application Firewall has blocked {$attackCount} attacks over the last {$durationMessage}. Below is a sample of these recent attacks:


ALERTMSG;
				$attackTable = array();
				$dateMax = $ipMax = $countryMax = 0;
				foreach ($attackData as $row) {
					$actionData = json_decode($row->actionData, true);
					if (!is_array($actionData) || !isset($actionData['paramKey']) || !isset($actionData['paramValue'])) {
						continue;
					}
					
					if (isset($actionData['failedRules']) && $actionData['failedRules'] == 'blocked') {
						$row->longDescription = "Blocked because the IP is blacklisted";
					}
					else {
						$row->longDescription = "Blocked for " . $row->actionDescription;
					}
					
					$paramKey = base64_decode($actionData['paramKey']);
					$paramValue = base64_decode($actionData['paramValue']);
					if (strlen($paramValue) > 100) {
						$paramValue = substr($paramValue, 0, 100) . chr(2026);
					}
					
					if (preg_match('/([a-z0-9_]+\.[a-z0-9_]+)(?:\[(.+?)\](.*))?/i', $paramKey, $matches)) {
						switch ($matches[1]) {
							case 'request.queryString':
								$row->longDescription = "Blocked for " . $row->actionDescription . ' in query string: ' . $matches[2] . '=' . $paramValue;
								break;
							case 'request.body':
								$row->longDescription = "Blocked for " . $row->actionDescription . ' in POST body: ' . $matches[2] . '=' . $paramValue;
								break;
							case 'request.cookie':
								$row->longDescription = "Blocked for " . $row->actionDescription . ' in cookie: ' . $matches[2] . '=' . $paramValue;
								break;
							case 'request.fileNames':
								$row->longDescription = "Blocked for a " . $row->actionDescription . ' in file: ' . $matches[2] . '=' . $paramValue;
								break;
						}
					}
					
					$date = date_i18n('F j, Y g:ia', floor($row->attackLogTime)); $dateMax = max(strlen($date), $dateMax);
					$ip = wfUtils::inet_ntop($row->IP); $ipMax = max(strlen($ip), $ipMax);
					$country = wfUtils::countryCode2Name(wfUtils::IP2Country($ip)); $country = (empty($country) ? 'Unknown' : $country); $countryMax = max(strlen($country), $countryMax); 
					$attackTable[] = array('date' => $date, 'IP' => $ip, 'country' => $country, 'message' => $row->longDescription);
				}
				
				foreach ($attackTable as $row) {
					$date = str_pad($row['date'], $dateMax + 2);
					$ip = str_pad($row['IP'] . " ({$row['country']})", $ipMax + $countryMax + 8);
					$attackMessage = $row['message'];
					$message .= $date . $ip . $attackMessage . "\n";
				}

				self::alert('Increased Attack Rate', $message, false);
				wfConfig::set('wafAlertLastSendTime', time());
			}
		}

		//Send attack data
		$limit = 500;
		$lastSendTime = wfConfig::get('lastAttackDataSendTime');
		$attackData = $wpdb->get_results($wpdb->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM {$wpdb->base_prefix}wfHits
WHERE action in ('blocked:waf', 'learned:waf', 'logged:waf', 'blocked:waf-always')
AND attackLogTime > %f
LIMIT %d", sprintf('%.6f', $lastSendTime), $limit));
		$totalRows = $wpdb->get_var('SELECT FOUND_ROWS()');

		if ($attackData && wfConfig::get('other_WFNet', true)) {
			$response = wp_remote_get(sprintf(WFWAF_API_URL_SEC . "waf-rules/%d.txt", $waf->getStorageEngine()->getConfig('attackDataKey')), array('headers' => array('Referer' => false)));

			if (!is_wp_error($response)) {
				$okToSendBody = wp_remote_retrieve_body($response);
				if ($okToSendBody === 'ok') {
					// Build JSON to send
					$dataToSend = array();
					$attackDataToUpdate = array();
					foreach ($attackData as $attackDataRow) {
						$actionData = (array) wfRequestModel::unserializeActionData($attackDataRow->actionData);
						$dataToSend[] = array(
							$attackDataRow->attackLogTime,
							$attackDataRow->ctime,
							wfUtils::inet_ntop($attackDataRow->IP),
							(array_key_exists('learningMode', $actionData) ? $actionData['learningMode'] : 0),
							(array_key_exists('paramKey', $actionData) ? base64_encode($actionData['paramKey']) : false),
							(array_key_exists('paramValue', $actionData) ? base64_encode($actionData['paramValue']) : false),
							(array_key_exists('failedRules', $actionData) ? $actionData['failedRules'] : ''),
							strpos($attackDataRow->URL, 'https') === 0 ? 1 : 0,
							(array_key_exists('fullRequest', $actionData) ? $actionData['fullRequest'] : ''),
						);
						if (array_key_exists('fullRequest', $actionData)) {
							unset($actionData['fullRequest']);
							$attackDataToUpdate[$attackDataRow->id] = array(
								'actionData' => wfRequestModel::serializeActionData($actionData),
							);
						}
						if ($attackDataRow->attackLogTime > $lastSendTime) {
							$lastSendTime = $attackDataRow->attackLogTime;
						}
					}
					
					$homeurl = wfUtils::wpHomeURL();
					$siteurl = wfUtils::wpSiteURL();
					$installType = wfUtils::wafInstallationType();
					$response = wp_remote_post(WFWAF_API_URL_SEC . "?" . http_build_query(array(
							'action' => 'send_waf_attack_data',
							'k'      => $waf->getStorageEngine()->getConfig('apiKey'),
							's'      => $siteurl,
							'h'		 => $homeurl,
							't'		 => microtime(true),
							'c'		 => $installType,
						), null, '&'),
						array(
							'body'    => json_encode($dataToSend),
							'headers' => array(
								'Content-Type' => 'application/json',
								'Referer' => false,
							),
							'timeout' => 30,
						));

					if (!is_wp_error($response) && ($body = wp_remote_retrieve_body($response))) {
						$jsonData = json_decode($body, true);
						if (is_array($jsonData) && array_key_exists('success', $jsonData)) {
							// Successfully sent data, remove the full request from the table to reduce storage size
							foreach ($attackDataToUpdate as $hitID => $dataToUpdate) {
								$wpdb->update($wpdb->base_prefix . 'wfHits', $dataToUpdate, array(
									'id' => $hitID,
								));
							}
							wfConfig::set('lastAttackDataSendTime', $lastSendTime);
							if ($totalRows > $limit) {
								self::scheduleSendAttackData();
							}
							
							if (array_key_exists('data', $jsonData) && array_key_exists('watchedIPList', $jsonData['data'])) {
								$waf->getStorageEngine()->setConfig('watchedIPs', $jsonData['data']['watchedIPList']);
							}
						}
					}
				} else if (is_string($okToSendBody) && preg_match('/next check in: ([0-9]+)/', $okToSendBody, $matches)) {
					self::scheduleSendAttackData(time() + $matches[1]);
				}

				// Could be that the server is down, so hold off on sending data for a little while.
			} else {
				self::scheduleSendAttackData(time() + 7200);
			}
		}
		else if (!wfConfig::get('other_WFNet', true)) {
			wfConfig::set('lastAttackDataSendTime', time());
		}

		self::trimWfHits();
	}

	public static function syncAttackData($exit = true) {
		global $wpdb;
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$log = self::getLog();
		$waf = wfWAF::getInstance();
		$lastAttackMicroseconds = $wpdb->get_var("SELECT MAX(attackLogTime) FROM {$wpdb->base_prefix}wfHits");
		if ($waf->getStorageEngine()->hasNewerAttackData($lastAttackMicroseconds)) {
			$attackData = $waf->getStorageEngine()->getNewestAttackDataArray($lastAttackMicroseconds);
			if ($attackData) {
				foreach ($attackData as $request) {
					if (count($request) !== 9 && count($request) !== 10 /* with metadata */) {
						continue;
					}

					list($logTimeMicroseconds, $requestTime, $ip, $learningMode, $paramKey, $paramValue, $failedRules, $ssl, $requestString, $metadata) = $request;

					// Skip old entries and hits in learning mode, since they'll get picked up anyways.
					if ($logTimeMicroseconds <= $lastAttackMicroseconds || $learningMode) {
						continue;
					}
					
					$statusCode = 403;

					$hit = new wfRequestModel();
					$hit->attackLogTime = $logTimeMicroseconds;
					$hit->ctime = $requestTime;
					$hit->IP = wfUtils::inet_pton($ip);

					if (preg_match('/user\-agent:(.*?)\n/i', $requestString, $matches)) {
						$hit->UA = trim($matches[1]);
						$hit->isGoogle = wfCrawl::isGoogleCrawler($hit->UA);
					}

					if (preg_match('/Referer:(.*?)\n/i', $requestString, $matches)) {
						$hit->referer = trim($matches[1]);
					}

					if (preg_match('/^[a-z]+\s+(.*?)\s+/i', $requestString, $uriMatches) && preg_match('/Host:(.*?)\n/i', $requestString, $hostMatches)) {
						$hit->URL = 'http' . ($ssl ? 's' : '') . '://' . trim($hostMatches[1]) . trim($uriMatches[1]);
					}

					$isHuman = false;
					if (preg_match('/cookie:(.*?)\n/i', $requestString, $matches)) {
						$hit->newVisit = strpos($matches[1], 'wfvt_' . crc32(site_url())) !== false ? 1 : 0;
						$hasVerifiedHumanCookie = strpos($matches[1], 'wordfence_verifiedHuman') !== false;
						if ($hasVerifiedHumanCookie && preg_match('/wordfence_verifiedHuman=(.*?);/', $matches[1], $cookieMatches)) {
							$hit->jsRun = (int) $log->validateVerifiedHumanCookie($cookieMatches[1], $hit->UA, $ip);
							$isHuman = !!$hit->jsRun;
						}

						$authCookieName = $waf->getAuthCookieName();
						$hasLoginCookie = strpos($matches[1], $authCookieName) !== false;
						if ($hasLoginCookie && preg_match('/' . preg_quote($authCookieName) . '=(.*?);/', $matches[1], $cookieMatches)) {
							$authCookie = rawurldecode($cookieMatches[1]);
							$decodedAuthCookie = $waf->parseAuthCookie($authCookie);
							if ($decodedAuthCookie !== false) {
								$hit->userID = $decodedAuthCookie['userID'];
								$isHuman = true;
							}
						}
					}

					$path = '/';
					if (preg_match('/^[A-Z]+ (.*?) HTTP\\/1\\.1/', $requestString, $matches)) {
						if (($pos = strpos($matches[1], '?')) !== false) {
							$path = substr($matches[1], 0, $pos);
						} else {
							$path = $matches[1];
						}
					}
					
					$metadata = ($metadata != null ? (array) $metadata : array());
					if (isset($metadata['finalAction']) && $metadata['finalAction']) { // The request was blocked/redirected because of its IP based on the plugin's blocking settings. WAF blocks should be reported but not shown in live traffic with that as a reason.
						$action = $metadata['finalAction']['action'];
						$actionDescription = $action;
						if (class_exists('wfWAFIPBlocksController')) {
							if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_UAREFIPRANGE) {
								$id = $metadata['finalAction']['id'];
								$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfBlocksAdv SET totalBlocked = totalBlocked + 1, lastBlocked = %d WHERE id = %d", $requestTime, $id));
								wfActivityReport::logBlockedIP($ip, null, 'advanced');
							}
							else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_COUNTRY_REDIR) {
								$actionDescription .= ' (' . wfConfig::get('cbl_redirURL') . ')';
								wfConfig::inc('totalCountryBlocked');
								wfActivityReport::logBlockedIP($ip, null, 'country');
							}
							else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_COUNTRY) {
								wfConfig::inc('totalCountryBlocked');
								wfActivityReport::logBlockedIP($ip, null, 'country');
							}
							else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_WFSN) {
								wordfence::wfsnReportBlockedAttempt($ip, 'login');
								wfActivityReport::logBlockedIP($ip, null, 'brute');
							}
							else if (defined('wfWAFIPBlocksController::WFWAF_BLOCK_BADPOST') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_BADPOST) {
								$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfBlocks SET blockedHits = blockedHits + 1, lastAttempt = %d WHERE IP = %s", $requestTime, wfUtils::inet_pton($ip)));
								wfActivityReport::logBlockedIP($ip, null, 'badpost');
							}
							else if (defined('wfWAFIPBlocksController::WFWAF_BLOCK_BANNEDURL') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_BANNEDURL) {
								$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfBlocks SET blockedHits = blockedHits + 1, lastAttempt = %d WHERE IP = %s", $requestTime, wfUtils::inet_pton($ip)));
								wfActivityReport::logBlockedIP($ip, null, 'bannedurl');
							}
							else if (defined('wfWAFIPBlocksController::WFWAF_BLOCK_FAKEGOOGLE') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_FAKEGOOGLE) {
								$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfBlocks SET blockedHits = blockedHits + 1, lastAttempt = %d WHERE IP = %s", $requestTime, wfUtils::inet_pton($ip)));
								wfActivityReport::logBlockedIP($ip, null, 'fakegoogle');
							}
							else if ((defined('wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC) ||
									(defined('wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD') && strpos($action, wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD) === 0) ||
									(defined('wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FAILURES') && strpos($action, wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FAILURES) === 0)) {
								$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfBlocks SET blockedHits = blockedHits + 1, lastAttempt = %d WHERE IP = %s", $requestTime, wfUtils::inet_pton($ip)));
								wfActivityReport::logBlockedIP($ip, null, 'brute');
							}
							else if ((defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEGLOBAL') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEGLOBAL) ||
									(defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLESCAN') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLESCAN) ||
									(defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLER') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLER) ||
									(defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLERNOTFOUND') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLERNOTFOUND) ||
									(defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMAN') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMAN) ||
									(defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMANNOTFOUND') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMANNOTFOUND)
							) {
								$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfThrottleLog SET timesThrottled = timesThrottled + 1, endTime = UNIX_TIMESTAMP() WHERE IP = %s", wfUtils::inet_pton($ip)));
								wfConfig::inc('totalIPsThrottled');
								wfActivityReport::logBlockedIP($ip, null, 'throttle');
							}
							else { //Manual block
								$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfBlocks SET blockedHits = blockedHits + 1, lastAttempt = %d WHERE IP = %s", $requestTime, wfUtils::inet_pton($ip)));
								wfActivityReport::logBlockedIP($ip, null, 'manual');
							}
						}
						
						if (strlen($actionDescription) == 0) {
							$actionDescription = 'Blocked by Wordfence';
						}
						
						if (empty($failedRules)) { // Just a plugin block
							$hit->action = 'blocked:wordfence';
							if (class_exists('wfWAFIPBlocksController')) {
								if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_WFSN) {
									$hit->action = 'blocked:wfsnrepeat';
									wordfence::wfsnReportBlockedAttempt($ip, 'waf');
								}
								else if (isset($metadata['finalAction']['lockout'])) {
									$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}wfLockedOut SET blockedHits = blockedHits + 1, lastAttempt = unix_timestamp() where IP=%s", wfUtils::inet_pton($ip)));
									$hit->action = 'lockedOut';
								}
								else if (isset($metadata['finalAction']['block'])) {
									//Do nothing
								}
							}
							$statusCode = 503;
							$hit->actionDescription = $actionDescription;
						}
						else if ($failedRules == 'logged') {
							$statusCode = 200;
							$hit->action = 'logged:waf';
						}
						else { // Blocked by the WAF but would've been blocked anyway by the plugin settings so that message takes priority
							$hit->action = 'blocked:waf-always';
							$hit->actionDescription = $actionDescription;
						}
					}
					else {
						if ($failedRules == 'logged') {
							$statusCode = 200;
							$hit->action = 'logged:waf';
						}
						else {
							$hit->action = 'blocked:waf';
							
							$type = null;
							if ($failedRules == 'blocked') {
								$type = 'blacklist';
							}
							else if (is_numeric($failedRules)) {
								$type = 'waf';
							}
							wfActivityReport::logBlockedIP($hit->IP, null, $type);
						}
					}

					/** @var wfWAFRule $rule */
					$ruleIDs = explode('|', $failedRules);
					$actionData = array(
						'learningMode' => $learningMode,
						'failedRules'  => $failedRules,
						'paramKey'     => $paramKey,
						'paramValue'   => $paramValue,
						'path'         => $path,
					);
					if ($ruleIDs && $ruleIDs[0]) {
						$rule = $waf->getRule($ruleIDs[0]);
						if ($rule) {
							if ($hit->action == 'logged:waf' || $hit->action == 'blocked:waf') { $hit->actionDescription = $rule->getDescription(); }
							$actionData['category'] = $rule->getCategory();
							$actionData['ssl'] = $ssl;
							$actionData['fullRequest'] = base64_encode($requestString);
						}
						else if ($ruleIDs[0] == 'logged') {
							if ($hit->action == 'logged:waf' || $hit->action == 'blocked:waf') { $hit->actionDescription = 'Watched IP Traffic: ' . $ip; } 
							$actionData['category'] = 'logged';
							$actionData['ssl'] = $ssl;
							$actionData['fullRequest'] = base64_encode($requestString);
						}
						else if ($ruleIDs[0] == 'blocked') {
							$actionData['category'] = 'blocked';
							$actionData['ssl'] = $ssl;
							$actionData['fullRequest'] = base64_encode($requestString);
						}
					}

					$hit->actionData = wfRequestModel::serializeActionData($actionData);
					$hit->statusCode = $statusCode;
					$hit->save();

					self::scheduleSendAttackData();
				}
			}
			$waf->getStorageEngine()->truncateAttackData();
		}
		update_site_option('wordfence_syncingAttackData', 0);
		update_site_option('wordfence_syncAttackDataAttempts', 0);
		update_site_option('wordfence_lastSyncAttackData', time());
		if ($exit) {
			exit;
		}
	}

	public static function addSyncAttackDataAjax() {
		$URL = home_url('/?wordfence_syncAttackData=' . microtime(true));
		$URL = esc_url(preg_replace('/^https?:/i', '', $URL));
		// Load as external script async so we don't slow page down.
		echo "<script type=\"text/javascript\" src=\"$URL\" async></script>";
	}

	/**
	 * This is the only hook I see to tie into WP's core update process.
	 * Since we hide the readme.html to prevent the WordPress version from being discovered, it breaks the upgrade
	 * process because it cannot copy the previous readme.html.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function restoreReadmeForUpgrade($string) {
		static $didRun;
		if (!isset($didRun)) {
			$didRun = true;
			wfUtils::showReadme();
			register_shutdown_function('wfUtils::hideReadme');
		}

		return $string;
	}

	public static function wafAutoPrependNotice() {
		$url = network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend');
		$dismissURL = network_admin_url('admin.php?page=WordfenceWAF&wafAction=dismissAutoPrependNotice&nonce=' .
			rawurlencode(wp_create_nonce('wfDismissAutoPrependNotice')));
		echo '<div class="update-nag" id="wf-extended-protection-notice">To make your site as secure as possible, take a moment to optimize the Wordfence Web
		Application Firewall: &nbsp;<a class="wf-btn wf-btn-default wf-btn-sm" href="' . esc_url($url) . '">Click here to configure.</a>
		<a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="' . esc_url($dismissURL) . '">Dismiss</a>
		<br>
		<em style="font-size: 85%;">If you cannot complete the setup process,
		<a target="_blank" rel="noopener noreferrer" href="https://docs.wordfence.com/en/Web_Application_Firewall_Setup">click here for help</a>.</em>
		</div>';
	}

	public static function wafAutoPrependVerify() {
		if (WFWAF_AUTO_PREPEND && !WFWAF_SUBDIRECTORY_INSTALL) {
			echo '<div class="updated is-dismissible"><p>The installation was successful! Your site is protected to the fullest extent!</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>The changes have not yet taken effect. If you are using LiteSpeed or IIS
as your web server or CGI/FastCGI interface, you may need to wait a few minutes for the changes to take effect since the
configuration files are sometimes cached. You also may need to select a different server configuration in order to
complete this step, but wait for a few minutes before trying. You can try refreshing this page. </p></div>';
		}
	}
	
	public static function wafAutoPrependRemoved() {
		if (!WFWAF_AUTO_PREPEND) {
			echo '<div class="updated is-dismissible"><p>Uninstallation was successful!</p></div>';
		}
		else if (WFWAF_SUBDIRECTORY_INSTALL) {
			echo '<div class="notice notice-warning"><p>Uninstallation from this site was successful! The Wordfence Firewall is still active because it is installed in another WordPress installation.</p></div>';
		}
		else {
			echo '<div class="notice notice-error"><p>The changes have not yet taken effect. If you are using LiteSpeed or IIS
as your web server or CGI/FastCGI interface, you may need to wait a few minutes for the changes to take effect since the
configuration files are sometimes cached. You also may need to select a different server configuration in order to
complete this step, but wait for a few minutes before trying. You can try refreshing this page. </p></div>';
		}
	}
	
	public static function wafCheckOutdatedConfiguration() {
		//suPHP_ConfigPath check
		$htaccess = @file_get_contents(get_home_path() . '.htaccess');
		if ($htaccess && preg_match('/(# Wordfence WAF.*?# END Wordfence WAF)/is', $htaccess, $matches)) {
			$wafSection = $matches[1];
			if (preg_match('#<IfModule\s+mod_suphp\.c>\s+suPHP_ConfigPath\s+\S+\s+</IfModule>#i', $wafSection)) {
				$url= network_admin_url('admin.php?page=WordfenceWAF&wafAction=updateSuPHPConfig');
				echo '<div class="notice notice-warning" id="wordfenceSuPHPUpdateWarning"><p>Your configuration for the Wordfence Firewall needs an update to continue operating optimally:&nbsp;<a class="wf-btn wf-btn-default wf-btn-sm" href="' . esc_url($url) . '">Click here to update</a> <a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#" onclick="wordfenceExt.suPHPWAFUpdateChoice(\'no\'); return false;">Dismiss</a></p></div>';
			}
		}
	}
	
	public static function wafUpdateSuccessful() {
		echo '<div class="updated is-dismissible"><p>The update was successful!</p></div>';
	}

	public static function getWAFBootstrapPath() {
		return ABSPATH . 'wordfence-waf.php';
	}

	public static function getWAFBootstrapContent($currentAutoPrependedFile = null) {
		$currentAutoPrepend = '';
		if ($currentAutoPrependedFile && is_file($currentAutoPrependedFile) && !WFWAF_SUBDIRECTORY_INSTALL) {
			$currentAutoPrepend = sprintf('
// This file was the current value of auto_prepend_file during the Wordfence WAF installation (%2$s)
if (file_exists(%1$s)) {
	include_once %1$s;
}', var_export($currentAutoPrependedFile, true), date('r'));
		}
		return sprintf('<?php
// Before removing this file, please verify the PHP ini setting `auto_prepend_file` does not point to this.
%3$s
if (file_exists(%1$s)) {
	define("WFWAF_LOG_PATH", %2$s);
	include_once %1$s;
}
?>',
			var_export(WORDFENCE_PATH . 'waf/bootstrap.php', true),
			var_export(WFWAF_SUBDIRECTORY_INSTALL ? WP_CONTENT_DIR . '/wflogs/' : WFWAF_LOG_PATH, true),
			$currentAutoPrepend);
	}

	public static function checkAndCreateBootstrap() {
		$bootstrapPath = self::getWAFBootstrapPath();
		if (!file_exists($bootstrapPath) || !filesize($bootstrapPath)) {
			@file_put_contents($bootstrapPath, self::getWAFBootstrapContent(), LOCK_EX);
			clearstatcache();
		}
		return file_exists($bootstrapPath) && filesize($bootstrapPath);
	}

	/**
	 * @return bool|string
	 */
	private static function getCurrentUserRole() {
		if (current_user_can('administrator') || is_super_admin()) {
			return 'administrator';
		}
		$roles = array('editor', 'author', 'contributor', 'subscriber');
		foreach ($roles as $role) {
			if (current_user_can($role)) {
				return $role;
			}
		}
		return false;
	}

	public static function licenseStatusChanged() {
		//Update the WAF cron
		$cron = wfWAF::getInstance()->getStorageEngine()->getConfig('cron');
		if (is_array($cron)) {
			/** @var wfWAFCronEvent $event */
			foreach ($cron as $index => $event) {
				$event->setWaf(wfWAF::getInstance());
				if (!$event->isInPast()) {
					$newEvent = $event->reschedule();
					if ($newEvent instanceof wfWAFCronEvent && $newEvent !== $event) {
						$cron[$index] = $newEvent;
					} else {
						unset($cron[$index]);
					}
				}
			}
		}
		wfWAF::getInstance()->getStorageEngine()->setConfig('cron', $cron);
	}

	/**
	 * @param string $adminURL
	 * @param string $homePath
	 * @param bool $relaxedFileOwnership
	 * @param bool $output
	 * @return bool
	 */
	public static function requestFilesystemCredentials($adminURL, $homePath = null, $relaxedFileOwnership = true, $output = true) {
		if ($homePath === null) {
			$homePath = get_home_path();
		}

		global $wp_filesystem;

		!$output && ob_start();
		if (false === ($credentials = request_filesystem_credentials($adminURL, '', false, $homePath,
				array('version', 'locale'), $relaxedFileOwnership))
		) {
			!$output && ob_end_clean();
			return false;
		}

		if (!WP_Filesystem($credentials, $homePath, $relaxedFileOwnership)) {
			// Failed to connect, Error and request again
			request_filesystem_credentials($adminURL, '', true, ABSPATH, array('version', 'locale'),
				$relaxedFileOwnership);
			!$output && ob_end_clean();
			return false;
		}

		if ($wp_filesystem->errors->get_error_code()) {
			!$output && ob_end_clean();
			return false;
		}
		!$output && ob_end_clean();
		return true;
	}
}

class wfWAFAutoPrependHelper {

	private $serverConfig;
	/**
	 * @var string
	 */
	private $currentAutoPrependedFile;

	/**
	 * @param string|null $serverConfig
	 * @param string|null $currentAutoPrependedFile
	 */
	public function __construct($serverConfig = null, $currentAutoPrependedFile = null) {
		$this->serverConfig = $serverConfig;
		$this->currentAutoPrependedFile = $currentAutoPrependedFile;
	}

	public function getFilesNeededForBackup() {
		$backups = array();
		$htaccess = $this->getHtaccessPath();
		switch ($this->getServerConfig()) {
			case 'apache-mod_php':
			case 'apache-suphp':
			case 'litespeed':
			case 'cgi':
				if (file_exists($htaccess)) {
					$backups[] = $htaccess;
				}
				break;
		}
		if ($userIni = ini_get('user_ini.filename')) {
			$userIniPath = $this->getUserIniPath();
			switch ($this->getServerConfig()) {
				case 'cgi':
				case 'apache-suphp':
				case 'nginx':
				case 'litespeed':
				case 'iis':
					if (file_exists($userIniPath)) {
						$backups[] = $userIniPath;
					}
					break;
			}
		}
		return $backups;
	}

	public function downloadBackups($index = 0) {
		$backups = $this->getFilesNeededForBackup();
		if ($backups && array_key_exists($index, $backups)) {
			$url = site_url();
			$url = preg_replace('/^https?:\/\//i', '', $url);
			$url = preg_replace('/[^a-zA-Z0-9\.]+/', '_', $url);
			$url = preg_replace('/^_+/', '', $url);
			$url = preg_replace('/_+$/', '', $url);
			header('Content-Type: application/octet-stream');
			$backupFileName = ltrim(basename($backups[$index]), '.');
			header('Content-Disposition: attachment; filename="' . $backupFileName . '_Backup_for_' . $url . '.txt"');
			readfile($backups[$index]);
			die();
		}
	}

	/**
	 * @return mixed
	 */
	public function getServerConfig() {
		return $this->serverConfig;
	}

	/**
	 * @param mixed $serverConfig
	 */
	public function setServerConfig($serverConfig) {
		$this->serverConfig = $serverConfig;
	}

	/**
	 * @param WP_Filesystem_Base $wp_filesystem
	 * @throws wfWAFAutoPrependHelperException
	 */
	public function performInstallation($wp_filesystem) {
		$bootstrapPath = wordfence::getWAFBootstrapPath();
		if (!$wp_filesystem->put_contents($bootstrapPath, wordfence::getWAFBootstrapContent($this->currentAutoPrependedFile))) {
			throw new wfWAFAutoPrependHelperException('We were unable to create the <code>wordfence-waf.php</code> file
in the root of the WordPress installation. It\'s possible WordPress cannot write to the <code>wordfence-waf.php</code>
file because of file permissions. Please verify the permissions are correct and retry the installation.');
		}

		$serverConfig = $this->getServerConfig();

		$htaccessPath = $this->getHtaccessPath();
		$homePath = dirname($htaccessPath);

		$userIniPath = $this->getUserIniPath();
		$userIni = ini_get('user_ini.filename');

		$userIniHtaccessDirectives = '';
		if ($userIni) {
			$userIniHtaccessDirectives = sprintf('<Files "%s">
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order deny,allow
	Deny from all
</IfModule>
</Files>
', addcslashes($userIni, '"'));
		}


		// .htaccess configuration
		switch ($serverConfig) {
			case 'apache-mod_php':
				$autoPrependDirective = sprintf("# Wordfence WAF
<IfModule mod_php%d.c>
	php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessDirectives
# END Wordfence WAF
", PHP_MAJOR_VERSION, addcslashes($bootstrapPath, "'"));
				break;

			case 'litespeed':
				$escapedBootstrapPath = addcslashes($bootstrapPath, "'");
				$autoPrependDirective = sprintf("# Wordfence WAF
<IfModule LiteSpeed>
php_value auto_prepend_file '%s'
</IfModule>
<IfModule lsapi_module>
php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessDirectives
# END Wordfence WAF
", $escapedBootstrapPath, $escapedBootstrapPath);
				break;

			case 'apache-suphp':
				$autoPrependDirective = sprintf("# Wordfence WAF
$userIniHtaccessDirectives
# END Wordfence WAF
", addcslashes($homePath, "'"));
				break;

			case 'cgi':
				if ($userIniHtaccessDirectives) {
					$autoPrependDirective = sprintf("# Wordfence WAF
$userIniHtaccessDirectives
# END Wordfence WAF
", addcslashes($homePath, "'"));
				}
				break;

		}

		if (!empty($autoPrependDirective)) {
			// Modify .htaccess
			$htaccessContent = $wp_filesystem->get_contents($htaccessPath);

			if ($htaccessContent) {
				$regex = '/# Wordfence WAF.*?# END Wordfence WAF/is';
				if (preg_match($regex, $htaccessContent, $matches)) {
					$htaccessContent = preg_replace($regex, $autoPrependDirective, $htaccessContent);
				} else {
					$htaccessContent .= "\n\n" . $autoPrependDirective;
				}
			} else {
				$htaccessContent = $autoPrependDirective;
			}

			if (!$wp_filesystem->put_contents($htaccessPath, $htaccessContent)) {
				throw new wfWAFAutoPrependHelperException('We were unable to make changes to the .htaccess file. It\'s
				possible WordPress cannot write to the .htaccess file because of file permissions, which may have been
				set by another security plugin, or you may have set them manually. Please verify the permissions allow
				the web server to write to the file, and retry the installation.');
			}
			if ($serverConfig == 'litespeed') {
				// sleep(2);
				$wp_filesystem->touch($htaccessPath);
			}

		}
		if ($userIni) {
			// .user.ini configuration
			switch ($serverConfig) {
				case 'cgi':
				case 'nginx':
				case 'apache-suphp':
				case 'litespeed':
				case 'iis':
					$autoPrependIni = sprintf("; Wordfence WAF
auto_prepend_file = '%s'
; END Wordfence WAF
", addcslashes($bootstrapPath, "'"));

					break;
			}

			if (!empty($autoPrependIni)) {

				// Modify .user.ini
				$userIniContent = $wp_filesystem->get_contents($userIniPath);
				if (is_string($userIniContent)) {
					$userIniContent = str_replace('auto_prepend_file', ';auto_prepend_file', $userIniContent);
					$regex = '/; Wordfence WAF.*?; END Wordfence WAF/is';
					if (preg_match($regex, $userIniContent, $matches)) {
						$userIniContent = preg_replace($regex, $autoPrependIni, $userIniContent);
					} else {
						$userIniContent .= "\n\n" . $autoPrependIni;
					}
				} else {
					$userIniContent = $autoPrependIni;
				}

				if (!$wp_filesystem->put_contents($userIniPath, $userIniContent)) {
					throw new wfWAFAutoPrependHelperException(sprintf('We were unable to make changes to the %1$s file.
					It\'s possible WordPress cannot write to the %1$s file because of file permissions.
					Please verify the permissions are correct and retry the installation.', basename($userIniPath)));
				}
			}
		}
	}
	
	/**
	 * @param WP_Filesystem_Base $wp_filesystem
	 * @throws wfWAFAutoPrependHelperException
	 * 
	 * @return bool Whether or not the .user.ini still has a commented-out auto_prepend_file setting
	 */
	public function performIniRemoval($wp_filesystem) {
		$serverConfig = $this->getServerConfig();
		
		$htaccessPath = $this->getHtaccessPath();
		
		$userIniPath = $this->getUserIniPath();
		$userIni = ini_get('user_ini.filename');
		
		// Modify .htaccess
		$htaccessContent = $wp_filesystem->get_contents($htaccessPath);
		
		if (is_string($htaccessContent)) {
			$htaccessContent = preg_replace('/# Wordfence WAF.*?# END Wordfence WAF/is', '', $htaccessContent);
		} else {
			$htaccessContent = '';
		}
		
		if (!$wp_filesystem->put_contents($htaccessPath, $htaccessContent)) {
			throw new wfWAFAutoPrependHelperException('We were unable to make changes to the .htaccess file. It\'s
			possible WordPress cannot write to the .htaccess file because of file permissions, which may have been
			set by another security plugin, or you may have set them manually. Please verify the permissions allow
			the web server to write to the file, and retry the installation.');
		}
		if ($serverConfig == 'litespeed') {
			// sleep(2);
			$wp_filesystem->touch($htaccessPath);
		}
	
		if ($userIni) {
			// Modify .user.ini
			$userIniContent = $wp_filesystem->get_contents($userIniPath);
			if (is_string($userIniContent)) {
				$userIniContent = preg_replace('/; Wordfence WAF.*?; END Wordfence WAF/is', '', $userIniContent);
				$userIniContent = str_replace('auto_prepend_file', ';auto_prepend_file', $userIniContent);
			} else {
				$userIniContent = '';
			}
			
			if (!$wp_filesystem->put_contents($userIniPath, $userIniContent)) {
				throw new wfWAFAutoPrependHelperException(sprintf('We were unable to make changes to the %1$s file.
				It\'s possible WordPress cannot write to the %1$s file because of file permissions.
				Please verify the permissions are correct and retry the installation.', basename($userIniPath)));
			}
			
			return strpos($userIniContent, 'auto_prepend_file') !== false;
		}
		
		return false;
	}
	
	/**
	 * @param WP_Filesystem_Base $wp_filesystem
	 * @throws wfWAFAutoPrependHelperException
	 */
	public function performAutoPrependFileRemoval($wp_filesystem) {
		$bootstrapPath = wordfence::getWAFBootstrapPath();
		if (!$wp_filesystem->delete($bootstrapPath)) {
			throw new wfWAFAutoPrependHelperException('We were unable to remove the <code>wordfence-waf.php</code> file
in the root of the WordPress installation. It\'s possible WordPress cannot remove the <code>wordfence-waf.php</code>
file because of file permissions. Please verify the permissions are correct and retry the removal.');
		}
	}

	public function getHtaccessPath() {
		return get_home_path() . '.htaccess';
	}

	public function getUserIniPath() {
		$userIni = ini_get('user_ini.filename');
		if ($userIni) {
			return get_home_path() . $userIni;
		}
		return false;
	}
	
	public function usesUserIni() {
		$userIni = ini_get('user_ini.filename');
		if (!$userIni) {
			return false;
		}
		switch ($this->getServerConfig()) {
			case 'cgi':
			case 'apache-suphp':
			case 'nginx':
			case 'litespeed':
			case 'iis':
				return true;
		}
		return false;
	}

	public function uninstall() {
		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$htaccessPath = $this->getHtaccessPath();
		$userIniPath = $this->getUserIniPath();

		$adminURL = admin_url('/');
		$allow_relaxed_file_ownership = true;
		$homePath = dirname($htaccessPath);

		ob_start();
		if (false === ($credentials = request_filesystem_credentials($adminURL, '', false, $homePath,
				array('version', 'locale'), $allow_relaxed_file_ownership))
		) {
			ob_end_clean();
			return false;
		}

		if (!WP_Filesystem($credentials, $homePath, $allow_relaxed_file_ownership)) {
			// Failed to connect, Error and request again
			request_filesystem_credentials($adminURL, '', true, ABSPATH, array('version', 'locale'),
				$allow_relaxed_file_ownership);
			ob_end_clean();
			return false;
		}

		if ($wp_filesystem->errors->get_error_code()) {
			ob_end_clean();
			return false;
		}
		ob_end_clean();

		if ($wp_filesystem->is_file($htaccessPath)) {
			$htaccessContent = $wp_filesystem->get_contents($htaccessPath);
			$regex = '/# Wordfence WAF.*?# END Wordfence WAF/is';
			if (preg_match($regex, $htaccessContent, $matches)) {
				$htaccessContent = preg_replace($regex, '', $htaccessContent);
				if (!$wp_filesystem->put_contents($htaccessPath, $htaccessContent)) {
					return false;
				}
			}
		}

		if ($wp_filesystem->is_file($userIniPath)) {
			$userIniContent = $wp_filesystem->get_contents($userIniPath);
			$regex = '/; Wordfence WAF.*?; END Wordfence WAF/is';
			if (preg_match($regex, $userIniContent, $matches)) {
				$userIniContent = preg_replace($regex, '', $userIniContent);
				if (!$wp_filesystem->put_contents($userIniPath, $userIniContent)) {
					return false;
				}
			}
		}

		$bootstrapPath = wordfence::getWAFBootstrapPath();
		if ($wp_filesystem->is_file($bootstrapPath)) {
			$wp_filesystem->delete($bootstrapPath);
		}
		return true;
	}
}

class wfWAFAutoPrependHelperException extends Exception {
}

?>
