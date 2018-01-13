<div class="wf-row">
	<div class="wf-col-xs-12">
		<div class="wf-dashboard-item active">
			<div class="wf-dashboard-item-inner">
				<div class="wf-dashboard-item-content">
					<div class="wf-dashboard-item-title">
						<strong>Firewall Summary - Attacks Blocked for <?php echo esc_html(preg_replace('/^[^:]+:\/\//', '', network_site_url())); ?></strong>
					</div>
					<div class="wf-dashboard-item-action"><div class="wf-dashboard-item-action-disclosure"></div></div>
				</div>
			</div>
			<div class="wf-dashboard-item-extra">
				<ul class="wf-dashboard-item-list">
					<li>
						<?php if ($d->localBlockToday === null): ?>
							<div class="wf-dashboard-item-list-text"><em>No blocks have been recorded.</em></div>
						<?php else: ?>
							<ul class="wf-dashboard-item-list wf-dashboard-item-list-horizontal">
								<li>
									<div class="wf-dashboard-item-labeled-count">
										<div class="wf-dashboard-item-labeled-count-count"><?php echo $d->localBlockToday; ?></div>
										<div class="wf-dashboard-item-labeled-count-label">Today</div>
									</div>
								</li>
								<li>
									<div class="wf-dashboard-item-labeled-count">
										<div class="wf-dashboard-item-labeled-count-count"><?php echo $d->localBlockWeek; ?></div>
										<div class="wf-dashboard-item-labeled-count-label">Week</div>
									</div>
								</li>
								<li>
									<div class="wf-dashboard-item-labeled-count">
										<div class="wf-dashboard-item-labeled-count-count"><?php echo $d->localBlockMonth; ?></div>
										<div class="wf-dashboard-item-labeled-count-label">Month</div>
									</div>
								</li>
							</ul>
						<?php endif; ?>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>