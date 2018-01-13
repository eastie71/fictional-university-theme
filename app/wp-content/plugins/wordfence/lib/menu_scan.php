<?php
$sigUpdateTime = wfConfig::get('signatureUpdateTime');
?>
<?php if (wfConfig::get('liveActivityPauseEnabled')): ?>
<div id="wfLiveTrafficOverlayAnchor"></div>
<div id="wfLiveTrafficDisabledMessage">
	<h2>Live Updates Paused<br /><small>Click inside window to resume</small></h2>
</div>
<?php endif; ?>
<div class="wrap wordfence">
	<div class="wf-container-fluid">
	
	<?php
	$nonce = filter_input(INPUT_GET, 'nonce', FILTER_SANITIZE_STRING);
	if (!empty($promptForCredentials) && !empty($wpFilesystemActionCallback) && is_callable($wpFilesystemActionCallback)):
		if (wp_verify_nonce($nonce, 'wp-ajax')) {
			$relaxedOwnership = true;
			$homePath = get_home_path();

			if (!wordfence::requestFilesystemCredentials($filesystemCredentialsAdminURL, $homePath, $relaxedOwnership, true)) {
				echo '</div>';
				return;
			}

			call_user_func_array($wpFilesystemActionCallback,
				!empty($wpFilesystemActionCallbackArgs) && is_array($wpFilesystemActionCallbackArgs) ? $wpFilesystemActionCallbackArgs : array());
		} else {
			printf("Security token has expired. Click <a href='%s'>here</a> to return to the scan page.", esc_url(network_admin_url('admin.php?page=WordfenceScan')));
		}

		?>

	<?php else: ?>


	<?php $pageTitle = "Wordfence Scan"; $helpLink="http://docs.wordfence.com/en/Wordfence_scanning"; $helpLabel="Learn more about scanning"; $options = array(array('t' => 'Scan', 'a' => 'scan'), array('t' => 'Scheduling', 'a' => 'scheduling'), array('t' => 'Options', 'a' => 'options')); include('pageTitle.php'); ?>
		<div class="wf-row">
			<?php
			$rightRail = new wfView('marketing/rightrail');
			echo $rightRail;
			?>
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div id="scan" class="wordfenceTopTab" data-title="Wordfence Scan">
					<?php require('menu_scan_scan.php'); ?>
				</div> <!-- end scan block -->
				<div id="scheduling" class="wordfenceTopTab" data-title="Schedule when Wordfence Scans Occur">
					<?php require('menu_scan_schedule.php'); ?>
				</div> <!-- end scheduling block -->
				<div id="options" class="wordfenceTopTab" data-title="Wordfence Scan Options">
					<?php require('menu_scan_options.php'); ?>
				</div> <!-- end options block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	<?php endif ?>
	</div> <!-- end container -->
