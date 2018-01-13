<select class="wfConfigElem" id="<?php echo $rateName; ?>" name="<?php echo $rateName; ?>">
	<option value="DISABLED"<?php $w->sel($rateName, 'DISABLED'); ?>>Unlimited</option>
	<option value="1"<?php $w->sel($rateName, '1'); ?>>1 per minute</option>
	<option value="2"<?php $w->sel($rateName, '2'); ?>>2 per minute</option>
	<option value="3"<?php $w->sel($rateName, '3'); ?>>3 per minute</option>
	<option value="4"<?php $w->sel($rateName, '4'); ?>>4 per minute</option>
	<option value="5"<?php $w->sel($rateName, '5'); ?>>5 per minute</option>
	<option value="10"<?php $w->sel($rateName, '10'); ?>>10 per minute</option>
	<option value="15"<?php $w->sel($rateName, '15'); ?>>15 per minute</option>
	<option value="30"<?php $w->sel($rateName, '30'); ?>>30 per minute</option>
	<option value="60"<?php $w->sel($rateName, '60'); ?>>60 per minute</option>
	<option value="120"<?php $w->sel($rateName, '120'); ?>>120 per minute</option>
	<option value="240"<?php $w->sel($rateName, '240'); ?>>240 per minute</option>
	<option value="480"<?php $w->sel($rateName, '480'); ?>>480 per minute</option>
	<option value="960"<?php $w->sel($rateName, '960'); ?>>960 per minute</option>
	<option value="1920"<?php $w->sel($rateName, '1920'); ?>>1920 per minute</option>
</select>

