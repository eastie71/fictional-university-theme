<?php
$waf = wfWAF::getInstance();
$config = $waf->getStorageEngine();
$wafConfigURL = network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend');
$wafRemoveURL = network_admin_url('admin.php?page=WordfenceWAF&wafAction=removeAutoPrepend');
/** @var array $wafData */
?>
<div class="wordfenceHelpLink"><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer" class="wfhelp"></a><a href="<?php echo $helpLink; ?>" target="_blank" rel="noopener noreferrer"><?php echo $helpLabel; ?></a></div>
<div>
	<div class="wordfenceModeElem" id="wordfenceMode_waf"></div>
	<?php
	if (defined('WFWAF_ENABLED') && !WFWAF_ENABLED) :
		$message = 'To allow the firewall to re-enable, please remove this line from the appropriate file.';
		$pattern = '/define\s*\(\s*(?:\'WFWAF_ENABLED\'|"WFWAF_ENABLED")\s*,(.+?)\)/x';
		$checkFiles = array(
			ABSPATH . 'wp-config.php',
			wordfence::getWAFBootstrapPath(),
		);

		foreach($checkFiles as $path) {
			if (!file_exists($path)) {
				continue;
			}

			if (($contents = file_get_contents($path)) !== false) {
				if (preg_match($pattern, $contents, $matches) && (trim($matches[1]) == 'false' || trim($matches[1]) == '0')) {
					$message = "To allow the firewall to re-enable, please remove this line from the file '" . $path . "'.";
					break;
				}
			}
		}

	?>
	<p class="wf-notice">The Wordfence Firewall is currently disabled because WFWAF_ENABLED is overridden and set to false. <?php echo $message; ?></p>
	<?php endif ?>

	<?php if (!empty($storageExceptionMessage)): ?>
		<div class="wf-add-top wf-add-bottom">
			<strong><?php echo wp_kses($storageExceptionMessage, 'post') ?></strong>
		</div>
	<?php elseif (!empty($wafActionContent)): ?>
		<div> 
			<!-- begin wafActionContent -->
		<?php echo $wafActionContent ?>
			<!-- end wafActionContent -->

		<?php if (!empty($_REQUEST['wafAction']) && $_REQUEST['wafAction'] == 'removeAutoPrepend') { ?>
			<p class="wf-notice"><em>If you cannot complete the uninstallation process, <a target="_blank" rel="noopener noreferrer" href="https://docs.wordfence.com/en/Web_Application_Firewall_FAQ#How_can_I_remove_the_firewall_setup_manually.3F">click here for help</a>.</em></p>
		<?php }
		else if (!empty($_REQUEST['wafAction']) && $_REQUEST['wafAction'] == 'updateSuPHPConfig') {
			//Do nothing
		}
		else { ?>
			<p class="wf-notice"><em>If you cannot complete the setup process, <a target="_blank" rel="noopener noreferrer" href="https://docs.wordfence.com/en/Web_Application_Firewall_Setup">click here for help</a>.</em></p>
		<?php } ?>
		</div>
	<?php else: ?>

		<?php if (!empty($configExceptionMessage)): ?>
			<div class="wf-add-top wf-add-bottom">
				<strong><?php echo wp_kses($configExceptionMessage, 'post') ?></strong>
			</div>
		<?php endif ?>

		<?php if (!wfConfig::get('isPaid')) { ?>
			<div class="wf-premium-callout wf-add-bottom">
				<h3>The Wordfence Firewall stops you from getting hacked</h3>

				<p>As new threats emerge, the Threat Defense Feed is updated to protect you from new attacks. The
					Premium version of the Threat Defense Feed is updated in real-time protecting you immediately. As a
					free user <strong>you are receiving the community version</strong> of the feed which is updated 30
					days later.</p>

				<p class="center"><a class="wf-btn wf-btn-primary wf-btn-callout" href="https://www.wordfence.com/wafOptions1/wordfence-signup/">Get Premium</a></p>
			</div>
		<?php } ?>

		<?php if (WFWAF_SUBDIRECTORY_INSTALL): ?>
			<div class="wf-notice">
				You are currently running the Wordfence Web Application Firewall from another WordPress installation.
				Please <a href="<?php echo network_admin_url('admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend'); ?>">click here</a> to configure the Firewall to run correctly on this site.
			</div>
		<?php else: ?>
				<?php if (wfConfig::get('isPaid')) { ?>
					<div class="wf-success" style="max-width: 881px;"> 
						You are running the Premium version of the Threat Defense Feed which is updated in real-time as new
						threats emerge. <a href="https://www.wordfence.com/zz14/sign-in/" target="_blank" rel="noopener noreferrer">Protect additional sites.</a>
					</div>
				<?php } ?>
				<form action="javascript:void(0)" id="waf-config-form" class="wf-form-horizontal">
					<div class="wf-form-group">
						<label for="wf-waf-protection-mode" class="wf-col-sm-5 wf-col-md-3 wf-control-label waf-config-label">Protection Level <a href="http://docs.wordfence.com/en/WAF#Protection_Level" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<div class="wf-col-sm-7 wf-col-md-5">
						<?php if (!WFWAF_AUTO_PREPEND): ?>
							<p class="wf-form-control-static wf-notice-text">Basic WordPress Protection</p>
							<p class="wf-form-control-static"><a class="wf-btn wf-btn-primary" href="<?php echo $wafConfigURL ?>">Optimize the Wordfence Firewall</a></p>
						<?php else: ?>
							<p class="wf-form-control-static wf-success-text">Extended Protection</p>
						<?php endif ?>
						</div>
					</div>
					<div class="wf-form-group">
						<label for="input-wafStatus" class="wf-col-sm-5 wf-col-md-3 wf-control-label waf-config-label">Firewall Status <a href="http://docs.wordfence.com/en/WAF#Firewall_Status" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						<?php $wafStatus = (!WFWAF_ENABLED ? 'disabled' : $config->getConfig('wafStatus')); ?>
						<div class="wf-col-sm-7 wf-col-md-5">
							<select id="input-wafStatus" name="wafStatus" class="wf-form-control"<?php echo !WFWAF_ENABLED ? ' disabled' : '' ?>>
								<option<?php echo $wafStatus == 'enabled' ? ' selected' : '' ?> class="wafStatus-enabled" value="enabled">Enabled and Protecting</option>
								<option<?php echo $wafStatus == 'learning-mode' ? ' selected' : '' ?> class="wafStatus-learning-mode" value="learning-mode">Learning Mode</option>
								<option<?php echo $wafStatus == 'disabled' ? ' selected' : '' ?> class="wafStatus-disabled" value="disabled">Disabled</option>
							</select>
							<script type="application/javascript">
								(function($) {
									$('#input-wafStatus').val(<?php echo json_encode($wafStatus) ?>)
										.on('change', function() {
											var val = $(this).val();
											$('.wafStatus-description').hide();
											$('#wafStatus-' + val + '-description').show();
										});
								})(jQuery);
							</script>
						</div>
					</div>
					<div id="waf-learning-mode-grace-row" class="wf-form-group">
						<div class="wf-col-sm-7 wf-col-sm-offset-5 wf-col-md-9 wf-col-md-offset-3">
							<div class="wf-form-inline">
								<div class="wf-checkbox">
									<label>
										<input type="checkbox" name="learningModeGracePeriodEnabled" value="1"<?php echo $config->getConfig('learningModeGracePeriodEnabled') ? ' checked' : ''; ?>> Automatically switch to Enabled Mode on
									</label>
								</div>
								<input type="text" name="learningModeGracePeriod" id="input-learningModeGracePeriod" class="wf-datetime wf-form-control" placeholder="Enabled until..." data-value="<?php echo esc_attr($config->getConfig('learningModeGracePeriod') ? (int) $config->getConfig('learningModeGracePeriod') : '') ?>">
							</div>
						</div>
					</div>
					<div class="wf-form-group">
						<div class="wf-col-sm-7 wf-col-sm-offset-5 wf-col-md-offset-3">
							<div class="wafStatus-description" id="wafStatus-enabled-description">
								In this mode, the Wordfence Web Application Firewall is actively blocking requests
								matching known attack patterns, and is actively protecting your site from attackers.
							</div>
							<div class="wafStatus-description" id="wafStatus-learning-mode-description">
								When you first install the Wordfence Web Application Firewall, it will be in
								learning
								mode. This allows
								Wordfence to learn about your site so that we can understand how to protect it and
								how
								to allow normal visitors through the firewall. We recommend you let Wordfence learn
								for
								a week before you enable the firewall.
							</div>
							<div class="wafStatus-description" id="wafStatus-disabled-description">
								In this mode, the Wordfence Web Application Firewall is functionally turned off and
								does not run any of its rules or analyze the request in any way.
							</div>
						</div>
					</div>
					<div class="wf-form-group">
						<div class="wf-col-sm-7 wf-col-sm-offset-5 wf-col-md-offset-3">
							<button type="submit" class="wf-btn wf-btn-primary wf-btn-callout"<?php echo !WFWAF_ENABLED ? ' disabled' : '' ?>>Save</button>
						</div>
					</div>

					<br>

					<h2>Rules <a href="http://docs.wordfence.com/en/WAF#Rules" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h2>

					<div id="waf-rules-wrapper"></div>

					<p>
						<?php if (wfConfig::get('isPaid')): ?>
							You are running Wordfence Premium firewall rules.
						<?php else: ?>
							You are running Wordfence community firewall rules.
						<?php endif ?>
						<!--						<em id="waf-rules-last-updated"></em>-->
					</p>

				</form>

				<br>

				<h2>Whitelisted URLs <a href="http://docs.wordfence.com/en/WAF#Whitelisted_URLs" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></h2>

				<p><em>The URL/parameters in this table will not be tested by the firewall. They are typically added
						while the firewall is in Learning Mode or by an admin who identifies a particular action/request
						is a false positive.</em></p>

				<div id="whitelist-form">
					<p><strong>Add Whitelisted URL/Param:</strong></p>
					<div class="wf-form-inline">
						<div class="wf-form-group">
							<label for="whitelistURL">URL:</label>
							<input class="wf-form-control" type="text" name="whitelistURL" id="whitelistURL">
						</div>
						<div class="wf-form-group">
							<label for="whitelistParam">Param:</label>
							<select class="wf-form-control" name="whitelistParam" id="whitelistParam">
								<option value="request.body">POST Body</option>
								<option value="request.cookies">Cookie</option>
								<option value="request.fileNames">File Name</option>
								<option value="request.headers">Header</option>
								<option value="request.queryString">Query String</option>
							</select>
						</div>
						<div class="wf-form-group">
							<label for="whitelistParamName">Param Name:</label>
							<input class="wf-form-control" type="text" name="whitelistParamName" id="whitelistParamName">
						</div>
						<button type="button" class="wf-btn wf-btn-default" id="waf-whitelisted-urls-add">Add</button>
					</div>
				</div>

				<div id="waf-whitelisted-urls-wrapper"></div>
				
				<div id="whitelist-monitor" class="wf-form-horizontal">
					<div class="wf-form-group">
						<div class="wf-col-xs-12">
							<p class="wf-form-control-static"><strong>Monitor Background Requests for False Positives</strong> <a href="https://docs.wordfence.com/en/WAF#Whitelisted_URLs" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></p>
						</div>
						<div class="wf-col-xs-12">
							<label class="wf-checkbox-inline"><input type="checkbox" id="monitor-front" name="monitor-front" value="1"<?php echo wfConfig::get('ajaxWatcherDisabled_front') ? '' : ' checked'; ?>>Front</label>
							<label class="wf-checkbox-inline"><input type="checkbox" id="monitor-admin" name="monitor-admin" value="1"<?php echo wfConfig::get('ajaxWatcherDisabled_admin') ? '' : ' checked'; ?>>Admin Panel</label>
						</div>
					</div>
					<br>
					
				</div>
				
				<h2>Advanced Configuration</h2>
				<div id="waf-advanced-options" class="wf-form-horizontal">
					<div class="wf-form-group">
						<div class="wf-col-xs-12">
							<p class="wf-form-control-static"><strong>Other Options</strong></p>
						</div>
						<div class="wf-col-xs-12">
							<label class="wf-checkbox-inline"><input type="checkbox" id="waf-disable-ip-blocking" name="waf-disable-ip-blocking" value="1"<?php echo $config->getConfig('disableWAFIPBlocking') ? ' checked' : ''; ?>>Delay IP and Country blocking until after WordPress and plugins have loaded (only process firewall rules early) <a href="https://docs.wordfence.com/en/WAF#Advanced_Configuration" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label>
						</div>
						<div class="wf-col-xs-12">
							<label class="wf-checkbox-inline<?php echo (wfConfig::p() ? '' : ' wf-disabled'); ?>"><input type="checkbox" id="waf-disable-blacklist-blocking" name="waf-disable-blacklist-blocking" value="1"<?php echo !$config->getConfig('disableWAFBlacklistBlocking') ? ' checked' : ''; echo (wfConfig::p() ? '' : ' disabled'); ?>>Preemptively block malicious IP addresses <span style="color: #F00;">Premium Feature</span> <a href="https://docs.wordfence.com/en/WAF#Advanced_Configuration" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></label> 
						</div>
					</div>
				</div>
				
				<?php if (WFWAF_AUTO_PREPEND) : ?>
				<div class="wf-form-horizontal">
					<div class="wf-form-group">
						<div class="wf-col-xs-12">
							<p class="wf-form-control-static"><strong>Remove Extended Protection <a href="https://docs.wordfence.com/en/Web_Application_Firewall_FAQ#How_can_I_remove_the_firewall_setup_manually.3F" target="_blank" rel="noopener noreferrer" class="wfhelp"></a></strong></p>
						</div>
						<div class="wf-col-xs-12">
							<p class="wf-form-control-static"><em>If you're moving to a new host or a new installation location, you may need to temporarily disable extended protection to avoid any file not found errors. Use this action to remove the configuration changes that enable extended protection mode or you can <a href="https://docs.wordfence.com/en/Web_Application_Firewall_FAQ#How_can_I_remove_the_firewall_setup_manually.3F" target="_blank" rel="noopener noreferrer">remove them manually</a>.</em></p>
						</div>
					</div>
					<div class="wf-form-group">
						<div class="wf-col-xs-12">
							<a href="<?php echo $wafRemoveURL; ?>" class="wf-btn wf-btn-default wf-btn-sm" id="waf-remove-extended">Remove Extended Protection</a> 
						</div>
					</div>
				</div>
				<?php endif ?>
		<?php endif ?>
	<?php endif ?>
