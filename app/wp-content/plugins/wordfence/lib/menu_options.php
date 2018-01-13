<?php
$w = new wfConfig();
?>
<div class="wordfenceModeElem" id="wordfenceMode_options"></div>
<div class="wrap wordfence">
	<?php $helpLink = "http://docs.wordfence.com/en/Wordfence_options";
	$helpLabel      = "Learn more about Wordfence Options";
	$pageTitle      = "Wordfence Options";
	$wantsLiveActivity = true;
	include( 'pageTitle.php' ); ?>
	
	<div class="wf-container-fluid">
		<div class="wf-row">
			<?php
			$rightRail = new wfView('marketing/rightrail');
			echo $rightRail;
			?>
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<form id="wfConfigForm" class="wf-form-horizontal">
					<h2>License</h2>
					<div class="wf-form-group">
						<label for="apiKey" class="wf-col-sm-3 wf-control-label">Your Wordfence API Key <a href="http://docs.wordfence.com/en/Wordfence_options#Wordfence_API_Key" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-9">
							<input type="text" id="apiKey" class="wf-form-control" name="apiKey" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?php $w->f( 'apiKey' ); ?>" size="80">
						</div>
					</div>
					<div class="wf-form-group">
						<label for="apiKeyDetail" class="wf-col-sm-3 wf-control-label">Key type currently active</label>
						<div class="wf-col-sm-9">
							<p class="wf-form-control-static">
								<?php if (wfConfig::get('hasKeyConflict')) { ?>
									<span style="font-weight: bold; color: #A00;">The currently active Premium API Key is in use on another site.</span>
								<?php } else if (wfConfig::get( 'isPaid' )){ ?>
									The currently active API Key is a Premium Key. <span style="font-weight: bold; color: #0A0;">Premium scanning enabled!</span>
								<?php } else { ?>
									The currently active API Key is a <span style="color: #F00; font-weight: bold;">Free Key</span>. <a
											href="https://www.wordfence.com/gnl1optAPIKey1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Click Here to Upgrade to
										Wordfence Premium now.</a>
								<?php } ?>
							</p>
						</div>
					</div>
					<div class="wf-form-group">
						<?php if (wfConfig::get('hasKeyConflict')): ?>
						<div class="wf-col-sm-9 wf-col-sm-offset-3">
							<a href="https://www.wordfence.com/gnl1optMngKysReset/manage-wordfence-api-keys/" target="_blank" rel="noopener noreferrer"><input type="button" class="wf-btn wf-btn-default wf-btn-sm" value="Reset your premium license"/></a> <input type="button" class="wf-btn wf-btn-default wf-btn-sm" value="Downgrade to a free license" onclick="WFAD.downgradeLicense();"/>
						</div>
						<?php elseif (wfConfig::get('isPaid')): ?>
						<div class="wf-col-sm-9 wf-col-sm-offset-3">
							<a href="https://www.wordfence.com/gnl1optMngKys/manage-wordfence-api-keys/" target="_blank" rel="noopener noreferrer"><input type="button" class="wf-btn wf-btn-default wf-btn-sm" value="Renew your premium license"/></a> <input type="button" class="wf-btn wf-btn-default wf-btn-sm" value="Downgrade to a free license"  onclick="WFAD.downgradeLicense();"/>
						</div>
						<?php else: ?>
						<div class="wf-col-xs-12">
							<div class="wf-premium-callout">
								<h3>Upgrade today:</h3>
								<ul>
									<li>Receive real-time Firewall and Scan engine rule updates for protection as threats emerge</li>
									<li>Advanced features like IP reputation monitoring, country blocking, an advanced comment spam filter and cell phone sign-in give you the best protection available</li>
									<li>Remote, frequent and scheduled scans</li>
									<li>Access to Premium Support</li>
									<li>Discounts of up to 90% for multiyear and multi-license purchases</li>
								</ul>
								<p class="center"><a class="wf-btn wf-btn-primary wf-btn-callout" href="https://www.wordfence.com/gnl1optCallout1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Get Premium</a></p>
							</div>
						</div>
						<?php endif ?>
					</div>
					
					<h2>Basic Options <a href="http://docs.wordfence.com/en/Wordfence_options#Basic_Options" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h2>
					<div class="wf-form-group">
						<label for="firewallEnabled" class="wf-col-sm-5 wf-control-label">Enable Rate Limiting and Advanced Blocking <a href="https://docs.wordfence.com/en/Wordfence_options#Enable_Rate_Limiting_and_Advanced_Blocking" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="firewallEnabled" class="wfConfigElem" name="firewallEnabled" value="1" <?php $w->cb( 'firewallEnabled' ); ?>></div>
							<span class="wf-help-block"><span style="color: #F00;">NOTE:</span> This checkbox enables ALL blocking/throttling functions including IP, country and advanced blocking, and the "Rate Limiting Rules" below.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="loginSecurityEnabled" class="wf-col-sm-5 wf-control-label">Enable login security <a href="http://docs.wordfence.com/en/Wordfence_options#Enable_login_security" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="loginSecurityEnabled" class="wfConfigElem" name="loginSecurityEnabled" value="1" <?php $w->cb( 'loginSecurityEnabled' ); ?>></div>
							<span class="wf-help-block">This option enables all "Login Security" options, including two-factor authentication, strong password enforcement, and invalid login throttling. You can modify individual options further down this page.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="liveTrafficEnabled" class="wf-col-sm-5 wf-control-label">Enable Live Traffic View <a href="http://docs.wordfence.com/en/Wordfence_options#Enable_Live_Traffic_View" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="liveTrafficEnabled" class="wfConfigElem" name="liveTrafficEnabled" value="1" <?php $w->cb( 'liveTrafficEnabled' ); ?> onclick="WFAD.reloadConfigPage = true; return true;"></div>
							<span class="wf-help-block">This option enables live traffic logging.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="advancedCommentScanning" class="wf-col-sm-5 wf-control-label">Advanced Comment Spam Filter <a href="http://docs.wordfence.com/en/Wordfence_options#Advanced_Comment_Spam_Filter" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="advancedCommentScanning" class="wfConfigElem" name="advancedCommentScanning" value="1" <?php $w->cbp( 'advancedCommentScanning' ); if (!wfConfig::get('isPaid')) { ?>onclick="alert('This is a paid feature because it places significant additional load on our servers.'); jQuery('#advancedCommentScanning').attr('checked', false); return false;" <?php } ?>></div>
							<span class="wf-help-block"><span style="color: #F00;">Premium Feature</span> In addition to free comment filtering (see below) this option filters comments against several additional real-time lists of known spammers and infected hosts.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="scansEnabled_checkGSB" class="wf-col-sm-5 wf-control-label">Check if this website is on a domain blacklist <a href="http://docs.wordfence.com/en/Wordfence_options#Check_if_this_website_is_on_a_domain_blacklist" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="scansEnabled_checkGSB" class="wfConfigElem" name="scansEnabled_checkGSB" value="1" <?php $w->cbp( 'scansEnabled_checkGSB' ); if (!wfConfig::get('isPaid')) { ?>onclick="alert('This is a paid feature because it places significant additional load on our servers.'); jQuery('#scansEnabled_checkGSB').attr('checked', false); return false;" <?php } ?>></div>
							<span class="wf-help-block"><span style="color: #F00;">Premium Feature</span> When doing a scan, Wordfence will check with multiple domain blacklists to see if your site is listed.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="spamvertizeCheck" class="wf-col-sm-5 wf-control-label">Check if this website is being "Spamvertised" <a href="http://docs.wordfence.com/en/Wordfence_options#Check_if_this_website_is_being_.22Spamvertized.22" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="spamvertizeCheck" class="wfConfigElem" name="spamvertizeCheck" value="1" <?php $w->cbp('spamvertizeCheck'); if (!wfConfig::get('isPaid')) { ?>onclick="alert('This is a paid feature because it places significant additional load on our servers.'); jQuery('#spamvertizeCheck').attr('checked', false); return false;" <?php } ?>></div>
							<span class="wf-help-block"><span style="color: #F00;">Premium Feature</span> When doing a scan, Wordfence will check with spam services if your site domain name is appearing as a link in spam emails.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="checkSpamIP" class="wf-col-sm-5 wf-control-label">Check if this website IP is generating spam <a href="http://docs.wordfence.com/en/Wordfence_options#Check_if_this_website_IP_is_generating_spam" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="checkSpamIP" class="wfConfigElem" name="checkSpamIP" value="1" <?php $w->cbp( 'checkSpamIP' ); if (!wfConfig::get('isPaid')) { ?>onclick="alert('This is a paid feature because it places significant additional load on our servers.'); jQuery('#checkSpamIP').attr('checked', false); return false;" <?php } ?>></div>
							<span class="wf-help-block"><span style="color: #F00;">Premium Feature</span> When doing a scan, Wordfence will check with spam services if your website IP address is listed as a known source of spam email.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="scheduledScansEnabled" class="wf-col-sm-5 wf-control-label">Enable automatic scheduled scans <a href="http://docs.wordfence.com/en/Wordfence_options#Enable_automatic_scheduled_scans" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="scheduledScansEnabled" class="wfConfigElem" name="scheduledScansEnabled" value="1" <?php $w->cb( 'scheduledScansEnabled' ); ?>></div>
							<span class="wf-help-block">Regular scans ensure your site stays secure.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="autoUpdate" class="wf-col-sm-5 wf-control-label">Update Wordfence automatically when a new version is released? <a href="http://docs.wordfence.com/en/Wordfence_options#Update_Wordfence_Automatically_when_a_new_version_is_released" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<div class="wf-checkbox"><input type="checkbox" id="autoUpdate" class="wfConfigElem" name="autoUpdate" value="1" <?php $w->cb( 'autoUpdate' ); ?>></div>
							<span class="wf-help-block">Automatically updates Wordfence to the newest version within 24 hours of a new release.<br>
								<?php if (!wfConfig::get('other_bypassLitespeedNoabort', false) && getenv('noabort') != '1' && stristr($_SERVER['SERVER_SOFTWARE'], 'litespeed') !== false) { ?>
									<span style="color: #F00;">Warning: </span>You are running the LiteSpeed web server and Wordfence can't determine whether "noabort" is set. Please verify that the environmental variable "noabort" is set for the local site, or the server's global External Application Abort is set to "No Abort".<br>
									<a href="https://docs.wordfence.com/en/LiteSpeed_aborts_Wordfence_scans_and_updates._How_do_I_prevent_that%3F" target="_blank" rel="noopener noreferrer">Please read this article in our FAQ to make an important change that will ensure your site stability during an update.<br>
								<?php } ?></span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="alertEmails" class="wf-col-sm-5 wf-control-label">Where to email alerts <a href="http://docs.wordfence.com/en/Wordfence_options#Where_to_email_alerts" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<input type="text" id="alertEmails" name="alertEmails" class="wf-form-control" value="<?php $w->f( 'alertEmails' ); ?>" size="50">
							<span class="wf-help-block">Separate multiple emails with commas.</span>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="howGetIPs" class="wf-col-sm-5 wf-control-label">How does Wordfence get IPs <a href="http://docs.wordfence.com/en/Wordfence_options#How_does_Wordfence_get_IPs" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7">
							<select id="howGetIPs" name="howGetIPs" class="wf-form-control">
								<option value="">Let Wordfence use the most secure method to get visitor IP addresses. Prevents spoofing and works with most sites.</option>
								<option value="REMOTE_ADDR"<?php $w->sel( 'howGetIPs', 'REMOTE_ADDR' ); ?>>Use PHP's built in REMOTE_ADDR and don't use anything else. Very secure if this is compatible with your site.</option>
								<option value="HTTP_X_FORWARDED_FOR"<?php $w->sel( 'howGetIPs', 'HTTP_X_FORWARDED_FOR' ); ?>>Use the X-Forwarded-For HTTP header. Only use if you have a front-end proxy or spoofing may result.</option>
								<option value="HTTP_X_REAL_IP"<?php $w->sel( 'howGetIPs', 'HTTP_X_REAL_IP' ); ?>>Use the X-Real-IP HTTP header. Only use if you have a front-end proxy or spoofing may result.</option>
								<option value="HTTP_CF_CONNECTING_IP"<?php $w->sel( 'howGetIPs', 'HTTP_CF_CONNECTING_IP' ); ?>>Use the Cloudflare "CF-Connecting-IP" HTTP header to get a visitor IP. Only use if you're using Cloudflare.</option>
							</select>
							<span class="wf-help-block">Detected IP(s): <span id="howGetIPs-preview-all"><?php echo wfUtils::getIPPreview(); ?></span></span>
							<span class="wf-help-block">Your IP with this setting: <span id="howGetIPs-preview-single"><?php echo wfUtils::getIP(); ?></span></span>
							<span class="wf-help-block"><a href="#" class="do-show" data-selector="#howGetIPs_trusted_proxies">+ Edit trusted proxies</a></span>
						</div>
					</div>
					<div class="wf-form-group wf-sub-group hidden" id="howGetIPs_trusted_proxies">
						<label for="howGetIPs_trusted_proxies_field" class="wf-col-sm-4 wf-col-sm-offset-1 wf-control-label">Trusted proxies</label>
						<div class="wf-col-sm-7">
							<textarea class="wf-form-control" rows="4" name="howGetIPs_trusted_proxies" id="howGetIPs_trusted_proxies_field"><?php echo $w->getHTML('howGetIPs_trusted_proxies'); ?></textarea>
							<span class="wf-help-block">These IPs (or CIDR ranges) will be ignored when determining the requesting IP via the X-Forwarded-For HTTP header. Enter one IP or CIDR range per line.</span>
							<script type="application/javascript">
								(function($) {
									var updateIPPreview = function() {
										WFAD.updateIPPreview({howGetIPs: $('#howGetIPs').val(), 'howGetIPs_trusted_proxies': $('#howGetIPs_trusted_proxies_field').val()}, function(ret) {
											if (ret && ret.ok) {
												$('#howGetIPs-preview-all').html(ret.ipAll);
												$('#howGetIPs-preview-single').html(ret.ip);
											}
											else {
												//TODO: implementing testing whether or not this setting will lock them out and show the error saying that they'd lock themselves out
											}
										});
									};
									
									$('#howGetIPs').on('change', function() {
										updateIPPreview();
									});
									
									var coalescingUpdateTimer;
									$('#howGetIPs_trusted_proxies_field').on('keyup', function() {
										clearTimeout(coalescingUpdateTimer);
										coalescingUpdateTimer = setTimeout(updateIPPreview, 1000);
									});
								})(jQuery);
							</script>
						</div>
					</div>
					<div class="wf-form-group">
						<div class="wf-col-sm-7 wf-col-sm-offset-5"> 
							<a class="wf-btn wf-btn-primary wf-btn-callout" href="#" onclick="WFAD.saveConfig(); return false;">Save Options</a> <div class="wfAjax24"></div><span class="wfSavedMsg">&nbsp;Your changes have been saved!</span>
						</div>
					</div>
					<div class="wfMarker" id="wfMarkerBasicOptions"></div>

					<h2>Advanced Options <a href="http://docs.wordfence.com/en/Wordfence_options#Advanced_Options" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h2>
					<div class="wf-form-group">
						<div class="wf-col-sm-9">
							<span class="wf-help-block">Wordfence works great out of the box for most websites. Simply install Wordfence and your site and content is protected. For finer granularity of control, we have provided advanced options.</span>
						</div>
					</div>
					<div id="wfConfigAdvanced">
						<h3>Alerts <a href="http://docs.wordfence.com/en/Wordfence_options#Alerts" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<?php
						$emails = wfConfig::getAlertEmails();
						if (count($emails) < 1):
						?>
						<div class="wf-form-group">
							<div class="wf-col-sm-9">
								<span class="wf-help-block" style="color: #ff0000;">You have not configured an email to receive alerts yet. Set this up under "Basic Options" above.</span>
							</div>
						</div>
						<?php
						endif;
						?>
						<?php
						$options = array( //Contents should already be HTML-escaped as needed
							array(
								'id' 		=> 'alertOn_update',
								'label'		=> 'Email me when Wordfence is automatically updated',
								'help'		=> 'If you have automatic updates enabled (see above), you\'ll get an email when an update occurs.',
							),
							array(
								'id' 		=> 'alertOn_wordfenceDeactivated',
								'label'		=> 'Email me if Wordfence is deactivated',
							),
							array(
								'id' 		=> 'alertOn_critical',
								'label'		=> 'Alert on critical problems',
							),
							array(
								'id' 		=> 'alertOn_warnings',
								'label'		=> 'Alert on warnings',
							),
							array(
								'id' 		=> 'alertOn_block',
								'label'		=> 'Alert when an IP address is blocked',
							),
							array(
								'id' 		=> 'alertOn_loginLockout',
								'label'		=> 'Alert when someone is locked out from login',
							),
							array(
								'id' 		=> 'alertOn_lostPasswdForm',
								'label'		=> 'Alert when the "lost password" form is used for a valid user',
							),
							array(
								'id' 		=> 'alertOn_adminLogin',
								'label'		=> 'Alert me when someone with administrator access signs in',
								'subs'		=> array(
													array(
														'id'		=> 'alertOn_firstAdminLoginOnly',
														'label'		=> 'Only alert me when that administrator signs in from a new device or location',
													),
												)
							),
							array(
								'id' 		=> 'alertOn_nonAdminLogin',
								'label'		=> 'Alert me when a non-admin user signs in',
								'subs'		=> array(
													array(
														'id'		=> 'alertOn_firstNonAdminLoginOnly',
														'label'		=> 'Only alert me when that user signs in from a new device or location',
													),
												)
							),
							array(
								'id' 		=> 'wafAlertOnAttacks',
								'label'		=> 'Alert me when there\'s a large increase in attacks detected on my site',
							),
						);
						foreach ($options as $o):
						?>
						<div class="wf-form-group">
							<label for="<?php echo $o['id']; ?>" class="wf-col-sm-5 wf-control-label"><?php echo $o['label']; ?></label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="<?php echo $o['id']; ?>" class="wfConfigElem" name="<?php echo $o['id']; ?>" value="1" <?php $w->cb($o['id']); ?>></div>
								<?php if (isset($o['help'])): ?>
								<span class="wf-help-block"><?php echo $o['help']; ?></span>
								<?php endif; ?>
							</div>
						</div>
						<?php if (isset($o['subs'])): ?>
							<?php foreach ($o['subs'] as $s): ?>
								<div class="wf-form-group wf-sub-group">
									<label for="<?php echo $s['id']; ?>" class="wf-col-sm-4 wf-col-sm-offset-1 wf-control-label"><?php echo $s['label']; ?></label>
									<div class="wf-col-sm-7">
										<div class="wf-checkbox"><input type="checkbox" id="<?php echo $s['id']; ?>" class="wfConfigElem" name="<?php echo $s['id']; ?>" value="1" <?php $w->cb($s['id']); ?>></div>
										<?php if (isset($s['help'])): ?>
											<span class="wf-help-block"><?php echo $s['help']; ?></span>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
						<?php
						endforeach;
						?>
						<div class="wf-form-group">
							<label for="alert_maxHourly" class="wf-col-sm-5 wf-control-label">Maximum email alerts to send per hour</label>
							<div class="wf-col-sm-7">
								<input type="text" id="alert_maxHourly" name="alert_maxHourly" class="wf-form-control" value="<?php $w->f( 'alert_maxHourly' ); ?>" size="4">
								<span class="wf-help-block">0 or empty means unlimited alerts will be sent.</span>
							</div>
						</div>

						<div class="wfMarker" id="wfMarkerEmailSummary"></div>
						<h3>Email Summary <a href="http://docs.wordfence.com/en/Wordfence_options#Email_Summary" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<div class="wf-form-group">
							<label for="email_summary_enabled" class="wf-col-sm-5 wf-control-label">Enable email summary</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="email_summary_enabled" class="wfConfigElem" name="email_summary_enabled" value="1" <?php $w->cb('email_summary_enabled'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="email_summary_interval" class="wf-col-sm-5 wf-control-label">Email summary frequency</label>
							<div class="wf-col-sm-7">
								<select id="email_summary_interval" name="email_summary_interval" class="wf-form-control">
									<option value="daily"<?php $w->sel( 'email_summary_interval', 'daily' ); ?>>Once a day</option>
									<option value="weekly"<?php $w->sel( 'email_summary_interval', 'weekly' ); ?>>Once a week</option>
									<option value="monthly"<?php $w->sel( 'email_summary_interval', 'monthly' ); ?>>Once a month</option>
								</select>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="email_summary_excluded_directories" class="wf-col-sm-5 wf-control-label">List of directories to exclude from recently modified file list</label>
							<div class="wf-col-sm-7">
								<textarea id="email_summary_excluded_directories" name="email_summary_excluded_directories" class="wf-form-control" rows="4"><?php echo esc_html(wfUtils::cleanupOneEntryPerLine($w->get('email_summary_excluded_directories', ''))); ?></textarea>
							</div>
						</div>
						<?php if ((defined('WP_DEBUG') && WP_DEBUG) || wfConfig::get('debugOn', 0)): ?>
						<div class="wf-form-group">
							<label for="email_summary_email_address_debug" class="wf-col-sm-5 wf-control-label">Send test email</label>
							<div class="wf-col-sm-7">
								<div class="wf-form-inline">
									<input type="email" id="email_summary_email_address_debug" class="wf-form-control">
									<a class="wf-btn wf-btn-sm wf-btn-default" href="javascript:void(0);" onclick="WFAD.ajax('wordfence_email_summary_email_address_debug', {email: jQuery('#email_summary_email_address_debug').val()});">Send Email</a>
								</div>
							</div>
						</div>	
						<?php endif; ?>
						<div class="wf-form-group">
							<label for="email_summary_dashboard_widget_enabled" class="wf-col-sm-5 wf-control-label">Enable activity report widget on dashboard</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="email_summary_dashboard_widget_enabled" class="wfConfigElem" name="email_summary_dashboard_widget_enabled" value="1" <?php $w->cb('email_summary_dashboard_widget_enabled'); ?>></div>
							</div>
						</div>

						<div class="wfMarker" id="wfMarkerLiveTrafficOptions"></div>
						<h3>Live Traffic View <a href="http://docs.wordfence.com/en/Wordfence_options#Live_Traffic_View" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<div class="wf-form-group">
							<label for="liveTraf_ignorePublishers" class="wf-col-sm-5 wf-control-label">Don't log signed-in users with publishing access</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="liveTraf_ignorePublishers" class="wfConfigElem" name="liveTraf_ignorePublishers" value="1" <?php $w->cb('liveTraf_ignorePublishers'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="liveTraf_ignoreUsers" class="wf-col-sm-5 wf-control-label">List of comma separated usernames to ignore</label>
							<div class="wf-col-sm-7">
								<input type="text" id="liveTraf_ignoreUsers" name="liveTraf_ignoreUsers" class="wf-form-control" value="<?php $w->f( 'liveTraf_ignoreUsers' ); ?>">
							</div>
						</div>
						<div class="wf-form-group">
							<label for="liveTraf_ignoreIPs" class="wf-col-sm-5 wf-control-label">List of comma separated IP addresses to ignore</label>
							<div class="wf-col-sm-7">
								<input type="text" id="liveTraf_ignoreIPs" name="liveTraf_ignoreIPs" class="wf-form-control" value="<?php $w->f( 'liveTraf_ignoreIPs' ); ?>">
							</div>
						</div>
						<div class="wf-form-group">
							<label for="liveTraf_ignoreUA" class="wf-col-sm-5 wf-control-label">Browser user-agent to ignore</label>
							<div class="wf-col-sm-7">
								<input type="text" id="liveTraf_ignoreUA" name="liveTraf_ignoreUA" class="wf-form-control" value="<?php $w->f( 'liveTraf_ignoreUA' ); ?>">
							</div>
						</div>
						<div class="wf-form-group">
							<label for="liveTraf_maxRows" class="wf-col-sm-5 wf-control-label">Amount of Live Traffic data to store (number of rows)</label>
							<div class="wf-col-sm-7">
								<input type="text" id="liveTraf_maxRows" name="liveTraf_maxRows" class="wf-form-control" value="<?php $w->f( 'liveTraf_maxRows' ); ?>">
							</div>
						</div>

						<div class="wfMarker" id="wfMarkerScansToInclude"></div>
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

						<div class="wfMarker" id="wfMarkerFirewallRules"></div>
						<h3>Rate Limiting Rules <a href="http://docs.wordfence.com/en/Wordfence_options#Rate_Limiting_Rules" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<div class="wf-form-group">
							<label for="blockFakeBots" class="wf-col-sm-5 wf-control-label">Immediately block fake Google crawlers <a href="http://docs.wordfence.com/en/Wordfence_options#Immediately_block_fake_Google_crawlers:" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="blockFakeBots" name="blockFakeBots" value="1" <?php $w->cb('blockFakeBots'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="neverBlockBG" class="wf-col-sm-5 wf-control-label">How should we treat Google's crawlers <a href="http://docs.wordfence.com/en/Wordfence_options#How_should_we_treat_Google.27s_crawlers" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<select id="neverBlockBG" class="wf-form-control" name="neverBlockBG">
									<option value="neverBlockVerified"<?php $w->sel( 'neverBlockBG', 'neverBlockVerified' ); ?>>Verified Google crawlers have unlimited access to this site</option>
									<option value="neverBlockUA"<?php $w->sel( 'neverBlockBG', 'neverBlockUA' ); ?>>Anyone claiming to be Google has unlimited access</option>
									<option value="treatAsOtherCrawlers"<?php $w->sel( 'neverBlockBG', 'treatAsOtherCrawlers' ); ?>>Treat Google like any other Crawler</option>
								</select>
							</div>
						</div>
						<?php
						$options = array( //Contents should already be HTML-escaped as needed
							array(
								'id' 		=> 'maxGlobalRequests',
								'label'		=> 'If anyone\'s requests exceed <a href="http://docs.wordfence.com/en/Wordfence_options#If_anyone.27s_requests_exceed:" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'maxRequestsCrawlers',
								'label'		=> 'If a crawler\'s page views exceed <a href="http://docs.wordfence.com/en/Wordfence_options#If_a_crawler.27s_page_views_exceed" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'max404Crawlers',
								'label'		=> 'If a crawler\'s pages not found (404s) exceed <a href="http://docs.wordfence.com/en/Wordfence_options#If_a_crawler.27s_pages_not_found_.28404s.29_exceed" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'maxRequestsHumans',
								'label'		=> 'If a human\'s page views exceed <a href="http://docs.wordfence.com/en/Wordfence_options#If_a_human.27s_page_views_exceed" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'max404Humans',
								'label'		=> 'If a human\'s pages not found (404s) exceed <a href="http://docs.wordfence.com/en/Wordfence_options#If_a_human.27s_pages_not_found_.28404s.29_exceed" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'maxScanHits',
								'label'		=> 'If 404s for known vulnerable URLs exceed <a href="http://docs.wordfence.com/en/Wordfence_options#If_404.27s_for_known_vulnerable_URL.27s_exceed" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
						);
						foreach ($options as $o): ?>
						<div class="wf-form-group<?php if (isset($o['hidden']) && $o['hidden']) { echo ' hidden'; } ?>">
							<label for="<?php echo $o['id']; ?>" class="wf-col-sm-5 wf-control-label"><?php echo $o['label']; ?></label>
							<div class="wf-col-sm-7">
								<div class="wf-form-inline">
									<select class="wf-form-control" id="<?php echo $o['id']; ?>" name="<?php echo $o['id']; ?>">
										<option value="DISABLED"<?php $w->sel($o['id'], 'DISABLED'); ?>>Unlimited</option>
										<option value="1"<?php $w->sel($o['id'], '1'); ?>>1 per minute</option>
										<option value="2"<?php $w->sel($o['id'], '2'); ?>>2 per minute</option>
										<option value="3"<?php $w->sel($o['id'], '3'); ?>>3 per minute</option>
										<option value="4"<?php $w->sel($o['id'], '4'); ?>>4 per minute</option>
										<option value="5"<?php $w->sel($o['id'], '5'); ?>>5 per minute</option>
										<option value="10"<?php $w->sel($o['id'], '10'); ?>>10 per minute</option>
										<option value="15"<?php $w->sel($o['id'], '15'); ?>>15 per minute</option>
										<option value="30"<?php $w->sel($o['id'], '30'); ?>>30 per minute</option>
										<option value="60"<?php $w->sel($o['id'], '60'); ?>>60 per minute</option>
										<option value="120"<?php $w->sel($o['id'], '120'); ?>>120 per minute</option>
										<option value="240"<?php $w->sel($o['id'], '240'); ?>>240 per minute</option>
										<option value="480"<?php $w->sel($o['id'], '480'); ?>>480 per minute</option>
										<option value="960"<?php $w->sel($o['id'], '960'); ?>>960 per minute</option>
										<option value="1920"<?php $w->sel($o['id'], '1920'); ?>>1920 per minute</option>
									</select>
									<p class="wf-form-control-static">then</p>
									<select class="wf-form-control" id="<?php echo $o['id']; ?>_action" name="<?php echo $o['id']; ?>_action">
										<option value="throttle"<?php $w->sel($o['id'] . '_action', 'throttle'); ?>>throttle it</option>
										<option value="block"<?php $w->sel($o['id'] . '_action', 'block'); ?>>block it</option>
									</select>
								</div>
								<?php if (isset($o['help']) || (!wfConfig::get('isPaid') && isset($o['premium']) && $o['premium'])): ?>
									<span class="wf-help-block"><?php if (!wfConfig::get('isPaid') && isset($o['premium']) && $o['premium']) { echo '<span style="color: #F00;">Premium Feature</span> This feature requires a <a href="https://www.wordfence.com/gnl1optPdOnly1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Wordfence Premium Key</a>. '; } ?><?php if (isset($o['help'])) { echo $o['help']; } ?></span>
								<?php endif; ?>
							</div>
						</div>
						<?php endforeach; ?>
						<div class="wf-form-group">
							<label for="blockedTime" class="wf-col-sm-5 wf-control-label">How long is an IP address blocked when it breaks a rule <a href="http://docs.wordfence.com/en/Wordfence_options#How_long_is_an_IP_address_blocked_when_it_breaks_a_rule" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<select id="blockedTime" class="wf-form-control" name="blockedTime">
									<option value="60"<?php $w->sel( 'blockedTime', '60' ); ?>>1 minute</option>
									<option value="300"<?php $w->sel( 'blockedTime', '300' ); ?>>5 minutes</option>
									<option value="1800"<?php $w->sel( 'blockedTime', '1800' ); ?>>30 minutes</option>
									<option value="3600"<?php $w->sel( 'blockedTime', '3600' ); ?>>1 hour</option>
									<option value="7200"<?php $w->sel( 'blockedTime', '7200' ); ?>>2 hours</option>
									<option value="21600"<?php $w->sel( 'blockedTime', '21600' ); ?>>6 hours</option>
									<option value="43200"<?php $w->sel( 'blockedTime', '43200' ); ?>>12 hours</option>
									<option value="86400"<?php $w->sel( 'blockedTime', '86400' ); ?>>1 day</option>
									<option value="172800"<?php $w->sel( 'blockedTime', '172800' ); ?>>2 days</option>
									<option value="432000"<?php $w->sel( 'blockedTime', '432000' ); ?>>5 days</option>
									<option value="864000"<?php $w->sel( 'blockedTime', '864000' ); ?>>10 days</option>
									<option value="2592000"<?php $w->sel( 'blockedTime', '2592000' ); ?>>1 month</option>
								</select>
							</div>
						</div>

						<div class="wfMarker" id="wfMarkerLoginSecurity"></div>
						<h3>Login Security Options <a href="http://docs.wordfence.com/en/Wordfence_options#Login_Security_Options" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<div class="wf-form-group">
							<label for="loginSec_strongPasswds" class="wf-col-sm-5 wf-control-label">Enforce strong passwords <a href="http://docs.wordfence.com/en/Wordfence_options#Enforce_strong_passwords.3F" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<select class="wf-form-control" id="loginSec_strongPasswds" name="loginSec_strongPasswds">
									<option value="">Do not force users to use strong passwords</option>
									<option value="pubs"<?php $w->sel( 'loginSec_strongPasswds', 'pubs' ); ?>>Force admins and publishers to use strong passwords (recommended)</option>
									<option value="all"<?php $w->sel( 'loginSec_strongPasswds', 'all' ); ?>>Force all members to use strong passwords</option>
								</select>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_maxFailures" class="wf-col-sm-5 wf-control-label">Lock out after how many login failures <a href="http://docs.wordfence.com/en/Wordfence_options#Lock_out_after_how_many_login_failures" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<select id="loginSec_maxFailures" class="wf-form-control" name="loginSec_maxFailures">
									<option value="1"<?php $w->sel( 'loginSec_maxFailures', '1' ); ?>>1</option>
									<option value="2"<?php $w->sel( 'loginSec_maxFailures', '2' ); ?>>2</option>
									<option value="3"<?php $w->sel( 'loginSec_maxFailures', '3' ); ?>>3</option>
									<option value="4"<?php $w->sel( 'loginSec_maxFailures', '4' ); ?>>4</option>
									<option value="5"<?php $w->sel( 'loginSec_maxFailures', '5' ); ?>>5</option>
									<option value="6"<?php $w->sel( 'loginSec_maxFailures', '6' ); ?>>6</option>
									<option value="7"<?php $w->sel( 'loginSec_maxFailures', '7' ); ?>>7</option>
									<option value="8"<?php $w->sel( 'loginSec_maxFailures', '8' ); ?>>8</option>
									<option value="9"<?php $w->sel( 'loginSec_maxFailures', '9' ); ?>>9</option>
									<option value="10"<?php $w->sel( 'loginSec_maxFailures', '10' ); ?>>10</option>
									<option value="20"<?php $w->sel( 'loginSec_maxFailures', '20' ); ?>>20</option>
									<option value="30"<?php $w->sel( 'loginSec_maxFailures', '30' ); ?>>30</option>
									<option value="40"<?php $w->sel( 'loginSec_maxFailures', '40' ); ?>>40</option>
									<option value="50"<?php $w->sel( 'loginSec_maxFailures', '50' ); ?>>50</option>
									<option value="100"<?php $w->sel( 'loginSec_maxFailures', '100' ); ?>>100</option>
									<option value="200"<?php $w->sel( 'loginSec_maxFailures', '200' ); ?>>200</option>
									<option value="500"<?php $w->sel( 'loginSec_maxFailures', '500' ); ?>>500</option>
								</select>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_maxForgotPasswd" class="wf-col-sm-5 wf-control-label">Lock out after how many forgot password attempts <a href="http://docs.wordfence.com/en/Wordfence_options#Lock_out_after_how_many_forgot_password_attempts" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<select id="loginSec_maxForgotPasswd" class="wf-form-control" name="loginSec_maxForgotPasswd">
									<option value="1"<?php $w->sel( 'loginSec_maxForgotPasswd', '1' ); ?>>1</option>
									<option value="2"<?php $w->sel( 'loginSec_maxForgotPasswd', '2' ); ?>>2</option>
									<option value="3"<?php $w->sel( 'loginSec_maxForgotPasswd', '3' ); ?>>3</option>
									<option value="4"<?php $w->sel( 'loginSec_maxForgotPasswd', '4' ); ?>>4</option>
									<option value="5"<?php $w->sel( 'loginSec_maxForgotPasswd', '5' ); ?>>5</option>
									<option value="6"<?php $w->sel( 'loginSec_maxForgotPasswd', '6' ); ?>>6</option>
									<option value="7"<?php $w->sel( 'loginSec_maxForgotPasswd', '7' ); ?>>7</option>
									<option value="8"<?php $w->sel( 'loginSec_maxForgotPasswd', '8' ); ?>>8</option>
									<option value="9"<?php $w->sel( 'loginSec_maxForgotPasswd', '9' ); ?>>9</option>
									<option value="10"<?php $w->sel( 'loginSec_maxForgotPasswd', '10' ); ?>>10</option>
									<option value="20"<?php $w->sel( 'loginSec_maxForgotPasswd', '20' ); ?>>20</option>
									<option value="30"<?php $w->sel( 'loginSec_maxForgotPasswd', '30' ); ?>>30</option>
									<option value="40"<?php $w->sel( 'loginSec_maxForgotPasswd', '40' ); ?>>40</option>
									<option value="50"<?php $w->sel( 'loginSec_maxForgotPasswd', '50' ); ?>>50</option>
									<option value="100"<?php $w->sel( 'loginSec_maxForgotPasswd', '100' ); ?>>100</option>
									<option value="200"<?php $w->sel( 'loginSec_maxForgotPasswd', '200' ); ?>>200</option>
									<option value="500"<?php $w->sel( 'loginSec_maxForgotPasswd', '500' ); ?>>500</option>
								</select>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_countFailMins" class="wf-col-sm-5 wf-control-label">Count failures over what time period <a href="http://docs.wordfence.com/en/Wordfence_options#Count_failures_over_what_time_period" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<select id="loginSec_countFailMins" class="wf-form-control" name="loginSec_countFailMins">
									<option value="5"<?php $w->sel( 'loginSec_countFailMins', '5' ); ?>>5 minutes</option>
									<option value="10"<?php $w->sel( 'loginSec_countFailMins', '10' ); ?>>10 minutes</option>
									<option value="30"<?php $w->sel( 'loginSec_countFailMins', '30' ); ?>>30 minutes</option>
									<option value="60"<?php $w->sel( 'loginSec_countFailMins', '60' ); ?>>1 hour</option>
									<option value="120"<?php $w->sel( 'loginSec_countFailMins', '120' ); ?>>2 hours</option>
									<option value="360"<?php $w->sel( 'loginSec_countFailMins', '360' ); ?>>6 hours</option>
									<option value="720"<?php $w->sel( 'loginSec_countFailMins', '720' ); ?>>12 hours</option>
									<option value="1440"<?php $w->sel( 'loginSec_countFailMins', '1440' ); ?>>1 day</option>
								</select>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_lockoutMins" class="wf-col-sm-5 wf-control-label">Amount of time a user is locked out <a href="http://docs.wordfence.com/en/Wordfence_options#Amount_of_time_a_user_is_locked_out" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<select id="loginSec_lockoutMins" class="wf-form-control" name="loginSec_lockoutMins">
									<option value="5"<?php $w->sel( 'loginSec_lockoutMins', '5' ); ?>>5 minutes</option>
									<option value="10"<?php $w->sel( 'loginSec_lockoutMins', '10' ); ?>>10 minutes</option>
									<option value="30"<?php $w->sel( 'loginSec_lockoutMins', '30' ); ?>>30 minutes</option>
									<option value="60"<?php $w->sel( 'loginSec_lockoutMins', '60' ); ?>>1 hour</option>
									<option value="120"<?php $w->sel( 'loginSec_lockoutMins', '120' ); ?>>2 hours</option>
									<option value="360"<?php $w->sel( 'loginSec_lockoutMins', '360' ); ?>>6 hours</option>
									<option value="720"<?php $w->sel( 'loginSec_lockoutMins', '720' ); ?>>12 hours</option>
									<option value="1440"<?php $w->sel( 'loginSec_lockoutMins', '1440' ); ?>>1 day</option>
									<option value="2880"<?php $w->sel( 'loginSec_lockoutMins', '2880' ); ?>>2 days</option>
									<option value="7200"<?php $w->sel( 'loginSec_lockoutMins', '7200' ); ?>>5 days</option>
									<option value="14400"<?php $w->sel( 'loginSec_lockoutMins', '14400' ); ?>>10 days</option>
									<option value="28800"<?php $w->sel( 'loginSec_lockoutMins', '28800' ); ?>>20 days</option>
									<option value="43200"<?php $w->sel( 'loginSec_lockoutMins', '43200' ); ?>>30 days</option>
									<option value="86400"<?php $w->sel( 'loginSec_lockoutMins', '86400' ); ?>>60 days</option>
								</select>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_lockInvalidUsers" class="wf-col-sm-5 wf-control-label">Immediately lock out invalid usernames <a href="http://docs.wordfence.com/en/Wordfence_options#Immediately_lock_out_invalid_usernames" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="loginSec_lockInvalidUsers" name="loginSec_lockInvalidUsers" value="1" <?php $w->cb('loginSec_lockInvalidUsers'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_maskLoginErrors" class="wf-col-sm-5 wf-control-label">Don't let WordPress reveal valid users in login errors <a href="http://docs.wordfence.com/en/Wordfence_options#Don.27t_let_WordPress_reveal_valid_users_in_login_errors" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="loginSec_maskLoginErrors" name="loginSec_maskLoginErrors" value="1" <?php $w->cb('loginSec_maskLoginErrors'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_blockAdminReg" class="wf-col-sm-5 wf-control-label">Prevent users registering 'admin' username if it doesn't exist <a href="http://docs.wordfence.com/en/Wordfence_options#Prevent_users_registering_.27admin.27_username_if_it_doesn.27t_exist" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="loginSec_blockAdminReg" name="loginSec_blockAdminReg" value="1" <?php $w->cb('loginSec_blockAdminReg'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_disableAuthorScan" class="wf-col-sm-5 wf-control-label">Prevent discovery of usernames through '/?author=N' scans, the oEmbed API, and the WordPress REST API <a href="http://docs.wordfence.com/en/Wordfence_options#Prevent_discovery_of_usernames_through_.27.3F.2Fauthor.3DN.27_scans" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="loginSec_disableAuthorScan" name="loginSec_disableAuthorScan" value="1" <?php $w->cb('loginSec_disableAuthorScan'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="loginSec_userBlacklist" class="wf-col-sm-5 wf-control-label">Immediately block the IP of users who try to sign in as these usernames <a href="http://docs.wordfence.com/en/Wordfence_options#Immediately_block_the_IP_of_users_who_try_to_sign_in_as_these_usernames" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<textarea id="loginSec_userBlacklist" class="wf-form-control" rows="4" name="loginSec_userBlacklist"><?php echo wfUtils::cleanupOneEntryPerLine($w->getHTML( 'loginSec_userBlacklist' )); ?></textarea>
								<span class="wf-help-block">(One per line. Existing users won't be blocked.)</span>
							</div>
						</div>

						<div class="wfMarker" id="wfMarkerNotification"></div>
						<h3>Dashboard Notification Options <a href="http://docs.wordfence.com/en/Wordfence_options#Dashboard_Notification_Options" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<div class="wf-form-group">
							<label for="notification_updatesNeeded" class="wf-col-sm-5 wf-control-label">Updates Needed (Plugin, Theme, or Core)</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="notification_updatesNeeded" name="notification_updatesNeeded" value="1" <?php $w->cb('notification_updatesNeeded'); ?>></div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="notification_securityAlerts" class="wf-col-sm-5 wf-control-label">Security Alerts</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="notification_securityAlerts"<?php if ($w->p()) { echo ' name="notification_securityAlerts"'; } ?> value="1" <?php if ($w->p()) { $w->cb('notification_securityAlerts'); } else { echo ' checked disabled'; } ?>></div>
								<?php if (!$w->p()): ?>
									<span class="wf-help-block"><span style="color: #F00;">Premium Option</span> This option requires a <a href="https://www.wordfence.com/gnl1optPdOnly1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Wordfence Premium Key</a>.</span>
									<?php if ($w->get('notification_securityAlerts')): ?><input type="hidden" name="notification_securityAlerts" value="<?php $w->f('notification_securityAlerts'); ?>"><?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="notification_promotions" class="wf-col-sm-5 wf-control-label">Promotions</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="notification_promotions"<?php if ($w->p()) { echo ' name="notification_promotions"'; } ?> value="1" <?php if ($w->p()) { $w->cb('notification_promotions'); } else { echo ' checked disabled'; } ?>></div>
								<?php if (!$w->p()): ?>
									<span class="wf-help-block"><span style="color: #F00;">Premium Option</span> This option requires a <a href="https://www.wordfence.com/gnl1optPdOnly1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Wordfence Premium Key</a>.</span>
									<?php if ($w->get('notification_promotions')): ?><input type="hidden" name="notification_promotions" value="<?php $w->f('notification_promotions'); ?>"><?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="notification_blogHighlights" class="wf-col-sm-5 wf-control-label">Blog Highlights</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="notification_blogHighlights"<?php if ($w->p()) { echo ' name="notification_blogHighlights"'; } ?> value="1" <?php if ($w->p()) { $w->cb('notification_blogHighlights'); } else { echo ' checked disabled'; } ?>></div>
								<?php if (!$w->p()): ?>
									<span class="wf-help-block"><span style="color: #F00;">Premium Option</span> This option requires a <a href="https://www.wordfence.com/gnl1optPdOnly1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Wordfence Premium Key</a>.</span>
									<?php if ($w->get('notification_blogHighlights')): ?><input type="hidden" name="notification_blogHighlights" value="<?php $w->f('notification_blogHighlights'); ?>"><?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="notification_productUpdates" class="wf-col-sm-5 wf-control-label">Product Updates</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="notification_productUpdates"<?php if ($w->p()) { echo ' name="notification_productUpdates"'; } ?> value="1" <?php if ($w->p()) { $w->cb('notification_productUpdates'); } else { echo ' checked disabled'; } ?>></div>
								<?php if (!$w->p()): ?>
									<span class="wf-help-block"><span style="color: #F00;">Premium Option</span> This option requires a <a href="https://www.wordfence.com/gnl1optPdOnly1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Wordfence Premium Key</a>.</span>
									<?php if ($w->get('notification_productUpdates')): ?><input type="hidden" name="notification_productUpdates" value="<?php $w->f('notification_productUpdates'); ?>"><?php endif; ?>
								<?php endif; ?>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="notification_scanStatus" class="wf-col-sm-5 wf-control-label">Scan Status</label>
							<div class="wf-col-sm-7">
								<div class="wf-checkbox"><input type="checkbox" id="notification_scanStatus" name="notification_scanStatus" value="1" <?php $w->cb('notification_scanStatus'); ?>></div>
							</div>
						</div>

						<div class="wfMarker" id="wfMarkerOtherOptions"></div>
						<h3>Other Options <a href="http://docs.wordfence.com/en/Wordfence_options#Other_Options" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<div class="wf-form-group">
							<label for="whitelisted" class="wf-col-sm-5 wf-control-label">Whitelisted IP addresses that bypass all rules <a href="http://docs.wordfence.com/en/Wordfence_options#Whitelisted_IP_addresses_that_bypass_all_rules" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<textarea id="whitelisted" class="wf-form-control" rows="4" name="whitelisted"><?php echo esc_html(preg_replace('/,/', "\n", $w->get('whitelisted'))); ?></textarea>
								<span class="wf-help-block">Whitelisted IPs must be separated by commas or placed on separate lines. You can specify ranges using the following format: 123.23.34.[1-50]<br/>Wordfence automatically whitelists <a href="http://en.wikipedia.org/wiki/Private_network" target="_blank" rel="noopener noreferrer">private networks</a> because these are not routable on the public Internet.</span>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="bannedURLs" class="wf-col-sm-5 wf-control-label">Immediately block IPs that access these URLs <a href="http://docs.wordfence.com/en/Wordfence_options#Immediately_block_IP.27s_that_access_these_URLs" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<textarea id="bannedURLs" class="wf-form-control" rows="4" name="bannedURLs"><?php echo esc_html(preg_replace('/,/', "\n", $w->get('bannedURLs'))); ?></textarea>
								<span class="wf-help-block">Separate multiple URLs with commas or place them on separate lines. Asterisks are wildcards, but use with care. If you see an attacker repeatedly probing your site for a known vulnerability you can use this to immediately block them. All URLs must start with a '/' without quotes and must be relative. e.g. /badURLone/, /bannedPage.html, /dont-access/this/URL/, /starts/with-*</span>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="allowed404s" class="wf-col-sm-5 wf-control-label">Whitelisted 404 URLs (one per line) <a href="http://docs.wordfence.com/en/Wordfence_options#Whitelisted_404_URLs" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<textarea id="allowed404s" class="wf-form-control" rows="4" name="allowed404s"><?php echo $w->getHTML( 'allowed404s' ); ?></textarea>
								<span class="wf-help-block">These URL patterns will be excluded from the throttling rules used to limit crawlers.</span>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="wafAlertWhitelist" class="wf-col-sm-5 wf-control-label">Ignored IP addresses for Wordfence Web Application Firewall alerting</label>
							<div class="wf-col-sm-7">
								<textarea id="wafAlertWhitelist" class="wf-form-control" rows="4" name="wafAlertWhitelist"><?php echo esc_html(preg_replace('/,/', "\n", $w->get('wafAlertWhitelist'))); ?></textarea>
								<span class="wf-help-block">Ignored IPs must be separated by commas or placed on separate lines. These addresses will be ignored from any alerts about increased attacks and can be used to ignore things like standalone website security scanners.</span>
							</div>
						</div>
						<div class="wf-form-group hidden">
							<label for="wafAlertThreshold" class="wf-col-sm-5 wf-control-label">Minimum number of blocked attacks before sending an alert</label>
							<div class="wf-col-sm-7">
								<input type="text" class="wf-form-control" name="wafAlertThreshold" id="wafAlertThreshold" value="<?php $w->f( 'wafAlertThreshold' ); ?>">
							</div>
						</div>
						<div class="wf-form-group hidden">
							<label for="wafAlertInterval" class="wf-col-sm-5 wf-control-label">Number of seconds to count the attacks over</label>
							<div class="wf-col-sm-7">
								<input type="text" class="wf-form-control" name="wafAlertInterval" id="wafAlertInterval" value="<?php $w->f( 'wafAlertInterval' ); ?>">
							</div>
						</div>
						<?php
						$options = array( //Contents should already be HTML-escaped as needed
							array(
								'id' 		=> 'other_hideWPVersion',
								'label'		=> 'Hide WordPress version <a href="http://docs.wordfence.com/en/Wordfence_options#Hide_WordPress_version" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'showAdminBarMenu',
								'label'		=> 'Show admin bar menu for administrators',
								'hidden'	=> true,
							),
							array(
								'id' 		=> 'other_blockBadPOST',
								'label'		=> 'Block IPs who send POST requests with blank User-Agent and Referer <a href="http://docs.wordfence.com/en/Wordfence_options#Block_IP.27s_who_send_POST_requests_with_blank_User-Agent_and_Referer" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'other_noAnonMemberComments',
								'label'		=> 'Hold anonymous comments using member emails for moderation <a href="http://docs.wordfence.com/en/Wordfence_options#Hold_anonymous_comments_using_member_emails_for_moderation" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'other_scanComments',
								'label'		=> 'Filter comments for malware and phishing URLs <a href="http://docs.wordfence.com/en/Wordfence_options#Filter_comments_for_malware_and_phishing_URL.27s" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'other_pwStrengthOnUpdate',
								'label'		=> 'Check password strength on profile update <a href="http://docs.wordfence.com/en/Wordfence_options#Check_password_strength_on_profile_update" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'other_WFNet',
								'label'		=> 'Participate in the Real-Time WordPress Security Network <a href="http://docs.wordfence.com/en/Wordfence_options#Participate_in_the_Real-Time_WordPress_Security_Network" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id'		=> 'other_bypassLitespeedNoabort',
								'label'		=> 'Bypass the LiteSpeed "noabort" check <a href="https://docs.wordfence.com/en/Wordfence_options#Bypass_the_LiteSpeed_noabort_check" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
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
						<?php endforeach; ?>
						<div class="wf-form-group">
							<label for="maxMem" class="wf-col-sm-5 wf-control-label">How much memory should Wordfence request when scanning <a href="http://docs.wordfence.com/en/Wordfence_options#How_much_memory_should_Wordfence_request_when_scanning" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<div class="wf-form-inline">
									<input type="text" class="wf-form-control" name="maxMem" id="maxMem" value="<?php $w->f( 'maxMem' ); ?>">
									<p class="wf-form-control-static">Megabytes</p>
								</div>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="maxExecutionTime" class="wf-col-sm-5 wf-control-label">Maximum execution time for each scan stage <a href="http://docs.wordfence.com/en/Wordfence_options#Maximum_execution_time_for_each_scan_stage" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<input type="text" class="wf-form-control" name="maxExecutionTime" id="maxExecutionTime" value="<?php $w->f( 'maxExecutionTime' ); ?>">
								<span class="wf-help-block">Blank for default. Must be greater than <?php echo intval(WORDFENCE_SCAN_MIN_EXECUTION_TIME) - 1; ?> and 10-20 or higher is recommended for most servers.</span>
							</div>
						</div>
						<div class="wf-form-group">
							<label for="actUpdateInterval" class="wf-col-sm-5 wf-control-label">Update interval in seconds (2 is default) <a href="http://docs.wordfence.com/en/Wordfence_options#Update_interval_in_seconds" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
							<div class="wf-col-sm-7">
								<input type="text" class="wf-form-control" name="actUpdateInterval" id="actUpdateInterval" value="<?php $w->f( 'actUpdateInterval' ); ?>">
								<span class="wf-help-block">Setting higher will reduce browser traffic but slow scan starts, live traffic &amp; status updates.</span>
							</div>
						</div>
						<?php
						$options = array( //Contents should already be HTML-escaped as needed
							array(
								'id'		=> 'liveActivityPauseEnabled',
								'label'		=> 'Pause live updates when window loses focus <a href="http://docs.wordfence.com/en/Wordfence_options#Pause_live_updates_when_window_loses_focus" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'deleteTablesOnDeact',
								'label'		=> 'Delete Wordfence tables and data on deactivation <a href="http://docs.wordfence.com/en/Wordfence_options#Delete_Wordfence_tables_and_data_on_deactivation.3F" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'disableCookies',
								'label'		=> 'Disable Wordfence Cookies <a href="http://docs.wordfence.com/en/Wordfence_options#Disable_Wordfence_Cookies" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
								'help'		=> 'When enabled, all visits in live traffic will appear to be new visits.',
							),
							array(
								'id' 		=> 'disableCodeExecutionUploads',
								'label'		=> 'Disable Code Execution for Uploads directory <a href="http://docs.wordfence.com/en/Wordfence_options#Disable_Code_Execution_for_Uploads_directory" target="_blank" rel="noopener noreferrer" class="wfhelp"></a>',
							),
							array(
								'id' 		=> 'ajaxWatcherDisabled_front',
								'label'		=> 'Monitor Front-end Background Requests for False Positives',
								'hidden'	=> true,
							),
							array(
								'id' 		=> 'ajaxWatcherDisabled_admin',
								'label'		=> 'Monitor Admin Panel Background Requests for False Positives',
								'hidden'	=> true,
							),
							array(
								'id' 		=> 'disableWAFIPBlocking',
								'label'		=> 'Delay IP and Country blocking until after WordPress and plugins have loaded (only process firewall rules early)',
								'hidden'	=> true,
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
						<?php endforeach; ?>

						<div class="wfMarker" id="wfMarkerExportOptions"></div>
						<h3>Exporting and Importing Wordfence Settings <a href="http://docs.wordfence.com/en/Wordfence_options#Exporting_and_Importing_Wordfence_Settings" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h3>
						<div class="wf-form-group">
							<label for="exportSettingsBut" class="wf-col-sm-5 wf-control-label">Export this site's Wordfence settings for import on another site</label>
							<div class="wf-col-sm-7">
								<input type="button" class="wf-btn wf-btn-sm wf-btn-default" id="exportSettingsBut" value="Export Wordfence Settings" onclick="WFAD.exportSettings(); return false;">
							</div>
						</div>
						<div class="wf-form-group">
							<label for="importToken" class="wf-col-sm-5 wf-control-label">Import Wordfence settings from another site using a token</label>
							<div class="wf-col-sm-7">
								<div class="wf-form-inline">
									<input type="text" class="wf-form-control" id="importToken">
									<input type="button" class="wf-btn wf-btn-sm wf-btn-default" name="importSettingsButton" value="Import Settings" onclick="WFAD.importSettings(jQuery('#importToken').val()); return false;">
								</div>
							</div>
						</div>

						<div class="wf-form-group">
							<div class="wf-col-sm-7 wf-col-sm-offset-5">
								<a class="wf-btn wf-btn-primary wf-btn-callout" href="#" onclick="WFAD.saveConfig(); return false;">Save Options</a> <div class="wfAjax24"></div><span class="wfSavedMsg">&nbsp;Your changes have been saved!</span>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script type="application/javascript">
	(function($) {
		$(document).on('ready', function() {
			if (window.location.hash) {
				var hashes = window.location.hash.split('#');
				var animatedOnce = false;
				for (var i = 0; i < hashes.length; i++) {
					var hash = hashes[i];
					if (/^focus-/.test(hash)) {
						var elementID = hash.replace('focus-', '');
						var wrapper = $('#' + elementID).closest('.wf-form-group');
						wrapper.addClass('wf-focus');
						
						if (!animatedOnce) {
							$('html, body').animate({
								scrollTop: wrapper.offset().top - 100
							}, 1000);
							animatedOnce = true;
						}
					}
				}
			}
		});
	})(jQuery);
</script>
<script type="text/x-jquery-template" id="wfContentBasicOptions">
	<div>
		<h3>Basic Options</h3>

		<p>
			Using Wordfence is simple. Install Wordfence, enter an email address on this page to send alerts to, and
			then do your first scan and work through the security alerts we provide.
			We give you a few basic security levels to choose from, depending on your needs. Remember to hit the "Save"
			button to save any changes you make.
		</p>

		<p>
			If you use the free edition of Wordfence, you don't need to worry about entering an API key in the "API Key"
			field above. One is automatically created for you. If you choose to <a
				href="https://www.wordfence.com/gnl1optUpg1/wordfence-signup/" target="_blank" rel="noopener noreferrer">upgrade to Wordfence Premium
				edition</a>, you will receive an API key. You will need to copy and paste that key into the "API Key"
			field above and hit "Save" to activate your key.
		</p>
	</div>
</script>
<script type="text/x-jquery-template" id="wfContentLiveTrafficOptions">
	<div>
		<h3>Live Traffic Options</h3>

		<p>
			These options let you ignore certain types of visitors, based on their level of access, usernames, IP
			address or browser type.
			If you run a very high traffic website where it is not feasible to see your visitors in real-time, simply
			un-check the live traffic option and nothing will be written to the Wordfence tracking tables.
		</p>
	</div>
</script>
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
<script type="text/x-jquery-template" id="wfContentFirewallRules">
	<div>
		<h3>Rate Limiting Rules</h3>

		<p>
			<strong>NOTE:</strong> Before modifying these rules, make sure you have access to the email address
			associated with this site's administrator account. If you accidentally lock yourself out, you will be given
			the option
			to enter that email address and receive an "unlock email" which will allow you to regain access.
		</p>

		<p>
			<strong>Tips:</strong>

		<p>&#8226; If you choose to limit the rate at which your site can be accessed, you need to customize the
			settings for your site.</p>

		<p>&#8226; If your users usually skip quickly between pages, you should set the values for human visitors to be
			high.</p>

		<p>&#8226; If you are aggressively crawled by non-Google crawlers like Baidu, you should set the page view limit
			for crawlers to a high value.</p>

		<p>&#8226; If you are currently under attack and want to aggressively protect your site or your content, you can
			set low values for most options.</p>

		<p>&#8226; In general we recommend you don't block fake Google crawlers unless you have a specific problem with
			someone stealing your content.</p>

		<p>
			Remember that as long as you have your administrator email set correctly in this site's user administration,
			and you are able to receive email at that address,
			you will be able to regain access if you are accidentally locked out because your rules are too strict.
		</p>
	</div>
</script>
<script type="text/x-jquery-template" id="wfContentLoginSecurity">
	<div>
		<h3>Login Security</h3>

		<p>
			We have found that real brute force login attacks make hundreds or thousands of requests trying to guess
			passwords or user login names.
			So in general you can leave the number of failed logins before a user is locked out as a fairly high number.
			We have found that blocking after 20 failed attempts is sufficient for most sites and it allows your real
			site users enough
			attempts to guess their forgotten passwords without getting locked out.
		</p>
	</div>
</script>
<script type="text/x-jquery-template" id="wfContentOtherOptions">
	<div>
		<h3>Other Options</h3>

		<p>
			We have worked hard to make Wordfence memory efficient and much of the heavy lifting is done for your site
			by our cloud scanning servers in our Seattle data center.
			On most sites Wordfence will only use about 8 megabytes of additional memory when doing a scan, even if you
			have large files or a large number of files.
			You should not have to adjust the maximum memory that Wordfence can use, but we have provided the option.
			Remember that this does not affect the actual memory usage of Wordfence, simply the maximum Wordfence can
			use if it needs to.
		</p>

		<p>
			You may find debugging mode helpful if Wordfence is not able to start a scan on your site or
			if you are experiencing some other problem. Enable debugging by checking the box, save your options
			and then try to do a scan. You will notice a lot more output on the "Scan" page.
		</p>

		<p>
			If you decide to permanently remove Wordfence, you can choose the option to delete all data on deactivation.
			We also provide helpful links at the bottom of this page which lets you see your systems configuration and
			test how
			much memory your host really allows you to use.
		</p>

		<p>
			Thanks for completing this tour and I'm very happy to have you as our newest Wordfence customer. Don't
			forget to <a href="http://wordpress.org/extend/plugins/wordfence/" target="_blank" rel="noopener noreferrer">rate us 5 stars if you
				love Wordfence</a>.<br/>
			<br/>
			<strong>Mark Maunder</strong> - Wordfence Creator.
		</p>
	</div>
</script>

