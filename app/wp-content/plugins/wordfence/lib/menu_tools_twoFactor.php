<div class="wordfenceHelpLink"><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer"><?php echo $helpLabel; ?></a></div>
<div>
	<div class="wordfenceModeElem" id="wordfenceMode_twoFactor"></div>
	<?php if(! wfConfig::get('isPaid')){ ?>
	<div class="wf-premium-callout wf-add-bottom">
		<h3>Cellphone Sign-in is only available to Premium Members</h3>
		<p>Our Cellphone Sign-in uses a technique called "Two Factor Authentication" which is used by banks, government
			agencies and military world-wide as one of the most secure forms of remote system authentication. It's now
			available from Wordfence for your WordPress website. We recommend you enable Cellphone Sign-in for all
			Administrator level accounts.</p>

		<p>Upgrade today:</p>
		<ul>
			<li>Receive real-time Firewall and Scan engine rule updates for protection as threats emerge</li>
			<li>Other advanced features like IP reputation monitoring, an advanced comment spam filter, advanced
				scanning options and country blocking give you the best protection available
			</li>
			<li>Access to Premium Support</li>
			<li>Discounts of up to 90% available for multiyear and multi-license purchases</li>
		</ul>

		<p class="center"><a class="wf-btn wf-btn-primary wf-btn-callout" href="https://www.wordfence.com/gnl1twoFac1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Get Premium</a></p>
	</div>
<?php } ?>
	
	<?php
	if (!wfConfig::get('loginSecurityEnabled')) {
		$url = network_admin_url('admin.php?page=WordfenceSecOpt');
	?>
		<div class="notice notice-error"><p>The login security option is currently disabled. This will prevent your website from enforcing two factor authentication for any users configured below. Visit the <a href="<?php echo esc_url($url); ?>">Options page</a> to enable login security.</p></div> 
	<?php
	}
	?>
	
	<h2>Enable Cellphone Sign-in</h2>
	<p><em>Our Cellphone Sign-in uses a technique called "Two Factor Authentication" which is used by banks, government agencies and military world-wide as one of the most secure forms of remote system authentication. We recommend you enable Cellphone Sign-in for all Administrator level accounts.</em></p>
	<div class="wf-form wf-form-twofactor">
		<div class="wf-form-group">
			<label for="wfUsername">Username<span class="wf-hidden-xs"> to enable Cellphone Sign-in for</span></label>
			<input type="text" id="wfUsername" class="wf-form-control" value="" size="20">
		</div>
		<div class="wf-form-group">
			<label for="wf2faMode"><span class="wf-visible-xs">Mode</span><span class="wf-hidden-xs">Code generation mode</span></label>
		</div>
		<div class="wf-radio">
			<label>
				<input type="radio" name="wf2faMode" id="wf2faMode-authenticator" value="authenticator" checked>
				Use authenticator app
			</label>
		</div>
		<div class="wf-radio">
			<label>
				<input type="radio" name="wf2faMode" id="wf2faMode-phone" value="phone">
				Send code to a phone number:
			</label>
			<div class="wf-radio-offset"><input type="text" id="wfPhone" value="" size="20" disabled><br><em>Format: +1-123-555-5034</em></div>
		</div>
		<div class="wf-form-group">
			<input type="button" class="wf-btn wf-btn-primary" value="Enable Cellphone Sign-in" onclick="WFAD.addTwoFactor(jQuery('#wfUsername').val(), jQuery('#wfPhone').val(), jQuery('input[name=wf2faMode]:checked').val());">
		</div>
	</div>
	<div style="height: 20px;">
		<div id="wfTwoFacMsg" style="color: #F00;">
		&nbsp;
		</div>
	</div>
	
	<h2>Cellphone Sign-in Users</h2>
	<div id="wfTwoFacUsers"></div>
	
	<br>
	
	<h2>Security Options</h2>
	<table class="wfConfigForm">
		<tr>
			<td><input type="checkbox" class="twoFactorOption" id="loginSec_requireAdminTwoFactor" name="loginSec_requireAdminTwoFactor"<?php echo wfConfig::get('loginSec_requireAdminTwoFactor') ? ' checked' : ''; ?>></td>
			<th>Require Cellphone Sign-in for all Administrators<a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><br>
				<em>This setting requires at least one administrator to have Cellphone Sign-in enabled. On multisite, this option applies only to super admins.</em></th>
		</tr>
		<tr>
			<?php
			$allowSeparatePrompt = ini_get('output_buffering') > 0;
			?>
			<td><input type="checkbox" class="twoFactorOption" id="loginSec_enableSeparateTwoFactor" name="loginSec_enableSeparateTwoFactor"<?php echo wfConfig::get('loginSec_enableSeparateTwoFactor') ? ' checked' : ''; echo ($allowSeparatePrompt ? '' : ' disabled'); ?>></td>
			<th>Enable Separate Prompt for Two Factor Code<a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><br>
				<em>This setting changes the behavior for obtaining the two factor authentication code from using the password field to showing a separate prompt. If your theme overrides the default login page, you may not be able to use this option.</em>
			<?php echo ($allowSeparatePrompt ? '' : '<br><strong>This setting will be ignored because the PHP configuration option <code>output_buffering</code> is off.</strong>'); ?></th>
		</tr>
	</table>
	
	<script type="text/javascript">
		jQuery('.twoFactorOption').on('click', function() {
			WFAD.updateConfig(jQuery(this).attr('name'), jQuery(this).is(':checked') ? 1 : 0, function() {});
		});
		
		jQuery('input[name=wf2faMode]').on('change', function() {
			var selectedMode = jQuery('input[name=wf2faMode]:checked').val();
			jQuery('#wfPhone').prop('disabled', selectedMode != 'phone');
		});
	</script>
