<?php

/********************************************************************************************************************************
*
*	Users.php
*	FabApp V0.96—Permissions
*
*	CREATED BY: Jon Le
*	EDITED BY: MPZinke on 2020.06.17
*		- Adds Permissions ability.  Sepparates Staff methods and Users methods.  Commenting.  Better logic &
*			queries.  Uses constructor and standardizes methods/variables.  Proper error throwing.
*
*	DESCRIPTION:	
*	BUGS:
*	FUTURE:
*
********************************************************************************************************************************/


include_once ($_SERVER['DOCUMENT_ROOT']."/class/site_variables.php");

$ROLE = array();
if(!$results = $mysqli->query("SELECT `r_id`, `variable` FROM `role`;"))
	throw new Exception("Users.php: Bad query: $mysqli->error");
else while($row = $results->fetch_assoc()) $ROLE[$row['variable']] = intval($row['r_id']);


// holds data about a user based on ID number.
// queries from `users`, `accounts`, `permissions`, `rfid` tables.
// used by transactional, material, and metrical operations.
// has static functions to safely create user objects based on ID, RFID. 
class Users
{
	const BAD_ID = 0;  // bad user ID
	const UNKNOWN_ID = 1;  // user not found in DB
	const KNOWN_ID = 2;  // user found in DB

	// `users` table data
	private $id;  // char[10](string)—user ID number (1000 number)
	private $adj_date;  // string—time role was set
	private $exp_date;  // string—time role expires
	private $icon;  // string—fontawesome code for icon
	private $notes;  // string—notes...
	private $r_id;  // int—assigned role to staff member

	// other tables
	private $accounts = array();  // array<Account>—accounts available to user
	private $permissions = array();  // array<string>—the permission codes for user
	private $rfid_no;  // string—rfid number assocated with ID

	// other
	private $time_limit;  // int—number of seconds before JS logout


	// ——————————————————— OBJECT CREATION ———————————————————

	public function __construct($id)
	{
		if(!self::regex_id($id)) throw new Exception("Bad user id: $id");  // be extra catious

		// —— USERS TABLE ATTRIBUTES ——
		$this->id = $id;

		global $mysqli;
		if(!$user_result = $mysqli->query("SELECT * FROM `users` WHERE `user_id` = '$id';"))
			throw new Exception("Users::__construct: Bad query: $mysqli->error");

		if(!$user_result->num_rows) $this->r_id = 2;  // user does not exist in DB
		else  // user exists in DB
		{
			$row = $user_result->fetch_assoc();

			$attributes = array("adj_date", "exp_date", "icon", "notes", "r_id");
			foreach($attributes as $attribute) $this->$attribute = $row[$attribute];
		}

		$this->set_user_time_limit();

		// —— OTHER TABLES ATTRIBUTES ——
		// accounts
		$this->set_accounts();

		// icon
		if(!$this->icon) $this->icon = "fas fa-user";

		// permissions
		if(!$permission_results = $mysqli->query(	"SELECT `perm_id` FROM `user_permissions`
														WHERE `user_id` = '$id'
														UNION SELECT `perm_id` FROM `permissions`
														WHERE `r_id` <= $this->r_id;"
		)) throw new Exception("Users::__construct: Bad query: $mysqli->error");
		while($row = $permission_results->fetch_assoc()) $this->permissions[] = $row["perm_id"];

		// rfid
		if($rfid_result = $mysqli->query("SELECT `rfid_no` FROM `rfid` WHERE `user_id` = '$id';"))
			$this->rfid_no = $rfid_result->fetch_assoc()["rfid_no"];
		else throw new Exception("Users::__construct: Bad query: $mysqli->error");
	}


	// formerly: public static function withID($operator).
	// safely create an object with ID.
	// takes ID (numeric char(10)).
	// returns Users obj for ID or false for bad ID format.
	public static function with_id($id)
	{
		if(self::is_staff_in_DB($id))
		{
			try
			{
				return new Staff($id);
			}
			catch(Exception $exception)
			{
				return false;  // is staff, but error
			}
		}
		else  // not staff (added else for clarity)
		{
			try
			{
				return new self($id);
			}
			catch(Exception $exception)
			{
				return false;
			}
		}
	}


