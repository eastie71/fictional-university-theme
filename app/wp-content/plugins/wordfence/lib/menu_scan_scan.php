<div class="wordfenceModeElem" id="wordfenceMode_scan"></div>
<div class="wf-alert wf-alert-danger" id="wf-scan-failed" style="display: none;">
	<h4>Scan Failed</h4>
	<p>The current scan looks like it has failed. Its last status update was <span id="wf-scan-failed-time-ago"></span> ago. You may continue to wait in case it resumes or cancel and restart the scan. Some sites may need adjustments to run scans reliably. <a href="https://docs.wordfence.com/en/My_scans_don%27t_finish._What_would_cause_that%3F" target="_blank" rel="noopener noreferrer">Click here for steps you can try.</a></p>
</div> 
<div class="wordfenceScanButton wf-center">
	<a href="#" id="wfStartScanButton1" class="wfStartScanButton button button-primary" onclick="wordfenceAdmin.startScan(); return false;">Start a Wordfence Scan</a><br />
	<a href="#" onclick="WFAD.killScan(); return false;" style="font-size: 10px; color: #AAA;">Click to kill the current scan.</a>
</div>
<div>
	<div class="consoleHead">
		<h3 class="consoleHeadText">Scan Summary</h3>
	</div>
	<?php
	$events = wordfence::getLog()->getStatusEvents(0);
	?>
	<div class="bevelDiv1 consoleOuter"><div class="bevelDiv2"><div class="bevelDiv3 consoleInner" id="consoleSummary">
				<?php if(sizeof($events) < 1){ ?>
					<div style="width: 500px;">
						Welcome to Wordfence!<br /><br />
						To get started, simply click the "Scan" button at the top of this page to start your first scan.
					</div>
				<?php } ?>
			</div></div></div>
	<?php if (wfConfig::get('isPaid')) { ?>
		<?php if (wfConfig::get('scansEnabled_fileContents')): ?>
			<div>
				<p class="wf-success">You are running the Premium version of the Threat Defense Feed which is
					updated in real-time as new threats emerge. <a href="https://www.wordfence.com/zz13/sign-in/" target="_blank" rel="noopener noreferrer">Protect additional sites.</a></p>
			</div>
		<?php else: ?>
			<div class="wfSecure">Premium scanning enabled</div>
		<?php endif ?>
	<?php } else { ?>
		<?php if (wfConfig::get('scansEnabled_fileContents')): ?>
			<p>You are running the Wordfence Community Scan signatures.
				<!--						<em id="wf-scan-sigs-last-update"></em>-->
			</p>
		<?php endif ?>
		
		<div class="wf-premium-callout">
			<h3>The Wordfence Scan alerts you if you've been hacked</h3>
			
			<p>As new threats emerge, the Threat Defense Feed is updated to detect these new hacks. The Premium
				version of the Threat Defense Feed is updated in real-time protecting you immediately. As a free
				user <strong>you are receiving the community version</strong> of the feed which is updated 30 days later.</p>
			<p class="center"><a class="wf-btn wf-btn-primary wf-btn-callout" href="https://www.wordfence.com/gnl1scanUpgrade/wordfence-signup/" target="_blank" rel="noopener noreferrer">Get Premium</a></p>
		</div>
	
	<?php } ?>
	
	<?php if ($sigUpdateTime ): ?>
		<script>
			WFAD.updateSignaturesTimestamp(<?php echo (int) $sigUpdateTime ?>);
		</script>
	<?php endif ?>
	
	<div class="consoleHead" style="margin-top: 20px;">
		<h3 class="consoleHeadText">Scan Detailed Activity</h3>
		<a href="#" class="wfALogMailLink" onclick="WFAD.emailActivityLog(); return false;">Email<span class="wf-hidden-xs"> activity log</span></a>
	</div>
	<div class="bevelDiv1 consoleOuter"><div class="bevelDiv2"><div class="bevelDiv3 consoleInner" id="consoleActivity">
				<?php
				if(sizeof($events) > 0){
					$debugOn = wfConfig::get('debugOn', false);
					$newestItem = 0;
					$sumEvents = array();
					$timeOffset = 3600 * get_option('gmt_offset');
					foreach($events as $e){
						if(strpos($e['msg'], 'SUM_') !== 0){
							if( $debugOn || $e['level'] < 4){
								$typeClass = '';
								if($debugOn){
									$typeClass = ' wf' . $e['type'];
								}
								echo '<div class="wfActivityLine' . $typeClass . '">[' . date('M d H:i:s', $e['ctime'] + $timeOffset) . ']&nbsp;' . $e['msg'] . '</div>';
							}
						}
						$newestItem = $e['ctime'];
					}
					
					echo '<script type="text/javascript">WFAD.lastALogCtime = ' . $newestItem . '; WFAD.processActArray(' . json_encode(wordfence::getLog()->getSummaryEvents()) . ');</script>';
				} else { ?>
					A live stream of what Wordfence is busy with right now will appear in this box.
					<?php
				}
				?>
	</div></div></div>
	<div class="consoleFooter">
		&nbsp;<a href="#" target="_blank" rel="noopener noreferrer" class="wfALogViewLink" id="wfALogViewLink">View activity log</a>
	</div>
	
	<div class="wf-premium-callout">
		<h3>Need help with a hacked website?</h3>
		<p>Our team of security experts will clean the infection and remove malicious content. Once your site is restored we will provide a detailed report of our findings. All for an affordable rate.</p>
		<?php if (!wfConfig::get('isPaid')) { ?><p><strong>Includes a 1 year Wordfence Premium license.</strong></p><?php } ?>
		<p class="center"><a class="wf-btn wf-btn-primary wf-btn-callout" href="https://www.wordfence.com/gnl1scanGetHelp/wordfence-site-cleanings/" target="_blank" rel="noopener noreferrer">Get Help</a></p>
	</div>
