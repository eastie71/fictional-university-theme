<?php
$d = new wfDashboard();
?>
<div class="wrap wordfence">
	<div class="wf-container-fluid">
		<?php $pageTitle = "Wordfence Dashboard"; include('pageTitle.php'); ?>
		<div class="wordfenceHelpLink"><a href="http://docs.wordfence.com/en/Wordfence_Dashboard" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="http://docs.wordfence.com/en/Wordfence_Dashboard" target="_blank" rel="noopener noreferrer">Learn more about the Wordfence Dashboard</a></div>
		<div id="wordfenceMode_dashboard"></div>
		<div class="wf-row wf-add-top">
			<div class="wf-col-xs-12">
				<div class="wf-dashboard-item">
					<div class="wf-dashboard-item-inner">
						<div class="wf-dashboard-item-content">
						<?php if ($d->scanLastStatus == wfDashboard::SCAN_NEVER_RAN): ?>
							<div class="wf-dashboard-item-title">
								<strong>Last scan completed: Never</strong>
							</div>
						<?php elseif ($d->scanLastStatus == wfDashboard::SCAN_SUCCESS): ?>
							<div class="wf-dashboard-item-title">
								<strong>Last scan completed: <?php echo esc_html(wfUtils::formatLocalTime(get_option('date_format') . ' ' . get_option('time_format'), $d->scanLastCompletion)); ?></strong>
							</div>
							<div class="wf-dashboard-item-action wf-dashboard-item-action-text wf-dashboard-item-action-text-success">No security problems detected</div>
						<?php elseif ($d->scanLastStatus == wfDashboard::SCAN_WARNINGS): ?>
							<div class="wf-dashboard-item-title">
								<strong>Last scan completed: <?php echo esc_html(wfUtils::formatLocalTime(get_option('date_format') . ' ' . get_option('time_format'), $d->scanLastCompletion)); ?></strong>
							</div>
							<div class="wf-dashboard-item-action wf-dashboard-item-action-text wf-dashboard-item-action-text-warning"><a href="<?php echo network_admin_url('admin.php?page=WordfenceScan'); ?>"><?php echo esc_html($d->scanLastStatusMessage); ?></a></div>
						<?php elseif ($d->scanLastStatus == wfDashboard::SCAN_FAILED): ?>
							<div class="wf-dashboard-item-title">
								<strong>Last scan failed</strong>
							</div>
							<div class="wf-dashboard-item-action wf-dashboard-item-action-text wf-dashboard-item-action-text-warning"><?php echo $d->scanLastStatusMessage; /* Already HTML-escaped */ ?></div>
						<?php endif; ?>
						</div>
					</div>
				</div>
			</div> <!-- end content block -->
		</div> <!-- end row -->
		<!-- begin notifications -->
		<?php include(dirname(__FILE__) . '/dashboard/widget_notifications.php'); ?>
		<!-- end notifications -->
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wf-dashboard-item active">
					<div class="wf-dashboard-item-inner">
						<div class="wf-dashboard-item-content">
							<div class="wf-dashboard-item-title">
								<strong>Feature Status</strong>
							</div>
							<div class="wf-dashboard-item-action"><div class="wf-dashboard-item-action-disclosure"></div></div>
						</div>
					</div>
					<div class="wf-dashboard-item-extra">
						<ul class="wf-dashboard-item-list">
							<li>
								<ul class="wf-dashboard-item-list wf-dashboard-item-list-horizontal wf-dashboard-item-list-equal">
								<?php foreach ($d->features as $f): ?>
									<li>
										<div class="wf-dashboard-item-list-title"><a href="<?php echo esc_html($f['link']); ?>"><?php echo esc_html($f['name']); ?></a></div>
										<?php if ($f['state'] == wfDashboard::FEATURE_ENABLED): ?>
										<div class="wf-dashboard-item-list-state wf-dashboard-item-list-state-enabled"><i class="fa fa-circle" aria-hidden="true"></i><span class="wf-hidden-lg"><br></span> Enabled</div>
										<?php elseif ($f['state'] == wfDashboard::FEATURE_DISABLED): ?>
											<div class="wf-dashboard-item-list-state wf-dashboard-item-list-state-disabled"><i class="fa fa-circle" aria-hidden="true"></i><span class="wf-hidden-lg"><br></span> Disabled</div>
										<?php elseif ($f['state'] == wfDashboard::FEATURE_PREMIUM): ?>
											<div class="wf-dashboard-item-list-state wf-dashboard-item-list-state-premium"><i class="fa fa-circle" aria-hidden="true"></i><span class="wf-hidden-lg"><br></span> Premium</div>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
								<?php if (count($d->features) % 2 == 1): ?>
									<li></li>
								<?php endif; ?>
								</ul>
							</li>
						</ul>
					</div>
				</div>
			</div> <!-- end content block -->
		</div> <!-- end row -->
		<div class="wf-row">
			<div class="wf-col-xs-12 wf-col-sm-6 wf-col-sm-half-padding-right">
				<!-- begin tdf stats -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_tdf.php'); ?>
				<!-- end tdf stats -->
				<!-- begin top ips blocked -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_ips.php'); ?>
				<!-- end top ips blocked -->
				<!-- begin recent logins -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_logins.php'); ?>
				<!-- end recent logins -->
			</div> <!-- end content block -->
			<div class="wf-col-xs-12 wf-col-sm-6 wf-col-sm-half-padding-left">
				<!-- begin firewall summary site -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_localattacks.php'); ?>
				<!-- end firewall summary site -->
				<!-- begin total attacks blocked network -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_networkattacks.php'); ?>
				<!-- end total attacks blocked network -->
				<!-- begin countries blocked -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_countries.php'); ?>
				<!-- end countries blocked -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>