	// formerly: public static function withRF($rfid_no).
	// safely create an object with RFID.
	// takes RFID number.
	public static function with_rfid($rfid_no)
	{
		global $mysqli;

		if(!self::regex_rfid($rfid_no)) return false;

		$result = $mysqli->query("SELECT `user_id` FROM `rfid` WHERE `rfid_no` = '$rfid_no';");
		if(!$result || !$result->num_rows) return false;

		try
		{
			return new self($result->fetch_assoc()["id"]);
		}
		catch (Exception $exception)
		{
			return false;
		}
	}


	// ———————————————————— PERMISSION —————————————————————

	// checks if passed user is same as this object
	// takes string ID or user object
	// returns is they are the same
	public function is_same_as($user)
	{
		if(is_object($user)) return $this->id == $user->id;
		if(is_string($user) && self::regex_id($user)) return $this->id == $user;
		return false;
	}


	// SUGAR: compare this r_id with global ROLE list.
	// return if this object is r_id.
	public function is_staff()
	{
		global $ROLE;

		return $ROLE["staff"] <= $this->r_id;
	}


	// validates if user has sufficient permission or role.
	// takes a single role or a single permission.
	// if either is null, returns false. checks that user has role or permission.
	// return bool of if they have it.
	public function validate($role_or_permission)
	{
		if(!$role_or_permission) return false;  // prevent unfinished/missed work from compromising security

		if(is_int($role_or_permission)) return $role <= $this->r_id;
		else if(is_string($role_or_permission))
			return in_array($role_or_permission, $this->permissions);
		return false;
	}


	// validate if user has permission(s).
	// takes a permission string or array of string permissions.
	// if either is null, returns false. checks that user has single permission or multiple.
	// return bool of if they have it.
	public function validate_permissions($permissions)
	{
		if(!$permissions) return false;  // prevent unfinished/missed work from compromising security

		if(is_string($permissions) && in_array($permissions, $this->permissions)) return true;
		else if(is_array($permissions))
		{
			foreach ($permissions as $permission)
			{
				if(!in_array($permission, $this->permissions)) return false;
			}
			return true;
		}

		return false;
	}


	// validate if user has role.
	// takes a role int.
	// if null or not int, returns false.
	// return bool of if role is high enough.
	public function validate_role($role)
	{
		if(!$role) return false;  // prevent unfinished/missed work from compromising security

		if(!is_int($role)) throw new Exception("Users::validate_role: Bad value: $role");
		return $role <= $this->r_id;
	}


	// ————————————————————— GETTERS —————————————————————

	// gets the (private) property of the object
	// takes property name as a string
	// returns value for property if it is set: otherwise NULL
	public function __get($property)
	{
		return isset($this->$property) ? $this->$property : NULL;
	}


	// "PHP magic function" acts as sugar to return user ID
	public function __toString()
	{
		return $this->id;
	}


	// check if an ID is in the DB for a user.
	// takes ID string.
	// regexes ID. checks DB for it.
	// returns whether in users table.
	public static function is_in_DB($id)
	{
		global $mysqli;

		if(!self::regex_id($id)) throw new Exception("Users::is_in_DB: user ID: $id is invalid");
		$result = $mysqli->query("SELECT `user_id` FROM `users` WHERE `user_id` = '$id';");
		return $result && $result->num_rows;
	}


	// check that user is staff in DB.
	// takes user ID.
	// queries DB.
	// returns if in DB && role is greater or equal to staff.
	private static function is_staff_in_DB($id)
	{
		global $mysqli, $ROLE;

		if(!self::regex_id($id)) return false;

		$result = $mysqli->query("SELECT `r_id` FROM `users` WHERE `user_id` = '$id';");
		if(!$result || !$result->num_rows) return false;

		return $ROLE["staff"] <= $result->fetch_assoc()['r_id'];
	}


