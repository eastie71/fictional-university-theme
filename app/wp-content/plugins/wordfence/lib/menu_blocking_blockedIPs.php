<div class="wordfenceHelpLink"><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer"><?php echo $helpLabel; ?></a></div>
<div>
	<div class="wordfenceModeElem" id="wordfenceMode_blockedIPs"></div>
	<?php if(! wfConfig::get('firewallEnabled')){ ?><div style="color: #F00; font-weight: bold;">Rate limiting rules and advanced blocking are disabled. You can enable it on the <a href="admin.php?page=WordfenceSecOpt">Wordfence Options page</a> at the top.</div><?php } ?>
	<p><a href="#" onclick="WFAD.clearAllBlocked('blocked'); return false;">Clear all blocked<span class="wf-hidden-xs"> IP addresses</span></a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="#" onclick="WFAD.clearAllBlocked('locked'); return false;">Clear all locked out<span class="wf-hidden-xs"> IP addresses</span></a></p>
	<div class="wf-form-inline">
		<div class="wf-form-group">
			<label class="wf-plain" for="wfManualBlock">You can manually (and permanently) block an IP by entering the address here:</label>
			<input class="wf-form-control" type="text" id="wfManualBlock" size="20" maxlength="40" value="" onkeydown="if(event.keyCode == 13){ WFAD.blockIPTwo(jQuery('#wfManualBlock').val(), 'Manual block by administrator', true); return false; }">
		</div>
		<input class="wf-btn wf-btn-default" type="button" name="but1" value="Manually block IP" onclick="WFAD.blockIPTwo(jQuery('#wfManualBlock').val(), 'Manual block by administrator', true); return false;">
	</div>
	<div class="wf-add-top">
		<div id="wfTabs">
			<a href="#" class="wfTab1 wfTabSwitch selected" onclick="wordfenceAdmin.switchTab(this, 'wfTab1', 'wfDataPanel', 'wfActivity_blockedIPs', function(){ WFAD.staticTabChanged(); }); return false;"><span class="wf-visible-xs">Blocked</span><span class="wf-hidden-xs">IPs blocked from accessing the site</span></a>
			<a href="#" class="wfTab1 wfTabSwitch" onclick="wordfenceAdmin.switchTab(this, 'wfTab1', 'wfDataPanel', 'wfActivity_lockedOutIPs', function(){ WFAD.staticTabChanged(); }); return false;"><span class="wf-visible-xs">Locked Out</span><span class="wf-hidden-xs">IPs locked out from login</span></a>
			<a href="#" class="wfTab1 wfTabSwitch" onclick="wordfenceAdmin.switchTab(this, 'wfTab1', 'wfDataPanel', 'wfActivity_throttledIPs', function(){ WFAD.staticTabChanged(); }); return false;"><span class="wf-visible-xs">Throttled</span><span class="wf-hidden-xs">IPs throttled for accessing the site too frequently</span></a>
		</div>
		<div class="wfTabsContainer">
			<div id="wfActivity_blockedIPs" class="wfDataPanel"><div class="wfLoadingWhite32"></div></div>
			<div id="wfActivity_lockedOutIPs" class="wfDataPanel" style="display: none;"><div class="wfLoadingWhite32"></div></div>
			<div id="wfActivity_throttledIPs" class="wfDataPanel" style="display: none;"><div class="wfLoadingWhite32"></div></div>
		</div>
	</div>
</div>

<script type="text/x-jquery-template" id="wfThrottledIPsWrapperTmpl">
	<div>
		<div style="border-bottom: 1px solid #CCC; padding-bottom: 10px; margin-bottom: 10px;">
			<table border="0" style="width: 100%" id="wfThrottledIPsWrapper">
			</table>
		</div>
		<!--<p><a class="button" href="#" onclick="WFAD.permanentlyBlockAllIPs('throttled'); return false;">Permanently block all throttled IP addresses</a></p>-->
	</div>
</script>