<script type="text/x-jquery-template" id="wfWelcomeContent1">
	<div>
		<h3>Welcome to Wordfence</h3>
		<p>
			Wordfence is a robust and complete security system for WordPress. It protects your WordPress site
			from security threats and keeps you off Google's SEO black-list by providing a firewall, brute force protection, continuous scanning and many other security enhancements.
		</p>
		<p>
			Wordfence also detects if there are any security problems on
			your site or if there has been an intrusion and will alert you via email.
			Wordfence can also help repair hacked sites, even if you don't have a backup of your site.
		</p>
	</div>
</script>
<script type="text/x-jquery-template" id="wfWelcomeContent2">
	<div>
		<h3>How Wordfence is different</h3>
		<p><strong>Powered by our Cloud Servers</strong></p>
		<p>
			Wordfence is not just a standalone plugin for WordPress. It is part of Feedjit Inc. and is powered by our cloud scanning servers based at our
			data center in Seattle, Washington in the USA. On these servers we keep an updated mirror of every version of WordPress ever released
			and every version of every plugin and theme ever released into the WordPress repository. That allows us to
			do an integrity check on your core files, plugins and themes. It also means that when we detect they have changed, we can show you the
			changes and we can give you the option to repair any corrupt files. Even if you don't have a backup of that file.
		</p>
		<p><strong>Keeping you off Google's SEO Black-List</strong></p>
		<p>
			We also maintain a real-time copy of the Google Safe Browsing list (the GSB) and use it to scan all your files, posts, pages and comments for dangerous URL's.
			If you accidentally link to a URL on the GSB, your site is often black-listed by Google and removed from search results.
			The GSB is constantly changing, so constant scanning of all your content is needed to keep you safe and off Google's SEO black-list.
		</p>
		<p><strong>Scans for back-doors, malware, viruses and other threats</strong></p>
		<p>
			Wordfence also maintains an updated threat and malware signature database which we use to scan your site for intrusions, malware, backdoors and more.
		</p>
	</div>
</script>