	// formerly: public static function RFIDtoID($rfid_no).
	// get the ID for a user based on their RFID number.
	// takes RFID number string.
	// queries DB and creates Users object if found.
	// returns Users object if found or false.
	public static function user_for_rfid_no($rfid_no)
	{
		global $mysqli;
		
		if(!self::regex_rfid($rfid_no)) return false;

		if(!$result = $mysqli->query("SELECT `user_id` FROM `rfid` WHERE `rfid_no` = '$rfid_no'")
		|| !$result->num_rows)
			return false;

		return self::with_id($result->fetch_assoc()["user_id"]);
	}


	// ————————————————————— SETTERS —————————————————————

	// queries DB for associated accounts (currently not implemented).
	// gets accounts for user from DB table `auth_accts`. adds accounts to property array.
	// returns success of query.
	private function set_accounts()
	{
		global $mysqli;
		
		//Authorized Accounts that the user is authorized to use
		if(!$result = $mysqli->query(	"SELECT `a_id` FROM `auth_accts`
										WHERE `auth_accts`.`user_id` = '$this' AND `valid` = 'Y';"
		)) return false;

		$this->accounts = array();  // (re)set accounts
		while($row = $result->fetch_assoc()) $this->accounts[] = new Accounts($row['a_id']);
		return true;
	}


	// sets the login time of a user.
	// based on role, sets time_limit property with the site variable.
	private function set_user_time_limit()
	{
		global $ROLE, $SITE_VARS;

		$this->time_limit = ($ROLE["lead"] <= $this->r_id) ? $SITE_VARS["limit_long"] : $SITE_VARS["limit"];
	}


	// —————————————————————— REGEX ——————————————————————

	// formerly regexUser($operator).
	// regexes a users ID.
	// takes user id string.
	// compares to DB stored regex format.
	// returns if ID invalid, not in DB, or in DB with class constants.
	public static function regex_id($id)
	{
		global $mysqli, $MAKERSPACE_VARS;

		if(!preg_match("/$MAKERSPACE_VARS[regex_id]/", $id)) return self::BAD_ID;
		if(!$result = $mysqli->query("SELECT * FROM `users` WHERE `user_id` = '$id';" || !$result->num_rows))
			return self::UNKNOWN_ID;
		return self::KNOWN_ID;
	}


	// formerly: public static function regexRFID($rfid_no).
	// regexes an RFID number.
	// takes an RFID number.
	// returns boolean value of the pregmatch.
	public static function regex_rfid($rfid_no)
	{
		return boolval(preg_match("/^\d{4,12}$/", $rfid_no));
	}


	// —————————————————————— STATS ——————————————————————

	// create list of tickets for this user.
	// query for all relevant transactional data. add data to array.
	// returns array of transactional data.
	public function transaction_history()
	{
		global $mysqli;
		
		$tickets = array();
		if($result = $mysqli->query(	"SELECT `transactions`.`trans_id`, `devices`.`device_desc` AS device_name,
										`transactions`.`t_start`, `status`.`message`, `acct_charge`.`amount`
										FROM `transactions`
										LEFT JOIN `devices` ON `transactions`.`d_id` = `devices`.`d_id`
										LEFT JOIN `status` ON `transactions`.`status_id` = `status`.`status_id`
										LEFT JOIN `acct_charge` ON `transactions`.`trans_id` = `acct_charge`.`trans_id`
										WHERE `transactions`.`user_id` = '$this'
										ORDER BY `trans_id` DESC;"
		))
		{
			while($row = $result->fetch_assoc()) $tickets[] = $row;
		}
		return $tickets;
	}


	// formerly: public function ticketsAssist().
	// gets the number of tickets user is marked as assistant to.
	// queries DB for count of assistants.
	// returns result (int) or -1 if bad result.
	public function transaction_assists()
	{
		global $mysqli;
		
		if(!$result = $mysqli->query(	"SELECT COUNT(`trans_id`) as ASSISTS FROM `transactions` 
										WHERE `staff_id` = '$this';"
		)) return -1;

		return $result->fetch_assoc()['ASSISTS'];  // returns 0 if no results found (thanks COUNT()!)

	}


