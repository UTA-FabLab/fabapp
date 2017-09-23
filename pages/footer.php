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
    setTimeout(function(){window.location.reload(1)}, <?php echo (1+$_SESSION["timeOut"]-time())*1000; ?>);
    function searchF(){
        var sForm = document.forms['searchForm'];
        if (sForm.searchType[0].checked == true) {
            sForm.searchField.type="number";
            sForm.searchField.placeholder="Search...";
            sForm.searchField.min = "1";
            sForm.searchField.autofocus=true;
        }
        if (sForm.searchType[1].checked == true) {
            sForm.searchField.type="text";
            sForm.searchField.placeholder="1000000000";
            sForm.searchField.maxLength="10";
            sForm.searchField.size="10";
            sForm.searchField.autofocus=true;
        }
    }
<?php } else { ?>
    setTimeout(function(){window.location.reload(1)}, 301000);
<?php } ?>
</script>
    <script src="/vendor/jquery/jquery.min.js"></script>
    <script src="/vendor/blackrock-digital/js/sb-admin-2.js"></script>
    <script src="/vendor/datatables/js/dataTables.min.js"></script>
    <script src="/vendor/fabapp/fabapp.js?=v15"></script>
    <script src="/vendor/metisMenu/metisMenu.min.js"></script>
    <script src="/vendor/morrisjs/morris.min.js"></script>
    <script src="/vendor/raphael/raphael.min.js"></script>
</body>
</html>