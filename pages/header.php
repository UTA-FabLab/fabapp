<!DOCTYPE html>
<!--
 *   CC BY-NC-AS UTA FabLab 2016-2018
 *   FabApp V 0.91
 -->
<html lang="en">
<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="FabApp, track your equipment">
	<meta name="author" content="UTA FabLab">
	<link rel="shortcut icon" href="/images/fa-icon.png" type="image/png">
	
	<link href="/vendor/blackrock-digital/css/sb-admin-2.css" rel="stylesheet">
	<link href="/vendor/bootstrap/css/bootstrap.css" rel="stylesheet">
	<link href="/vendor/bs-datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
	<link href="/vendor/datatables/css/dataTables.bootstrap.css" rel="stylesheet" type="text/css">
	<link href="/vendor/fabapp/fabapp.css" rel="stylesheet">
	<link href="/vendor/fontawesome/css/all.min.css" rel="stylesheet">
	<link href="/vendor/metisMenu/metisMenu.min.css" rel="stylesheet">
	<link href="/vendor/morrisjs/morris.css" rel="stylesheet">
	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
	<![endif]-->
<?php
$staff = null;
ob_start();
session_start();
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/db_connect8.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/ldap.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
date_default_timezone_set($sv['timezone']);
if(!$mysqli->query("SET NAMES 'utf8';")) throw new Exception("Could not set MySqli encoding to UTF-8");

