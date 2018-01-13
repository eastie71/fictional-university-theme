If you are a site administrator and have been accidentally locked out, please enter your email in the box below and click "Send". If the email address you enter belongs to a known site administrator or someone set to receive Wordfence alerts, we will send you an email to help you regain access. <a href="https://docs.wordfence.com/en/Help!_I_locked_myself_out_and_can't_get_back_in._What_can_I_do%3F" target="_blank" rel="noopener noreferrer">Please read this FAQ entry if this does not work.</a>
<br /><br />
<form method="POST" id="unlock-form" action="#">
<?php require_once(ABSPATH .'wp-includes/pluggable.php'); ?>
<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wf-form'); ?>" />
<input type="text" size="50" name="email" id="unlock-email" value="" maxlength="255" />&nbsp;<input type="submit" id="unlock-submit" name="s" value="Send me an unlock email" disabled />
</form>
<script type="application/javascript">
	(function() {
		var textfield = document.getElementById('unlock-email');
		textfield.addEventListener('focus', function() {
			document.getElementById('unlock-form').action = "<?php echo esc_attr(wfUtils::getSiteBaseURL()); ?>" + "?_wfsf=unlockEmail";
			document.getElementById('unlock-submit').disabled = false;
		});
	})();
</script>
<br /><br />
