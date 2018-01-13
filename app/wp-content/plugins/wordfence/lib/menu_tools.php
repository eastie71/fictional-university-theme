<div class="wrap wordfence">
	<div class="wf-container-fluid">
			<?php $pageTitle = "Wordfence Tools"; $options = array(array('t' => 'Password Audit', 'a' => 'passwd'), array('t' => 'Whois Lookup', 'a' => 'whois'), array('t' => 'Cellphone Sign-in', 'a' => 'twofactor'), array('t' => 'Diagnostics', 'a' => 'diagnostics')); $wantsLiveActivity = true; include('pageTitle.php'); ?>
			<div class="wf-row">
				<?php
				$rightRail = new wfView('marketing/rightrail');
				echo $rightRail;
				?>
				<div class="<?php echo wfStyle::contentClasses(); ?>">
					<div id="passwd" class="wordfenceTopTab" data-title="Audit the Strength of your Passwords">
						<?php
						$helpLink = 'http://docs.wordfence.com/en/Wordfence_Password_Auditing';
						$helpLabel = 'Learn more about Password Auditing';
						require('menu_tools_passwd.php');
						?>
					</div> <!-- end passwd block -->
					<div id="whois" class="wordfenceTopTab" data-title="WHOIS Lookup">
						<?php
						$helpLink = 'http://docs.wordfence.com/en/Whois_Lookup';
						$helpLabel = 'Learn more about Whois Lookups';
						require('menu_tools_whois.php');
						?>
					</div> <!-- end whois block -->
					<div id="twofactor" class="wordfenceTopTab" data-title="Cellphone Sign-in">
						<?php
						$helpLink = "http://docs.wordfence.com/en/Cellphone_sign-in";
						$helpLabel = "Learn more about Cellphone Sign-in";
						require('menu_tools_twoFactor.php');
						?>
					</div> <!-- end twofactor block -->
					<div id="diagnostics" class="wordfenceTopTab" data-title="Diagnostics">
						<?php require('menu_tools_diagnostic.php'); ?>
					</div> <!-- end diagnostics block -->
				</div> <!-- end content block -->
			</div> <!-- end row -->
	</div> <!-- end container -->
</div>
