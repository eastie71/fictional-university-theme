<?php
$diagnostic = new wfDiagnostic;
$plugins = get_plugins();
$activePlugins = array_flip(get_option('active_plugins'));
$activeNetworkPlugins = is_multisite() ? array_flip(wp_get_active_network_plugins()) : array();
$muPlugins = get_mu_plugins();
$themes = wp_get_themes();
$currentTheme = wp_get_theme();
$cols = 3;

$w = new wfConfig();
if (!isset($sendingDiagnosticEmail)) { $sendingDiagnosticEmail = false; }
?>

<div>
	<?php if (!$sendingDiagnosticEmail): ?>
	<div id="sendByEmailThanks" class="hidden">
		<h3>Thanks for sending your diagnostic page over email</h3>
	</div>
	<div id="sendByEmailDiv" class="wf-add-bottom">
		<div id="sendByEmailForm" class="hidden">
			<table class="wfConfigForm">
				<tr>
					<th>Email address:</th>
					<td><input type="email" id="_email" value="wftest@wordfence.com"/></td>
				</tr>
				<tr>
					<th>Ticket Number/Forum Username:</th>
					<td><input type="text" id="_ticketnumber" required/></td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right;"><input class="wf-btn wf-btn-default" type="button" id="doSendEmail" value="Send"/></td>
				</tr>
			</table>
		</div>
		<input class="wf-btn wf-btn-default" type="submit" id="sendByEmail" value="Send Report by Email"/>
	</div>
	<?php endif; ?>
	
	<form id="wfConfigForm" style="overflow-x: auto;">
		<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
			<?php foreach ($diagnostic->getResults() as $title => $tests): ?>
				<tbody class="thead">
				<tr>
					<th colspan="<?php echo $cols ?>"><?php echo esc_html($title) ?></th>
				</tr>
				</tbody>
				<tbody>
				<?php foreach ($tests as $result): ?>
					<tr>
						<td style="width: 75%;"
						    colspan="<?php echo $cols - 1 ?>"><?php echo wp_kses($result['label'], array(
								'code'   => array(),
								'strong' => array(),
								'em'     => array(),
								'a'      => array('href' => true),
							)) ?></td>
						<?php if ($result['test']): ?>
							<td class="success"><?php echo esc_html($result['message']) ?></td>
						<?php else: ?>
							<td class="error"><?php echo esc_html($result['message']) ?></td>
						<?php endif ?>
					</tr>
				<?php endforeach ?>
				</tbody>
				<tbody class="empty-row">
				<tr>
					<td colspan="<?php echo $cols ?>"></td>
				</tr>
				</tbody>
			<?php endforeach ?>

			<tbody class="thead">
			<tr>
				<th>IPs</th>
				<th>Value</th>
				<th>Used</th>
			</tr>
			</tbody>
			<tbody>
			<?php
			$howGet = wfConfig::get('howGetIPs', false);
			list($currentIP, $currentServerVarForIP) = wfUtils::getIPAndServerVariable();
			foreach (array(
				         'REMOTE_ADDR'           => 'REMOTE_ADDR',
				         'HTTP_CF_CONNECTING_IP' => 'CF-Connecting-IP',
				         'HTTP_X_REAL_IP'        => 'X-Real-IP',
				         'HTTP_X_FORWARDED_FOR'  => 'X-Forwarded-For',
			         ) as $variable => $label): ?>
				<tr>
					<td><?php echo $label ?></td>
					<td><?php 
						if (!array_key_exists($variable, $_SERVER)) {
							echo '(not set)';
						}
						else {
							if (strpos($_SERVER[$variable], ',') !== false) {
								$trustedProxies = explode("\n", wfConfig::get('howGetIPs_trusted_proxies', ''));
								$items = preg_replace('/[\s,]/', '', explode(',', $_SERVER[$variable]));
								$items = array_reverse($items);
								$output = '';
								$markedSelectedAddress = false;
								foreach ($items as $index => $i) {
									foreach ($trustedProxies as $proxy) {
										if (!empty($proxy)) {
											if (wfUtils::subnetContainsIP($proxy, $i) && $index < count($items) - 1) {
												$output = esc_html($i) . ', ' . $output;
												continue 2;
											}
										}
									}
									
									if (!$markedSelectedAddress) {
										$output = '<strong>' . esc_html($i) . '</strong>, ' . $output;
										$markedSelectedAddress = true;
									}
									else {
										$output = esc_html($i) . ', ' . $output;
									}
								}
								
								echo substr($output, 0, -2);
							}
							else {
								echo esc_html($_SERVER[$variable]);
							}
						}
						?></td>
					<?php if ($currentServerVarForIP && $currentServerVarForIP === $variable): ?>
						<td class="success">In use</td>
					<?php elseif ($howGet === $variable): ?>
						<td class="error">Configured, but not valid</td>
					<?php else: ?>
						<td></td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>
			<tbody class="empty-row">
			<tr>
				<td colspan="<?php echo $cols ?>"></td>
			</tr>
			</tbody>

			<tbody class="thead">
			<tr>
				<th colspan="<?php echo $cols ?>">WordPress</th>
			</tr>
			</tbody>
			<tbody>
			<?php
			require(ABSPATH . 'wp-includes/version.php');
			$postRevisions = (defined('WP_POST_REVISIONS') ? WP_POST_REVISIONS : true);
			$wordPressValues = array(
				'WordPress Version' => array('description' => '', 'value' => $wp_version),
				'WP_DEBUG' => array('description' => 'WordPress debug mode', 'value' => (defined('WP_DEBUG') && WP_DEBUG ? 'On' : 'Off')),
				'WP_DEBUG_LOG' => array('description' => 'WordPress error logging override', 'value' => defined('WP_DEBUG_LOG') ? (WP_DEBUG_LOG ? 'Enabled' : 'Disabled') : '(not set)'),
				'WP_DEBUG_DISPLAY' => array('description' => 'WordPress error display override', 'value' => defined('WP_DEBUG_DISPLAY') ? (WP_DEBUG_LOG ? 'Enabled' : 'Disabled') : '(not set)'),
				'SCRIPT_DEBUG' => array('description' => 'WordPress script debug mode', 'value' => (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'On' : 'Off')),
				'SAVEQUERIES' => array('description' => 'WordPress query debug mode', 'value' => (defined('SAVEQUERIES') && SAVEQUERIES ? 'On' : 'Off')),
				'DB_CHARSET' => 'Database character set',
				'DB_COLLATE' => 'Database collation',
				'WP_SITEURL' => 'Explicitly set site URL',
				'WP_HOME' => 'Explicitly set blog URL',
				'WP_CONTENT_DIR' => array('description' => '"wp-content" folder is in default location', 'value' => (realpath(WP_CONTENT_DIR) === realpath(ABSPATH . 'wp-content') ? 'Yes' : 'No')),
				'WP_CONTENT_URL' => 'URL to the "wp-content" folder',
				'WP_PLUGIN_DIR' => array('description' => '"plugins" folder is in default location', 'value' => (realpath(WP_PLUGIN_DIR) === realpath(ABSPATH . 'wp-content/plugins') ? 'Yes' : 'No')),
				'WP_LANG_DIR' => array('description' => '"languages" folder is in default location', 'value' => (realpath(WP_LANG_DIR) === realpath(ABSPATH . 'wp-content/languages') ? 'Yes' : 'No')),
				'WPLANG' => 'Language choice',
				'UPLOADS' => 'Custom upload folder location',
				'TEMPLATEPATH' => array('description' => 'Theme template folder override', 'value' => (defined('TEMPLATEPATH') && realpath(get_template_directory()) !== realpath(TEMPLATEPATH) ? 'Overridden' : '(not set)')),
				'STYLESHEETPATH' => array('description' => 'Theme stylesheet folder override', 'value' => (defined('STYLESHEETPATH') && realpath(get_stylesheet_directory()) !== realpath(STYLESHEETPATH) ? 'Overridden' : '(not set)')),
				'AUTOSAVE_INTERVAL' => 'Post editing automatic saving interval',
				'WP_POST_REVISIONS' => array('description' => 'Post revisions saved by WordPress', 'value' => is_numeric($postRevisions) ? $postRevisions : ($postRevisions ? 'Unlimited' : 'None')),
				'COOKIE_DOMAIN' => 'WordPress cookie domain',
				'COOKIEPATH' => 'WordPress cookie path',
				'SITECOOKIEPATH' => 'WordPress site cookie path',
				'ADMIN_COOKIE_PATH' => 'WordPress admin cookie path',
				'PLUGINS_COOKIE_PATH' => 'WordPress plugins cookie path',
				'WP_ALLOW_MULTISITE' => array('description' => 'Multisite/network ability enabled', 'value' => (defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE ? 'Yes' : 'No')),
				'NOBLOGREDIRECT' => 'URL redirected to if the visitor tries to access a nonexistent blog',
				'CONCATENATE_SCRIPTS' => array('description' => 'Concatenate JavaScript files', 'value' => (defined('CONCATENATE_SCRIPTS') && CONCATENATE_SCRIPTS ? 'Yes' : 'No')),
				'WP_MEMORY_LIMIT' => 'WordPress memory limit',
				'WP_MAX_MEMORY_LIMIT' => 'Administrative memory limit',
				'WP_CACHE' => array('description' => 'Built-in caching', 'value' => (defined('WP_CACHE') && WP_CACHE ? 'Enabled' : 'Disabled')),
				'CUSTOM_USER_TABLE' => array('description' => 'Custom "users" table', 'value' => (defined('CUSTOM_USER_TABLE') ? 'Set' : '(not set)')),
				'CUSTOM_USER_META_TABLE' => array('description' => 'Custom "usermeta" table', 'value' => (defined('CUSTOM_USER_META_TABLE') ? 'Set' : '(not set)')),
				'FS_CHMOD_DIR' => array('description' => 'Overridden permissions for a new folder', 'value' => defined('FS_CHMOD_DIR') ? decoct(FS_CHMOD_DIR) : '(not set)'),
				'FS_CHMOD_FILE' => array('description' => 'Overridden permissions for a new file', 'value' => defined('FS_CHMOD_FILE') ? decoct(FS_CHMOD_FILE) : '(not set)'),
				'ALTERNATE_WP_CRON' => array('description' => 'Alternate WP cron', 'value' => (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON ? 'Enabled' : 'Disabled')),
				'DISABLE_WP_CRON' => array('description' => 'WP cron status', 'value' => (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'Disabled' : 'Enabled')),
				'WP_CRON_LOCK_TIMEOUT' => 'Cron running frequency lock',
				'EMPTY_TRASH_DAYS' => array('description' => 'Interval the trash is automatically emptied at in days', 'value' => (EMPTY_TRASH_DAYS > 0 ? EMPTY_TRASH_DAYS : 'Never')),
				'WP_ALLOW_REPAIR' => array('description' => 'Automatic database repair', 'value' => (defined('WP_ALLOW_REPAIR') && WP_ALLOW_REPAIR ? 'Enabled' : 'Disabled')),
				'DO_NOT_UPGRADE_GLOBAL_TABLES' => array('description' => 'Do not upgrade global tables', 'value' => (defined('DO_NOT_UPGRADE_GLOBAL_TABLES') && DO_NOT_UPGRADE_GLOBAL_TABLES ? 'Yes' : 'No')),
				'DISALLOW_FILE_EDIT' => array('description' => 'Disallow plugin/theme editing', 'value' => (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'Yes' : 'No')),
				'DISALLOW_FILE_MODS' => array('description' => 'Disallow plugin/theme update and installation', 'value' => (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS ? 'Yes' : 'No')),
				'IMAGE_EDIT_OVERWRITE' => array('description' => 'Overwrite image edits when restoring the original', 'value' => (defined('IMAGE_EDIT_OVERWRITE') && IMAGE_EDIT_OVERWRITE ? 'Yes' : 'No')),
				'FORCE_SSL_ADMIN' => array('description' => 'Force SSL for administrative logins', 'value' => (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ? 'Yes' : 'No')),
				'WP_HTTP_BLOCK_EXTERNAL' => array('description' => 'Block external URL requests', 'value' => (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL ? 'Yes' : 'No')),
				'WP_ACCESSIBLE_HOSTS' => 'Whitelisted hosts',
				'WP_AUTO_UPDATE_CORE' => array('description' => 'Automatic WP Core updates', 'value' => defined('WP_AUTO_UPDATE_CORE') ? (is_bool(WP_AUTO_UPDATE_CORE) ? (WP_AUTO_UPDATE_CORE ? 'Everything' : 'None') : WP_AUTO_UPDATE_CORE) : 'Default'),
				'WP_PROXY_HOST' => array('description' => 'Hostname for a proxy server', 'value' => defined('WP_PROXY_HOST') ? WP_PROXY_HOST : '(not set)'),
				'WP_PROXY_PORT' => array('description' => 'Port for a proxy server', 'value' => defined('WP_PROXY_PORT') ? WP_PROXY_PORT : '(not set)'),
			);

			foreach ($wordPressValues as $settingName => $settingData):
				$escapedName = esc_html($settingName);
				$escapedDescription = '';
				$escapedValue = '(not set)';
				if (is_array($settingData)) {
					$escapedDescription = esc_html($settingData['description']);
					if (isset($settingData['value'])) {
						$escapedValue = esc_html($settingData['value']);
					}
				}
				else {
					$escapedDescription = esc_html($settingData);
					if (defined($settingName)) {
						$escapedValue = esc_html(constant($settingName));
					}
				}
			?>
				<tr>
					<td><strong><?php echo $escapedName ?></strong></td>
					<td><?php echo $escapedDescription ?></td>
					<td><?php echo $escapedValue ?></td>
				</tr>
			<?php endforeach ?>
			</tbody>
			<tbody class="empty-row">
			<tr>
				<td colspan="<?php echo $cols ?>"></td>
			</tr>
			</tbody>

			<tbody class="thead">
			<tr>
				<th colspan="<?php echo $cols ?>">WordPress Plugins</th>
			</tr>
			</tbody>
			<tbody>
			<?php foreach ($plugins as $plugin => $pluginData): ?>
				<tr>
					<td colspan="<?php echo $cols - 1 ?>"><strong><?php echo esc_html($pluginData['Name']) ?></strong>
						<?php if (!empty($pluginData['Version'])): ?>
							- Version <?php echo esc_html($pluginData['Version']) ?>
						<?php endif ?>
					</td>
					<?php if (array_key_exists(trailingslashit(WP_PLUGIN_DIR) . $plugin, $activeNetworkPlugins)): ?>
						<td class="success">Network Activated</td>
					<?php elseif (array_key_exists($plugin, $activePlugins)): ?>
						<td class="success">Active</td>
					<?php else: ?>
						<td class="inactive">Inactive</td>
					<?php endif ?>
				</tr>
			<?php endforeach ?>
			</tbody>

			<tbody class="empty-row">
			<tr>
				<td colspan="<?php echo $cols ?>"></td>
			</tr>
			</tbody>
			<tbody class="thead">
			<tr>
				<th colspan="<?php echo $cols ?>">Must-Use WordPress Plugins</th>
			</tr>
			</tbody>
			<?php if (!empty($muPlugins)): ?>
				<tbody>
				<?php foreach ($muPlugins as $plugin => $pluginData): ?>
					<tr>
						<td colspan="<?php echo $cols - 1 ?>">
							<strong><?php echo esc_html($pluginData['Name']) ?></strong>
							<?php if (!empty($pluginData['Version'])): ?>
								- Version <?php echo esc_html($pluginData['Version']) ?>
							<?php endif ?>
						</td>
						<td class="success">Active</td>
					</tr>
				<?php endforeach ?>
				</tbody>
			<?php else: ?>
				<tbody>
				<tr>
					<td colspan="<?php echo $cols ?>">No MU-Plugins</td>
				</tr>
				</tbody>

			<?php endif ?>

			<tbody class="empty-row">
			<tr>
				<td colspan="<?php echo $cols ?>"></td>
			</tr>
			</tbody>
			<tbody class="thead">
			<tr>
				<th colspan="<?php echo $cols ?>">Themes</th>
			</tr>
			</tbody>
			<?php if (!empty($themes)): ?>
				<tbody>
				<?php foreach ($themes as $theme => $themeData): ?>
					<tr>
						<td colspan="<?php echo $cols - 1 ?>">
							<strong><?php echo esc_html($themeData['Name']) ?></strong>
							Version <?php echo esc_html($themeData['Version']) ?></td>
						<?php if ($currentTheme instanceof WP_Theme && $theme === $currentTheme->get_stylesheet()): ?>
							<td class="success">Active</td>
						<?php else: ?>
							<td class="inactive">Inactive</td>
						<?php endif ?>
					</tr>
				<?php endforeach ?>
				</tbody>
			<?php else: ?>
				<tbody>
				<tr>
					<td colspan="<?php echo $cols ?>">No MU-Plugins</td>
				</tr>
				</tbody>

			<?php endif ?>

			<tbody class="empty-row">
			<tr>
				<td colspan="<?php echo $cols ?>"></td>
			</tr>
			</tbody>
			<tbody class="thead">
			<tr>
				<th colspan="<?php echo $cols ?>">Cron Jobs</th>
			</tr>
			</tbody>
			<tbody>
			<?php
			$cron = _get_cron_array();

			foreach ($cron as $timestamp => $values) {
				if (is_array($values)) {
					foreach ($values as $cron_job => $v) {
						if (is_numeric($timestamp)) {
							?>
							<tr>
								<td colspan="<?php echo $cols - 1 ?>"><?php echo esc_html(date('r', $timestamp)) ?></td>
								<td><?php echo esc_html($cron_job) ?></td>
							</tr>
							<?php
						}
					}
				}
			}
			?>
			</tbody>
		</table>
		<?php
		global $wpdb;
		$wfdb = new wfDB();
		//This must be done this way because MySQL with InnoDB tables does a full regeneration of all metadata if we don't. That takes a long time with a large table count.
		$tables = $wfdb->querySelect('SELECT SQL_CALC_FOUND_ROWS TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME ASC LIMIT 250');
		$total = $wfdb->querySingle('SELECT FOUND_ROWS()');
		foreach ($tables as &$t) {
			$t = "'" . esc_sql($t['TABLE_NAME']) . "'";
		}
		unset($t);
		$q = $wfdb->querySelect("SHOW TABLE STATUS WHERE Name IN (" . implode(',', $tables) . ')');
		if ($q):
			$databaseCols = count($q[0]);
			?>
			<div style="max-width: 100%; overflow: auto; padding: 1px;">
				<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
					<tbody class="empty-row">
					<tr>
						<td colspan="<?php echo $databaseCols ?>"></td>
					</tr>
					</tbody>
					<tbody class="thead">
					<tr>
						<th colspan="<?php echo $databaseCols ?>">Database Tables</th>
					</tr>
					</tbody>
					<tbody class="thead thead-subhead" style="font-size: 85%">
					<?php
					$val = wfUtils::array_first($q);
					?>
					<tr>
						<?php foreach ($val as $tkey => $tval): ?>
							<th><?php echo esc_html($tkey) ?></th>
						<?php endforeach; ?>
					</tr>
					</tbody>
					<tbody style="font-size: 85%">
					<?php
					$count = 0;
					foreach ($q as $val) {
					?>
						<tr>
							<?php foreach ($val as $tkey => $tval): ?>
								<td><?php echo esc_html($tval) ?></td>
							<?php endforeach; ?>
						</tr>
					<?php
						$count++;
						if ($count >= 250) {
							?>
						<tr>
							<td colspan="<?php echo $databaseCols; ?>">and <?php echo $total - $count; ?> more</td>
						</tr>
							<?php
							break;
						}
					}
					?>
					</tbody>

				</table>
			</div>
		<?php endif ?>
		<div style="max-width: 100%; overflow: auto; padding: 1px;">
			<table class="wf-striped-table"<?php echo !empty($inEmail) ? ' border=1' : '' ?>>
				<tbody class="empty-row">
				<tr>
					<td colspan="2"></td>
				</tr>
				</tbody>
				<tbody class="thead">
				<tr>
					<th colspan="2">Log Files (Error messages from WordPress core, plugins, and themes)</th>
				</tr>
				</tbody>
				<tbody class="thead thead-subhead" style="font-size: 85%">
				<tr>
					<th>File</th>
					<th>Download</th>
				</tr>
				</tbody>
				<tbody style="font-size: 85%">
				<?php
				$errorLogs = wfErrorLogHandler::getErrorLogs();
				if (count($errorLogs) < 1): ?>
					<tr>
						<td colspan="2"><em>No log files found.</em></td>
					</tr>
				<?php else:
					foreach ($errorLogs as $log => $readable): ?>
						<tr>
							<td style="width: 100%"><?php echo esc_html($log) . ' (' . wfUtils::formatBytes(filesize($log)) . ')'; ?></td>
							<td style="white-space: nowrap; text-align: right;"><?php echo ($readable ? '<a href="#" data-logfile="' . esc_html($log) . '" class="downloadLogFile" target="_blank" rel="noopener noreferrer">Download</a>' : '<em>Requires downloading from the server directly</em>'); ?></td> 
						</tr>
					<?php endforeach;
				endif; ?>
				</tbody>
			
			</table>
		</div>
	</form>

