<?php
$w = new wfConfig();
?>
<div class="wordfenceHelpLink"><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer"><?php echo $helpLabel; ?></a></div>
<div class="wf-add-top">
	<form id="wfConfigForm-rateLimiting" class="wf-form-horizontal">
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

		<div class="wf-form-group">
			<div class="wf-col-sm-7 wf-col-sm-offset-5">
				<a class="wf-btn wf-btn-primary wf-btn-callout" href="#" onclick="WFAD.savePartialConfig('#wfConfigForm-rateLimiting'); return false;">Save Options</a> <div class="wfAjax24"></div><span class="wfSavedMsg">&nbsp;Your changes have been saved!</span>
			</div>
		</div>
	</form>
</div>