</div>

<script type="text/x-jquery-template" id="wfTwoFacUserTmpl">
	<table class="wf-table wf-table-striped wf-table-bordered wf-table-twofactor"> 
		<thead>
			<tr>
				<th></th>
				<th>User</th>
				<th>Mode</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
		{{each(idx, user) users}}
			<tr id="twoFactorUser-${user.userID}">
				<td style="white-space: nowrap; text-align: center;" class="wf-twofactor-delete"><a href="#" onclick="WFAD.delTwoFac('${user.userID}'); return false;"><i class="wf-ion-trash-a"></i></a></td>
				<td style="white-space: nowrap;">${user.username}</td>
				{{if user.mode == 'phone'}}
				<td style="white-space: nowrap;">Phone (${user.phone})</td>
				{{else}}
				<td style="white-space: nowrap;">Authenticator</td>
				{{/if}}
				<td style="white-space: nowrap;"> 
					{{if user.status == 'activated'}}
						<span style="color: #0A0;">Cellphone Sign-in Enabled</span>
					{{else}}
					<div class="wf-form-inline">
						<div class="wf-form-group">
							<label class="wf-plain wf-hidden-xs" style="margin: 0;" for="wfActivate-${user.userID}">Enter activation code:</label> <input class="wf-form-control" type="text" id="wfActivate-${user.userID}" size="6" placeholder="Code">
						</div>
						<input class="wf-btn wf-btn-default" type="button" value="Activate" onclick="WFAD.twoFacActivate('${user.userID}', jQuery('#wfActivate-${user.userID}').val());">
					</div>
					{{/if}}
				</td>
			</tr>
		{{/each}}
		{{if (users.length == 0)}}
		<tr id="twoFactorUser-none">
			<td colspan="4">No users currently have cellphone sign-in enabled.</td>
		</tr>
		{{/if}}
		</tbody>
	</table>
</script>
<script type="text/x-jquery-template" id="wfWelcomeTwoFactor">
<div>
<h3>Secure Sign-in using your Cellphone</h3>
<strong><p>Want to permanently block all brute-force hacks?</p></strong>
<p>
	The premium version of Wordfence includes Cellphone Sign-in, also called Two Factor Authentication in the security industry.
	When you enable Cellphone Sign-in on a member's account, they need to complete a 
	two step process to sign in. First they enter their username and password 
	as usual to sign-into your WordPress website. Then they're told
	that a code was sent to their phone. Once they get the code, they sign
	into your site again and this time they add a space and the code to the end of their password.
</p>
<p>
	This technique is called Two Factor Authentication because it relies on two factors: 
	Something you know (your password) and something you have (your phone).
	It is used by banks and military world-wide as a way to dramatically increase
	security.
</p>
<p>
<?php
if(wfConfig::get('isPaid')){
?>
	You have upgraded to the premium version of Wordfence and have full access
	to this feature along with our other premium features.
<?php
} else {
?>
	If you would like access to this premium feature, please 
	<a href="https://www.wordfence.com/gnl1twoFac2/wordfence-signup/" target="_blank" rel="noopener noreferrer">upgrade to our premium version</a>.
<?php
}
?>
</p>
</div>
</script>
