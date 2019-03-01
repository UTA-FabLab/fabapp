<?php  

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

if (!empty(filter_input(INPUT_GET, "table_id"))) {
	$table = new Table(filter_input(INPUT_GET, "table_id")); ?>
	<!-- /#page-wrapper -->
	<form method="POST">
		<div>
			<table class='table table-striped'>
				<thead>
					<th></th>
					<th>Data</th>
					<th>Type</th>
				</thead>
				<?php 
				$x = 0;
				$columns = $table->get_columns();
				foreach($columns as $key => $value) {
					echo "<tr> <td> <input type='checkbox' name='select-$x' onchange='isChecked($x)'/> </td>".
					"<td>".$key."</td>";
					if($value === "datetime") {
						echo "<td> <div id='$x' hidden> <div class='input-group'> <span class='input-group-addon'>Start&nbsp;</span><input type='date' name='dt-start-$x' class='form-control'/> </div>";
						echo "<div class='input-group'> <span class='input-group-addon'>End&nbsp;&nbsp;</span><input type='date' name='dt-end-$x' class='form-control'/> </div> </div> </td> </tr>";
					}
					else {
						echo "<td>".$value."</td> </tr>";
					}
					$x++;
				} ?>
			</table>
		</div>
		<div>
			<!-- actions here -->
			<!-- get list of actions predetermined by the table object -->
		</div>
	</form>
<?php }?>