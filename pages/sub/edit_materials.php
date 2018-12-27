<?php

/* 
 * License - FabApp V 0.91
 * 2016-2018 CC BY-NC-AS UTA FabLab
 */

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "m_id"))) { 
	$m_id = filter_input(INPUT_GET, "m_id");

	if(!preg_match("/^\d+$/", $m_id)) { 
		$_SESSION['error_msg'] = "Bad input in material modal";
		header("Location:inventory.php");
	}

	$material = new Materials($m_id);
	$quantity = Mats_Used::units_in_system($m_id); ?>
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<form name="materialForm" method="post" action="" autocomplete="off">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title" id="materialTitle">Edit <?php echo $material->getM_name(); ?></h4>
			</div>
			<div class="modal-body" id="materialBody">
				<table class="table table-bordered table-striped">
					<tr>
						<td>
							Material
						</td>
						<td>
							<?php echo $material->getM_name(); ?>
						</td>
					</tr>
					<tr>
						<td>
							Quantity on Hand
						</td>
						<td>
							<div class="input-group">
								<input type="text" class="form-control loc" value="<?php echo $quantity;?>" name="quantity" autocomplete="off"/>
								<span class="input-group-addon"><?php echo $material->getUnit(); ?></span>
							</div>
						</td>
					</tr>
					<!-- TODO: add reason -->
				</table>
				<input name='m_id' value='<?php echo $m_id; ?>' hidden>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-primary" name="save_material">Save</button>
			</div> 
			</form>
		</div>
	</div>
<?php } ?>