<?php
$w = new wfConfig();
?>
<div class="wordfenceHelpLink"><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer"><?php echo $helpLabel; ?></a></div>
<div class="wf-add-top">
	<form id="wfConfigForm-bruteForce" class="wf-form-horizontal">
		<div class="wf-form-group">
			<label for="blockedTime" class="wf-col-sm-5 wf-control-label">Enforce strong passwords <a href="http://docs.wordfence.com/en/Wordfence_options#Enforce_strong_passwords.3F" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
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
		<div class="wf-form-group">
			<div class="wf-col-sm-7 wf-col-sm-offset-5">
				<a class="wf-btn wf-btn-primary wf-btn-callout" href="#" onclick="WFAD.savePartialConfig('#wfConfigForm-bruteForce'); return false;">Save Options</a> <div class="wfAjax24"></div><span class="wfSavedMsg">&nbsp;Your changes have been saved!</span>
			</div>
		</div>
	</form>
</div>