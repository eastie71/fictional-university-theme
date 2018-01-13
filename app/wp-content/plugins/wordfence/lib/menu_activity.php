<?php if (wfConfig::liveTrafficEnabled() && wfConfig::get('liveActivityPauseEnabled')): ?>
	<div id="wfLiveTrafficOverlayAnchor"></div>
	<div id="wfLiveTrafficDisabledMessage">
		<h2>Live Updates Paused<br /><small>Click inside window to resume</small></h2>
	</div>
<?php endif ?>
<div class="wrap wordfence">
	<div class="wf-page-title">
		<div class="wordfence-lock-icon wordfence-icon32"></div>
		<h2 id="wfHeading">Site Activity in Real-Time</h2>
		<div class="wfOnOffSwitch" id="wfOnOffSwitchID">
			<input type="checkbox" name="wfOnOffSwitch" class="wfOnOffSwitch-checkbox"
				   id="wfLiveTrafficOnOff" <?php if (wfConfig::liveTrafficEnabled()) {
				echo ' checked ';
			} ?>>
			<label class="wfOnOffSwitch-label" for="wfLiveTrafficOnOff">
				<div class="wfOnOffSwitch-inner"></div>
				<div class="wfOnOffSwitch-switch"></div>
			</label>
		</div>
	</div>
	<div class="wp-header-end"></div>
	
	<a href="http://docs.wordfence.com/en/Live_traffic" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="http://docs.wordfence.com/en/Live_traffic" target="_blank" rel="noopener noreferrer">Learn more about Wordfence Live Traffic</a>

	<div class="wordfenceModeElem" id="wordfenceMode_activity"></div>
	<?php include('live_activity.php'); ?>
	<div class="wf-container-fluid">
		<div class="wf-row">
			<?php
			$rightRail = new wfView('marketing/rightrail');
			echo $rightRail;
			?>
			<div class="<?php echo wfStyle::contentClasses(); ?>">
			<?php
			$overridden = false;
			if (!wfConfig::liveTrafficEnabled($overridden)):
			?>
				<div id="wordfenceLiveActivityDisabled"><p><strong>Live activity is disabled<?php if ($overridden) { echo ' by the host'; } ?>.</strong> Login and firewall activity will still appear below.</p></div>
			<?php endif ?>
				<div class="wf-row wf-add-bottom-small">
					<div class="wf-col-xs-12" id="wf-live-traffic-legend-wrapper">
						<div id="wf-live-traffic-legend-placeholder"></div>
						<div id="wf-live-traffic-legend">
							<ul>
								<li class="wfHuman">Human</li>
								<li class="wfBot">Bot</li>
								<li class="wfNotice">Warning</li>
								<li class="wfBlocked">Blocked</li>
							</ul>
						</div>
					</div>
				</div>
				<div class="wf-row">
					<div class="wf-col-xs-12">
						<div id="wf-live-traffic" class="wfTabsContainer">
							<form data-bind="submit: reloadListings">
			
								<?php if (defined('WP_DEBUG') && WP_DEBUG && false): ?>
									<pre data-bind="text: 'DEBUG: ' + sql(), visible: sql"></pre>
								<?php endif ?>
			
								<div class="wfActEvent wf-live-traffic-filter">
									<h2>Filter Traffic:</h2>
			
									<select id="wf-lt-preset-filters" data-bind="options: presetFiltersOptions, optionsText: presetFiltersOptionsText,
										value: selectedPresetFilter">
									</select>
									&nbsp;&nbsp;
									<label>
										<input data-bind="checked: showAdvancedFilters" type="checkbox">
										Show Advanced Filters
									</label>
								</div>
			
								<div class="wfActEvent" data-bind="visible: showAdvancedFilters" id="wf-lt-advanced-filters">
									<div class="wf-live-traffic-filter-detail">
										<div>
											<div data-bind="foreach: filters">
												<div class="wf-live-traffic-filter-item">
													<div class="wf-live-traffic-filter-item-parameters">
														<div>
															<select name="param[]" class="wf-lt-advanced-filters-param" data-bind="options: filterParamOptions, optionsText: filterParamOptionsText, value: selectedFilterParamOptionValue, optionsCaption: 'Filter...'"></select>
														</div>
														<div data-bind="visible: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() != 'bool'">
															<select name="operator[]" class="wf-lt-advanced-filters-operator" data-bind="options: filterOperatorOptions, optionsText: filterOperatorOptionsText, value: selectedFilterOperatorOptionValue"></select>
														</div>
														<div data-bind="attr: {colSpan: (selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'bool' ? 2 : 1)}" class="wf-lt-advanced-filters-value-cell">
															<span data-bind="if: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'enum'">
																<select data-bind="options: selectedFilterParamOptionValue().values, optionsText: selectedFilterParamOptionValue().optionsText, value: value"></select>
															</span>
			
															<span data-bind="if: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'text'">
																<input data-bind="value: value" type="text">
															</span>
			
															<span data-bind="if: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'bool'">
																<label>Yes <input data-bind="checked: value" type="radio" value="1"></label>
																<label>No <input data-bind="checked: value" type="radio" value="0"></label>
															</span>
														</div>
													</div>
													<div>
														<!--<button data-bind="click: $root.removeFilter" type="button" class="wf-btn wf-btn-default">Remove</button> -->
														<a href="#" data-bind="click: $root.removeFilter" class="wf-live-traffic-filter-remove"><i class="wf-ion-trash-a"></i></a>
													</div>
												</div>
											</div>
											<div>
											<tr>
												<td colspan="3">
													<div class="wf-pad-small">
														<button type="button" class="wf-btn wf-btn-default" data-bind="click: addFilter">
															Add Filter
														</button>
													</div>
												</td>
											</tr>
											</div>
										</div>
										<div class="wf-form wf-form-horizontal">
												<div class="wf-form-group">
													<label for="wf-live-traffic-from" class="wf-col-sm-2">From:&nbsp;</label>
													<div class="wf-col-sm-10">
														<input placeholder="Start date" id="wf-live-traffic-from" type="text" class="wf-datetime" data-bind="value: startDate, datetimepicker: null, datepickerOptions: { timeFormat: 'hh:mm tt z' }">
														<button data-bind="click: startDate('')" class="wf-btn wf-btn-default wf-btn-sm" type="button">Clear</button>
													</div>
												</div>
												<div class="wf-form-group">
													<label for="wf-live-traffic-to" class="wf-col-sm-2">To:&nbsp;</label>
													<div class="wf-col-sm-10">
														<input placeholder="End date" id="wf-live-traffic-to" type="text" class="wf-datetime" data-bind="value: endDate, datetimepicker: null, datepickerOptions: { timeFormat: 'hh:mm tt z' }">
														<button data-bind="click: endDate('')" class="wf-btn wf-btn-default wf-btn-sm" type="button">Clear</button>
													</div>
												</div>
												<div class="wf-form-group">
													<label for="wf-live-traffic-group-by" class="wf-col-sm-2">Group&nbsp;By:&nbsp;</label>
													<div class="wf-col-sm-10">
														<select id="wf-live-traffic-group-by" name="groupby" class="wf-lt-advanced-filters-groupby" data-bind="options: filterGroupByOptions, optionsText: filterGroupByOptionsText, value: groupBy, optionsCaption: 'None'"></select>
													</div>
												</div>
										</div>
									</div>
								</div>
							</form>
				
							<div data-bind="if: groupBy()" border="0" style="width: 100%">
								<div class="wf-filtered-traffic" data-bind="foreach: listings">
									<div>
										<div>
											<!-- ko if: $root.groupBy().param() == 'ip' -->
											<div data-bind="if: loc()">
												<img data-bind="attr: { src: '<?php echo wfUtils::getBaseURL() . 'images/flags/'; ?>' + loc().countryCode.toLowerCase() + '.png',
															alt: loc().countryName, title: loc().countryName }" width="16" height="11"
													 class="wfFlag"/>
												<a data-bind="text: (loc().city ? loc().city + ', ' : '') + loc().countryName,
															attr: { href: 'http://maps.google.com/maps?q=' + loc().lat + ',' + loc().lon + '&z=6' }"
												   target="_blank" rel="noopener noreferrer"></a>
											</div>
											<div data-bind="if: !loc()">
												An unknown location at IP <a
													data-bind="text: IP, attr: { href: WFAD.makeIPTrafLink(IP()) }" target="_blank" rel="noopener noreferrer"></a>
											</div>
				
											<div>
												<strong>IP:</strong>&nbsp;<a
													data-bind="text: IP, attr: { href: WFAD.makeIPTrafLink(IP()) }" target="_blank" rel="noopener noreferrer"></a>
												<span data-bind="if: blocked()">
													[<a data-bind="click: $root.unblockIP">unblock</a>]
												</span>
												<span data-bind="if: rangeBlocked()">
													[<a data-bind="click: $root.unblockNetwork">unblock this range</a>]
												</span>
												<span data-bind="if: !blocked() && !rangeBlocked()">
													[<a data-bind="click: $root.blockIP">block</a>]
												</span>
											</div>
											<div>
												<span class="wfReverseLookup"><span data-bind="text: IP" style="display:none;"></span></span>
											</div> 
											<!-- /ko -->
											<!-- ko if: $root.groupBy().param() == 'type' -->
											<div>
												<strong>Type:</strong>
												<span data-bind="if: jsRun() == '1'">Human</span>
												<span data-bind="if: jsRun() == '0'">Bot</span>
											</div>
											<!-- /ko -->
											<!-- ko if: $root.groupBy().param() == 'user_login' -->
											<div>
												<strong>Username:</strong> <span data-bind="text: username()"></span>
											</div>
											<!-- /ko -->
											<!-- ko if: $root.groupBy().param() == 'statusCode' -->
											<div>
												<strong>HTTP Response Code:</strong> <span data-bind="text: statusCode()"></span>
											</div>
											<!-- /ko -->
											<!-- ko if: $root.groupBy().param() == 'action' -->
											<div>
												<strong>Firewall Response:</strong> <span data-bind="text: firewallAction()"></span>
											</div>
											<!-- /ko -->
											<!-- ko if: $root.groupBy().param() == 'url' -->
											<div>
												<strong>URL:</strong> <span data-bind="text: displayURL()"></span>
											</div>
											<!-- /ko -->
											<div>
												<strong>Last Hit:</strong> <span
													data-bind="attr: { 'data-timestamp': ctime, text: 'Last hit was ' + ctime() + ' ago.' }"
													class="wfTimeAgo wfTimeAgo-timestamp"></span>
											</div>
										</div>
										<div>
											<span class="wf-filtered-traffic-hits" data-bind="text: hitCount"></span> hits
										</div>
									</div>
								</div>
							</div>
				
							<div data-bind="if: !groupBy()">
								<div id="wf-lt-listings" data-bind="foreach: listings">
									<div data-bind="attr: { id: ('wfActEvent_' + id()), 'class': cssClasses }">
										<div>
													<span data-bind="if: action() != 'loginOK' && action() != 'loginFailValidUsername' && action() != 'loginFailInvalidUsername' && user()">
														<span data-bind="html: user.avatar" class="wfAvatar"></span>
														<a data-bind="attr: { href: user.editLink }, text: user().display_name"
														   target="_blank" rel="noopener noreferrer"></a>
													</span>
													<span data-bind="if: loc()">
														<span data-bind="if: action() != 'loginOK' && action() != 'loginFailValidUsername' && action() != 'loginFailInvalidUsername' && user()"> in</span>
														<img data-bind="attr: { src: '<?php echo wfUtils::getBaseURL() . 'images/flags/'; ?>' + loc().countryCode.toLowerCase() + '.png',
															alt: loc().countryName, title: loc().countryName }" width="16"
															 height="11"
															 class="wfFlag"/>
														<a data-bind="text: (loc().city ? loc().city + ', ' : '') + loc().countryName,
															attr: { href: 'http://maps.google.com/maps?q=' + loc().lat + ',' + loc().lon + '&z=6' }"
														   target="_blank" rel="noopener noreferrer"></a>
													</span>
													<span data-bind="if: !loc()">
														<span
															data-bind="text: action() != 'loginOK' && action() != 'loginFailValidUsername' && action() != 'loginFailInvalidUsername' && user() ? 'at an' : 'An'"></span> unknown location at IP <a
															data-bind="text: IP, attr: { href: WFAD.makeIPTrafLink(IP()) }"
															target="_blank" rel="noopener noreferrer"></a>
													</span>
													<span data-bind="if: referer()">
														<span data-bind="if: extReferer()">
															arrived from <a data-bind="text: referer, attr: { href: referer }"
																			target="_blank" rel="noopener noreferrer"
																			style="color: #A00; font-weight: bold;" class="wf-split-word-xs"></a> and
														</span>
														<span data-bind="if: !extReferer()">
															left <a data-bind="text: referer, attr: { href: referer }"
																	target="_blank" rel="noopener noreferrer"
																	style="color: #999; font-weight: normal;" class="wf-split-word-xs"></a> and
														</span>
													</span>
													<span data-bind="if: statusCode() == 404">
														tried to access <span style="color: #F00;">non-existent page</span>
													</span>
			
													<span data-bind="if: statusCode() == 200 && !action()">
														visited
													</span>
													<span data-bind="if: statusCode() == 403 || statusCode() == 503">
														was <span data-bind="text: firewallAction" style="color: #F00;"></span> at
													</span>
			
													<span data-bind="if: action() == 'loginOK'">
														logged in successfully as "<strong data-bind="text: username"></strong>".
													</span>
													<span data-bind="if: action() == 'logout'">
														logged out successfully.
													</span>
													<span data-bind="if: action() == 'lostPassword'">
														requested a password reset.
													</span>
													<span data-bind="if: action() == 'loginFailValidUsername'">
														attempted a failed login as "<strong data-bind="text: username"></strong>".
													</span>
													<span data-bind="if: action() == 'loginFailInvalidUsername'">
														attempted a failed login using an invalid username "<strong
															data-bind="text: username"></strong>".
													</span>
													<span data-bind="if: action() == 'user:passwordReset'">
														changed their password.
													</span>
													<a class="wf-lt-url wf-split-word-xs"
													   data-bind="text: displayURL, attr: { href: URL, title: URL }"
													   target="_blank" rel="noopener noreferrer"></a>
										</div>
										<div>
											<span data-bind="text: timeAgo, attr: { 'data-timestamp': ctime }"
														  class="wfTimeAgo wfTimeAgo-timestamp"></span>&nbsp;&nbsp;
													<strong>IP:</strong> <a
														data-bind="attr: { href: WFAD.makeIPTrafLink(IP()) }, text: IP"
														target="_blank" rel="noopener noreferrer"></a>
													<span data-bind="if: blocked()">
														[<a data-bind="click: $root.unblockIP">unblock</a>]
													</span>
													<span data-bind="if: rangeBlocked()">
														[<a data-bind="click: $root.unblockNetwork">unblock this range</a>]
													</span>
													<span data-bind="if: !blocked() && !rangeBlocked()">
														[<a data-bind="click: $root.blockIP">block</a>]
													</span>
													&nbsp;
													<span class="wfReverseLookup">
														<span data-bind="text: IP"
															  style="display:none;"></span>
													</span>
										</div>
			
											<div data-bind="if: browser() && browser().browser != 'Default Browser'">
													<strong>Browser:</strong>
													<span data-bind="text: browser().browser +
														(browser().version ? ' version ' + browser().version : '') +
														(browser().platform  && browser().platform != 'unknown' ? ' running on ' + browser().platform : '')
														">
													</span>
											</div>
												<div data-bind="text: UA" style="color: #AAA;"></div>
											<div>
													<span data-bind="if: blocked()">
														<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																data-bind="click: $root.unblockIP">
															Unblock this IP
														</a>
													</span>
													<span data-bind="if: rangeBlocked()">
														<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																data-bind="click: $root.unblockNetwork">Unblock this range
														</a>
													</span>
													<span data-bind="if: !blocked() && !rangeBlocked()">
														<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																data-bind="click: $root.blockIP">
															Block this IP
														</a>
													</span>
													<a class="wf-btn wf-btn-default wf-btn-sm"
															data-bind="attr: { href: 'admin.php?page=WordfenceTools&whoisval=' + IP() + '&wfnetworkblock=1#top#whois'}">
														Block this network
													</a>
													<a class="wf-btn wf-btn-default wf-btn-sm" data-bind="text: 'Run WHOIS on ' + IP(),
														attr: { href: 'admin.php?page=WordfenceTools&whoisval=' + IP() + '#top#whois' }"
															target="_blank" rel="noopener noreferrer"></a>
													<a class="wf-btn wf-btn-default wf-btn-sm"
															data-bind="attr: { href: WFAD.makeIPTrafLink(IP()) }" target="_blank" rel="noopener noreferrer">
														See recent traffic
													</a>
													<span data-bind="if: action() == 'blocked:waf'">
														<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																data-bind="click: function () { $root.whitelistWAFParamKey(actionData().path, actionData().paramKey, actionData().failedRules) }"
																title="If this is a false positive, you can exclude this parameter from being filtered by the firewall">
															Whitelist param from Firewall
														</a>
														<?php if (WFWAF_DEBUG): ?>
															<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																	data-bind="attr: { href: '<?php echo esc_js(home_url()) ?>?_wfsf=debugWAF&nonce=' + WFAD.nonce + '&hitid=' + id() }" target="_blank" rel="noopener noreferrer">
																Debug this Request
															</a>
														<?php endif ?>
													</span>
											</div>
									</div>
								</div>
							</div>
							<div data-bind="if: !listings">
								No events to report yet.
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/x-jquery-template" id="wfWelcomeContent3">
	<div>
		<h3>Welcome to ALL Your Site Visits, Live!</h3>
		<strong><p>Traffic you've never seen before</p></strong>

		<p>
			Google Analytics and other Javascript analytics packages can't show you crawlers, RSS feed readers, hack
			attempts and other non-human traffic that hits your site.
			Wordfence runs on your server and shows you, in real-time, all the traffic that is hitting your server right
			now, including those non-human crawlers, feed readers and hackers that Analytics can't track.
		</p>
		<strong><p>Separated into the important categories</p></strong>

		<p>
			You'll notice that you can filter traffic. The options include "All Hits" to simply view everything that is
			hitting your server right now. We then sub-divide that into human visits, your site members, crawlers -
			which we further break down into Google crawlers - and various other choices.
		</p>

		<p>
			<strong>How to use this page when your site is being attacked</strong>
		</p>

		<p>
			Start by looking at "All Hits" because you may notice that a single IP address is generating most of your
			traffic.
			This could be a denial of service attack, someone stealing your content or a hacker probing for weaknesses.
			If you see a suspicious pattern, simply block that IP address. If they attack from a different IP on the
			same network, simply block that network.
			You can also run a WHOIS on any IP address to find the host and report abuse via email.
		</p>

		<p>
			If you don't see any clear patterns of attack, take a look at "Pages Not Found" which will show you IP
			addresses that are generating excessive page not found errors. It's common for an attacker probing for
			weaknesses to generate a lot of these errors. If you see one IP address that is generating many of these
			requests, and it's not Google or another trusted crawler, then you should consider blocking them.
		</p>

		<p>
			Next look at "Logins and Logouts". If you see a large number of failed logins from an IP address, block them
			if you don't recognize who they are.
		</p>

	</div>
</script>
