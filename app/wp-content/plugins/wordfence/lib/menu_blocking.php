<div class="wrap wordfence">
	<div class="wf-container-fluid">
		<?php $pageTitle = "Wordfence Blocking"; $options = array(array('t' => 'Blocked IPs', 'a' => 'blockedips'), array('t' => 'Country Blocking', 'a' => 'countryblocking'), array('t' => 'Advanced Blocking', 'a' => 'advancedblocking')); $wantsLiveActivity = true; include('pageTitle.php'); ?>
		<div class="wf-row">
			<?php
			$rightRail = new wfView('marketing/rightrail');
			echo $rightRail;
			?>
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div id="blockedips" class="wordfenceTopTab" data-title="Wordfence Blocked IPs">
					<?php
					$helpLink = "http://docs.wordfence.com/en/Blocked_IPs";
					$helpLabel = "Learn more about Blocked IPs";
					require('menu_blocking_blockedIPs.php');
					?>
				</div> <!-- end blockedips block -->
				<div id="countryblocking" class="wordfenceTopTab" data-title="Block Selected Countries from Accessing your Site">
					<?php
					$helpLink = "http://docs.wordfence.com/en/Country_blocking";
					$helpLabel = "Learn more about Country Blocking";
					require('menu_blocking_countryBlocking.php');
					?>
				</div> <!-- end countryblocking block -->
				<div id="advancedblocking" class="wordfenceTopTab" data-title="Advanced Blocking">
					<?php
					$helpLink = "http://docs.wordfence.com/en/Advanced_Blocking";
					$helpLabel = "Learn more about Advanced Blocking";
					require('menu_blocking_advancedBlocking.php');
					?>
				</div> <!-- end advancedblocking block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
