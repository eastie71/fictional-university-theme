<?php
//$d is defined here as a wfDashboard instance

$initial = false;
if (!isset($limit)) { $limit = 10; $initial = true; }
?>
<div class="wf-row">
	<div class="wf-col-xs-12">
		<div class="wf-dashboard-item active">
			<div class="wf-dashboard-item-inner">
				<div class="wf-dashboard-item-content">
					<div class="wf-dashboard-item-title">
						<strong>Top IPs Blocked</strong>
					</div>
					<div class="wf-dashboard-item-action"><div class="wf-dashboard-item-action-disclosure"></div></div>
				</div>
			</div>
			<div class="wf-dashboard-item-extra">
				<ul class="wf-dashboard-item-list">
					<li>
						<div>
							<div class="wf-dashboard-toggle-btns">
								<ul class="wf-pagination wf-pagination-sm">
									<li class="wf-active"><a href="#" class="wf-dashboard-ips" data-grouping="24h">24 Hours</a></li>
									<li><a href="#" class="wf-dashboard-ips" data-grouping="7d">7 Days</a></li>
									<li><a href="#" class="wf-dashboard-ips" data-grouping="30d">30 Days</a></li>
								</ul>
							</div>
							<div class="wf-ips wf-ips-24h">
								<?php if (count($d->ips24h) == 0): ?>
									<div class="wf-dashboard-item-list-text"><p><em>No blocks have been recorded.</em></p></div>
								<?php else: ?>
									<?php $data = array_slice($d->ips24h, 0, min($limit, count($d->ips24h)), true); include(dirname(__FILE__) . '/widget_content_ips.php'); ?>
									<?php if (count($d->ips24h) > $limit && $initial): ?>
										<div class="wf-dashboard-item-list-text"><div class="wf-dashboard-show-more" data-grouping="ips" data-period="24h"><a href="#">Show more</a></div></div>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<div class="wf-ips wf-ips-7d wf-hidden">
								<?php if (count($d->ips7d) == 0): ?>
									<div class="wf-dashboard-item-list-text"><p><em>No blocks have been recorded.</em></p></div>
								<?php else: ?>
									<?php $data = array_slice($d->ips7d, 0, min($limit, count($d->ips7d)), true); include(dirname(__FILE__) . '/widget_content_ips.php'); ?>
									<?php if (count($d->ips7d) > $limit && $initial): ?>
										<div class="wf-dashboard-item-list-text"><div class="wf-dashboard-show-more" data-grouping="ips" data-period="7d"><a href="#">Show more</a></div></div>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<div class="wf-ips wf-ips-30d wf-hidden">
								<?php if (count($d->ips30d) == 0): ?>
									<div class="wf-dashboard-item-list-text"><p><em>No blocks have been recorded.</em></p></div>
								<?php else: ?>
									<?php $data = array_slice($d->ips30d, 0, min($limit, count($d->ips30d)), true); include(dirname(__FILE__) . '/widget_content_ips.php'); ?>
									<?php if (count($d->ips30d) > $limit && $initial): ?>
										<div class="wf-dashboard-item-list-text"><div class="wf-dashboard-show-more" data-grouping="ips" data-period="30d"><a href="#">Show more</a></div></div>
									<?php endif; ?>
								<?php endif; ?>
							</div>
							<script type="application/javascript">
								(function($) {
									$('.wf-dashboard-ips').on('click', function(e) {
										e.preventDefault();
										e.stopPropagation();

										$(this).closest('ul').find('li').removeClass('wf-active');
										$(this).closest('li').addClass('wf-active');

										$('.wf-ips').addClass('wf-hidden');
										$('.wf-ips-' + $(this).data('grouping')).removeClass('wf-hidden');
									});
									
									$('.wf-ips .wf-dashboard-show-more a').on('click', function(e) {
										e.preventDefault();
										e.stopPropagation();
										
										var grouping = $(this).parent().data('grouping');
										var period = $(this).parent().data('period');
										
										$(this).closest('.wf-dashboard-item-list-text').fadeOut();

										var self = this;
										WFAD.ajax('wordfence_dashboardShowMore', {
											grouping: grouping,
											period: period
										}, function(res) {
											if (res.ok) {
												var table = $('#ips-data-template').tmpl(res);
												$(self).closest('.wf-ips').css('overflow-y', 'auto');
												$(self).closest('.wf-ips').find('table').replaceWith(table);
											}
											else {
												WFAD.colorbox('300px', 'An error occurred', 'We encountered an error trying load more data.');
												$(this).closest('.wf-dashboard-item-list-text').fadeIn();
											}
										});
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
<script type="text/x-jquery-template" id="ips-data-template">
	<table class="wf-table wf-table-hover">
		<thead>
		<tr>
			<th>IP</th>
			<th colspan="2">Country</th>
			<th>Block Count</th>
		</tr>
		</thead>
		<tbody>
		{{each(idx, d) data}}
		<tr>
			<td>${d.IP}</td>
			<td>${d.countryName}</td>
			<td><img src="${d.countryFlag}" class="wfFlag" height="11" width="16" alt="${d.countryName}" title="${d.countryName}"></td>
			<td>${d.blockCount}</td>
		</tr>
		{{/each}}
		</tbody>
	</table>
</script>