	// formerly: public function ticketsAssistRank().
	// gets the ranking of number of assists.
	// queries DB for count of assistants as subquery. then counts the number of those 
	// greater than user's number of assists.
	// returns result (int) or -1 if bad result.
	public function transaction_assists_rank()
	{
		global $mysqli;

		$assists = self::transaction_assists();
		if($assists == -1 || !$result = $mysqli->query(	"SELECT COUNT(ASSISTS) AS RANKING FROM (
																SELECT COUNT(*) as ASSISTS FROM `transactions`
																GROUP BY `staff_id` ORDER BY ASSISTS DESC
															) AS ASSIST_RANK WHERE ASSISTS > $assists;"
		)) return -1;

		return $result->fetch_assoc()["RANKING"];
	}


	// formerly: public function ticketsTotal().
	// gets the number of tickets user has created.
	// queries DB for count of tickets.
	// returns result (int) or -1 if bad result.
	public function total_transactions()
	{
		global $mysqli;
		
		if(!$result = $mysqli->query(	"SELECT COUNT(`trans_id`) as TICKETS FROM `transactions`
										WHERE `user_id` = '$this';"
		)) return -1;

		return $result->fetch_assoc()["TICKETS"];  // returns 0 if no results found (thanks COUNT()!)
	}


	// formerly: public function ticketsTotalRank().
	// gets the ranking of number of tickets.
	// queries DB for count of tickets as subquery. then counts the number of those 
	// greater than user's number of tickets.
	// returns result (int) or -1 if bad result.
	public function total_transactions_rank()
	{
		global $mysqli;

		$tickets = self::total_transactions();
		if($tickets == -1 || !$result = $mysqli->query(	"SELECT COUNT(TICKETS) AS RANKING FROM (
															SELECT COUNT(*) as TICKETS FROM `transactions`
															GROUP BY `user_id` ORDER BY TICKETS DESC
														) AS TICKET_RANK WHERE TICKETS > $tickets;"
		)) return -1;

		return $result->fetch_assoc()["RANKING"];
	}
 }



// ——————————————————————— ROLE ———————————————————————
// —————————————————————————————————————————————————

// manages data for `role` table.
class Role
{
	// formerly: public static function getTabResult().
	// gets number of roles currenly in use.
	// creates array. queries for used roles & adds them to array.
	// returns array of currently used roles.
	public static function current_used_roles()
	{
		global $mysqli;

		$used_roles = array();
		if($result = $mysqli->query(	"SELECT `r_id`, `title` FROM `role` 
										WHERE `r_id` IN (SELECT DISTINCT `r_id` FROM `users`);"
		))
		{
			while($row = $result->fetch_assoc()) $used_roles[$row["r_id"]] = $row["title"];
		}

		return $used_roles;
	}

	
	// formerly: public static function getTitle($r_id).
	// converts an r_id to its title.
	// takes r_id (int).
	// if valid, queries DB for title string.
	// return title string if found, else false.
	public static function to_title($r_id)
	{
		global $mysqli;

		if(!self::regex_id($r_id)) return false;

		if(!$result = $mysqli->query("SELECT `title` FROM `role` WHERE `r_id` = $r_id;") || !$result->num_rows)
			return false;

		return $result->fetch_assoc()["title"];
	}


	// formerly: public static function listRoles().
	// lists roles by r_id and title.
	// adds query to associative array <r_id, title>. throws error if bad query.
	// returns array.
	public static function list_roles()
	{
		global $mysqli;

		$roles = array();
		if(!$result = $mysqli->query("SELECT `r_id`, `title` FROM `role`;") || !$result->num_rows)
			throw new Exception("Role::list_roles: bad query: $mysqli->error");
			
		while($row = $result->fetch_assoc()) $roles[$row["r_id"]] = $row["title"];
		return $roles;
	}


	// —————————————————————— REGEX ——————————————————————

	// check if role_id is valid.
	// takes role_id.
	// converts role to in if string. checks that role is within listed role bounds.
	// returns true if all conditions met, else false.
	public static function regex_id($id)
	{
		global $ROLE;

		if(is_int($id) || preg_match("/^\d{1,2}/", $id))
		{
			if(!is_int($id)) $id = intval($id);
			return 0 < $id && $id <= $ROLE["super"];
		}

		return false;
	}
}



// ——————————————————————— STAFF ———————————————————————
// —————————————————————————————————————————————————

