#!/usr/bin/php
<?php 
/**
 * Script to create more dummy assurer accounts (for use on the test system)
 * which can be used by the test manager to create automated assurances
 * 
 * @author Michael Tänzer
 */

class CreateAssurers {
	protected $db;
	
	// define constants for the positions in $argv
	const ARGV_SCRIPT    = 0;
	const ARGV_DB_HOST   = 1;
	const ARGV_DB_USER   = 2;
	const ARGV_DB_PASSWD = 3;
	const ARGV_DB_NAME   = 4;
	const ARGV_QUANTITY  = 5;
	
	// required length of $argv
	const ARGV_LENGTH    = 6;
	
	
	function __construct(mysqli $db){
        $this->db = $db;
	}
	
	
	public static function echoUsage($script_name){
		print 'Usage: ' . $script_name; ?> <db_host> <db_user> <db_passwd>
		<db_name> <quantity>

Script to create more dummy assurer accounts (for use on the test system)
which can be used by the test manager to create automated assurances

<quantity> specifies how many new dummy accounts should be created

<?php
	}
	
	
	public static function main(){
		$argc = $_SERVER['argc'];
		$argv = $_SERVER['argv'];
		
    	if ($argc != self::ARGV_LENGTH){
        	self::echoUsage($argv[self::ARGV_SCRIPT]);
        	fwrite(STDERR, "Error: Invalid number of parameters\n");
        	exit(1);
        }
        
    	$quantity = (int)($argv[self::ARGV_QUANTITY]);
        if (!is_numeric($argv[self::ARGV_QUANTITY]) || $quantity < 0){
        	self::echoUsage($argv[self::ARGV_SCRIPT]);
        	fwrite(STDERR, "Error: Last parameter has to be a positive integer\n");
        	exit(2);
        }
        
    	// Try to connect to the database
        $db = new mysqli($argv[self::ARGV_DB_HOST], $argv[self::ARGV_DB_USER],
        		$argv[self::ARGV_DB_PASSWD], $argv[self::ARGV_DB_NAME]);
        if (mysqli_connect_error()){
        	fwrite(STDERR, "Error: Could not connect to the data base\n".
        		"(".mysqli_connect_errno()."): ".mysqli_connect_error());
        	exit(3);
        }
        
        
        $me = new self($db);
        $status = $me->createAssurers($quantity); 
        
        $db->close();
        exit($status);
	}
	
	
	public function createAssurers($quantity){
		// get last assurer
		$result = $this->db->query('select `mname` from `users` where `id`=
			(select max(`id`) from `users` where `email` like
			\'john.doe-___@example.com\')');
		$row = $result->fetch_assoc();
		if ($row === NULL){
			$last_assurer = 0;
			printf("1\n");
		} else {
			$last_assurer = (int)($row['mname']);
			printf("2: \$last_assurer: %d\n", $last_assurer);
		}
		
		
		// prepare the statements
		$insert_user = $this->db->prepare('insert into `users` set
			`email` = ? ,
			`password` = \'invalid\' ,
			`fname` = ? ,
			`mname` = ? ,
			`lname` = \'Doe\' ,
			`suffix` = \'\' ,
			`dob` = ? ,
			`Q1` = SHA1(rand()) ,
			`Q2` = SHA1(rand()) ,
			`Q3` = SHA1(rand()) ,
			`Q4` = SHA1(rand()) ,
			`Q5` = SHA1(rand()) ,
			`A1` = SHA1(rand()) ,
			`A2` = SHA1(rand()) ,
			`A3` = SHA1(rand()) ,
			`A4` = SHA1(rand()) ,
			`A5` = SHA1(rand()) ,
			`created` = now() ,
			`uniqueID` = SHA1(rand()) ,
			`verified` = 1 ,
			`assurer` = 1 ');
		$insert_user->bind_param('ssss', $email, $fname, $mname, $dob);
		
		$insert_email = $this->db->prepare('insert into `email` set
			`email` = ? ,
			`hash` = \'\' ,
			`created` = now() ,
			`modified` = now() ,
			`memid` = ? ');
		$insert_email->bind_param('si', $email, $memid);
		
		$insert_alerts = $this->db->prepare('insert into `alerts` set
			`memid` = ? ,
			`general` = 0 ,
			`country` = 0 ,
			`regional` = 0 ,
			`radius` = 0 ');
		$insert_alerts->bind_param('i', $memid);
		
		$insert_points = $this->db->prepare('insert into `notary` set
			`from` = ? ,
			`to` = ? ,
			`points` = 150 ,
			`awarded` = 150 ,
			`location` = \'Init Points\' ,
			`date` = curdate() ,
			`method` = \'Administrative Increase\' ,
			`when` = now() ');
		$insert_points->bind_param('ii', $memid, $memid);
		
		$insert_cats = $this->db->prepare('insert into `cats_passed` set
			`user_id` = ? ,
			`pass_date` = now() ,
			`variant_id` =
				(select `id` from `cats_variant` where `type_id` = 1) ');
		$insert_cats->bind_param('i', $memid);

		
		// do the actual work
		for ($i = $last_assurer + 1; $i <= $last_assurer + $quantity; $i++){
			$email = sprintf('john.doe-%03u@example.com', $i);
			$fname = sprintf('John %u', $i);
			$mname = sprintf('%u', $i);
			$dob = sprintf('19%02u-01-%02u', $i % 90, (int)(($i/90) + 1) );
			
			$insert_user->execute();
			$memid = $insert_user->insert_id;
			if ($memid == 0){
				fwrite(STDERR, "Error: didn't get a valid ID for the user\n");
				return 10;
			}
			
			$insert_email->execute();
			$insert_alerts->execute();
			$insert_points->execute();
			$insert_cats->execute();
			
			printf('Assurer number %u %s Doe <%s>'."\n".
				'born on %s with the ID %d has been added'."\n", $i,
				$fname, $email, $dob, $memid);
		}
		
		return 0;
	}
}


CreateAssurers::main();