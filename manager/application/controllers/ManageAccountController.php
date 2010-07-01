<?php
/**
 * @author Michael Tänzer
 */

class ManageAccountController extends Zend_Controller_Action
{
    const MAX_POINTS_PER_ASSURANCE = 35;
    const MAX_ASSURANCE_POINTS = 100;
    const MAX_POINTS_TOTAL = 150;
    const ADMIN_INCREASE_FRAGMENT_SIZE = 2;
    
    // Value used in the database to identify a admin increase
    const ADMIN_INCREASE_METHOD = 'Administrative Increase';
    
    protected $db;
    
    public function init()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini',
            APPLICATION_ENV);

        $this->db = Zend_Db::factory($config->ca_mgr->db->auth->pdo,
            $config->ca_mgr->db->auth);
        
        // Build the left navigation
        $actions = array();
        $actions['assurance'] = I18n::_('Automated Assurance');
        $actions['admin-increase'] = I18n::_('Administrative Increase');
        $actions['assurer-challenge'] = I18n::_('Assurer Challenge');
        $url = array('controller' => 'manage-account');
        foreach ($actions as $action => $label) {
            $url['action'] = $action;
            $link = '<a href="'.$this->view->url($url, 'default', true).'">'.
                $label . '</a>';
            $this->view->leftNav($link);
    	}
    	
    }
    
    public function indexAction()
    {
        // Just render the view
        return;
    }
    
    public function assuranceAction()
    {
        // Validate form
        $form = $this->getAssuranceForm();
        if (!$this->getRequest()->isPost() || !$form->isValid($_POST)) {
            $this->view->assurance_form = $form;
            return $this->render('assuranceform');
        }
        
        // Form is valid -> get values for processing
        $values = $form->getValues();
        
        // Get user data
        $user['id'] = $this->getUserId();
        $user['points'] = $this->getPoints($user['id']);
        
        
        // Do the actual assurances
        $assurance = array(); // Make sure the array is empty
        $assurance['to'] = $user['id'];
        $assurance['location'] = $values['location'];
        $assurance['date'] = $values['date'];
        $assurance['when'] = new Zend_Db_Expr('now()');
        $this->view->assurancesDone = array();
        
        $quantity = $values['quantity'];
        do {
            // split up into multiple assurances
            if ($quantity > self::MAX_POINTS_PER_ASSURANCE) {
                $assurance['awarded'] = self::MAX_POINTS_PER_ASSURANCE;
                $quantity -= self::MAX_POINTS_PER_ASSURANCE;
            } else {
                $assurance['awarded'] = $quantity;
                $quantity = 0;
            }
            
            // Get the assurer for this assurance
            $assurance['from'] = $this->getNewAssurer($user['id']);
            
            // only assign points whithin the limit
            if ($user['points'] + $assurance['awarded'] > self::MAX_ASSURANCE_POINTS){
                $assurance['points'] = self::MAX_ASSURANCE_POINTS - $user['points'];
            } else {
                $assurance['points'] = $assurance['awarded'];
            }
            
            $this->db->insert('notary', $assurance);
            
            $user['points'] += $assurance['points'];
            $this->view->assurancesDone[] = $assurance['points'];
        } while ($quantity > 0);
        
        
        // Maybe user is now assurer
        $this->fixAssurerFlag($user['id']);
        
        return;
    }
    
    public function adminIncreaseAction()
    {
        // Validate form
        $form = $this->getAdminIncreaseForm();
        if (!$this->getRequest()->isPost() || !$form->isValid($_POST)) {
            $this->view->admin_increase_form = $form;
            return $this->render('admin-increase-form');
        }
        
        // Form is valid -> get values for processing
        $values = $form->getValues();
        
        // Get user data
        $user['id'] = $this->getUserId();
        $user['points'] = $this->getPoints($user['id']);
        
        
        // Do the actual increase
        $increase = array(); // Make sure the array is empty
        $increase['from'] = $user['id'];
        $increase['to'] = $user['id'];
        $increase['location'] = $values['location'];
        $increase['date'] = $values['date'];
        $increase['method'] = self::ADMIN_INCREASE_METHOD;
        $increase['when'] = new Zend_Db_Expr('now()');
        $this->view->adminIncreasesDone = array();
        
        $quantity = $values['quantity'];
        do {
            // Split up into multiple increases if fragment flag is set
            if ($values['fragment'] == '1' &&
                    $quantity > self::ADMIN_INCREASE_FRAGMENT_SIZE) {
                $increase['awarded'] = self::ADMIN_INCREASE_FRAGMENT_SIZE;
                $quantity -= self::ADMIN_INCREASE_FRAGMENT_SIZE;
            } else {
                $increase['awarded'] = $quantity;
                $quantity = 0;
            }
            
            // Only assign points within the limit if unlimited flag is not set
            if ($values['unlimited'] != '1') {
                if ($user['points'] >= self::MAX_POINTS_TOTAL) {
                    // No more administrative increases should be done
                    break;
                } elseif ($user['points'] + $increase['awarded'] > self::MAX_POINTS_TOTAL) {
                    $increase['awarded'] = self::MAX_POINTS_TOTAL - $user['points'];
                }
            }
            
            // Admin increases always have `points` == `awarded`
            $increase['points'] = $increase['awarded'];
            
            $this->db->insert('notary', $increase);
            
            $user['points'] += $increase['points'];
            $this->view->adminIncreasesDone[] = $increase['points'];
        } while ($quantity > 0);
        
        // Maybe user is now assurer
        $this->fixAssurerFlag($user['id']);
        
        return;
    }
    
    
    public function assurerChallengeAction()
    {
        // Validate form
        $form = $this->getAssurerChallengeForm();
        if (!$this->getRequest()->isPost() || !$form->isValid($_POST)) {
            $this->view->assurer_challenge_form = $form;
            return $this->render('assurer-challenge-form');
        }
        
        // Form is valid -> get values for processing
        $values = $form->getValues();
        
        // Get user data
        $user['id'] = $this->getUserId();
        
        // Assign the assurer challenge
        $challenge = array(); // Make sure the array is empty
        $challenge['user_id'] = $user['id'];
        $challenge['variant_id'] = $values['variant'];
        $challenge['pass_date'] = date('Y-m-d H:i:s');
        $this->db->insert('cats_passed', $challenge);
        
        // Maybe user is now assurer
        $this->fixAssurerFlag($user['id']);
        
        return;
    }
    
    /**
     * Get and check the user ID of the current user
     * 
     * @return int The ID of the current user
     */
    protected function getUserId()
    {
        $session = Zend_Registry::get('session');
        if ($session->authdata['authed'] !== true) {
            throw new Exception(__METHOD__ . ': you need to log in to use this feature');
        }
        
        // Check if the ID is present on the test server
        $query = 'select `id` from `users` where `id` = :user';
        $query_params['user'] = $session->authdata['authed_id'];
        $result = $this->db->query($query, $query_params);
        if ($result->rowCount() !== 1) {
            throw new Exception(__METHOD__ . ': user ID not found in the data base');
        }
        $row = $result->fetch();
        
        return $row['id'];
    }
    
    /**
     * Get current points of the user
     * 
     * @param int $user_id ID of the user
     * @return int the amount of points the user currently has
     */
    protected function getPoints($user_id)
    {
        $query = 'select sum(`points`) as `total` from `notary` where `to` = :user';
        $query_params['user'] = $user_id;
        $row = $this->db->query($query, $query_params)->fetch();
        if ($row['total'] === NULL) $row['total'] = 0;
        
        return $row['total'];
    }
    
    /**
     * Get the first assurer who didn't already assure the user
     * 
     * @param int $user_id The ID of the user who should get assured
     * @return int The ID of the selected assurer
     */
    protected function getNewAssurer($user_id)
    {
        $query = 'select min(`id`) as `assurer` from `users` ' .
        	'where `email` like \'john.doe-___@example.com\' and ' .
            '`id` not in (select `from` from `notary` where `to` = :user)';
        $query_params['user'] = $user_id;
        $row = $this->db->query($query, $query_params)->fetch();
        
        if ($row['assurer'] === NULL) {
            throw new Exception(__METHOD__ . ': no more assurers that haven\'t '.
                'already assured this account');
        }
        
        return $row['assurer'];
    }
    
    /**
     * Fix the assurer flag for the given user
     * 
     * @param $user_id ID of the user
     */
    protected function fixAssurerFlag($user_id)
    {
    	// TODO: unset flag if requirements are not met
    	
        $query = 'UPDATE `users` SET `assurer` = 1 WHERE `users`.`id` = :user AND '.
            
            'EXISTS(SELECT * FROM `cats_passed` AS `cp`, `cats_variant` AS `cv` '.
            'WHERE `cp`.`variant_id` = `cv`.`id` AND `cv`.`type_id` = 1 AND '.
            '`cp`.`user_id` = :user) AND '.
            
            '(SELECT SUM(`points`) FROM `notary` WHERE `to` = :user AND '.
            '`expire` < now()) >= 100';
        $query_params['user'] = $user_id;
        $this->db->query($query, $query_params);
    }
    
    protected function getAssuranceForm()
    {
        $form = new Zend_Form();
        $form->setAction('/manage-account/assurance')->setMethod('post');
        
        $quantity = new Zend_Form_Element_Text('quantity');
        $quantity->setRequired(true)
                ->setLabel(I18n::_('Number of Points'))
                ->addFilter(new Zend_Filter_Int())
                ->addValidator(new Zend_Validate_Between(0, 100));
        $form->addElement($quantity);
        
        $location = new Zend_Form_Element_Text('location');
        $location->setRequired(true)
                ->setLabel(I18n::_('Location'))
                ->setValue(I18n::_('CAcert Test Manager'))
                ->addValidator(new Zend_Validate_StringLength(1,255));
        $form->addElement($location);
        
        $date = new Zend_Form_Element_Text('date');
        $date->setRequired(true)
            ->setLabel(I18n::_('Date of Assurance'))
            ->setValue(date('Y-m-d H:i:s'))
            ->addValidator(new Zend_Validate_StringLength(1,255));
        $form->addElement($date);
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Assure Me'));
        $form->addElement($submit);
        
        return $form;
    }
    
    protected function getAdminIncreaseForm()
    {
        $form = new Zend_Form();
        $form->setAction('/manage-account/admin-increase')->setMethod('post');
        
        $quantity = new Zend_Form_Element_Text('quantity');
        $quantity->setRequired(true)
                ->setLabel(I18n::_('Number of Points'))
                ->addFilter(new Zend_Filter_Int())
                ->addValidator(new Zend_Validate_GreaterThan(0));
        $form->addElement($quantity);
        
        $fragment = new Zend_Form_Element_Checkbox('fragment');
        $fragment->setLabel(I18n::_('Split into 2-Point Fragments'))
                ->setChecked(true);
        $form->addElement($fragment);
        
        $unlimited = new Zend_Form_Element_Checkbox('unlimited');
        $unlimited->setLabel(I18n::_('Assign Points even if the Limit of 150 '.
                        'is exceeded'))
                ->setChecked(false);
        $form->addElement($unlimited);
        
        $location = new Zend_Form_Element_Text('location');
        $location->setRequired(true)
                ->setLabel(I18n::_('Location'))
                ->setValue(I18n::_('CAcert Test Manager'))
                ->addValidator(new Zend_Validate_StringLength(1,255));
        $form->addElement($location);
        
        $date = new Zend_Form_Element_Text('date');
        $date->setRequired(true)
            ->setLabel(I18n::_('Date of Increase'))
            ->setValue(date('Y-m-d H:i:s'))
            ->addValidator(new Zend_Validate_StringLength(1,255));
        $form->addElement($date);
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Give Me Points'));
        $form->addElement($submit);
        
        return $form;
    }
    
    protected function getAssurerChallengeForm()
    {
        $form = new Zend_Form();
        $form->setAction('/manage-account/assurer-challenge')
            ->setMethod('post');
        
        $variant = new Zend_Form_Element_Select('variant');
        $variant->setLabel(I18n::_('Variant'));
        // Get the available variants from the database
        $query = 'select `id`, `test_text` from `cats_variant`
            where `type_id` = 1';
        $options = $this->db->fetchPairs($query);
        $variant->setOptions($options)
            ->setRequired(true);
        $form->addElement($variant);
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Challenge Me'));
        $form->addElement($submit);
        
        return $form;
    }
}