// holds data for staff object.
// adds priviledged methods to users who are staff in the DB.
class Staff extends Users
{
	// ————————————————————— CREATION —————————————————————
	
	public function __construct($id)
	{
		global $ROLE;

		parent::__construct($id);  // create user

		// validate staff level
		if($this->r_id < $ROLE["staff"]) throw new Exception("Staff::__construct: user is not staff");
	}


	// ————————————————————— CREATORS —————————————————————

	// formerly: public function insertRFID($staff, $rfid_no).
	// adds rfid to user.
	// takes rfid number (string), user (object or id).
	// validates permission of staff to edit rfid & rfid number. checks that user not already in rfid table. if so, calls
	// update_rfid. else creates new instance in DB (users object converts to string w/ magic methos __toString). 
	// returns execution success (bool).
	public function new_rfid($rfid_no, $user)
	{
		global $mysqli;

		// validate data
		if(!$this->validate("edit_rfid"))
		{
			$_SESSION["error_msg"] = "Insufficient role to add new RFID";
			return false;
		}
		if(!self::regex_rfid($rfid_no))
		{
			$_SESSION["error_msg"] = "Invalid RFID number: $rfid_no";
			return false;
		}
		
		// check if RFID already exists: if it does, return false
		if(!$result = $mysqli->query("SELECT `user_id` FROM `rfid` WHERE `rfid_no` = $rfid_no;"))
			throw new Exception("Users::new_rfid: bad query: $mysqli->error");
		if($result->num_rows) return self::update_rfid($rfid_no, $user);

		$statement = $mysqli->prepare("INSERT INTO `rfid` (`rfid_no`, `user_id`) VALUES (?, ?);");
		if(!$statement) throw new Exception("Users::new_rfid: bad prepare: $mysqli->error");
		$statement->bind_param("ss", $rfid_no, $user);
		if(!$statement) throw new Exception("Users::new_rfid: bad parameter binding: $mysqli->error");

		// submit & return outcome
		return $statement->execute();
	}

	
	// formerly: public function insertUser($staff, $r_id).
	// adds new user in `users` table.
	// takes new user's ID (string), role (int), notes (string).
	// validates permission to change. check if already in DB: if so, uses update function. else add to DB.
	// returns success (bool).
	public function new_user($new_user_id, $r_id, $notes=NULL)
	{
		global $mysqli, $ROLE;

		if(!$this->validate($ROLE["admin"]))
		{
			$_SESSION["error_msg"] = "Insufficient role to Modify Role";
			return false;
		}
		if($this->is_same_as($new_user_id))
		{
			$_SESSION["error_msg"] = "Staff can not modify their own Role ID";
			return false;
		}

		// check if user already exists
		if(!$result = $mysqli->query("SELECT * FROM `users` WHERE `user_id` = '$new_user_id';"))
			throw new Exception("Users::new_user: bad query: $mysqli->error");
		if($result->num_rows) return self::update_user($new_user_id, $r_id, $notes);  // already exists; update

		if(!$statement = $mysqli->prepare("INSERT INTO `users` (`user_id`, `r_id`, `staff_id`) VALUES (?, ?, ?);"))
			throw new Exception("Users::new_user: bad prepare: $mysqli->query");
		if(!$statement->bind_param("sds", $new_user_id, $r_id, $this->id))
			throw new Exception("Users::new_user: bad parameter binding: $mysqli->query");

		return $statement->execute();
	}


	// ————————————————————— GETTERS —————————————————————

	// formerly: public static function rfidExist($rfid_no).
	// checks if rfid number has already in use.
	// takes rfid_no (string).
	// queries DB if in.
	// returns if found (bool).
	public static function rfid_exist($rfid_no)
	{
		global $mysqli;
		
		if($result = $mysqli->query("SELECT * FROM `rfid` WHERE `rfid_no` = '$rfid_no';"))
		{
			return boolval($result->num_rows);
		}

		throw new Exception("Users::rfid_exist: bad query: $mysqli->error");
	}


	// ————————————————————— SETTERS —————————————————————