</div>

<script type="text/x-jquery-template" id="waf-rules-tmpl">
	<table class="wf-striped-table">
		<thead>
		<tr>
			<th style="width: 5%">Enabled</th>
			<th>Category</th>
			<th>Description</th>
		</tr>
		</thead>
		<tbody>
		{{each(idx, rule) rules}}
		<tr>
			<td style="text-align: center">
				<input type="checkbox" name="ruleEnabled"
				       value="${rule.ruleID}" {{if (!disabledRules[rule.ruleID])}} checked{{/if}}>
			</td>
			<td>${rule.category}</td>
			<td>${rule.description}</td>
		</tr>
		{{/each}}
		{{if (rules.length == 0)}}
		<tr>
			<td colspan="4">No rules currently set.
				<a href="#" onclick="WFAD.wafUpdateRules();return false;">Click here</a> to pull down the latest from
				the Wordfence servers.
			</td>
		</tr>
		{{/if}}
		</tbody>
	</table>
</script>
<script type="text/x-jquery-template" id="waf-whitelisted-urls-tmpl">
	<?php ob_start() ?>
	<form action="javascript:void(0)" class="wf-bulk-action wf-whitelist-actions wf-form-inline">
		<select class="wf-form-control" name="wf-bulk-action">
			<option value="">Bulk Actions</option>
			<option value="delete">Delete</option>
			<option value="enable">Enable</option>
			<option value="disable">Disable</option>
		</select>
		<button type="submit" class="wf-btn wf-btn-default">Apply</button>
	</form>
	<?php
	$bulkActionForm = ob_get_clean();
	echo $bulkActionForm;
	?>
	<div class="whitelist-table-container">
		<table class="wf-striped-table whitelist-table">
			<thead>
			<tr>
				<th style="width: 2%;text-align: center"><input type="checkbox" class="wf-whitelist-table-bulk-action"></th>
				<th style="width: 5%;">Enabled</th>
				<th>URL</th>
				<th>Param</th>
				<th>Created</th>
				<th>Source</th>
				<th>User</th>
				<th>IP</th>
				<th>Action</th>
			</tr>
			</thead>
			{{if whitelistedURLParams.length > 5}}
			<tfoot>
			<tr>
				<th><input type="checkbox" class="wf-whitelist-table-bulk-action"></th>
				<th style="width: 5%;">Enabled</th>
				<th>URL</th>
				<th>Param</th>
				<th>Created</th>
				<th>Source</th>
				<th>User</th>
				<th>IP</th>
				<th>Action</th>
			</tr>
			{{/if}}
			</tfoot>
			<tbody>
			<tr class="wf-table-filters">
				<td colspan="2"></td>
				<td><input data-column-index="2" placeholder="Filter URL" type="text"></td>
				<td><input data-column-index="3" placeholder="Filter Param" type="text"></td>
				<td><input data-column-index="4" placeholder="Filter Created" type="text"></td>
				<td><input data-column-index="5" placeholder="Filter Source" type="text"></td>
				<td><input style="max-width:100px;" data-column-index="6" placeholder="Filter User" type="text"></td>
				<td><input style="max-width:100px;" data-column-index="7" placeholder="Filter IP" type="text"></td>
				<td></td>
			</tr>
			{{each(idx, whitelistedURLParam) whitelistedURLParams}}
			<tr data-index="${idx}">
				<td style="text-align: center;"><input type="checkbox" class="wf-whitelist-table-bulk-checkbox"></td>
				<td style="text-align: center;">
					<input name="replaceWhitelistedEnabled" type="hidden" value="${whitelistedURLParam.data.disabled}">
					<input name="whitelistedEnabled" type="checkbox" value="1"
						   {{if (!whitelistedURLParam.data.disabled)}} checked{{/if}}>
				</td>
				<td>
					<input name="replaceWhitelistedPath" type="hidden" value="${whitelistedURLParam.path}">
					<span class="whitelist-display">${WFAD.htmlEscape(WFAD.base64_decode(whitelistedURLParam.path))}</span>
					<input name="whitelistedPath" class="whitelist-edit whitelist-path" type="text"
						   value="${WFAD.htmlEscape(WFAD.base64_decode(whitelistedURLParam.path))}">
				</td>
				<td>
					<input name="replaceWhitelistedParam" type="hidden" value="${whitelistedURLParam.paramKey}">
					<span class="whitelist-display">${WFAD.htmlEscape(WFAD.base64_decode(whitelistedURLParam.paramKey))}</span>
					<input name="whitelistedParam" class="whitelist-edit whitelist-param-key"
						   type="text" value="${WFAD.htmlEscape(WFAD.base64_decode(whitelistedURLParam.paramKey))}">
				</td>
				<td>
					{{if (whitelistedURLParam.data.timestamp)}}
					${WFAD.dateFormat((new Date(whitelistedURLParam.data.timestamp * 1000)))}
					{{else}}
					-
					{{/if}}
				</td>
				<td>
					{{if (whitelistedURLParam.data.description)}}
					${whitelistedURLParam.data.description}
					{{else}}
					-
					{{/if}}
				</td>
				<td>
					{{if (whitelistedURLParam.data.userID)}}
					{{if (whitelistedURLParam.data.username)}}
					${whitelistedURLParam.data.username}
					{{else}}
					${whitelistedURLParam.data.userID}
					{{/if}}
					{{else}}
					-
					{{/if}}
				</td>
				<td>
					{{if (whitelistedURLParam.data.ip)}}
					${whitelistedURLParam.data.ip}
					{{else}}
					-
					{{/if}}
				</td>
				<td>
					<span class="whitelist-display" style="white-space: nowrap">
						<button type="button" class="wf-btn wf-btn-default wf-btn-sm whitelist-url-edit">Edit</button>
						<button type="button" class="wf-btn wf-btn-default wf-btn-sm whitelist-url-delete">Delete</button>
					</span>
					<span class="whitelist-edit" style="white-space: nowrap">
						<button type="button" class="wf-btn wf-btn-default wf-btn-sm whitelist-url-save">Save</button>
						<button type="button" class="wf-btn wf-btn-default wf-btn-sm whitelist-url-cancel">Cancel</button>
					</span>
				</td>
			</tr>
			{{/each}}
			{{if (whitelistedURLParams.length == 0)}}
			<tr>
				<td colspan="9">No whitelisted URLs currently set.</td>
			</tr>
			{{/if}}
			</tbody>
		</table>
	</div>
	<?php echo $bulkActionForm ?>

