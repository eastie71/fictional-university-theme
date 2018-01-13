<?php //$d is defined here as a wfDashboard instance ?>
<div class="wf-row">
	<div class="wf-col-xs-12">
		<div class="wf-dashboard-item active">
			<div class="wf-dashboard-item-inner">
				<div class="wf-dashboard-item-content">
					<div class="wf-dashboard-item-title">
						<strong>Top Countries by Number of Attacks - Last 7 Days</strong>
					</div>
					<div class="wf-dashboard-item-action"><div class="wf-dashboard-item-action-disclosure"></div></div>
				</div>
			</div>
			<div class="wf-dashboard-item-extra">
				<ul class="wf-dashboard-item-list">
					<li>
						<div>
							<?php if (count($d->countriesNetwork) > 0): ?>
							<div class="wf-dashboard-toggle-btns">
								<ul class="wf-pagination wf-pagination-sm">
									<li class="wf-active"><a href="#" class="wf-dashboard-countries" data-grouping="local">Local Site</a></li>
									<li><a href="#" class="wf-dashboard-countries" data-grouping="network">Wordfence Network</a></li>
								</ul>
							</div>
							<?php endif; ?>
							<div class="wf-countries wf-countries-local">
								<?php if (count($d->countriesLocal) == 0): ?>
									<div class="wf-dashboard-item-list-text"><p><em>No blocks have been recorded.</em></p></div>
								<?php else: ?>
									<?php $data = array_slice($d->countriesLocal, 0, min(10, count($d->countriesLocal)), true); include(dirname(__FILE__) . '/widget_content_countries.php'); ?>
								<?php endif; ?>
							</div>
							<div class="wf-countries wf-countries-network wf-hidden">
								<?php if (count($d->countriesNetwork) == 0): ?>
									<div class="wf-dashboard-item-list-text"><p><em>No blocks have been recorded.</em></p></div>
								<?php else: ?>
									<?php $data = array_slice($d->countriesNetwork, 0, min(10, count($d->countriesNetwork)), true); include(dirname(__FILE__) . '/widget_content_countries.php'); ?>
								<?php endif; ?>
							</div>
							<script type="application/javascript">
								(function($) {
									$('.wf-dashboard-countries').on('click', function(e) {
										e.preventDefault();
										e.stopPropagation();

										$(this).closest('ul').find('li').removeClass('wf-active');
										$(this).closest('li').addClass('wf-active');

										$('.wf-countries').addClass('wf-hidden');
										$('.wf-countries-' + $(this).data('grouping')).removeClass('wf-hidden');
									});
								})(jQuery);
							</script>
						</div>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>