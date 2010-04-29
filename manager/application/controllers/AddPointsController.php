<?php
/**
 * @author Michael Tänzer
 */

class AddPointsController extends Zend_Controller_Action
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
        $this->view->assurance_form = $this->getAssuranceForm();
        $this->render('index');
    }
    
    public function assuranceAction()
    {
        // Validate form
        if (!$this->getRequest()->isPost()) {
            return $this->_forward('index');
        }
        
        $form = $this->getAssuranceForm();
        if (!$form->isValid($_POST)) {
            $this->view->assurance_form = $form;
            return $this->render('index');
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
        
        
        // Get the first assurer who didn't already assure the user 
        $query = 'select min(`id`) as `assurer` from `users` ' .
        	'where `email` like \'john.doe-___@example.com\' and ' .
            '`id` not in (select `from` from `notary` where `to` = :user)';
        $query_params['user'] = $user['id'];
        $row = $this->db->query($query, $query_params)->fetch();
        if ($row['assurer'] === NULL) {
            throw new Exception(__METHOD__ . ': no more assurers that haven\'t '.
                'already assured this account');
        }
        $assurer = $row['assurer'];
        
        
        // Get current points of the user
        $query = 'select sum(`points`) as `total` from `notary` where `to` = :user';
        $query_params['user'] = $user['id'];
        $row = $this->db->query($query, $query_params)->fetch();
        if ($row['total'] === NULL) $row['total'] = 0;
        $user['points'] = $row['total'];
        
        
        // Do the actual assurances
        $assurance = array(); // Make sure the array is empty
        $assurance['from'] = $assurer;
        $assurance['to'] = $user['id'];
        $assurance['location'] = $values['location'];
        $assurance['date'] = $values['date'];
        $assurance['when'] = new Zend_Db_Expr('now()');
        $this->view->assurancesDone = array();
        
        $points = $values['quantity'];
        do {
            // split up into multiple assurances
            if ($points > MAX_POINTS_PER_ASSURANCE) {
                $assurance['awarded'] = MAX_POINTS_PER_ASSURANCE;
                $points -= MAX_POINTS_PER_ASSURANCE;
            } else {
                $assurance['awarded'] = $points;
                $points = 0;
            }
            
            // only assign points whithin the limit
            if ($user['points'] + $assurance['awarded'] > MAX_ASSURANCE_POINTS){
                $assurance['points'] = MAX_ASSURANCE_POINTS - $user['points'];
            } else {
                $assurance['points'] = $assurance['awarded'];
            }
            
            $this->db->insert('notary', $assurance);
            
            $user['points'] += $assurance['points'];
            $this->view->assurancesDone[] = $assurance['points'];
        } while ($points > 0);
        
        
        // Fix the assurer flag
        $where = array();
        $query = '`users`.`id` = :user';
        $query_params['user'] = $user['id'];
        $where[] = $this->db->quoteInto($query, $query_params);
        $query = 'exists(select * from `cats_passed` as `cp`, ' .
        	'`cats_variant` as `cv` where `cp`.`variant_id` = `cv`.`id` and ' .
        	'`cv`.`type_id` = 1 and `cp`.`user_id` = :user';
        $where[] = $this->db->quoteInto($query, $query_params);
        $query = '(select sum(`points`) from `notary` where `to`= :user and ' .
        	'`expire` > now()) >= 100';
        $where[] = $this->db->quoteInto($query, $query_params);
        $this->db->update('users', array('assurer' => 1), $where);
        
        return;
    }
    
    protected function getAssuranceForm()
    {
        $form = new Zend_Form();
        $form->setAction('/add-points/assurance')->setMethod('post');
        
        $quantity = new Zend_Form_Element_Text('quantity');
        $quantity->setRequired(true)
                ->setLabel(I18n::_('Number of Points'))
                ->addFilter(new Zend_Filter_Int())
                ->addValidator(new Zend_Validate_Between(0, 100));
        $form->addElement($quantity);
        
        $location = new Zend_Form_Element_Text('location');
        $location->setRequired(true)
                ->setLabel(I18n::_('Location'))
                ->setValue(I18n::_('CACert Test Manager'))
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
}