<?php if (!empty($inEmail)): ?>
	<?php phpinfo(); ?>
<?php endif ?>

<?php if (!empty($emailForm)): ?>
	<h3>Other Tests</h3>

	<ul>
		<li>
			<a href="<?php echo wfUtils::siteURLRelative(); ?>?_wfsf=sysinfo&nonce=<?php echo wp_create_nonce('wp-ajax'); ?>"
			   target="_blank" rel="noopener noreferrer">Click to view your system's configuration in a new window</a>
			<a href="https://docs.wordfence.com/en/Wordfence_diagnostics#Click_to_view_your_system.27s_configuration_in_a_new_window"
			   target="_blank" rel="noopener noreferrer" class="wfhelp"></a></li>
		<li>
			<a href="<?php echo wfUtils::siteURLRelative(); ?>?_wfsf=testmem&nonce=<?php echo wp_create_nonce('wp-ajax'); ?>"
			   target="_blank" rel="noopener noreferrer">Test your WordPress host's available memory</a>
			<a href="https://docs.wordfence.com/en/Wordfence_diagnostics#Test_your_WordPress_host.27s_available_memory"
			   target="_blank" rel="noopener noreferrer" class="wfhelp"></a>
		</li>
		<li>
			Send a test email from this WordPress server to an email address:<a
				href="https://docs.wordfence.com/en/Wordfence_diagnostics#Send_a_test_email_from_this_WordPress_server_to_an_email_address"
				target="_blank" rel="noopener noreferrer" class="wfhelp"></a>
			<input type="text" id="testEmailDest" value="" size="20" maxlength="255" class="wfConfigElem"/>
			<input class="wf-btn wf-btn-default" type="button" value="Send Test Email"
			       onclick="WFAD.sendTestEmail(jQuery('#testEmailDest').val());"/>
		</li>
	</ul>

	<?php if (!WFWAF_SUBDIRECTORY_INSTALL): ?>
	<div id="updateWAFRules">
		<h3>Firewall Rules <a href="https://docs.wordfence.com/en/Wordfence_diagnostics#Firewall_Rules" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>

		<p>
			<button type="button" onclick="WFAD.wafUpdateRules()" class="wf-btn wf-btn-primary">
				Manually refresh firewall rules
			</button>
