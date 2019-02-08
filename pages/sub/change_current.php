<?php  
/**************************************************
*
*	@author MPZinke on 01.31.19
*
*	-Used as AJAX call with current_inventory.php
*	 to change state & echo success msg
*
**************************************************/

session_start();  // used for trainer authentification (ln-28)
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "id"))) { ?>
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class='close' onclick='dismiss_modal()'>&times;</button>
				<h4 class="modal-title">Query Message</h4>
			</div>

			<div class="modal-body">
				<?php if(unserialize($_SESSION['staff'])->getRoleID() < $sv['minRoleTrainer']) {
						echo "Insufficient role level";
					}
					else {
						// get parts of value passed
						$id = explode("|", filter_input(INPUT_GET, "id"), 2);
						// regexing and query
						if(is_numeric($id[0]) && ($id[1] == 'Y' || $id[1] == 'N') && $mysqli->query("UPDATE `materials`
																 SET `current` = '$id[1]'
																 WHERE `m_id` = '$id[0]';
						")) {
							echo "Successfully updated material to current status '$id[1]'";
						}
						else {
							echo "Unable to switch material to current status '$id[1]'.  Please refresh the page.";
						}
					} ?>
			</div>

			<div class="modal-footer">
				  <button type="button" class='btn btn-default' onclick='dismiss_modal()'>Close</button>
			</div>
		</div>
	</div>

<?php } ?>