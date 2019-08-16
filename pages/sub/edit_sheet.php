<?php

/* 
 * License - FabApp V 0.91
 * 2016-2018 CC BY-NC-AS UTA FabLab
 */

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "inv_id")) && !empty(filter_input(INPUT_GET, "m_id"))) { 
	$inv_id = filter_input(INPUT_GET, "inv_id");
	$m_id = filter_input(INPUT_GET, "m_id");
	$width = filter_input(INPUT_GET, "width");
	$height = filter_input(INPUT_GET, "height");

	if(!preg_match("/^\d+$/", $inv_id)) { 
		$_SESSION['error_msg'] = "Bad input in inventory material modal";
		header("Location: /pages/sheet_goods.php");
	}
	if(!preg_match("/^\d+$/", $m_id)) { 
		$_SESSION['error_msg'] = "Bad input in material modal";
		header("Location: /pages/sheet_goods.php");
	}

	$material = new Materials($m_id);
	$quantity = Materials::sheet_quantity($inv_id); ?>
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<form name="materialForm" method="post" action="" autocomplete="off">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title" id="materialTitle">Edit <?php echo($material->m_name); ?></h4>
			</div>
			<div class="modal-body" id="materialBody">
				<table class="table table-bordered table-striped">
					<tr>
						<td>
							Sheet Material
						</td>
						<td>
							<?php echo $material->m_name; ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $width;?><i>in </i>x<?php echo(" ".$height);?><i>in</i>
						</td>
					</tr>
					<tr>
						<td>
							Quantity
						</td>
						<td>
							<div class="input-group">
                                <input type="number" class="form-control" name="new_quantity" min="0" value="<?php echo($quantity);?>" step="1" placeholder="Enter Quantity"/>
								<span class="input-group-addon"><?php echo("On Hand"); ?></span>
							</div>
						</td>
					</tr>
					<!-- TODO: add reason -->
				</table>
				<input name='inv_id_quantity' value='<?php echo $inv_id; ?>' hidden>
				<input name='m_id_quantity' value='<?php echo $m_id; ?>' hidden>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-primary" name="save_material">Save</button>
			</div> 
			</form>
		</div>
	</div>
<?php } ?>