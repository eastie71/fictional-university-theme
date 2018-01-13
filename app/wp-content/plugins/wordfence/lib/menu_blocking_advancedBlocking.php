<div class="wordfenceHelpLink"><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer"><?php echo $helpLabel; ?></a></div>
<div>
	<div class="wordfenceModeElem" id="wordfenceMode_rangeBlocking"></div>
	<?php if (!wfConfig::get('firewallEnabled')) { ?>
	<div class="wf-notice"><p><strong>Rate limiting rules and advanced blocking are disabled.</strong> You can enable it on the <a href="admin.php?page=WordfenceSecOpt">Wordfence Options page</a> at the top.</p></div>
	<?php } ?>
	<div class="wf-form wf-form-horizontal wf-add-top">
		<div class="wf-form-group">
			<label for="ipRange" class="wf-col-sm-2 wf-control-label">IP address range</label>
			<div class="wf-col-sm-6">
				<input type="text" id="ipRange" name="ipRange" class="wf-form-control" value="<?php
				if( isset( $_GET['wfBlockRange'] ) && preg_match('/^[\da-f\.\s\t\-:]+$/i', $_GET['wfBlockRange']) ){ echo wp_kses($_GET['wfBlockRange'], array()); }
				?>" onkeyup="WFAD.calcRangeTotal();">
				<span class="wf-help-block" id="wfShowRangeTotal"></span>
				<span class="wf-help-block">Examples: 192.168.200.200 - 192.168.200.220</span>
			</div>
		</div>
		<div class="wf-form-group">
			<label for="hostname" class="wf-col-sm-2 wf-control-label">Hostname</label>
			<div class="wf-col-sm-6">
				<input type="text" id="hostname" name="hostname" class="wf-form-control" value="<?php
				if( isset( $_GET['wfBlockHostname'] ) ){ echo esc_attr($_GET['wfBlockHostname']); }
				?>">
				<span class="wf-help-block">Examples: *.amazonaws.com, *.linode.com</span>
				<span class="wf-help-block">Using this setting will make a DNS query per unique IP address (per visitor), and can add additional load. High traffic<br> sites may not want to use this feature.</span>
			</div>
		</div>
		<div class="wf-form-group">
			<label for="uaRange" class="wf-col-sm-2 wf-control-label">User-Agent (browser) that matches</label>
			<div class="wf-col-sm-6">
				<input type="text" id="uaRange" name="uaRange" class="wf-form-control">
				<span class="wf-help-block">(Case insensitive)</span>
				<span class="wf-help-block">Examples: *badRobot*, AnotherBadRobot*, *someBrowserSuffix</span>
			</div>
		</div>
		<div class="wf-form-group">
			<label for="wfreferer" class="wf-col-sm-2 wf-control-label">Referer (website visitor arrived from) that matches</label>
			<div class="wf-col-sm-6">
				<input type="text" id="wfreferer" name="wfreferer" class="wf-form-control">
				<span class="wf-help-block">(Case insensitive)</span>
				<span class="wf-help-block">Examples: *badWebsite*, AnotherBadWebsite*, *someWebsiteSuffix</span>
			</div>
		</div>
		<div class="wf-form-group">
			<label for="wfReason" class="wf-col-sm-2 wf-control-label">Enter a reason you're blocking this visitor pattern</label>
			<div class="wf-col-sm-6">
				<input type="text" id="wfReason" name="wfReason" class="wf-form-control">
				<span class="wf-help-block">(Case insensitive)</span>
				<span class="wf-help-block">Why a reason: The reason you specify above is for your own record keeping.</span>
			</div>
		</div>
		<div class="wf-form-group">
			<div class="wf-col-sm-6 wf-col-sm-offset-2">
				<a class="wf-btn wf-btn-primary" href="#" onclick="WFAD.blockIPUARange(jQuery('#ipRange').val(), jQuery('#hostname').val(), jQuery('#uaRange').val(), jQuery('#wfreferer').val(), jQuery('#wfReason').val()); return false;">Block Visitors Matching this Pattern</a>
			</div>
		</div>
	</div>
	<p>
		<h2>Current list of ranges and patterns you've blocked</h2>
		<div id="currentBlocks"></div>
	</p>
</div>
<script type="text/x-jquery-template" id="wfBlockedRangesTmpl">
<div>
<div style="padding-bottom: 10px; margin-bottom: 10px;">
<table border="0" style="width: 100%" class="block-ranges-table">
{{each(idx, elem) results}}
<tr><td>
	{{if patternDisabled}}
	<div style="width: 500px; margin-top: 20px;">
		<span style="color: #F00;">Pattern Below has been DISABLED:</span>
	</div>
	<div style="color: #AAA;">
	{{/if}}
	<div>
		<strong>IP Range:</strong>&nbsp;<span class="wf-split-word-xs">${ipPattern}</span>
	</div>
	<div>
		<strong>Hostname:</strong>&nbsp;<span class="wf-split-word-xs">${hostnamePattern}</span>
	</div>
	<div>
		<strong>Browser Pattern:</strong>&nbsp;<span class="wf-split-word-xs">${browserPattern}</span>
	</div>
	<div>
		<strong>Source website:</strong>&nbsp;<span class="wf-split-word-xs">${refererPattern}</span>
	</div>
	<div>
		<strong>Reason:</strong>&nbsp;${reason}
	</div>
	<div><a href="#" onclick="WFAD.unblockRange('${id}'); return false;">Delete this blocking pattern</a></div>
	{{if patternDisabled}}
	</div>
	{{/if}}
</td>
<td style="color: #999;">
	<ul>
	<li>${totalBlocked} blocked hits</li>
	{{if lastBlockedAgo}}
	<li>Last blocked: ${lastBlockedAgo}</li>
	{{/if}}
	</ul>
</td></tr>
{{/each}}
</table>
</div>
</div>
</script>
<script type="text/x-jquery-template" id="wfWelcomeContentRangeBlocking">
<div>
<h3>Block Networks &amp; Browsers</h3>
<strong><p>Easily block advanced attacks</p></strong>
<p>
	Advanced Blocking is a new feature in Wordfence that lets you block whole networks and certain types of web browsers.
	You'll sometimes find a smart attacker will change their IP address frequently to make it harder to identify and block
	the attack. Usually those attackers stick to a certain network or IP address range. 
	Wordfence lets you block entire networks using Advanced blocking to easily defeat advanced attacks.
</p>
<p>
	You may also find an attacker that is identifying themselves as a certain kind of web browser that your 
	normal visitors don't use. You can use our User-Agent or Browser ID blocking feature to easily block
	attacks like this.
</p>
<p>
	You can also block any combination of network address range and User-Agent by specifying both in Wordfence Advanced Blocking.
	As always we keep track of how many attacks have been blocked and when the last attack occured so that you know
	when it's safe to remove the blocking rule. 
</p>
</div>
</script>