</div>
<div id="wfScanIssuesWrapper" style="margin-top: 20px;">
	<div id="wfTabs">
		<a href="#" id="wfNewIssuesTab" class="wfTab2 wfTabSwitch selected" onclick="wordfenceAdmin.switchIssuesTab(this, 'new'); return false;">New Issues<span class="wfIssuesCount"></span></a>
		<a href="#" id="wfIgnoredIssuesTab" class="wfTab2 wfTabSwitch" onclick="wordfenceAdmin.switchIssuesTab(this, 'ignored'); return false;">Ignored Issues<span class="wfIssuesCount"></span></a>
	</div>
	<div class="wfTabsContainer wfScanIssuesTabs">
		<div id="wfIssues_new" class="wfIssuesContainer">
			<h2>New Issues</h2>
			<?php if (wfConfig::get('scansEnabled_highSense')): ?>
				<div class="wf-notice">
					<em>HIGH SENSITIVITY scanning is enabled, it may produce false positives</em>
				</div>
			<?php endif ?>
			<p>
				The list below shows new problems or warnings that Wordfence found with your site.
				If you have fixed all the issues below, you can <a href="#" onclick="WFAD.updateAllIssues('deleteNew'); return false;">click here to mark all new issues as fixed</a>.
				You can also <a href="#" onclick="WFAD.updateAllIssues('ignoreAllNew'); return false;">ignore all new issues</a> which will exclude all issues listed below from future scans.
			</p>
			<p>
				<a href="#" onclick="jQuery('#wfBulkOps').toggle(); return false;">Bulk operation&raquo;&raquo;</a>
			<div id="wfBulkOps" style="display: none;">
				<input type="button" name="but2" value="Select All Repairable files" onclick="jQuery('input.wfrepairCheckbox').prop('checked', true); return false;" />
				&nbsp;<input type="button" name="but1" value="Bulk Repair Selected Files" onclick="WFAD.bulkOperation('repair'); return false;" />
				<br />
				<br />
				<input type="button" name="but2" value="Select All Deletable files" onclick="jQuery('input.wfdelCheckbox').prop('checked', true); return false;" />
				&nbsp;<input type="button" name="but1" value="Bulk Delete Selected Files" onclick="WFAD.bulkOperation('del'); return false;" />
			</div>
			
			</p>
			<div id="wfIssues_dataTable_new">
			</div>
		</div>
		<div id="wfIssues_ignored" class="wfIssuesContainer">
			<h2>Ignored Issues</h2>
			<p>
				The list below shows issues that you know about and have chosen to ignore.
				You can <a href="#" onclick="WFAD.updateAllIssues('deleteIgnored'); return false;">click here to clear all ignored issues</a>
				which will cause all issues below to be re-scanned by Wordfence in the next scan.
			</p>
			<div id="wfIssues_dataTable_ignored"></div>
		</div>
	</div>
</div>