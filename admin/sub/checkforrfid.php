<?php
	/*
	 *   CC BY-NC-AS UTA FabLab 2016-2017
	 *   FabApp V 0.9
	 */
	include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/db_connect8.php');
	include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');

	if (!empty(filter_input(INPUT_GET, "operator")))
	{
		$operator = Users::with_id(filter_input(INPUT_GET, "operator"));
		if($operator && $operator->rfid_no)
		{
			?>
			<td id="rfid_td"><input type="text" name="rfid" id="rfid"
				value="<?php echo $operator->rfid_no; ?>"  tabindex="3">
				<label for="override">Overwrite Existing RFID? </label>
				<input type="checkbox" name="override" id="override" tabindex="4" value="yes"
				class='form-control'>
			</td>
			<?php 
		}
		else
		{
			?>
			<td id="rfid_td"><input type="text" name="rfid" id="rfid" placeholder="RFID Number" 
				 tabindex="3" class='form-control'>
			</td>
			<?php
		}
	}
	else
	{
		?>
		<td id="rfid_td">
			<input type="text" name="rfid" id="rfid" placeholder="RFID Number" 
			tabindex="3" class='form-control'>
		</td>
		<?php
	}
?>