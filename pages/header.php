<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="FabApp, track your equipment">
    <meta name="author" content="UTA FabLab">
    <link rel="shortcut icon" href="/images/fa-icon.png" type="image/png">
    
    <link href="/vendor/fabapp/fabapp.css?=v3" rel="stylesheet">
    <link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendor/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="/vendor/blackrock-digital/css/sb-admin-2.css?=v8" rel="stylesheet">
    <link href="/vendor/morrisjs/morris.css" rel="stylesheet">
    <link href="/vendor/font-awesome/css/font-awesome.min.css?=v1" rel="stylesheet" type="text/css">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
<?php
$staff = null;
ob_start();
date_default_timezone_set('America/Chicago');
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/db_connect8.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/connections/ldap.php');
include_once ($_SERVER['DOCUMENT_ROOT'].'/class/all_classes.php');
session_start();
if( isset($_SESSION['staff']) ){
    $staff = $_SESSION['staff'];
    $_SESSION['loc'] = $_SERVER['REQUEST_URI'];
    if ($_SESSION["timeOut"] < time()) {
        header("Location:/logout.php");
    } else {
        //echo $_SESSION["timeOut"] ." - ". time();
        $_SESSION["timeOut"] = (intval(time()) + $staff->getTimeLimit());
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if( isset($_POST['signBtn']) ){
        if ( count($_POST) && !empty($_POST["netID"]) && !empty($_POST["pass"]) ){
            $operator = AuthenticateUser($_POST["netID"],$_POST["pass"],"utaEmplID");
            $_SESSION['netID'] = $_POST["netID"];
            if ($operator) {
                $staff = Staff::withID($operator);
                $_SESSION["staff"] = $staff;
                //staff get either limit or limit_long as their auto logout timer
                if ($staff->getRoleID() > 8)
                    $staff->setTimeLimit( $sv["limit_long"] );
                else
                    $staff->setTimeLimit( $sv["limit"] );
                
                //set the timeOut = current + limit of login
                $_SESSION["timeOut"] = (intval(time()) + $staff->getTimeLimit());
                $staff->setLoc($_SERVER['REQUEST_URI']);
                    $_SESSION["staff"] = $staff;
                    header("Location:".$staff->getLoc());
                if (!headers_sent()){
                    echo "<script>window.location.href='/index.php';</script>";
                }
                exit();
            }
            
        } else {
            echo "NOPE";
        }
    } elseif( isset($_POST['searchBtn']) ){
        echo "Someone needs to program the search function.";
    } elseif( isset($_POST['pickBtn']) ){
        echo "Someone needs to program the Pick-Up function.";
    }
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
<?php if(!$staff){ ?>
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#"> 
                        <i class="fa fa-sign-in fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-alerts">
                        <form role="form" class="form-horizontal" method="POST" action="" autocomplete="off">
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">
                                NetID</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="netID" placeholder="UTA NetID" value="<?php if(isset($_SESSION['netID'])) echo $_SESSION['netID'];?>"/>
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
                            <div class="col-sm-12">
                                <button type="submit" class="btn btn-primary btn-sm" name="signBtn">
                                    Sign In</button>
                                <a href="http://<?php echo $sv["forgotten"];?>">Forgot your password?</a>
                            </div>
                        </div>
                        </form>
                    </ul>
                    <!-- /.dropdown-login -->
                </li>                
<!--php class Staff if logged in-->
<?php } else {?>
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                        <i class="fa fa-<?php if ( $staff->getIcon() ) echo $staff->getIcon(); else echo "user";?> fa-2x"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a></li>
                        <li><a href="#"><i class="fa fa-bar-chart-o fa-fw"></i> Stats</a></li>
                        <li class="divider"></li>
                        <li><a href="/logout.php?n=n"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        </li>
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
                            <a href="/index.php"><i class="fa fa-ticket fa-fw"></i> FabApp</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa fa-calculator fa-fw"></i> Tools</a>
                        </li>
<!-- if role > 6 {show} -->
<?php if ($staff) if($staff->getRoleID() > 6) { ?>
                        <li>
                            <a href="#"><i class="fa fa-gift fa-fw"></i> Pick Up 3D Print<span class="fa arrow"></span></a>
							<ul class="nav nav-second-level">
                            <form name="pickForm" method="POST" action="" autocomplete="off" onsubmit="return validateNum('pickForm')">
                                <li class="sidebar-search">
                                    <div class="input-group custom-search-form">
                                        <input type="text" name="pickField" class="form-control" placeholder="Enter ID #" maxlength="10" size="10">
                                        <span class="input-group-btn">
                                        <button class="btn btn-default" type="submit" name="pickBtn">
                                            <i class="fa fa-search"></i>
                                        </button>
                                        </span>
                                    </div>
                                </li>
                            </form>
                            </ul>
                        </li>
<!-- if role > 6 {show} else {look up trans staff->getID()} -->
                        <li>
                            <a href="#"><i class="fa fa-search fa-fw"></i> Look-Up By<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                            <form name="searchForm" method="POST" action="" autocomplete="off"  onsubmit="return validateNum('searchForm')"> 
                                <li class="sidebar-radio">
                                    <input type="radio" name="searchType" value="trans" id="trans" checked><label for="trans">Ticket</label>
                                    <input type="radio" name="searchType" value="uta_id" id="uta"><label for="uta">UTA ID</label>
                                </li>
                                <li class="sidebar-search">
                                    <div class="input-group custom-search-form">
                                        <input type="number" name="searchField" class="form-control" placeholder="Search..." name="searchField">
                                        <span class="input-group-btn">
                                        <button class="btn btn-default" type="submit" name="searchBtn">
                                            <i class="fa fa-search"></i>
                                        </button>
                                        </span>
                                    </div>
                                </li>
                            </form>
                            </ul>
                        </li>
<?php } ?>
                        <li>
                            <a href="#"><i class="fa fa-linode fa-fw"></i> Inventory</a>
                        </li>
<?php if ($staff) if($staff->getRoleID() > 6) { ?>
                        <li>
                            <a href="#"><i class="fa fa-wrench fa-fw"></i> Service Request<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="#"><i class="fa fa-fire fa-fw"></i> Report Issue</a>
                                </li>
                                <li>
                                    <a href="#"><i class="fa fa-history fa-fw"></i> History</a>
                                </li>
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>
                        <li>
                            <a href="#"><i class="fa fa-sitemap fa-fw"></i> Admin<span class="fa arrow"></span></a>
                            <ul class="nav nav-second-level">
                                <li>
                                    <a href="/admin/index.php"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                                </li>
                                <li>
                                    <a href="#"><i class="fa fa-money fa-fw"></i> Accounts<span class="fa arrow"></span></a>
                                    <ul class="nav nav-third-level">
                                        <li>
                                            <a href="#"><i class="fa fa-edit fa-fw"></i> Manage Accounts</a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fa fa-balance-scale fa-fw"></i> Reconcile</a>
                                        </li>
                                    </ul>
                                    <!-- /.nav-third-level -->
                                </li>
                                <li>
                                    <a href="#"><i class="fa fa-bar-chart-o fa-fw"></i> Charts</a>
                                </li>
                                <li>
                                    <a herf="#"><i class="fa fa-cubes fa-fw"></i> Devices<span class="fa arrow"></span></a>
                                    <ul class="nav nav-third-level">
                                        <li>
                                            <a href="#"><i class="fa fa-cube fa-fw"></i> Manage Device</a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fa fa-life-buoy fa-fw"></i> Device Materials</a>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <a herf="#"><i class="fa fa-linode fa-fw"></i> Materials<span class="fa arrow"></span></a>
                                    <ul class="nav nav-third-level">
                                        <li>
                                            <a href="#"><i class="fa fa-bar-chart-o fa-fw"></i> Inventory</a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fa fa-truck fa-fw"></i> Receive</a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fa fa-life-buoy fa-fw"></i> Manage Materials</a>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <a herf="#"><i class="fa fa-users fa-fw"></i> Users<span class="fa arrow"></span></a>
                                    <ul class="nav nav-third-level">
                                        <li>
                                            <a href="#"><i class="fa fa-user-circle-o fa-fw"></i> Manage Users</a>
                                        </li>
                                        <li>
                                            <a href="#"><i class="fa fa-tag fa-fw"></i> Citation</a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                            <!-- /.nav-second-level -->
                        </li>
<?php } ?>
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
        </nav>