<script type="text/x-jquery-template" id="wfThrottledIPsTmpl">
{{each(idx, elem) results}}
<tr class="throttledIPsRecord">
	<td style="vertical-align: top;">
		<div>
			{{if loc}}
				<img src="<?php echo wfUtils::getBaseURL() . 'images/flags/'; ?>${loc.countryCode.toLowerCase()}.png" width="16" height="11" alt="${loc.countryName}" title="${loc.countryName}" class="wfFlag" />
				<a href="http://maps.google.com/maps?q=${loc.lat},${loc.lon}&z=6" target="_blank" rel="noopener noreferrer">{{if loc.city}}${loc.city}, {{/if}}${loc.countryName}</a>
			{{else}}
				An unknown location at IP <a href="${WFAD.makeIPTrafLink(IP)}" target="_blank" rel="noopener noreferrer">${IP}</a>
			{{/if}}
		</div>
		<div>
			<strong>IP:</strong>&nbsp;<a href="${WFAD.makeIPTrafLink(IP)}" target="_blank" rel="noopener noreferrer">${IP}</a>
		</div>
		<div>
			<strong>Reason:</strong>&nbsp;${lastReason}
		</div>
		<div>
			<span class="wfReverseLookup"><span style="display:none;">${IP}</span></span>
		</div>
		<div>
			<span>Throttled <strong>${timesThrottled}</strong> times starting <strong>${startTimeAgo} ago</strong> and ending <strong>${endTimeAgo} ago</strong>.</span>
		</div>
	</td>
</tr>
{{/each}}
</script>

<script type="text/x-jquery-template" id="wfLockedOutIPsWrapperTmpl">
	<div>
		<p><a class="wf-btn wf-btn-default" href="#" onclick="WFAD.permanentlyBlockAllIPs('lockedOut'); return false;"><span class="wf-visible-xs">Block All Temporary</span><span class="wf-hidden-xs">Permanently block all locked out IP addresses</span></a></p>
		<div style="border-top: 1px solid #CCC; padding-top: 10px; margin-top: 10px;">
			<table border="0" style="width: 100%" id="wfLockedOutIPsWrapper">
			</table>
		</div>
		<p><a class="wf-btn wf-btn-sm wf-btn-default wfLoadMoreButton" href="#">Load More</a></p>
	</div>
</script>

<script type="text/x-jquery-template" id="wfLockedOutIPsTmpl">
{{each(idx, elem) results}}
<tr class="lockedOutIPsRecord">
	<td>
		<div>
			{{if loc}}
				<img src="<?php echo wfUtils::getBaseURL() . 'images/flags/'; ?>${loc.countryCode.toLowerCase()}.png" width="16" height="11" alt="${loc.countryName}" title="${loc.countryName}" class="wfFlag" />
				<a href="http://maps.google.com/maps?q=${loc.lat},${loc.lon}&z=6" target="_blank" rel="noopener noreferrer">{{if loc.city}}${loc.city}, {{/if}}${loc.countryName}</a>
			{{else}}
				An unknown location at IP <a href="${WFAD.makeIPTrafLink(IP)}" target="_blank" rel="noopener noreferrer">${IP}</a>
			{{/if}}
		</div>
		<div>
			<strong>IP:</strong>&nbsp;<a href="${WFAD.makeIPTrafLink(IP)}" target="_blank" rel="noopener noreferrer">${IP}</a> [<a href="#" onclick="WFAD.unlockOutIP('${IP}'); return false;">unlock</a>]
		</div>
		<div>
			<strong>Reason:</strong>&nbsp;${reason}
		</div>
		<div>
			<span class="wfReverseLookup"><span style="display:none;">${IP}</span></span>
		</div>
		<div>
			{{if lastAttemptAgo}}
				<span class="wfTimeAgo">Last blocked attempt to sign-in or use the forgot password form was <span class="wfTimeAgo-timestamp" data-timestamp="${lastAttempt}">${lastAttemptAgo} ago</span>.</span>
			{{else}}
				<span class="wfTimeAgo">No attempts have been made to sign-in or use the forgot password form since this IP was locked out.</span>
			{{/if}}
		</div>
	</td>
	<td style="color: #999;">
		<ul>
		<li>${blockedHits} attempts have been blocked</li>
		<li>Will be unlocked in ${blockedForAgo}</li>
		</ul>
	</td>
</tr>
{{/each}}
</script>

