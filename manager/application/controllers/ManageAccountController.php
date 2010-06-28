<?php
/**
 * @author Michael Tänzer
 */

class ManageAccountController extends Zend_Controller_Action
{
    const MAX_POINTS_PER_ASSURANCE = 35;
    const MAX_ASSURANCE_POINTS = 100;
    
    protected $db;
    
    public function init()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini',
            APPLICATION_ENV);

        $this->db = Zend_Db::factory($config->ca_mgr->db->auth->pdo,
    	    $config->ca_mgr->db->auth);
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
        
        
        // Check identity of the user
        $session = Zend_Registry::get('session');
        if ($session->authdata['authed'] !== true) {
            throw new Exception(__METHOD__ . ': you need to log in to use this feature');
        }
        $query = 'select `id` from `users` where `id` = :user';
        $query_params['user'] = $session->authdata['authed_id'];
        $result = $this->db->query($query, $query_params);
        if ($result->rowCount() !== 1) {
            throw new Exception(__METHOD__ . ': user ID not found in the data base');
        }
        $row = $result->fetch();
        $user['id'] = $row['id'];
        
        
        // Get current points of the user
        $query = 'select sum(`points`) as `total` from `notary` where `to` = :user';
        $query_params['user'] = $user['id'];
        $row = $this->db->query($query, $query_params)->fetch();
        if ($row['total'] === NULL) $row['total'] = 0;
        $user['points'] = $row['total'];
        
        
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
        
        
        // Fix the assurer flag
        $query = 'UPDATE `users` SET `assurer` = 1 WHERE `users`.`id` = :user AND '.
            
            'EXISTS(SELECT * FROM `cats_passed` AS `cp`, `cats_variant` AS `cv` '.
            'WHERE `cp`.`variant_id` = `cv`.`id` AND `cv`.`type_id` = 1 AND '.
            '`cp`.`user_id` = :user) AND '.
            
		    '(SELECT SUM(`points`) FROM `notary` WHERE `to` = :user AND '.
		    '`expire` < now()) >= 100';
        $query_params['user'] = $user['id'];
        $this->db->query($query, $query_params);
        
        return;
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
    }
}