<!--			<em id="waf-rules-last-updated"></em>-->
		</p>
		<p><em id="waf-rules-next-update"></em></p>
		<?php
		try {
			$lastUpdated = wfWAF::getInstance()->getStorageEngine()->getConfig('rulesLastUpdated');

			$nextUpdate = PHP_INT_MAX;
			$cron = wfWAF::getInstance()->getStorageEngine()->getConfig('cron');
			if (is_array($cron)) {
				/** @var wfWAFCronEvent $event */
				foreach ($cron as $index => $event) {
					$event->setWaf(wfWAF::getInstance());
					if (!$event->isInPast()) {
						$nextUpdate = min($nextUpdate, $event->getFireTime());
					}
				}
			}
		} catch (wfWAFStorageFileException $e) {
			error_log($e->getMessage());
		}
		if (!empty($lastUpdated)): ?>
			<script>
				var lastUpdated = <?php echo (int) $lastUpdated ?>;
				WFAD.renderWAFRulesLastUpdated(new Date(lastUpdated * 1000));
			</script>
		<?php endif ?>

		<?php if ($nextUpdate < PHP_INT_MAX): ?>
		<script>
			var nextUpdate = <?php echo (int) $nextUpdate ?>;
			WFAD.renderWAFRulesNextUpdate(new Date(nextUpdate * 1000));
		</script>
		<?php endif ?>

	</div>
	<?php endif ?>

	<h3>Debugging Options</h3>
	<form action="#" id="wfDebuggingConfigForm">
		<table class="wfConfigForm">
			<tr>
				<th>Enable debugging mode (increases database load)<a
						href="https://docs.wordfence.com/en/Wordfence_diagnostics#Enable_debugging_mode_.28increases_database_load.29"
						target="_blank" rel="noopener noreferrer" class="wfhelp"></a></th>
				<td><input type="checkbox" id="debugOn" class="wfConfigElem" name="debugOn"
				           value="1" <?php $w->cb('debugOn'); ?> /></td>
			</tr>

			<tr>
				<th>Start all scans remotely<a
						href="https://docs.wordfence.com/en/Wordfence_diagnostics#Start_all_scans_remotely"
						target="_blank" rel="noopener noreferrer" class="wfhelp"></a></th>
				<td><input type="checkbox" id="startScansRemotely" class="wfConfigElem" name="startScansRemotely"
				           value="1" <?php $w->cb('startScansRemotely'); ?> />
					(Try this if your scans aren't starting and your site is publicly accessible)
				</td>
			</tr>

			<tr>
				<th><label class="wf-plain" for="ssl_verify">Enable SSL Verification</label><a
						href="https://docs.wordfence.com/en/Wordfence_diagnostics#Enable_SSL_Verification"
						target="_blank" rel="noopener noreferrer" class="wfhelp"></a>
				</th>
				<td style="vertical-align: top;"><input type="checkbox" id="ssl_verify" class="wfConfigElem"
				                                        name="ssl_verify"
				                                        value="1" <?php $w->cb('ssl_verify'); ?> />
					(Disable this if you are <strong><em>consistently</em></strong> unable to connect to the Wordfence
					servers.)
				</td>
			</tr>

			<tr>
				<th><label class="wf-plain" for="betaThreatDefenseFeed">Enable beta threat defense feed</label><a
							href="https://docs.wordfence.com/en/Wordfence_diagnostics#Enable_beta_threat_defense_feed"
							target="_blank" rel="noopener noreferrer" class="wfhelp"></a></th>
				<td style="vertical-align: top;"><input type="checkbox" id="betaThreatDefenseFeed"
				                                        class="wfConfigElem"
				                                        name="betaThreatDefenseFeed"
				                                        value="1" <?php $w->cb('betaThreatDefenseFeed'); ?> />
				</td>
			</tr>

		</table>
		<br>
		<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td><input type="button" id="button1" name="button1" class="wf-btn wf-btn-primary" value="Save Changes"
				           onclick="WFAD.saveDebuggingConfig();"/></td>
				<td style="height: 24px;">
					<div class="wfAjax24"></div>
					<span class="wfSavedMsg">&nbsp;Your changes have been saved!</span></td>
			</tr>
		</table>
	</form>

<?php endif ?>
</div>
<div class="wf-scrollTop">
	<a href="javascript:void(0);"><i class="wf-ionicons wf-ion-chevron-up"></i></a>
</div>