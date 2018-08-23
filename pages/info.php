<?php
/*
 *   CC BY-NC-AS UTA FabLab 2016-2017
 *   FabApp V 0.9
 */
 //This will import all of the CSS and HTML code necessary to build the basic page
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/header.php');
?>
<title><?php echo $sv['site_name'];?> Information</title>
<?php if(!is_object($staff)){ ?>
    <div id="page-wrapper">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header">Please Sign In</h1>
            </div>
            <!-- /.col-md-12 -->
        </div>
    </div>
<?php } else {?>
<div id="page-wrapper">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Information</h1>
        </div>
        <!-- /.col-md-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-ticket-alt fa-fw"></i> Tickets
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered table-hover" id="ticketsTable">
                        <thead>
                            <tr class="tablerow">
                                <th align="right">Ticket</th>
                                <th>Device</th>
                                <th>Start Time</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <?php foreach ($staff->history() as $ticket){ ?>
                            <tr>
                                <td align="Center"><a href="/pages/lookup.php?trans_id=<?php echo $ticket[0];?>"><?php echo $ticket[0];?></a></td>
                                <td><?php echo $ticket[1];?></td>
                                <td><?php echo $ticket[2];?></td>
                                <td><?php echo $ticket[3];?></td>
                                <td><?php echo $ticket[4];?></td>
                            </tr>
                        <?php }?>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.col-md-8 -->
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fas fa-chart-bar fa-fw"></i> Stats
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered table-hover">
                        <tr>
                            <td align="Center">Total Tickets</td>
                            <td><p title="<?php echo "$sv[rank_period] Month Rank : ".$staff->ticketsTotalRank();?>"><?php echo $staff->ticketsTotal();?></p></td>
                        </tr>
                        <tr>
                            <td align="Center">Tickets Assisted</td>
                            <td><p title="<?php echo "$sv[rank_period] Month Rank : ".$staff->ticketsAssistRank();?>"><?php echo $staff->ticketsAssist();?></p></td>
                        </tr>
                        <tr>
                            <td align="Center">Assigned Role</td>
                            <td><p><?php echo Role::getTitle($staff->getRoleID());?></p></td>
                        </tr>
                        <tr>
                            <td align="Center">LC</td>
                            <td><p><?php echo $staff->getLong_close();?></p></td>
                        </tr>
                    </table>
                </div>
                <!-- /.panel-body -->
            </div>
            <!-- /.panel -->
        </div>
        <!-- /.col-md-4 -->
    </div>
    <!-- /.row -->
</div>
<!-- /#page-wrapper -->
<?php } ?>
<?php
//Standard call for dependencies
include_once ($_SERVER['DOCUMENT_ROOT'].'/pages/footer.php');
?>
<script>
$('#ticketsTable').DataTable({
    "iDisplayLength": 25,
    "order": []
});
</script>