<?php 
/**************************************************
*
*	@author MPZinke on 11.26.18
*
*	-AJAX call by training_revoke.php to populate
*	 revoke modal
*
**************************************************/

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "training_ID"))) {
	$tme_key = filter_input(INPUT_GET, "training_ID"); ?>
	<!-- /#page-wrapper -->
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<form name="revokeForm" method="post" action="">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">Revoke Training</h4>
				</div>
				<div class="modal-body">
					<p>Reason</p>
					<input type="text" name="reason" id="operator" class="form-control" 
											placeholder="Enter a reason" maxlength="100" size="40"/>
					<p><br/>Expiration</p>
					<input id="date" type="date" name='expiration' value="<?php echo date('Y-m-d', strtotime("+1 year")); ?>">
				</div>
				<input id='tme_key' name='tme_key' value=<?php echo $tme_key; ?> hidden>
				<div class="modal-footer">
					  <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					  <button type="submit" class="btn btn-danger" name="submit_revoke">Revoke</button>
				</div>
			</form>
		</div>
	</div>

<?php } ?>