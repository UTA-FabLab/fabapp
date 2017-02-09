<?php //Standard call for dependencies?>
    </div>
    <!-- /#wrapper -->
<script type="text/javascript">
	<?php if ($staff){?>
		setTimeout(function(){window.location.reload(1)}, <?php 
		echo (1+$_SESSION["timeOut"]-time())*1000; ?>);
		var sForm = document.forms['searchForm'];
		sForm.searchType[0].onfocus = function () {
			sForm.searchField.type="number";
			sForm.searchField.placeholder="Search...";
			sForm.searchField.min = "1";
			sForm.searchField.autofocus=true;
		}
		sForm.searchType[1].onfocus = function () {
			sForm.searchField.type="text";
			sForm.searchField.placeholder="1000000000";
			sForm.searchField.maxLength="10";
			sForm.searchField.size="10";
			sForm.searchField.autofocus=true;
		}
	<?php } else { ?>
		setTimeout(function(){window.location.reload(1)}, 301000);
	<?php } ?>
</script>
    <script src="/vendor/jquery/jquery.min.js"></script>
    <script src="/vendor/blackrock-digital/js/sb-admin-2.js"></script>
    <script src="/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="/vendor/fabapp/fabapp.js?=v7"></script>
    <script src="/vendor/metisMenu/metisMenu.min.js"></script>
    <script src="/vendor/morrisjs/morris.min.js"></script>
    <script src="/vendor/raphael/raphael.min.js"></script>
</body>
</html>

