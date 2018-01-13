<p>This email was sent from your website "<?php echo get_bloginfo('name', 'raw'); ?>" by the Wordfence plugin.</p>

<p>Wordfence found the following new issues on "<?php echo get_bloginfo('name', 'raw'); ?>".</p>

<p>Alert generated at <?php echo wfUtils::localHumanDate(); ?></p>
	

<?php if (wfConfig::get('scansEnabled_highSense')): ?>
	<div style="margin: 12px 0;padding: 8px; background-color: #ffffe0; border: 1px solid #ffd975; border-width: 1px 1px 1px 10px;">
		<em>HIGH SENSITIVITY scanning is enabled, it may produce false positives</em>
	</div>
<?php endif ?>

<?php if ($timeLimitReached): ?>
	<div style="margin: 12px 0;padding: 8px; background-color: #ffffe0; border: 1px solid #ffd975; border-width: 1px 1px 1px 10px;">
		<em>The scan was terminated early because it reached the time limit for scans. If you would like to allow your scans to run longer, you can customize the limit on the options page: <a href="<?php echo esc_attr(network_admin_url('admin.php?page=WordfenceSecOpt')); ?>"><?php echo esc_attr(network_admin_url('admin.php?page=WordfenceSecOpt')); ?></a> or read more about scan options to improve scan speed here: <a href="https://docs.wordfence.com/en/Scan_time_limit">https://docs.wordfence.com/en/Scan_time_limit</a></em>
	</div>
<?php endif ?>

<?php if($totalCriticalIssues > 0){ ?>
<p>Critical Problems:</p>

<?php foreach($issues as $i){ if($i['severity'] == 1){ ?>
<p>* <?php echo htmlspecialchars($i['shortMsg']) ?></p>
<?php
	if ((isset($i['tmplData']['wpRemoved']) && $i['tmplData']['wpRemoved']) || (isset($i['tmplData']['abandoned']) && $i['tmplData']['abandoned'])) {
		if (isset($i['tmplData']['vulnerable']) && $i['tmplData']['vulnerable']) {
			echo '<p><strong>Plugin contains an unpatched security vulnerability.</strong>';
			if (isset($i['tmplData']['vulnerabilityLink'])) {
				echo ' <a href="' . $i['tmplData']['vulnerabilityLink'] . '" target="_blank" rel="nofollow noreferer noopener">Vulnerability Information</a>';
			}
			echo '</p>';
		}
	}
	else if (isset($i['tmplData']['wpURL'])) {
		echo '<p>';
		if (isset($i['tmplData']['vulnerable']) && $i['tmplData']['vulnerable']) {
			echo '<strong>Update includes security-related fixes.</strong> ';
			if (isset($i['tmplData']['vulnerabilityLink'])) {
				echo '<a href="' . $i['tmplData']['vulnerabilityLink'] . '" target="_blank" rel="nofollow noreferer noopener">Vulnerability Information</a> ';
			}
		}
		echo $i['tmplData']['wpURL'] . '/#developers</p>';
	}
	else if (isset($i['tmplData']['vulnerable']) && $i['tmplData']['vulnerable']) {
		echo '<p><strong>Update includes security-related fixes.</strong>';
		if (isset($i['tmplData']['vulnerabilityLink'])) {
			echo ' <a href="' . $i['tmplData']['vulnerabilityLink'] . '" target="_blank" rel="nofollow noreferer noopener">Vulnerability Information</a>';
		}
		echo '</p>';
	}
?>
<?php if (!empty($i['tmplData']['badURL'])): ?>
<p><img src="<?php echo WORDFENCE_API_URL_BASE_NONSEC . "?" . http_build_query(array(
		'v' => wfUtils::getWPVersion(), 
		's' => home_url(),
		'k' => wfConfig::get('apiKey'),
		'action' => 'image',
		'txt' => base64_encode($i['tmplData']['badURL'])
	), '', '&') ?>" alt="" /></p>
<?php endif ?>

<?php } } } ?>

<?php if($level == 2 && $totalWarningIssues > 0){ ?>
<p>Warnings:</p>

<?php foreach($issues as $i){ if($i['severity'] == 2){  ?>
<p>* <?php echo htmlspecialchars($i['shortMsg']) ?></p>
		<?php if (isset($i['tmplData']['wpURL'])): ?>
			<p><?php echo $i['tmplData']['wpURL']; ?>/#developers</p>
		<?php endif ?>

<?php } } } ?>

<?php if ($issuesNotShown > 0) { ?>
<p><?php echo wfUtils::pluralize($issuesNotShown, 'issue'); ?> were omitted from this email. View every issue: <a href="<?php echo esc_attr(network_admin_url('admin.php?page=WordfenceScan')); ?>"><?php echo esc_html(network_admin_url('admin.php?page=WordfenceScan')); ?></a></p>
<?php } ?>


<?php if(! $isPaid){ ?>
	<p>NOTE: You are using the free version of Wordfence. Upgrade today:</p>

	<ul>
		<li>Receive real-time Firewall and Scan engine rule updates for protection as threats emerge</li>
		<li>Other advanced features like IP reputation monitoring, country blocking, an advanced comment spam filter and cell phone sign-in give you the best protection available</li>
		<li>Remote, frequent and scheduled scans</li>
		<li>Access to Premium Support</li>
		<li>Discounts of up to 90% for multiyear and multi-license purchases</li>
	</ul>

	<p>
		Click here to upgrade to Wordfence Premium:<br>
		<a href="https://www.wordfence.com/zz2/wordfence-signup/">https://www.wordfence.com/zz2/wordfence-signup/</a>
	</p>
<?php } ?>