	// sets user role to below staff & remove permissions.
	// takes ID (string) of user.
	// reduces role to 2 & sets any permissions user may have to invalid.
	// returns success (bool).
	public static function offboarding($id){
		global $mysqli;
		
		// revoke role
		$mysqli->query("UPDATE `users` SET `r_id` = 2 WHERE `user_id` = '$id';");
		if(!$mysqli->affected_rows) return false;

		// revoke permissions
		if($mysqli->query("UPDATE `users_permissions` SET `valid` = FALSE WHERE `user_id` = '$id';")) return false;
		return true;
	}


	// updates user's icon.
	// takes icon (string), user (object or id).
	// inserts into DB new string for user icon.
	// returns execute success (bool).
	public function update_icon($icon, $user)
	{
		global $mysqli;

		if(!$statement = $mysqli->prepare("UPDATE `users` SET `icon` = ? WHERE `user_id` = ?;"))
			throw new Exception("onboarding.php: bad prepare: $mysqli->error");

		if(!$statement->bind_param("ss", $icon, $user))
			throw new Exception("onboarding.php: bad parameter binding: $mysqli->error");

		return $statement->execute();	
	}


	// updates rfid_no for user.
	// takes rfid number (string), user (object or id).
	// validates permission of staff to edit rfid & rfid number. checks that rfid is in rfid table. if not, calls
	// new_rfid. else updates instance in DB (users object converts to string w/ magic methos __toString). 
	// returns execution success (bool).
	public function update_rfid($rfid_no, $user)
	{
		global $mysqli;

		// validate data
		if(!$this->validate("edit_rfid"))
		{
			$_SESSION["error_msg"] = "Insufficient role to add new RFID";
			return false;
		}
		if(!self::regex_rfid($rfid_no))
		{
			$_SESSION["error_msg"] = "Invalid RFID number: $rfid_no";
			return false;
		}
		
		// check if RFID already exists: if it does, return false
		if(!$result = $mysqli->query("SELECT `user_id` FROM `rfid` WHERE `rfid_no` = $rfid_no;"))
			throw new Exception("Users::new_rfid: bad query: $mysqli->error");
		if(!$result->num_rows) return self::new_rfid($rfid_no, $user);  // check if exists; if not, create new

		// update
		$statement = $mysqli->prepare("UPDATE `rfid` SET `rfid_no` = ? WHERE `user_id` = ?;");
		if(!$statement) throw new Exception("Users::new_rfid: bad query: $mysqli->error");
		$statement->bind_param("ss", $rfid_no, $user->id);
		if(!$statement) throw new Exception("Users::new_rfid: bad parameter binding: $mysqli->error");

		// submit & return outcome
		return $statement->execute();
	}


	// formerly: public function modifyRoleID($staff, $notes).
	// updates role of user.
	// takes user (object or id), role (int), notes (string).
	// validates permission of staff to edit role. checks that user is in users table. if not, calls
	// new_user. else updates instance in DB (users object converts to string w/ magic methos __toString). 
	// returns execution success (bool).
	public function update_r_id($user, $r_id, $notes=NULL)
	{
		global $mysqli, $ROLE;

		if(!$this->validate($ROLE["admin"]))
		{
			$_SESSION["error_msg"] = "Insufficient role to Modify Role";
			return false;
		}
		if($this->is_same_as($user))
		{
			$_SESSION["error_msg"] = "Staff can not modify their own Role ID";
			return false;
		}

		if(!$results = $mysqli->query("SELECT `user_id` FROM `users` WHERE `user_id` = '$id';"))
			throw new Exception("Users::new_user: bad query: $mysqli->error");
		if(!$result->num_rows) return self::new_user($user, $r_id, $notes);  // does not exist; add

		if(!$statement = $mysqli->prepare(	"UPDATE `users` SET  `notes` = ?, `r_id` = ?, `staff_id` = ?
												WHERE `user_id` = ?;"
		)) throw new Exception("Users::new_user: bad prepare: $mysqli->query");
		if(!$statement->bind_param("sdss", $notes, $r_id, $this->id, $user))
			throw new Exception("Users::new_user: bad bind: $mysqli->query");

		return $statement->execute();
	}
}

?>