<script type="text/x-jquery-template" id="wfBlockedIPsWrapperTmpl">
	<div>
		<p><a class="wf-btn wf-btn-default" href="#" onclick="WFAD.permanentlyBlockAllIPs('blocked'); return false;"><span class="wf-visible-xs">Block All Temporary</span><span class="wf-hidden-xs">Permanently block all temporarily blocked IP addresses</span></a></p>
		<p><a class="wf-btn wf-btn-default" href="<?php echo wfUtils::siteURLRelative(); ?>?_wfsf=blockedIPs&amp;nonce=<?php echo wp_create_nonce('wp-ajax'); ?>"><span class="wf-visible-xs">Export</span><span class="wf-hidden-xs">Export all blocked IP addresses</span></a></p>
		<div style="border-top: 1px solid #CCC; padding-top: 10px; margin-top: 10px;">
			<table border="0" style="width: 100%" id="wfBlockedIPsWrapper">
			</table>
		</div>
		<p><a class="wf-btn wf-btn-sm wf-btn-default wfLoadMoreButton" href="#">Load More</a></p>
	</div>
</script>

<script type="text/x-jquery-template" id="wfBlockedIPsTmpl">
{{each(idx, elem) results}}
	<tr class="blockedIPsRecord">
		<td>
			<div>
				{{if loc}}
					<img src="<?php echo wfUtils::getBaseURL() . 'images/flags/'; ?>${loc.countryCode.toLowerCase()}.png" width="16" height="11" alt="${loc.countryName}" title="${loc.countryName}" class="wfFlag" />
					<a href="http://maps.google.com/maps?q=${loc.lat},${loc.lon}&z=6" target="_blank" rel="noopener noreferrer">{{if loc.city}}${loc.city}, {{/if}}${loc.countryName}</a>
				{{else}}
					An unknown location at IP <a href="${WFAD.makeIPTrafLink(IP)}" target="_blank" rel="noopener noreferrer">${IP}</a>
				{{/if}}
			</div>
			<div>
				<strong>IP:</strong>&nbsp;<a href="${WFAD.makeIPTrafLink(IP)}" target="_blank" rel="noopener noreferrer">${IP}</a> [<a href="#" onclick="WFAD.unblockIPTwo('${IP}'); return false;">unblock</a>]
				{{if permanent == '1'}}
					[<span style="color: #F00;">permanently blocked</span>]
				{{else}}&nbsp;&nbsp;[<a href="#" onclick="WFAD.permBlockIP('${IP}'); return false;">make permanent</a>]{{/if}}
			</div>
			<div>
				<strong>Reason:</strong>&nbsp;${reason}
			</div>
			<div>
				<span class="wfReverseLookup"><span style="display:none;">${IP}</span></span>
			</div>
			<div>
				{{if lastAttemptAgo}}
					<span class="wfTimeAgo">Last blocked attempt to access the site was <span class="wfTimeAgo-timestamp" data-timestamp="${lastAttempt}">${lastAttemptAgo} ago</span>.</span>
				{{else}}
					<span class="wfTimeAgo">No attempts have been made to access the site since this IP was blocked.</span>
				{{/if}}
			</div>
			<div>
				{{if lastHitAgo}}
					<span class="wfTimeAgo">Last site access before this IP was blocked was <span class="wfTimeAgo-timestamp" data-timestamp="${lastHit}">${lastHitAgo} ago</span></span>
				{{/if}}
			</div>
		</td>
		<td style="color: #999;">
			<ul>
			<li>${totalHits} hits before blocked</li>
			<li>${blockedHits} blocked hits</li>
			<li>
				{{if permanent == '1'}}Permanently blocked{{else}}
				Will be unblocked in ${blockedForAgo}{{/if}}
			</li>
			</ul>
		</td>
	</tr>
{{/each}}
</script>

<script type="text/x-jquery-template" id="wfWelcomeContent4">
<div>
<h3>How to manage Blocked IP addresses</h3>
<strong><p>Block IPs temporarily or permanently</p></strong>
<p>
	When you block an IP address, it will appear here with some additional information. 
	You will be able to see the geographic location of the IP, how many hits occured before
	it was blocked and how many attempts it has made on your site since it was blocked.
</p>
<p>
	You can also see how long until a blocked IP will be automatically unblocked. 
	You can also manually add IP addresses on this page to be blocked.
</p>
<p>
	You also have the option to see IP addresses who have been locked out from the login system for too many login attempts. 
	And finally, when the firewall "throttles" someone's access for accessing the site too quickly, you can 
	see which IP addresses have been throttled.
</p>
</div>
</script>
