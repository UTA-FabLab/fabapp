<?php
/*
 *
 *  Jonathan Le, Super FabLabian
 *	FabLab @ University of Texas at Arlington
 
 *  version: 0.90 beta (2018-03-19)
 *
*/
session_start();
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/db_connect8.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
?>
<div align="center" ><a href='http://fablab.uta.edu/policy/' style='color:blue'>UTA FabLab's Wait Policy</a></div>
<table class="table table-striped table-bordered">
	<tr>
		<td>Equipment</td>
		<td>Now Serving</td>
		<td>Next #</td>
	</tr>
	<?php if ($sv['next'] != 0){ ?><tr id="next">
		<td>PolyPrinter</td>
		<td align="center"><h4 id="serving"><?php echo $sv['serving']; ?></h4></td>
		<td align="center" title="Next Issuable Number"><?php echo $sv['next']+1; ?></td>
	</tr><?php } ?>
	<?php if ($sv['eNext'] != 0){ ?><tr id="next">
		<td>Epilog Laser</td>
		<td align="center"><h4 id="eServing">E<?php echo $sv['eServing']; ?></h4></td>
		<td align="center" title="Next Issuable Number">E<?php echo $sv['eNext']+1; ?></td>
	</tr><?php } ?>
	<?php if ($sv['bNext'] != 0){ ?><tr id="next">
		<td>Boss Laser</td>
		<td align="center"><h4 id="bServing">B<?php echo $sv['bServing']; ?></h4></td>
		<td align="center" title="Next Issuable Number">B<?php echo $sv['bNext']+1; ?></td>
	</tr><?php } ?>
	<?php if ($sv['mNext'] != 0){ ?><tr id="next">
		<td><?php echo $sv['misc'];?></td>
		<td align="center"><h4 id="mServing">M<?php echo $sv['mServing']; ?></h4></td>
		<td align="center" title="Next Issuable Number">M<?php echo $sv['mNext']+1; ?></td>
	</tr><?php } ?>
</table>