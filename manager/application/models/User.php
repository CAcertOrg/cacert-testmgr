<?php
/**
 * @author Michael Tänzer
 */

class Default_Model_User {
    protected $db;
    
    protected $id;
    protected $points = null;
    
    protected function __construct(Zend_Db_Adapter_Abstract $db, $id) {
        // Not allowed to create new users from within the manager
        
        $this->db = $db;
        $this->id = $id;
    }
    
    /**
     * Get an user object for the given ID
     * 
     * @param $id int
     * @return Default_Model_User
     */
    public static function findById($id) {
        // Get database connection
        $config = new Zend_Config_Ini(
            APPLICATION_PATH . '/configs/application.ini',
            APPLICATION_ENV);
        $db = Zend_Db::factory($config->ca_mgr->db->auth->pdo,
            $config->ca_mgr->db->auth);
        
        // Check if the ID is present on the test server
        $query = 'select `id` from `users` where `id` = :user';
        $query_params['user'] = $id;
        $result = $db->query($query, $query_params);
        if ($result->rowCount() !== 1) {
            throw new Exception(
                __METHOD__ . ': user ID not found in the data base');
        }
        $row = $result->fetch();
        
        return new Default_Model_User($db, $row['id']);
    }
    
    /**
     * Get an user object for the currently logged in user
     * 
     * @return Default_Model_User
     */
    public static function findCurrentUser() {
        $session = Zend_Registry::get('session');
        if ($session->authdata['authed'] !== true) {
            throw new Exception(
                __METHOD__ . ': you need to log in to use this feature');
        }
        
        return self::findById($session->authdata['authed_id']);
    }
    
    /**
     * Get the first assurer who didn't already assure the user
     * 
     * @return Default_Model_User
     */
    public function findNewAssurer()
    {
        $query = 'select min(`id`) as `assurer` from `users` ' .
        	'where `email` like \'john.doe-___@example.com\' and ' .
            '`id` not in (select `from` from `notary` where `to` = :user)';
        $query_params['user'] = $this->id;
        $row = $this->db->query($query, $query_params)->fetch();
        
        if ($row['assurer'] === NULL) {
            throw new Exception(
                __METHOD__ . ': no more assurers that haven\'t already '.
                'assured this account');
        }
        
        return new Default_Model_User($this->db, $row['assurer']);
    }
    
    /**
     * Refresh the current value of points from the test server
     * 
     * Needed if operations outside this class are made, that might affect the
     * user's points
     */
    public function refreshPoints() {
        $query = 'select sum(`points`) as `total` from `notary` '.
        	'where `to` = :user';
        $query_params['user'] = $this->id;
        $row = $this->db->query($query, $query_params)->fetch();
        if ($row['total'] === null) $row['total'] = 0;
        
        $this->points = $row['total'];
    }
    
	/**
     * Get points of the user
     * 
     * @return int
     * 		The amount of points the user has
     */
    public function getPoints()
    {
        if ($this->points === null) {
            $this->refreshPoints();
        }
        
        return $this->points;
    }
    
    /**
     * Fix the assurer flag for the user
     */
    public function fixAssurerFlag()
    {
    	// TODO: unset flag if requirements are not met
    	
        $query = 'UPDATE `users` SET `assurer` = 1 WHERE `users`.`id` = :user AND '.
            
            'EXISTS(SELECT * FROM `cats_passed` AS `cp`, `cats_variant` AS `cv` '.
            'WHERE `cp`.`variant_id` = `cv`.`id` AND `cv`.`type_id` = 1 AND '.
            '`cp`.`user_id` = :user) AND '.
            
            '(SELECT SUM(`points`) FROM `notary` WHERE `to` = :user AND '.
            '`expire` < now()) >= 100';
        $query_params['user'] = $this->id;
        $this->db->query($query, $query_params);
    }
    