if( isset($_SESSION['staff']) ){
	$staff = unserialize($_SESSION['staff']);
	$_SESSION['loc'] = $_SERVER['PHP_SELF'];
	//Logout if session has timed out.
	if ($_SESSION["timeOut"] < time()) {
		header("Location:/logout.php");
	}
	else {
		//echo $_SESSION["timeOut"] ." - ". time();
		$_SESSION["timeOut"] = (intval(time()) + $staff->getTimeLimit());
	}
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if( isset($_POST['signBtn']) ){
		if ( empty($_POST["netID"])){
			$_SESSION['error_msg'] = 'No User Name';
		}
		elseif (empty($_POST["pass"]) ){
			$_SESSION['error_msg'] = 'Missing Password';
		}
		else {
			//Remove 3rd argument, define attribute in ldap.php
			$operator = AuthenticateUser($_POST["netID"],$_POST["pass"]);
			if (array_key_exists('netID', $_SESSION)){
				if ($_SESSION['netID'] != $_POST["netID"]){
					unset($_SESSION['loc']);
				}
				$_SESSION['netID'] = $_POST["netID"];
			}
			if (Users::regexUser($operator)) {
				$staff = Staff::withID($operator);
				//staff get either limit or limit_long as their auto logout timer
				if ($staff->getRoleID() > $sv["LvlOfStaff"])
					$staff->setTimeLimit( $sv["limit_long"] );
				else
					$staff->setTimeLimit( $sv["limit"] );
				//set the timeOut = current + limit of login
				$_SESSION["timeOut"] = (intval(time()) + $staff->getTimeLimit());
				$_SESSION["staff"] = serialize($staff);
				if ( isset($_SESSION['loc']) ){
					header("Location:$_SESSION[loc]");
				}
				if (!headers_sent()){
					echo "<script>window.location.href='/index.php';</script>";
				}
				exit();
			}
			else {
				echo "<script type='text/javascript'> window.onload = function(){goModal('Invalid','Invalid user name and/or password!', false)}</script>";
			}
		}
	} elseif( isset($_POST['searchBtn']) ){
		if(filter_input(INPUT_POST, 'searchField')){
			$searchField = filter_input(INPUT_POST, 'searchField');
			if(filter_input(INPUT_POST, 'searchType')){
				$searchType = filter_input(INPUT_POST, 'searchType');
				if(strcmp($searchType, "s_trans") == 0){
					$trans_id = $searchField;
					header("location:/pages/lookup.php?trans_id=$trans_id");
				}
				elseif (strcmp($searchType, "s_operator") == 0){
					$operator = $searchField;
					header("location:/pages/lookup.php?operator=$operator");
				}
				else {
					echo "<script type='text/javascript'> window.onload = function(){goModal('Invalid','Illegal Search Condition', false)}</script>";
				}
			}
			else {
				echo "<script type='text/javascript'> window.onload = function(){goModal('Invalid','Illegal Search Condition', false)}</script>";
			}
		}
		else {
			echo "<script type='text/javascript'> window.onload = function(){goModal('Invalid','Please enter a number.', false)}</script>";
		}
	}
}
//Display a Successful message from a previous page
if (isset($_SESSION['success_msg']) && $_SESSION['success_msg']!= ""){
	echo "<script>window.onload = function(){goModal('Success',\"$_SESSION[success_msg]\", true)}</script>";
	unset($_SESSION['success_msg']);
}
elseif (isset($_SESSION['error_msg']) && $_SESSION['error_msg']!= ""){
	echo "<script>window.onload = function(){goModal('Error',\"$_SESSION[error_msg]\", false)}</script>";
	unset($_SESSION['error_msg']);
}
?>
</head>
<body>
	<div id="wrapper">
		<!-- Navigation -->
		<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0" id='navbar'>
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" id="navbar-brand" href="http://fablab.uta.edu"><img src="/images/FLlogo_143.png" type="image/png"></a>
			</div>
			<!-- /.navbar-header -->
			<ul class="nav navbar-top-links navbar-right">
				<!--php class Staff if not logged in-->
				<?php if(!isset($staff)){ ?>
					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#" id="loginlink"> 
							<i class="fas fa-sign-in-alt fa-lg"></i> <i class="fas fa-caret-down"></i>
						</a>
						<ul class="dropdown-menu dropdown-alerts">
							<form role="form" class="form-horizontal" method="POST" action="" autocomplete="off">
							<div class="form-group">
								<label for="email" class="col-sm-3 control-label">
									NetID</label>
								<div class="col-sm-9">
									<input type="text" class="form-control" id="netID" name="netID" placeholder="NetID" value="<?php if(isset($_SESSION['netID'])) echo $_SESSION['netID'];?>"/>
								</div>
							</div>
							<div class="form-group">
								<label for="exampleInputPassword1" class="col-sm-3 control-label">
									Password</label>
								<div class="col-sm-9">
									<input type="password" class="form-control" name="pass" placeholder="Password" />
								</div>
							</div>
							<div class="row">
								<div class="col-sm-12 col-sm-offset-1">
									<button type="submit" class="btn btn-primary btn-sm" name="signBtn" onclick="loadingModal()">
										Sign In</button>
									<a href="http://<?php echo $sv["forgotten"];?>">Forgot your password?</a>
								</div>
							</div>
							</form>
						</ul>
						<!-- /.dropdown-login -->
					</li>
				<!--php class Staff if logged in-->
				<?php }
				else {?>
					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#">
							<i class="<?php echo $staff->getIcon();?> fa-2x"></i> <i class="fas fa-caret-down"></i>
						</a>
						<ul class="dropdown-menu dropdown-user">
							<li><a href="/pages/info.php" onclick="loadingModal()"><i class="fas fa-info"></i> Information</a></li>
							<li class="divider"></li>
							<li><a href="/logout.php?n=n" onclick="loadingModal()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
						</ul>
						<!-- /.dropdown-user -->
					</li>
					<!-- /.dropdown -->
			<?php }?>	
			</ul>
			<!-- /.navbar-top-links -->
			<div class="navbar-default sidebar" role="navigation">
				<div class="sidebar-nav navbar-collapse">
					<ul class="nav" id="side-menu">
						<li>
							<a href="/index.php"><i class="fas fa-fw fa-ticket-alt"></i> FabApp</a>
						</li>
						<?php if (isset($staff) && $staff->getRoleID() >=  $sv['LvlOfStaff']) { ?>
							<li>
								<a href="/admin/error.php"><i class="fas fa-fw fa-bolt"></i> Error</a>
							</li>
						<?php } 
						if(isset($staff) && $staff->getRoleID() >= $sv['LvlOfLead']) { ?>
							<li>
								<a href="#"><i class="fas fa-fw fa-warehouse"></i> Inventory<span class="fas fa-angle-left"></span></a>
								<ul class="nav nav-second-level">
									<li>
										<a href="/pages/inventory.php"><i class="fas fa-fw fa-box"></i> On Hand</a>
									</li>
									<li>
										<a href="/pages/inventory_processing.php"><i class="fas fa-fw fa-shipping-fast"></i> Edit Inventory</a>
									</li>
									<?php if(isset($staff) && $staff->getRoleID() >= $sv['minRoleTrainer']) { ?>
									<li>
										<a href="/pages/current_inventory.php"><i class="far fa-fw fa-check-square"></i> Usable Inventory</a>
									</li>
									<li>
										<a href="/pages/sheet_goods.php"><i class="fas fa-fw fa-square"></i> Sheet Goods</a>
									</li>
									<?php } ?>
								</ul>
								<!-- /.nav-second-level -->
							</li>
						<?php } 
						else { ?>
						<li>
							<a href="/pages/inventory.php"><i class="fas fa-fw fa-warehouse"></i> Inventory</a>
						</li>
						<!-- if role > 6 {show} -->
						<?php }
						if (isset($staff) && $staff->getRoleID() >=  $sv['LvlOfStaff']) { ?>
							<li>
								<a href="#" id="searchLink"><i class="fas fa-fw fa-search"></i> Look-Up By<span class="fas fa-angle-left"></span></a>
								<ul class="nav nav-second-level">
								<form name="searchForm" method="POST" action="" autocomplete="off"  onsubmit="return validateNum('searchForm')"> 
									<li class="sidebar-radio">
										<input type="radio" name="searchType" value="s_trans" id="s_trans" checked onchange="searchF()" onclick="searchF()"><label for="s_trans">Ticket</label>
										<input type="radio" name="searchType" value="s_operator" id="s_operator" onchange="searchF()" onclick="searchF()"><label for="s_operator">ID #</label>
									</li>
									<li class="sidebar-search">
										<div class="input-group custom-search-form">
											<input type="number" name="searchField" id="searchField" class="form-control" placeholder="Search..." name="searchField" onclick="searchF()">
											<span class="input-group-btn">
											<button class="btn btn-default" type="submit" name="searchBtn">
												<i class="fas fa-search"></i>
											</button>
											</span>
										</div>
									</li>
								</form>
								</ul>
							</li>
							<?php if ($sv['wait_system'] != "new") { ?>
								<li>
									<a href="/admin/now_serving.php"><i class="fas fa-fw fa-list-ol"></i> Now Serving</a>
								</li>
							<?php 
							}
						}
						if (isset($staff) && ($staff->getRoleID() >=  $sv['LvlOfStaff'] || $staff->getRoleID() ==  $sv['serviceTechnican'])) { ?>
							<li>
								<a href="#"><i class="fa fa-fw fa-wrench"></i> Service<span class="fa arrow"></span></a>
								<ul class="nav nav-second-level">
									<li>
										<a href="/pages/sr_history.php"><i class="fas fa-fw fa-history"></i> Device History</a>
									</li>
									<li>
										<a href='/pages/open_sr.php'><i class='far fa-fw fa-comment'></i> Open Service Issues</a>
									</li>
									<li>
										<a href="/pages/sr_issue.php"><i class="fas fa-fw fa-fire"></i> Report Issue</a>
									</li>
								</ul>
								<!-- /.nav-second-level -->
							</li>
						<?php } ?>
							<li>
								<a href="/pages/tools.php"><i class="fas fa-fw fa-toolbox"></i> Tools</a>
							</li>
						<?php
						if (isset($staff) && $staff->getRoleID() >=  $sv['LvlOfLead']) { ?>
							<li>
								<a herf="#"><i class="fas fa-fw fa-book"></i> Training<span class="fas fa-angle-left"></span></a>
								<ul class="nav nav-third-level">
									<li>
										<a href="/admin/training_certificate.php"><i class="far fa-fw fa-check-circle"></i> Issue Certificate</a>
									</li>
									<li>
										<a href="/admin/training_revoke.php"><i class="fas fa-fw fa-search"></i> Issued Trainings</a>
									</li>
									<li>
										<a href="/admin/manage_trainings.php"><i class="fas fa-fw fa-edit"></i> Manage Trainings</a>
									</li>
								</ul>
							</li>
						<?php }
						if(isset($staff) && $staff->getRoleID() >=  $sv['LvlOfStaff'] && $sv['wait_system'] == "new"){ ?>
							<li>
								<a href="/pages/wait_ticket.php"><i class="fas fa-fw fa-list-ol"></i> Wait Queue Ticket</a>
							</li>
						<?php } 
						if(isset($staff) && $staff->getRoleID() >= $sv['minRoleTrainer']) {
						?>
							<li>
								<a href="#"><i class="fas fa-fw fa-sitemap"></i> Admin<span class="fas fa-angle-left"></span></a>
								<ul class="nav nav-second-level">
									<li>
										<a href="/admin/stats.php"><i class="fas fa-fw fa-chart-line"></i> Data Reports</a>
									</li>
									<li>
										<a href="/admin/manage_device.php"><i class="fas fa-fw fa-edit"></i> Manage Devices</a>
									</li>
									<li>
										<a href="/admin/objbox.php"><i class="fas fa-fw fa-gift"></i> Objects in Storage</a>
									</li>
									<li>
										<a href="/admin/onboarding.php"><i class="fas fa-fw fa-clipboard"></i> OnBoarding</a>
									</li>
									<li>
										<a herf="#"><i class="fas fa-fw fa-users"></i> Users<span class="fas fa-angle-left"></span></a>
										<ul class="nav nav-third-level">
											<li>
												<a href="/admin/addrfid.php"><i class="fas fa-fw fa-wifi"></i> Add RFID</a>
											</li>
										</ul>
									</li>
								</ul>
								<!-- /.nav-second-level -->
							</li>
						<li>
							<a href="#"><i class="fas fa-user-cog"></i> Site Tools<span class="fas fa-angle-left"></span></a>
							<ul class="nav nav-second-level">
								<li>
									<a href="/admin/sv.php"><i class="fas fa-sliders-h"></i> Site Variables</a>
								</li>
								<li>
									<a href="/admin/storage_unit_creator.php"><i class="fas fa-inbox"></i> Storage Box</a>
								</li>
							</ul>
						</li>
						<?php } ?>
					</ul>
				</div>
				<!-- /.sidebar-collapse -->
			</div>
			<!-- /.navbar-static-side -->
		</nav>