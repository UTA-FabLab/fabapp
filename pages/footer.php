<?php //Standard call for dependencies?>
    </div>
    <!-- /#wrapper -->
	
<div id="popModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modal-title">TM</h4>
            </div>
          <div class="modal-body">
                <p id="modal-body"> - </p>
          </div>
          <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </div>
    </div>
</div>
<!-- Modal -->
    
<!-- dev details -->
<div class="col-sm-12">
    <?php echo "IP addy - ".getenv('REMOTE_ADDR');?>
    <?php if(!empty($_SESSION['type'])) {echo "\n<br>type - ".$_SESSION['type'];}?>
</div>

<script type="text/javascript">
<?php if ($staff){?>
    setTimeout(function(){window.location.href = "<?php echo $_SESSION['loc']?>"}, <?php echo (1+$_SESSION["timeOut"]-time())*1000; ?>);
<?php } else { ?>
    //setTimeout(function(){window.location.href = "<?php if (isset($_SESSION['loc'])){echo $_SESSION['loc'];} else {echo "/index.php";}?>"}, 301000);
	setTimeout(function(){window.location.href = "/index.php"}, 301000);
<?php } ?>
</script>
    <script src="/vendor/jquery/jquery.min.js"></script>
    <script src="/vendor/blackrock-digital/js/sb-admin-2.js"></script>
    <script src="/vendor/datatables/js/dataTables.min.js"></script>
    <script src="/vendor/fabapp/fabapp.js?=v26"></script>
    <script src="/vendor/metisMenu/metisMenu.min.js"></script>
    <script src="/vendor/morrisjs/morris.min.js"></script>
    <script src="/vendor/raphael/raphael.min.js"></script>
</body>
</html>