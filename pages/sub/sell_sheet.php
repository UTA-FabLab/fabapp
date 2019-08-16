<?php

/* 
 * License - FabApp V 0.91
 * 2016-2018 CC BY-NC-AS UTA FabLab
 */

include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/connections/db_connect8.php');
include_once (filter_input(INPUT_SERVER,'DOCUMENT_ROOT').'/class/all_classes.php');

session_start();

if (true) { 
	if(empty($_SESSION['cart_array'])) { 
		$_SESSION['error_msg'] = "Bad input in inventory sell modal";
		header("Location: /pages/sheet_goods.php");
	}
?>

	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<form name="sellMaterialForm" method="post" action="" autocomplete="off">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title" id="materialTitle">Confirm Cart</h4>
			</div>
			<div class="modal-body" id="materialBody">
                <table class="table table-condensed" id="invTable1">
                    <thead>
                        <tr>
                            <th>Sheet</th>
                            <th>Size (Inches)</th>
                            <th>Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>   
                    <tbody>
                    <?php 
                    if(!empty($_SESSION['cart_array'])){
                        for ($ii = 0; $ii < sizeof($_SESSION['cart_array']); $ii++) { ?>
                            <tr>
                                <?php
                                $temp_v = $_SESSION['cart_array'][$ii];
                                if ($result = $mysqli->query("
                                        SELECT *
                                        FROM sheet_good_inventory SI JOIN materials M ON SI.m_ID = M.m_ID
                                        WHERE SI.inv_ID=$temp_v AND SI.quantity != 0;
                                ")) {
                                    while ($row = $result->fetch_assoc()) { ?>
                                <td>
                                    <?php echo ($row['m_name']); ?>
                                </td>
                                <td>
                                    <?php echo ($row['width']." x ".$row['height']); ?>
                                </td>
                                <td>
                                    <t><?php echo ($_SESSION['co_quantity'][$ii]); ?></t>
                                </td>
                                <td>
                                    <?php echo ("$".number_format((float)((($row["width"]*$row["height"]) * $row["price"])* $_SESSION['co_quantity'][$ii]), 2, '.', '')); ?>
                                </td>
                                <?php } } ?>
                            </tr>
                        <?php } ?>
                            <tr>
                                <td>
                                </td>
                                <td>
                                </td>
                                <td>
                                </td>
                                <td>
                                    <b><?php echo ("Total: $".$_SESSION['co_price']); ?></b>
                                </td>
                            </tr>
                            
                            
                   <?php } else { ?>
                        <tr><td colspan="3"><div style='text-align: center'>Shopping Cart is Empty!</div></td></tr>
                    <?php } ?>
                    </tbody>
                </table>
                
				<table class="table table-bordered table-striped">
					<tr>
						<td>
							Operator
						</td>
						<td>
                            <input type="text" name="sell_operator" id="sell_operator" class="form-control" placeholder="1000000000" maxlength="10" size="10"/>
						</td>
					</tr>
                    <tr>
                            <?php //Build Purpose Option List
                            if($pArray = Purpose::getList()){ ?>
                            <td>
                                Purpose of Visit
                            </td>
                            <td>
                                <select class="form-control" name="p_id" tabindex="8">
                                <option disabled hidden selected value="">Select</option>
                                <?php foreach($pArray as $key => $value){ 
                                    echo("<option value='$key'>$value</option>");
                                }
                                                            } ?>
                            </td>
                    </tr>
					<!-- TODO: add reason -->
				</table>
            
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-success" name="sell_sheet">Sell</button>
			</div> 
			</form>
		</div>
	</div>
<?php } ?>

<script>
</script>