    /**
     * @return boolean
     */
    public function getAssurerStatus() {
        $query = 'SELECT 1 FROM `users` WHERE `users`.`id` = :user AND '.
        	'`assurer_blocked` = 0 AND'.
            
        	'EXISTS(SELECT * FROM `cats_passed` AS `cp`, `cats_variant` AS `cv` '.
        	'WHERE `cp`.`variant_id` = `cv`.`id` AND `cv`.`type_id` = 1 AND '.
        	'`cp`.`user_id` = :user) AND '.
            
            '(SELECT SUM(`points`) FROM `notary` WHERE `to` = :user AND '.
            '`expire` < now()) >= 100';
        $query_params['user'] = $this->id;
        $result = $this->db->query($query, $query_params);
        if ($result->rowCount() === 1) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @return Zend_Date
     */
    public function getDob() {
        $query = 'select `dob` from `users` where `id` = :user';
        $query_params['user'] = $this->id;
        $row = $this->db->query($query, $query_params)->fetch();
        
        return new Zend_Date($row['dob'], Zend_Date::ISO_8601);
    }
    
    /**
     * @return int
     */
    public function getAge() {
        $now = new Zend_Date();
        return $now->sub($this->getDob())->toValue(Zend_Date::YEAR);
    }
    
    /**
     * Assure another user. Usual restrictions apply
     * 
     * @param $assuree Default_Model_User
     * @param $points int
     * @param $location string
     * @param $date string
     * @throws Exception
     * 
     * @return int
     * 		The amount of points that have been issued (might be less than
     * 		$points)
     */
    public function assure(Default_Model_User $assuree, $points, $location,
            $date) {
        // Sanitize inputs
        $points = intval($points);
        $location = stripslashes($location);
        $date = stripslashes($date);
        
        if (!$this->getAssurerStatus()) {
            throw new Exception(
                __METHOD__ . ': '.$this->id.' needs to be an assurer to do '.
                'assurances');
        }
        
        if ($this->id === $assuree->id) {
            throw new Exception(
                __METHOD__ . ': '.$this->id.' is not allowed to assure '.
                'himself');
        }
        
        $query = 'select * from `notary` where `from`= :assurer and '.
        	'`to`= :assuree';
        $query_params['assurer'] = $this->id;
        $query_params['assuree'] = $assuree->id;
        $result = $this->db->query($query, $query_params);
        if ($result->rowCount() > 0 && $this->getPoints() < 200) {
            throw new Exception(
                __METHOD__ . ': '.$this->id.' is not allowed to assure '.
                $assuree->id .' more than once');
        }
        
        // Respect the maximum points
        $max = $this->maxpoints();
        $points = min($points, $max);
        
        $rounddown = $points;
        if ($max < 100) {
            if ($assuree->getPoints() + $points > 100)
                $rounddown = 100 - $assuree->getPoints();
        } else {
            if ($assuree->getPoints() + $points > $max)
                $rounddown = $max - $assuree->getPoints();
        }
        if ($rounddown < 0) $rounddown = 0;
        
        $query = 'select * from `notary` where `from` = :assurer and '.
        	'`to` = :assuree and `awarded` = :points and '.
        	'`location` = :location and `date` = :date';
        $query_params['assurer'] = $this->id;
        $query_params['assuree'] = $assuree->id;
        $query_params['points'] = $points;
        $query_params['location'] = $location;
        $query_params['date'] = $date;
        $result = $this->db->query($query, $query_params);
        if ($result->rowCount() > 0) {
            throw new Exception(
                __METHOD__ . ': '.$this->id.' is not allowed to do the same '.
                'assurance to '.$assuree->id.' more than once');
        }
        
        // Make sure it is empty
        $assurance = array();
        $assurance['from'] = $this->id;
        $assurance['to'] = $assuree->id;
        $assurance['points'] = $rounddown;
        $assurance['awarded'] = $points;
        $assurance['location'] = $location;
        $assurance['date'] = $date;
        $assurance['when'] = new Zend_Db_Expr('now()');
        
        $this->db->insert('notary', $assurance);
        $assuree->points += $rounddown;
        $assuree->fixAssurerFlag();
        
        if ($this->getPoints() < 150) {
            $addpoints = 0;
            if ($this->getPoints() < 149 && $this->getPoints() >= 100) {
                $addpoints = 2;
            } elseif ($this->getPoints() === 149) {
                $addpoints = 1;
            }
            
            $increase = array();
            $increase['from'] = $this->id;
            $increase['to'] = $this->id;
            $increase['points'] = $addpoints;
            $increase['awarded'] = $addpoints;
            $increase['location'] = $location;
            $increase['date'] = $date;
            $increase['method'] = 'Administrative Increase';
            $increase['when'] = new Zend_Db_Expr('now()');
            
            $this->db->insert('notary', $increase);
            $this->points += $addpoints;
            // No need to fix assurer flag here
        }
        
        return $rounddown;
    }
    
    /**
     * Maximum number of points the user may issue
     * 
     * @return int
     */
    private function maxpoints() {
        if (!$this->getAssurerStatus()) return 0;
        
        if ($this->getAge() < 18) return 10;
        
        $points = $this->getPoints();
        if ($points >= 300) return 200;
        if ($points >= 200) return 150;
        if ($points >= 150) return 35;
        if ($points >= 140) return 30;
        if ($points >= 130) return 25;
        if ($points >= 120) return 20;
        if ($points >= 110) return 15;
        if ($points >= 100) return 10;
        
        // Should not get here
        throw new Exception(
            __METHOD__ . ': '.$this->id.' We have reached unreachable code');
    }
}