</script>

<script type="text/javascript">
	(function($) {
		WFAD.wafData = <?php echo json_encode($wafData); ?>;
		$('#waf-whitelisted-urls-add').on('click', function() {
			var form = $('#whitelist-form');

			var inputURL = form.find('[name=whitelistURL]');
			var inputParam = form.find('[name=whitelistParam]');
			var inputParamName = form.find('[name=whitelistParamName]');

			var url = inputURL.val();
			var param = inputParam.val();
			var paramName = inputParamName.val();
			if (url && param) {
				WFAD.wafConfigSave('addWhitelist', {
					whitelistedEnabled: 1,
					whitelistedPath: url,
					whitelistedParam: param + '[' + paramName + ']'
				}, function() {
					WFAD.colorbox('300px', 'Firewall Configuration', 'The Wordfence Web Application Firewall ' +
						'whitelist was saved successfully.');
				}, false);
			}
		});

		$('#input-wafStatus').on('change', function() {
			var gracePeriodRow = $('#waf-learning-mode-grace-row');
			if ($(this).val() == 'learning-mode') {
				gracePeriodRow.show();
			} else {
				gracePeriodRow.hide();
			}
		}).triggerHandler('change');

		$('#waf-config-form').on("submit", function() {
			WFAD.wafConfigSave('config', $(this).serializeArray());
		});
		$(function() {
			WFAD.wafConfigPageRender();

			$('#input-wafStatus').select2({
				minimumResultsForSearch: -1
			}).on('change', function() {
				var select = $(this);
				var container = $($(this).data('select2').$container);
				container.removeClass('wafStatus-enabled wafStatus-learning-mode wafStatus-disabled')
					.addClass('wafStatus-' + select.val());
			}).triggerHandler('change');

			$('.wf-datetime').datetimepicker({
				timeFormat: 'hh:mmtt z'
			}).each(function() {
				var el = $(this);
				if (el.attr('data-value')) {
					el.datetimepicker('setDate', new Date(el.attr('data-value') * 1000));
				}
			});

			var learningModeGracePeriod = $('input[name=learningModeGracePeriod]');
			$('input[name=learningModeGracePeriodEnabled]').on('click', function() {
				if (this.value == '1' && this.checked) {
					learningModeGracePeriod.attr('disabled', false);
					if (!learningModeGracePeriod.val()) {
						var date = new Date();
						date.setDate(date.getDate() + 7);
						learningModeGracePeriod.datetimepicker('setDate', date);
					}
				} else {
					learningModeGracePeriod.attr('disabled', true);
					learningModeGracePeriod.val('');
				}
			}).triggerHandler('click');

			var whitelistWrapper = $('#waf-whitelisted-urls-wrapper');

			var whitelistTable = null;
			var whitelistTableRows = null;
			var bulkActionCheckboxes = null;
			var bulkActionTriggerCheckboxes = null;
			var filterInputs = [];

			function requeryWhitelistDOMElements() {
				whitelistWrapper = $('#waf-whitelisted-urls-wrapper');
				whitelistTable = whitelistWrapper.find('.whitelist-table');
				whitelistTableRows = whitelistTable.find('> tbody > tr[data-index]');
				bulkActionCheckboxes = whitelistTable.find('> tbody > tr[data-index] > td > input[type=checkbox].wf-whitelist-table-bulk-checkbox');
				filterInputs = [];

				whitelistWrapper.find('.wf-table-filters input').each(function() {
					var el = $(this);
					var index = el.attr('data-column-index');
					filterInputs[index] = function(td) {
						if (el.val().length == 0) {
							return true;
						}
						return $(td).text().indexOf(el.val()) > -1;
					};
				}).on('keydown', function(evt) {

					if (evt.keyCode == 13) {
						filterWhitelistTable();
						return false;
					}
				}).on('blur', function() {
					filterWhitelistTable();
				});

				// Bulk actions
				bulkActionTriggerCheckboxes = whitelistWrapper.find('input[type=checkbox].wf-whitelist-table-bulk-action').on('click', function() {
					if (this.checked) {
						whitelistCheckAllVisible();
					} else {
						whitelistUncheckAll();
					}
				});

				whitelistWrapper.find('form.wf-whitelist-actions').on('submit', function() {
					var select = $(this).find('select[name=wf-bulk-action]');
					var bulkActionCallback = function(res) {
						if (typeof res === 'object' && res.success) {
							WFAD.colorbox('300px', 'Firewall Configuration', 'The Wordfence Web Application Firewall ' +
								'whitelist was saved successfully.');
							WFAD.wafData = res.data;
							WFAD.wafConfigPageRender();
						} else {
							WFAD.colorbox('300px', 'Error saving Firewall configuration', 'There was an error saving the ' +
								'Web Application Firewall whitelist.');
						}
					};
					switch (select.val()) {
						case 'delete':
							WFAD.ajax('wordfence_whitelistBulkDelete', {
								items: JSON.stringify(getBulkWhitelistChecked())
							}, bulkActionCallback);
							break;

						case 'enable':
							WFAD.ajax('wordfence_whitelistBulkEnable', {
								items: JSON.stringify(getBulkWhitelistChecked())
							}, bulkActionCallback);
							break;

						case 'disable':
							WFAD.ajax('wordfence_whitelistBulkDisable', {
								items: JSON.stringify(getBulkWhitelistChecked())
							}, bulkActionCallback);
							break;
					}
					return false;
				});
			}

			// Whitelist table filters
			function filterWhitelistTable() {
				var zebraCount = 0;
				whitelistTableRows.each(function() {
					var tr = $(this);
					var isMatch = true;
					tr.find('> td').each(function(index) {
						if (typeof filterInputs[index] === 'function') {
							isMatch = filterInputs[index](this);
							if (!isMatch) {
								return false;
							}
						}
					});
					tr.removeClass('even odd');
					if (isMatch) {
						tr.show();
						tr.addClass(zebraCount++ % 2 === 0 ? 'even' : 'odd');
					} else {
						tr.hide();
					}
				});
				whitelistUncheckAll();
			}

			var keyupTimeout = null;

			function getBulkWhitelistChecked() {
				var data = [];
				bulkActionCheckboxes.each(function() {
					if (this.checked) {
						var tr = $(this).closest('tr');
						if (tr.is(':visible')) {
							var index = tr.attr('data-index');
							if (index in WFAD.wafData.whitelistedURLParams) {
								var path = WFAD.wafData.whitelistedURLParams[index].path;
								var paramKey = WFAD.wafData.whitelistedURLParams[index].paramKey;
								var enabled = tr.find('input[name=whitelistedEnabled]').attr('checked') ? 1 : 0;
								data.push([path, paramKey, enabled]);
							}
						}
					}
				});
				return data;
			}

			function whitelistCheckAllVisible() {
				bulkActionTriggerCheckboxes.attr('checked', true);
				bulkActionCheckboxes.each(function() {
					this.checked = $(this).closest('tr').is(':visible');
				});
			}

			function whitelistUncheckAll() {
				bulkActionTriggerCheckboxes.attr('checked', false);
				bulkActionCheckboxes.attr('checked', false);
			}

			requeryWhitelistDOMElements();
			$(window).on('wordfenceWAFConfigPageRender', function() {
				requeryWhitelistDOMElements();
			})
		});

		$(document).on('click', '.whitelist-url-edit', function() {
			var tr = $(this).closest('tr');
			tr.addClass('edit-mode');
		});
		$(document).on('click', '.whitelist-url-delete', function() {
			if (confirm('Are you sure you\'d like to delete this URL?')) {
				var tr = $(this).closest('tr');
				var index = tr.attr('data-index');

				if (index in WFAD.wafData.whitelistedURLParams) {
					var path = WFAD.wafData.whitelistedURLParams[index].path;
					var param = WFAD.wafData.whitelistedURLParams[index].paramKey;
					WFAD.wafConfigSave('deleteWhitelist', {
						deletedWhitelistedPath: path,
						deletedWhitelistedParam: param
					}, function() {
						WFAD.colorbox('300px', 'Firewall Configuration', 'The Wordfence Web Application Firewall ' +
							'whitelist was saved successfully.');
					}, false);
				}
			}
		});
		$(document).on('click', '.whitelist-url-save', function() {
			var tr = $(this).closest('tr');

			var oldWhitelistedPath = tr.find('input[name=replaceWhitelistedPath]');
			var oldWhitelistedParam = tr.find('input[name=replaceWhitelistedParam]');
			var oldWhitelistedEnabled = tr.find('input[name=replaceWhitelistedEnabled]');

			var newWhitelistedPath = tr.find('input[name=whitelistedPath]');
			var newWhitelistedParam = tr.find('input[name=whitelistedParam]');
			var newWhitelistedEnabled = tr.find('input[name=whitelistedEnabled]');

			WFAD.wafConfigSave('replaceWhitelist', {
				oldWhitelistedPath: oldWhitelistedPath.val(),
				oldWhitelistedParam: oldWhitelistedParam.val(),
				oldWhitelistedEnabled: oldWhitelistedEnabled.val(),
				newWhitelistedPath: newWhitelistedPath.val(),
				newWhitelistedParam: newWhitelistedParam.val(),
				newWhitelistedEnabled: newWhitelistedEnabled.val()
			}, function() {
				WFAD.colorbox('300px', 'Firewall Configuration', 'The Wordfence Web Application Firewall ' +
					'whitelist was saved successfully.');
			}, false);
		});
		$(document).on('click', '.whitelist-url-cancel', function() {
			var tr = $(this).closest('tr');
			tr.removeClass('edit-mode');
		});
		$(document).on('click', 'input[name=whitelistedEnabled]', function() {
			var tr = $(this).closest('tr');

			var oldWhitelistedPath = tr.find('input[name=replaceWhitelistedPath]');
			var oldWhitelistedParam = tr.find('input[name=replaceWhitelistedParam]');
			var enabled = this.checked ? 1 : 0;

			WFAD.wafConfigSave('enableWhitelist', {
				whitelistedPath: oldWhitelistedPath.val(),
				whitelistedParam: oldWhitelistedParam.val(),
				whitelistedEnabled: enabled
			}, function() {
				WFAD.colorbox('300px', 'Firewall Configuration', 'The Wordfence Web Application Firewall ' +
					'whitelist was saved successfully.');
			}, false);
		});

		$(document).on('click', 'input[name=ruleEnabled]', function() {
			var enabled = this.checked ? 1 : 0;

			WFAD.wafConfigSave('enableRule', {
				ruleID: this.value,
				ruleEnabled: enabled
			});
		});
		
		$('#monitor-front').on('click', function() {
			var disabled = this.checked ? 0 : 1;
			WFAD.updateConfig('ajaxWatcherDisabled_front', disabled);
		})
		
		$('#monitor-admin').on('click', function() {
			var disabled = this.checked ? 0 : 1;
			WFAD.updateConfig('ajaxWatcherDisabled_admin', disabled); 
		})
		
		$('#waf-disable-ip-blocking').on('click', function() {
			var disabled = this.checked ? 1 : 0;
			WFAD.updateConfig('disableWAFIPBlocking', disabled);
		})
		
		$('#waf-disable-blacklist-blocking').on('click', function() {
			var disabled = this.checked ? 0 : 1;
			WFAD.wafConfigSave('disableWAFBlacklistBlocking', {disableWAFBlacklistBlocking: disabled});
		})
	})(jQuery);
</script>

<script type="text/x-jquery-template" id="wfWAFTour">
	<div>
		<h3>Wordfence Web Application Firewall</h3>
		<p>The Wordfence Web Application Firewall filters out malicious requests before they reach your site. Once it
			is enabled, it runs before WordPress itself, to filter attacks before plugins or themes can run any
			potentially vulnerable code. As new threats emerge, the rules are updated in real-time from the Wordfence
			servers for Premium members. Free users receive the community version of the rules which are updated
			30 days later.</p>

		<?php if (!wfConfig::get('isPaid')): ?>
			<p>If you would like to get real-time updates to firewall rules, please <a
					href="https://www.wordfence.com/wafOptions2/wordfence-signup/">upgrade to our premium version</a>.
			</p>
		<?php endif ?>
	</div>
</script>
