<?php
$w = new wfConfig();
?>
<div class="wordfenceModeElem" id="wordfenceMode_scanOptions"></div>
<form id="wfConfigForm-scanOptions" class="wf-form-horizontal">
	<h3>Scans to include <a href="http://docs.wordfence.com/en/Wordfence_options#Scans_to_Include" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
	<?php
	$options = array( //Contents should already be HTML-escaped as needed
		array(
			'id' 		=> 'scansEnabled_checkHowGetIPs',
			'label'		=> 'Scan for misconfigured How does Wordfence get IPs <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_for_misconfigured_How_does_Wordfence_get_IPs" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_checkReadableConfig',
			'label'		=> 'Scan for publicly accessible configuration, backup, or log files <a href="http://docs.wordfence.com/en/Wordfence_options#Configuration_Readable" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_suspectedFiles',
			'label'		=> 'Scan for publicly accessible quarantined files <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_for_publicly_accessible_quarantined_files" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_core',
			'label'		=> 'Scan core files against repository versions for changes <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_core_files_against_repository_version_for_changes" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_themes',
			'label'		=> 'Scan theme files against repository versions for changes <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_theme_files_against_repository_versions_for_changes" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_plugins',
			'label'		=> 'Scan plugin files against repository versions for changes <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_plugin_files_against_repository_versions_for_changes" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_coreUnknown',
			'label'		=> 'Scan wp-admin and wp-includes for files not bundled with WordPress <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_wordpress_core_for_unknown_files" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_malware',
			'label'		=> 'Scan for signatures of known malicious files <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_for_signatures_of_known_malicious_files" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_fileContents',
			'label'		=> 'Scan file contents for backdoors, trojans and suspicious code <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_file_contents_for_backdoors.2C_trojans_and_suspicious_code" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
			'help'		=> '<a href="#add-more-rules" class="do-show" data-selector="#scan_include_extra">+ Add additional signatures</a>',
			'subs'		=> array(
				array(
					'html'		=> '
								<div class="wf-form-group wf-sub-group hidden" id="scan_include_extra">
									<label for="scan_include_extra_field" class="wf-col-sm-4 wf-col-sm-offset-1 wf-control-label">Additional scan signatures</label>
									<div class="wf-col-sm-7">
										<textarea class="wf-form-control" rows="4" name="scan_include_extra" id="scan_include_extra_field">' . $w->getHTML('scan_include_extra') . '</textarea>
									</div>
								</div>
'
				),
			),
		),
		array(
			'id' 		=> 'scansEnabled_fileContentsGSB',
			'label'		=> 'Scan file contents for malicious URLs <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_file_contents_for_malicious_URLs" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_posts',
			'label'		=> 'Scan posts for known dangerous URLs and suspicious content <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_posts_for_known_dangerous_URLs_and_suspicious_content" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_comments',
			'label'		=> 'Scan comments for known dangerous URLs and suspicious content <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_comments_for_known_dangerous_URLs_and_suspicious_content" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_suspiciousOptions',
			'label'		=> 'Scan WordPress core, plugin, and theme options for known dangerous URLs and suspicious content <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_WordPress_core.2C_plugin.2C_and_theme_options_for_known_dangerous_URLs_and_suspicious_content" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_oldVersions',
			'label'		=> 'Scan for out of date, abandoned, and vulnerable plugins, themes, and WordPress versions <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_for_out_of_date_plugins.2C_themes_and_WordPress_versions" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_suspiciousAdminUsers',
			'label'		=> 'Scan for admin users created outside of WordPress <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_for_admin_users_created_outside_of_WordPress" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_passwds',
			'label'		=> 'Check the strength of passwords <a href="http://docs.wordfence.com/en/Wordfence_options#Check_the_strength_of_passwords" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_diskSpace',
			'label'		=> 'Monitor disk space<a href="http://docs.wordfence.com/en/Wordfence_options#Monitor_disk_space" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_dns',
			'label'		=> 'Scan for unauthorized DNS changes <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_for_unauthorized_DNS_changes" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'other_scanOutside',
			'label'		=> 'Scan files outside your WordPress installation <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_files_outside_your_WordPress_installation" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_scanImages',
			'label'		=> 'Scan images, binary, and other files as if they were executable <a href="http://docs.wordfence.com/en/Wordfence_options#Scan_image_files_as_if_they_were_executable" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'scansEnabled_highSense',
			'label'		=> 'Enable HIGH SENSITIVITY scanning (may give false positives) <a href="http://docs.wordfence.com/en/Wordfence_options#Enable_HIGH_SENSITIVITY_scanning" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
		array(
			'id' 		=> 'lowResourceScansEnabled',
			'label'		=> 'Use low resource scanning (reduces server load by lengthening the scan duration) <a href="http://docs.wordfence.com/en/Wordfence_options#Use_low_resource_scanning" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
		),
	);
	foreach ($options as $o):
		?>
		<div class="wf-form-group<?php if (isset($o['hidden']) && $o['hidden']) { echo ' hidden'; } ?>">
			<label for="<?php echo $o['id']; ?>" class="wf-col-sm-5 wf-control-label"><?php echo $o['label']; ?></label>
			<div class="wf-col-sm-7">
				<div class="wf-checkbox"><input type="checkbox" id="<?php echo $o['id']; ?>" class="wfConfigElem" name="<?php echo $o['id']; ?>" value="1" <?php $w->cb($o['id']); ?> <?php if (!wfConfig::get('isPaid') && isset($o['premium']) && $o['premium']) { echo 'disabled'; } ?>></div>
				<?php if (isset($o['help']) || (!wfConfig::get('isPaid') && isset($o['premium']) && $o['premium'])): ?>
					<span class="wf-help-block"><?php if (!wfConfig::get('isPaid') && isset($o['premium']) && $o['premium']) { echo '<span style="color: #F00;">Premium Feature</span> This feature requires a <a href="https://www.wordfence.com/gnl1optPdOnly1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Wordfence Premium Key</a>. '; } ?><?php if (isset($o['help'])) { echo $o['help']; } ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php if (isset($o['subs'])): ?>
		<?php foreach ($o['subs'] as $s): ?>
			<?php if (isset($s['html'])): ?>
				<?php echo $s['html']; ?>
			<?php else: ?>
				<div class="wf-form-group wf-sub-group">
					<label for="<?php echo $s['id']; ?>" class="wf-col-sm-4 wf-col-sm-offset-1 wf-control-label"><?php echo $s['label']; ?></label>
					<div class="wf-col-sm-7">
						<div class="wf-checkbox"><input type="checkbox" id="<?php echo $s['id']; ?>" class="wfConfigElem" name="<?php echo $s['id']; ?>" value="1" <?php $w->cb($s['id']); ?>></div>
						<?php if (isset($s['help'])): ?>
							<span class="wf-help-block"><?php echo $s['help']; ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php endif; ?>
		<?php
	endforeach;
	?>
	<div class="wf-form-group">
		<label for="scan_exclude" class="wf-col-sm-5 wf-control-label">Exclude files from scan that match these wildcard patterns (one per line) <a href="http://docs.wordfence.com/en/Wordfence_options#Exclude_files_from_scan_that_match_these_wildcard_patterns." target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
		<div class="wf-col-sm-7">
			<textarea id="scan_exclude" class="wf-form-control" rows="4" name="scan_exclude"><?php echo wfUtils::cleanupOneEntryPerLine($w->getHTML( 'scan_exclude' )); ?></textarea>
		</div>
	</div>
	<div class="wf-form-group">
		<label for="scan_maxIssues" class="wf-col-sm-5 wf-control-label">Limit the number of issues sent in the scan results email <a href="https://docs.wordfence.com/en/Wordfence_options#Limit_the_number_of_issues_sent_in_the_scan_results_email" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
		<div class="wf-col-sm-7">
			<input type="text" class="wf-form-control" name="scan_maxIssues" id="scan_maxIssues" value="<?php $w->f( 'scan_maxIssues' ); ?>">
			<span class="wf-help-block">0 or empty means unlimited issues will be sent.</span>
		</div>
	</div>
	<div class="wf-form-group">
		<label for="scan_maxDuration" class="wf-col-sm-5 wf-control-label">Time limit that a scan can run in seconds <a href="http://docs.wordfence.com/en/Wordfence_options#Time_limit_that_a_scan_can_run_in_seconds" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
		<div class="wf-col-sm-7">
			<input type="text" class="wf-form-control" name="scan_maxDuration" id="scan_maxDuration" value="<?php $w->f( 'scan_maxDuration' ); ?>">
			<span class="wf-help-block">0 or empty means the default of <?php echo wfUtils::makeDuration(WORDFENCE_DEFAULT_MAX_SCAN_TIME); ?> will be used.</span>
		</div>
	</div>

	<div class="wf-form-group">
		<div class="wf-col-sm-7 wf-col-sm-offset-5">
			<a class="wf-btn wf-btn-primary wf-btn-callout" href="#" onclick="WFAD.savePartialConfig('#wfConfigForm-scanOptions'); return false;">Save Options</a> <div class="wfAjax24"></div><span class="wfSavedMsg">&nbsp;Your changes have been saved!</span>
		</div>
	</div>
</form>
<script type="text/x-jquery-template" id="wfContentScansToInclude">
	<div>
		<h3>Scans to Include</h3>
		
		<p>
			This section gives you the ability to fine-tune what we scan.
			If you use many themes or plugins from the public WordPress directory we recommend you
			enable theme and plugin scanning. This will verify the integrity of all these themes and plugins and alert
			you of any changes.
		
		<p>
		
		<p>
			The option to "scan files outside your WordPress installation" will cause Wordfence to do a much wider
			security scan
			that is not limited to your base WordPress directory and known WordPress subdirectories. This scan may take
			longer
			but can be very useful if you have other infected files outside this WordPress installation that you would
			like us to look for.
		</p>
	</div>
</script>
