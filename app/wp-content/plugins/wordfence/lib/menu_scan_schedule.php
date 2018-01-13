<div class="wordfenceModeElem" id="wordfenceMode_scanScheduling"></div>
<?php if (!wfConfig::get('isPaid')) { ?>
<div class="wf-premium-callout" style="margin: 20px;">
	<h3>Scan Scheduling is only available to Premium Members</h3>
	<p>Premium users can increase their WordPress protection by controlling scan frequency up to once per hour. Premium also allows you to control when Wordfence initiates a scan, selecting optimal times that don’t interfere with high-traffic or optimal usage of your site.</p>

	<p>Upgrade today:</p>
	<ul>
		<li>Receive real-time Firewall and Scan engine rule updates for protection as threats emerge</li>
		<li>Other advanced features like IP reputation monitoring, an advanced comment spam filter, country blocking and cell phone sign-in give you the best protection available</li>
		<li>Access to Premium Support</li>
		<li>Discounts of up to 90% available for multiyear and multi-license purchases</li>
	</ul>
	<p class="center"><a class="wf-btn wf-btn-primary wf-btn-callout" href="https://www.wordfence.com/gnl1scanSched1/wordfence-signup/" target="_blank" rel="noopener noreferrer">Get Premium</a></p>
</div>
<?php } ?>
<div class="wf-container-fluid">
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wf-card wf-card-left<?php echo (wfScan::isAutoScanSchedule() ? ' active' : ''); ?>" data-mode="auto">
				<div class="wf-card-inner">
					<div class="wf-card-content">
						<div class="wf-card-title">
							Let Wordfence automatically schedule scans (recommended)
						</div>
						<div class="wf-card-subtitle">
							<?php if (wfScan::isAutoScanSchedule()) : ?>
								<?php echo wordfence::getNextScanStartTime(); ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="wf-card-action"> 
						<div class="wf-card-action-checkbox<?php echo (wfScan::isAutoScanSchedule() ? ' checked' : ''); ?>"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wf-card wf-card-left<?php echo (wfScan::isManualScanSchedule() ? ' active' : ''); ?><?php if (!wfConfig::get('isPaid')) { echo ' disabled'; } ?>" data-mode="manual">
				<div class="wf-card-inner">
					<div class="wf-card-content">
						<div class="wf-card-title">
							Manually schedule scans<?php if (!wfConfig::get('isPaid')) { echo ' (Premium Members Only)'; } ?>
						</div>
						<div class="wf-card-subtitle">
							<?php if (wfScan::isManualScanSchedule()) : ?>
								<?php echo wordfence::getNextScanStartTime(); ?>
							<?php endif; ?>
						</div>
					</div>
					<div class="wf-card-action">
						<div class="wf-card-action-checkbox<?php echo (wfScan::isManualScanSchedule() ? ' checked' : ''); ?>"></div>
					</div>
				</div>
				<div class="wf-card-extra">
					<table class="scan-schedule">
						<tr class="wf-visible-xs">
							<th>Shortcuts</th>
						</tr>
						<tr>
							<th class="wf-hidden-xs">Shortcuts</th>
							<td>
								<button type="button" class="wf-btn wf-btn-primary scan-shortcut" data-shortcut="onceDaily">Once per day</button> <button type="button" class="wf-btn wf-btn-primary scan-shortcut" data-shortcut="twiceDaily">Twice per day</button> <button type="button" class="wf-btn wf-btn-primary scan-shortcut" data-shortcut="weekends">Weekends</button> <button type="button" class="wf-btn wf-btn-primary scan-shortcut" data-shortcut="oddDaysWE">Odd days and weekends</button> <button type="button" class="wf-btn wf-btn-primary scan-shortcut" data-shortcut="every6hours">Every 6 hours</button>
							</td>
						</tr>
						<?php
						$daysOfWeek = array(
							array(1, 'Monday'),
							array(2, 'Tuesday'),
							array(3, 'Wednesday'),
							array(4, 'Thursday'),
							array(5, 'Friday'),
							array(6, 'Saturday'),
							array(0, 'Sunday')
						);
						$sched = wfConfig::get_ser('scanSched', array());
						foreach ($daysOfWeek as $d) :
							list($dayNumber, $dayName) = $d;
							?>
							<tr class="wf-visible-xs">
								<th><?php echo $dayName; ?></th>
							</tr>
							<tr class="schedule-day" data-day="<?php echo $dayNumber; ?>">
								<th class="wf-hidden-xs"><?php echo $dayName; ?></th>
								<td>
									<div class="schedule-times-wrapper">
										<div class="wf-visible-xs wf-center">AM</div>
										<ul class="schedule-times">
											<li class="text-only wf-hidden-xs">AM</li>
											<?php
											for ($h = 0; $h < 12; $h++) {
												$active = (isset($sched[$dayNumber]) && $sched[$dayNumber][$h] ? ' active' : '');
												echo '<li class="time' . $active . '" data-hour="' . $h . '"><a href="#">' . str_pad($h, 2, '0', STR_PAD_LEFT) . '</a></li>';
											}
											?>
										</ul>
									</div>
									<div class="schedule-times-wrapper">
										<div class="wf-visible-xs wf-center">PM</div>
										<ul class="schedule-times">
											<li class="text-only wf-hidden-xs">PM</li>
											<?php
											for ($i = 0; $i < 12; $i++) {
												$h = $i;
												if ($h == 0) { $h = 12; }
												$active = (isset($sched[$dayNumber]) && $sched[$dayNumber][$i + 12] ? ' active' : '');
												echo '<li class="time' . $active . '" data-hour="' . ($i + 12) . '"><a href="#">' . str_pad($h, 2, '0', STR_PAD_LEFT) . '</a></li>';
											}
											?>
										</ul>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="application/javascript">
	(function($) {
		function WFScanScheduleSave() {
			var schedMode = $('.wf-card.active').data('mode');
			
			var schedule = [];
			$('.schedule-day').each(function() {
				var hours = [];
				$(this).find('.time').each(function() {
					hours[$(this).data('hour')] = $(this).hasClass('active') ? '1' : '0';
				});
				schedule[$(this).data('day')] = hours.join(',');
			});
			var schedTxt = schedule.join('|');

			$('.wf-card-subtitle').html('');
			$('.wf-card.active .wf-card-subtitle').html('Updating scan schedule...');
			
			WFAD.ajax('wordfence_saveScanSchedule', {
				schedMode: schedMode,
				schedTxt: schedTxt
			}, function(res) {
				if (res.ok) {
					$('.wf-card.active .wf-card-subtitle').html(res.nextStart);
				}
			});
		}
		
		$('.wf-card-inner').on('click', function() {
			var self = this;
			if ($(this).closest('.wf-card').hasClass('disabled')) {
				return;
			}
			
			$('.wf-card-inner').each(function() {
				$(this).find('.wf-card-action-checkbox').removeClass('checked');
				$(this).closest('.wf-card').removeClass('active');
			});
			$(self).find('.wf-card-action-checkbox').addClass('checked');
			$(self).closest('.wf-card').addClass('active');

			WFScanScheduleSave();
		});
		
		$('.schedule-times a').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var selected = $(this).closest('li').hasClass('active');
			if (selected) {
				$(this).closest('li').removeClass('active');
			}
			else {
				$(this).closest('li').addClass('active');
			}

			WFScanScheduleSave();
		});
		
		$('.scan-shortcut').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			$('.schedule-times li').removeClass('active');
			if (WFAD.schedStartHour === false) {
				WFAD.schedStartHour = Math.min(Math.floor((Math.random() * 24)), 23);
			}
			else {
				WFAD.schedStartHour = (WFAD.schedStartHour + 1) % 24;
			}
			
			var mode = $(this).data('shortcut');
			var schedule = [ //Can't use Array.prototype.fill because of IE
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
				[0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
			];
			if (mode == 'onceDaily') {
				for (var i = 0; i < schedule.length; i++) {
					schedule[i][WFAD.schedStartHour] = 1;
				}
			} else if (mode == 'twiceDaily') {
				var secondHour = (WFAD.schedStartHour + 12) % 24;
				for (var i = 0; i < schedule.length; i++) {
					schedule[i][WFAD.schedStartHour] = 1;
					schedule[i][secondHour] = 1;
				}
			} else if (mode == 'oddDaysWE') {
				for (var i = 0; i < schedule.length; i++) {
					if (i == 2 || i == 4) { continue; }
					schedule[i][WFAD.schedStartHour] = 1;
				}
			} else if (mode == 'weekends') {
				schedule[0][WFAD.schedStartHour] = 1;
				schedule[6][WFAD.schedStartHour] = 1;
			} else if (mode == 'every6hours') {
				for (var i = 0; i < schedule.length; i++) {
					for (var hour = WFAD.schedStartHour; hour < WFAD.schedStartHour + 24; hour = hour + 6) {
						schedule[i][hour % 24] = 1; 
					}
				}
			}

			$('.schedule-day').each(function() {
				var day = $(this).data('day');
				$(this).find('.time').each(function() {
					var hour = $(this).data('hour');
					if (schedule[day][hour]) {
						$(this).addClass('active');
					}
				});
			});

			WFScanScheduleSave();
		});
	})(jQuery);
</script>

<script type="text/x-jquery-template" id="wfWelcomeContentScanSched">
<div>
<h3>Premium Feature: Scan Scheduling</h3>
<strong><p>Want full control over when your scans run?</p></strong>
<p>
	If you upgrade to our premium version you will have access to our scan scheduling feature.
	This gives you full control over when and how frequently your site is scanned
	for security vulnerabilities and intrusions.
</p>
<p>
	If your site gets a surge of traffic in the mornings, you may choose to run
	two scans in the late afternoon and at midnight, for example. Or if you
	are experiencing an unusually high number of attacks, you might choose
	to run scans once every two to four hours to be extra vigilant during the attack.
<p>
<?php
if(wfConfig::get('isPaid')){
?>
	You have upgraded to the premium version of Wordfence and have full access
	to this feature along with our other premium features and priority support.
<?php
} else {
?>
	If you would like access to this premium feature, please 
	<a href="https://www.wordfence.com/gnl1scanSched2/wordfence-signup/" target="_blank" rel="noopener noreferrer">upgrade to our Premium version</a>.
</p>
<?php
}
?>
</div>
</script>