</div>
<script type="text/x-jquery-template" id="issueTmpl_configReadable">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr>
			<th>URL:</th>
			<td><a href="${data.url}" target="_blank" rel="noopener noreferrer">${data.url}</a></td>
		<tr>
			<th>Severity:</th>
			<td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td>
		</tr>
		<tr>
			<th>Status</th>
			<td>
				{{if status == 'new' }}New{{/if}}
				{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
			</td>
		</tr>
	</table>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		<ul>
			<li><h3>Tools:</h3></li>
			{{if data.fileExists}}
			<li><a target="_blank" rel="noopener noreferrer" href="${WFAD.makeViewFileLink(data.file)}">View the file</a></li>
			{{/if}}
			<li><a href="#" onclick="WFAD.hideFile('${id}', 'delete'); return false;">Hide this file in <em>.htaccess</em></a></li>
			{{if data.canDelete}}
			<li><a href="#" onclick="WFAD.deleteFile('${id}'); return false;">Delete this file (can't be undone)</a></li>
			{{/if}}
		</ul>
		{{if data.canDelete}}
		<p>
			<label><input type="checkbox" class="wfdelCheckbox" value="${id}" />&nbsp;Select for bulk delete</label>
		</p>
		{{/if}}
	</div>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_publiclyAccessible">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr>
					<th>URL:</th>
					<td><a href="${data.url}" target="_blank" rel="noopener noreferrer">${data.url}</a></td>
				<tr>
					<th>Severity:</th>
					<td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td>
				</tr>
				<tr>
					<th>Status</th>
					<td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
					</td>
				</tr>
			</table>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				<ul>
					<li><h3>Tools:</h3></li>
					{{if data.fileExists}}
					<li><a target="_blank" rel="noopener noreferrer" href="${WFAD.makeViewFileLink(data.file)}">View the file</a></li>
					{{/if}}
					<li><a href="#" onclick="WFAD.hideFile('${id}', 'delete'); return false;">Hide this file in <em>.htaccess</em></a></li>
					{{if data.canDelete}}
					<li><a href="#" onclick="WFAD.deleteFile('${id}'); return false;">Delete this file (can't be undone)</a></li>
					{{/if}}
				</ul>
				{{if data.canDelete}}
				<p>
					<label><input type="checkbox" class="wfdelCheckbox" value="${id}" />&nbsp;Select for bulk delete</label>
				</p>
				{{/if}}
			</div>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_wpscan_fullPathDiscl">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>URL:</th><td><a href="${data.url}" target="_blank" rel="noopener noreferrer">${data.url}</a></td>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if (status == 'new')}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<?php if (!wfUtils::isNginx()): ?>
				<li><a href="#" onclick="WFAD.fixFPD('${id}'); return false;">Fix this issue</a></li>
			<?php endif ?>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		{{/if}}
		</ul>
	</div>
	{{if (status == 'new')}}
	<div class="wfIssueOptions">
		<ul>
			<li><h3>Manual Fix:</h3></li>
			<li>Set <code>display_errors</code> to <code>Off</code> in your php.ini file.</li>
		</ul>
	</div>
	{{/if}}
</div>
</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_wpscan_directoryList">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>URL:</th><td><a href="${data.url}" target="_blank" rel="noopener noreferrer">${data.url}</a></td>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>

	<div class="wfIssueOptions">
		{{if (status == 'new')}}
		<ul>
			<li><h3>Resolve:</h3></li>
		<?php if (!wfUtils::isNginx()): ?>
			<li><a href="#" onclick="WFAD.disableDirectoryListing('${id}'); return false;">Fix this issue</a></li>
		<?php endif ?>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
	<?php if (!wfUtils::isNginx()): ?>
	{{if (status == 'new')}}
	<div class="wfIssueOptions">
		<ul>
			<li><h3>Manual Fix:</h3></li>
			<li>Add <code>Options -Indexes</code> to your .htaccess file.</li>
		</ul>
	</div>
	{{/if}}
	<?php endif ?>

</div>
</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_wfThemeUpgrade">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th><span class="wf-hidden-xs">Theme </span>Name:</th><td>${data.name}</td></tr>
		<tr><th>Current <span class="wf-hidden-xs">Theme </span>Version:</th><td>${data.version}</td></tr>
		<tr><th>New <span class="wf-hidden-xs">Theme </span>Version:</th><td>${data.newVersion}</td></tr>
		<tr><th><span class="wf-hidden-xs">Theme </span>URL:</th><td><a href="${data.URL}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.URL}</span><span class="wf-visible-xs">View</span></a></td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		{{if data.vulnerabilityLink}}<tr><th>Vulnerability <span class="wf-hidden-xs">Information</span>:</th><td><a href="${data.vulnerabilityLink}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.vulnerabilityLink}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
		</td></tr>
		</table>
	</p>
	{{if data.vulnerable}}<p><strong>Update includes security-related fixes.</strong></p>{{/if}}
	<p>
		{{html longMsg}}
		<a href="<?php echo get_admin_url() . 'update-core.php'; ?>">Click here to update now</a>.
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_wfPluginUpgrade">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th><span class="wf-hidden-xs">Plugin </span>Name:</th><td>${data.Name}</td></tr>
		{{if data.PluginURI}}<tr><th><span class="wf-hidden-xs">Plugin </span>Website:</th><td><a href="${data.PluginURI}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.PluginURI}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
		{{if data.wpURL}}<tr><th>Changelog:</th><td><a href="${data.wpURL}/#developers" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.wpURL}/#developers</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
		<tr><th>Current <span class="wf-hidden-xs">Plugin </span>Version:</th><td>${data.Version}</td></tr>
		<tr><th>New <span class="wf-hidden-xs">Plugin </span>Version:</th><td>${data.newVersion}</td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		{{if data.vulnerabilityLink}}<tr><th>Vulnerability <span class="wf-hidden-xs">Information</span>:</th><td><a href="${data.vulnerabilityLink}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.vulnerabilityLink}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
		</td></tr>
		</table>
	</p>
	{{if data.vulnerable}}<p><strong>Update includes security-related fixes.</strong></p>{{/if}}
	<p>
		{{html longMsg}}
		<a href="<?php echo get_admin_url() . 'update-core.php'; ?>">Click here to update now</a>.
	</p>
	<div class="wfIssueOptions">
	{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
		</ul>
	{{/if}}
	{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
	{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_wfPluginRemoved">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr><th><span class="wf-hidden-xs">Plugin </span>Name:</th><td>${data.Name}</td></tr>
				{{if data.PluginURI}}<tr><th><span class="wf-hidden-xs">Plugin </span>Website:</th><td><a href="${data.PluginURI}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.PluginURI}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				<tr><th>Current <span class="wf-hidden-xs">Plugin </span>Version:</th><td>${data.Version}</td></tr>
				<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
				{{if data.vulnerabilityLink}}<tr><th>Vulnerability <span class="wf-hidden-xs">Information</span>:</th><td><a href="${data.vulnerabilityLink}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.vulnerabilityLink}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
					</td></tr>
			</table>
			</p>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_wfPluginAbandoned">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr><th><span class="wf-hidden-xs">Plugin </span>Name:</th><td>${data.name}</td></tr>
				{{if data.homepage}}<tr><th><span class="wf-hidden-xs">Plugin </span>Website:</th><td><a href="${data.homepage}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.homepage}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				{{if data.wpURL}}<tr><th>Repository<span class="wf-hidden-xs"> Link</span>:</th><td><a href="${data.wpURL}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.wpURL}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				<tr><th>Current <span class="wf-hidden-xs">Plugin </span>Version:</th><td>${data.version}</td></tr>
				<tr><th>Last Updated:</th><td>${data.dateUpdated}</td></tr>
				<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
				{{if data.vulnerabilityLink}}<tr><th>Vulnerability <span class="wf-hidden-xs">Information</span>:</th><td><a href="${data.vulnerabilityLink}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.vulnerabilityLink}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
					</td></tr>
			</table>
			</p>
			{{if data.vulnerable}}<p><strong>Plugin has unpatched security issues.</strong></p>{{/if}}
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_wfPluginVulnerable">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr><th><span class="wf-hidden-xs">Plugin </span>Name:</th><td>${data.Name}</td></tr>
				{{if data.PluginURI}}<tr><th><span class="wf-hidden-xs">Plugin </span>Website:</th><td><a href="${data.PluginURI}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.PluginURI}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				{{if data.wpURL}}<tr><th>Repository<span class="wf-hidden-xs"> Link</span>:</th><td><a href="${data.wpURL}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.wpURL}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				<tr><th>Current <span class="wf-hidden-xs">Plugin </span>Version:</th><td>${data.Version}</td></tr>
				<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
				{{if data.vulnerabilityLink}}<tr><th>Vulnerability <span class="wf-hidden-xs">Information</span>:</th><td><a href="${data.vulnerabilityLink}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.vulnerabilityLink}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
					</td></tr>
			</table>
			</p>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_wfUpgrade">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Current <span class="wf-hidden-xs">WordPress </span>Version:</th><td>${data.currentVersion}</td></tr>
		<tr><th>New <span class="wf-hidden-xs">WordPress </span>Version:</th><td>${data.newVersion}</td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		{{if data.vulnerabilityLink}}<tr><th>Vulnerability <span class="wf-hidden-xs">Information</span>:</th><td><a href="${data.vulnerabilityLink}" target="_blank" rel="noopener noreferrer"><span class="wf-hidden-xs wf-split-word">${data.vulnerabilityLink}</span><span class="wf-visible-xs">View</span></a></td></tr>{{/if}}
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
		<a href="<?php echo get_admin_url() . 'update-core.php'; ?>">Click here to update now</a>.
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this issue</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_dnsChange">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Old<span class="wf-hidden-xs"> DNS records</span>:</th><td>${data.oldDNS}</td></tr>
		<tr><th>New<span class="wf-hidden-xs"> DNS records</span>:</th><td>${data.newDNS}</td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
	{{if (status == 'new')}}
		<strong>Resolve:</strong> 
		<a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I know about this change</a>
	{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_diskSpace">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Space remaining:</th><td>${data.spaceLeft}</td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignoring all disk space alerts{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore disk space alerts</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring disk space alerts</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_easyPassword">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th><span class="wf-hidden-xs">Issue First </span>Detected:</th><td>${timeAgo} ago.</td></tr>
		<tr><th>Login<span class="wf-hidden-xs"> Name</span>:</th><td>${data.user_login}</td></tr>
		<tr><th><span class="wf-hidden-xs">User </span>Email:</th><td>${data.user_email}</td></tr>
		<tr><th><span class="wf-hidden-xs">Full </span>Name:</th><td>${data.first_name} ${data.last_name}</td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}Ignored until user changes password{{/if}}
			{{if status == 'ignoreP' }}Ignoring all for this user{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		<ul>
			<li><h3>Tools:</h3></li>
			<li><a target="_blank" rel="noopener noreferrer" href="${data.editUserLink}">Edit this user</a></li>
		</ul>
	</div>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this weak password</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore all for this user</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_commentBadURL">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Author</th><td>${data.author}</td></tr>
		<tr><th>Bad URL:</th><td><strong class="wfWarn wf-split-word">${data.badURL}</strong></td></tr>
		<tr><th>Posted on:</th><td>${data.commentDate}</td></tr>
		{{if data.isMultisite}}
		<tr><th>Multisite Blog ID:</th><td>${data.blog_id}</td></tr>
		<tr><th>Multisite Blog Domain:</th><td>${data.domain}</td></tr>
		<tr><th>Multisite Blog Path:</th><td>${data.path}</td></tr>
		{{/if}}
		<tr><th>Severity:</th><td>Critical</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' || status == 'ignoreC' }}Ignored{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="WfIssueOptions">
		<ul>
			<li><h3>Tools:</h3></li>
			<li><a target="_blank" rel="noopener noreferrer" href="${data.editCommentLink}">Edit this ${data.type}</a></li>
		</ul>
	</div>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this ${data.type}</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this ${data.type}</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_postBadTitle">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Title:</th><td><strong class="wfWarn">${data.postTitle}</strong></td></tr>
		<tr><th>Posted on:</th><td>${data.postDate}</td></tr>
		{{if data.isMultisite}}
		<tr><th>Multisite Blog ID:</th><td>${data.blog_id}</td></tr>
		<tr><th>Multisite Blog Domain:</th><td>${data.domain}</td></tr>
		<tr><th>Multisite Blog Path:</th><td>${data.path}</td></tr>
		{{/if}}
		<tr><th>Severity:</th><td>Critical</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}This bad title will be ignored in this ${data.type}.{{/if}}
			{{if status == 'ignoreP' }}This post won't be scanned for bad titles.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		<ul>
			<li><h3>Tools:</h3></li> 
			<li><a target="_blank" rel="noopener noreferrer" href="${data.editPostLink}">Edit this ${data.type}</a></li>
		</ul>
	</div>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this title in this ${data.type}</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore all dangerous titles in this ${data.type}</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_postBadURL">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		{{if data.isMultisite}}
		<tr><th>Title:</th><td><a href="${data.permalink}" target="_blank" rel="noopener noreferrer">${data.postTitle}</a></td></tr>
		{{else}}
		<tr><th>Title:</th><td><a href="${data.permalink}" target="_blank" rel="noopener noreferrer">${data.postTitle}</a></td></tr>
		{{/if}}
		<tr><th>Bad URL:</th><td><strong class="wfWarn wf-split-word">${data.badURL}</strong></td></tr>
		<tr><th>Posted on:</th><td>${data.postDate}</td></tr>
		{{if data.isMultisite}}
		<tr><th>Multisite Blog ID:</th><td>${data.blog_id}</td></tr>
		<tr><th>Multisite Blog Domain:</th><td>${data.domain}</td></tr>
		<tr><th>Multisite Blog Path:</th><td>${data.path}</td></tr>
		{{/if}}
		<tr><th>Severity:</th><td>Critical</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}This bad URL will be ignored in this ${data.type}.{{/if}}
			{{if status == 'ignoreP' }}This post won't be scanned for bad URL's.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		<ul>
			<li><h3>Tools:</h3></li> 
			<li><a target="_blank" rel="noopener noreferrer" href="${data.editPostLink}">Edit this ${data.type}</a></li>
		</ul>
	</div>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this bad URL in this ${data.type}</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore all bad URLs in this ${data.type}</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_optionBadURL">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr><th>Option Name:</th><td><strong>${data.optionKey}</strong></td></tr>
				<tr><th>Bad URL:</th><td><strong class="wfWarn wf-split-word">${data.badURL}</strong></td></tr>
				{{if data.isMultisite}}
				<tr><th>Multisite Blog ID:</th><td>${data.blog_id}</td></tr>
				<tr><th>Multisite Blog Domain:</th><td>${data.domain}</td></tr>
				<tr><th>Multisite Blog Path:</th><td>${data.path}</td></tr>
				{{/if}}
				<tr><th>Severity:</th><td>Critical</td></tr>
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreC' }}This bad URL will be ignored in this ${data.type}.{{/if}}
						{{if status == 'ignoreP' }}This post won't be scanned for bad URL's.{{/if}}
					</td></tr>
			</table>
			</p>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this bad URL in this option</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore all bad URLs in this option</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>



<script type="text/x-jquery-template" id="issueTmpl_file">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Filename:</th><td>${data.file}</td></tr>
		{{if ((typeof data.badURL !== 'undefined') && data.badURL)}}
		<tr><th>Bad URL:</th><td><strong class="wfWarn wf-split-word">${data.badURL}</strong></td></tr>
		{{/if}}
		<tr><th><span class="wf-hidden-xs">File </span>Type:</th><td>{{if data.cType}}${WFAD.ucfirst(data.cType)}{{else}}Not a core, theme or plugin file.{{/if}}</td></tr>
		<tr><th><span class="wf-hidden-xs">Issue First </span>Detected:</th><td>${timeAgo} ago.</td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' }}Permanently ignoring this file{{/if}}
			{{if status == 'ignoreC' }}Ignoring this file until it changes{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Tools:</h3></li>
			{{if data.fileExists}}
			<li><a target="_blank" rel="noopener noreferrer" href="${WFAD.makeViewFileLink(data.file)}">View the file.</a></li>
			{{/if}}
			{{if data.canFix}}
			<li><a href="#" onclick="WFAD.restoreFile('${id}'); return false;">Restore the original version of this file.</a></li>
			{{/if}}
			{{if data.canDelete}}
			<li><a href="#" onclick="WFAD.deleteFile('${id}'); return false;">Delete this file (can't be undone)</a></li>
			{{/if}}
			{{if data.canDiff}}
			<li><a href="${WFAD.makeDiffLink(data)}" target="_blank" rel="noopener noreferrer">See how the file has changed.</a></li>
			{{/if}}
		</ul>
		{{if data.canFix}}
		<br />&nbsp;<input type="checkbox" class="wfrepairCheckbox" value="${id}" />&nbsp;Select for bulk repair
		{{/if}}
		{{if data.canDelete}}
		<br />&nbsp;<input type="checkbox" class="wfdelCheckbox" value="${id}" />&nbsp;Select for bulk delete
		{{/if}}
		{{/if}}
	</div>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			{{if data.fileExists}}
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore until the file changes.</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Always ignore this file.</a></li>
			{{else}}
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore missing file</a></li>
			{{/if}}
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_coreUnknown">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr><th><span class="wf-hidden-xs">Issue First </span>Detected:</th><td>${timeAgo} ago.</td></tr>
				<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreP' }}Permanently ignoring this version{{/if}}
						{{if status == 'ignoreC' }}Ignoring this version until it changes{{/if}}
					</td></tr>
			</table>
			</p>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore until the version changes</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Always ignore this version</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_database">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Option<span class="wf-hidden-xs"> Name</span>:</th><td>${data.option_name}</td></tr>
		{{if ((typeof data.badURL !== 'undefined') && data.badURL)}}
		<tr><th>Bad URL:</th><td><strong class="wfWarn wf-split-word">${data.badURL}</strong></td></tr>
		{{/if}}
		<tr><th><span class="wf-hidden-xs">Issue First </span>Detected:</th><td>${timeAgo} ago.</td></tr>
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreP' }}Permanently ignoring this option{{/if}}
			{{if status == 'ignoreC' }}Ignoring this option until it changes{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		<ul>
			<li><h3>Tools:</h3></li>
			{{if data.optionExists}}
			<li><a target="_blank" rel="noopener noreferrer" href="${WFAD.makeViewOptionLink(data.option_name, data.site_id)}">View this option.</a></li>
			{{/if}}
			{{if data.canDelete}}
			<li><a href="#" onclick="WFAD.deleteDatabaseOption('${id}'); return false;">Delete this option from the database (can't be undone)</a></li>
			{{/if}}
		</ul>
		{{if data.canDelete}}
		<br />&nbsp;<input type="checkbox" class="wfdelCheckbox" value="${id}" />&nbsp;Select for bulk delete
		{{/if}}
	</div>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			{{if data.optionExists}}
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore until the option changes.</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Always ignore this option.</a></li>
			{{else}}
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore missing option.</a></li>
			{{/if}}
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_pubBadURLs">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}These bad URLs will be ignored until they change.{{/if}}
			{{if status == 'ignoreP' }}These bad URLs will be permanently ignored.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore these URLs until they change.</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore these URLs permanently</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>


<script type="text/x-jquery-template" id="issueTmpl_pubDomainRedir">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}This redirect will be ignored until it changes.{{/if}}
			{{if status == 'ignoreP' }}This redirect is permanently ignored.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreC'); return false;">Ignore this redirect until it changes.</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore any redirect like this permanently</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_heartbleed">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}This redirect will be ignored until it changes.{{/if}}
			{{if status == 'ignoreP' }}This redirect is permanently ignored.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore this problem</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_checkSpamIP">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}This redirect will be ignored until it changes.{{/if}}
			{{if status == 'ignoreP' }}This redirect is permanently ignored.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore this problem</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_checkGSB">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				{{if ((typeof data.badURL !== 'undefined') && data.badURL)}}
				<tr><th>Bad URL:</th><td><strong class="wfWarn wf-split-word">${data.badURL}</strong></td></tr>
				{{/if}}
				<tr><th><span class="wf-hidden-xs">Issue First </span>Detected:</th><td>${timeAgo} ago.</td></tr>
				<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreC' }}This issue will be ignored until it changes.{{/if}}
						{{if status == 'ignoreP' }}This issue is permanently ignored.{{/if}}
					</td></tr>
			</table>
			</p>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore this problem</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>
<script type="text/x-jquery-template" id="issueTmpl_checkHowGetIPs">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr><th><span class="wf-hidden-xs">Issue First </span>Detected:</th><td>${timeAgo} ago.</td></tr>
				<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreC' }}This issue will be ignored until it changes.{{/if}}
						{{if status == 'ignoreP' }}This issue is permanently ignored.{{/if}}
					</td></tr>
			</table>
			</p>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					{{if ((typeof data.recommendation !== 'undefined') && data.recommendation)}}
					<li><a href="#" onclick="WFAD.useRecommendedHowGetIPs('${id}'); return false;">Use recommended value</a></li>
					{{/if}}
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore this problem</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_spamvertizeCheck">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}This redirect will be ignored until it changes.{{/if}}
			{{if status == 'ignoreP' }}This redirect is permanently ignored.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore this problem</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_suspiciousAdminUsers">
<div>
<div class="wfIssue">
	<h2>${shortMsg}</h2>
	<p>
		<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
		<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
		<tr><th>Status</th><td>
			{{if status == 'new' }}New{{/if}}
			{{if status == 'ignoreC' }}This issue will be ignored until it changes.{{/if}}
			{{if status == 'ignoreP' }}This issue is permanently ignored.{{/if}}
		</td></tr>
		</table>
	</p>
	<p>
		{{html longMsg}}
	</p>
	<div class="wfIssueOptions">
		{{if status == 'new'}}
		<ul>
			<li><h3>Resolve:</h3></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
			<li><a href="#" onclick="WFAD.deleteAdminUser('${id}'); return false;">Delete this user</a></li>
			<li><a href="#" onclick="WFAD.revokeAdminUser('${id}'); return false;">Revoke all capabilities from this user</a></li>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore this problem</a></li>
		</ul>
		{{/if}}
		{{if status == 'ignoreC' || status == 'ignoreP'}}
		<ul>
			<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
		</ul>
		{{/if}}
	</div>
</div>
</div>
</script>

<script type="text/x-jquery-template" id="issueTmpl_timelimit">
	<div>
		<div class="wfIssue">
			<h2>${shortMsg}</h2>
			<p>
			<table border="0" class="wfIssue" cellspacing="0" cellpadding="0">
				<tr><th>Severity:</th><td>{{if severity == '1'}}Critical{{else}}Warning{{/if}}</td></tr>
				<tr><th>Status</th><td>
						{{if status == 'new' }}New{{/if}}
						{{if status == 'ignoreC' }}This issue will be ignored until it changes.{{/if}}
						{{if status == 'ignoreP' }}This issue is permanently ignored.{{/if}}
					</td></tr>
			</table>
			</p>
			<p>
				{{html longMsg}}
			</p>
			<div class="wfIssueOptions">
				{{if status == 'new'}}
				<ul>
					<li><h3>Resolve:</h3></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">I have fixed this issue</a></li>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'ignoreP'); return false;">Ignore this problem</a></li>
				</ul>
				{{/if}}
				{{if status == 'ignoreC' || status == 'ignoreP'}}
				<ul>
					<li><a href="#" onclick="WFAD.updateIssueStatus('${id}', 'delete'); return false;">Stop ignoring this issue</a></li>
				</ul>
				{{/if}}
			</div>
		</div>
	</div>
</script>




<script type="text/x-jquery-template" id="wfNoScanYetTmpl">
<div>
	<table class="wfSummaryParent" cellpadding="0" cellspacing="0">
	<tr><th class="wfHead">Your first scan is starting now</th></tr>
	<tr><td>
		<table class="wfSC1"  cellpadding="0" cellspacing="0">
		<tr><td>
			Your first Wordfence scan should be automatically starting now
			and you will see the scan details in the "Activity Log" above in a few seconds.
		</td></tr>
		<tr><td>
			<div class="wordfenceScanButton"><button type="button" id="wfStartScanButton2" class="wfStartScanButton button-primary">Start a Wordfence Scan</button></div>
		</td></tr>
		</table>
	</td>
	</tr></table>
</div>
</script>

<script type="text/x-jquery-template" id="wfTourScan">
<div>
<h3>How to use Wordfence</h3>
<strong><p>Start with a Scan</p></strong>
<p>
	Using Wordfence is simple. Start by doing a scan. 
	Once the scan is complete, a list of issues will appear at the bottom of this page. Work through each issue one at a time. If you know an 
	issue is not a security problem, simply choose to ignore it. When you click "ignore" it will be moved to the list of ignored issues.
</p>
<strong><p>Use the tools we provide</p></strong>
<p>
	You'll notice that with each issue we provide tools to help you repair problems you may find. For example, if a core file has been modified
	you can view how it has been changed, view the whole file or repair the file. If we find a back-door a hacker has left behind, we give
	you the option to delete the file. Using these tools is an essential part of the diagnostic and cleaning process if you have been hacked.
</p>
<p>
	Repair each security problem that you find. You may have to fix a weak password that we detected, upgrade a theme or plugin, delete a comment that
	contains an unsafe URL and so on. Once you're done, start another scan and your site should come back with no security issues.
</p>
<strong><p>Regular scheduled scans keep your site safe</p></strong>
<p>
	Once you've done your initial scan and cleanup, Wordfence will automatically scan your site once a day.
	If you would like to scan your site more frequently or control when Wordfence does a scan, upgrade to the 
	paid version of Wordfence which includes other features like country blocking.
</p>
</div>
</script>
