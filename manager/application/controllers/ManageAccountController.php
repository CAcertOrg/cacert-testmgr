<?php
/**
 * @author Michael Tänzer
 */

class ManageAccountController extends Zend_Controller_Action
{
    const MAX_POINTS_PER_ASSURANCE = 35;
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
        $actions['flags'] = I18n::_('Set Flags');
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
        
        // Get the current user
        $user = Default_Model_User::findCurrentUser();
        
        $this->view->assurancesDone = array();
        $quantity = $values['quantity'];
        do {
            // split up into multiple assurances
            if ($quantity > self::MAX_POINTS_PER_ASSURANCE) {
                $points = self::MAX_POINTS_PER_ASSURANCE;
                $quantity -= self::MAX_POINTS_PER_ASSURANCE;
            } else {
                $points = $quantity;
                $quantity = 0;
            }
            
            // Get the assurer for this assurance
            $issued = $user->findNewAssurer()
                ->assure($user, $points, $values['location'], $values['date']);
            
            $this->view->assurancesDone[] = $issued;
        } while ($quantity > 0);
        
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
        
        // Get current user
        $user = Default_Model_User::findCurrentUser();
        
        $this->view->adminIncreasesDone = array();
        $points = $values['points'];
        
        // Only assign points within the limit if unlimited flag is not set
        if ($values['unlimited'] != '1') {
            if ($user->getPoints() >= self::MAX_POINTS_TOTAL) {
                // No more administrative increases should be done
                return;
            } elseif ($user->getPoints() + $points > self::MAX_POINTS_TOTAL) {
                $points = self::MAX_POINTS_TOTAL - $user->getPoints();
            }
        }
        
        $user->adminIncrease($points, $values['location'], $values['date']);
        $this->view->adminIncreasesDone[] = $points;
    
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
        $user = Default_Model_User::findCurrentUser();
        
        $user->assignChallenge(1, $values['variant']);
    }
    
    public function flagsAction()
    {
        // Get user data
        $user['id'] = $this->getUserId();
        
        // Validate form
        $form = $this->getFlagsForm($user['id']);
        $this->view->flags_form = $form;
        if (!$this->getRequest()->isPost() || !$form->isValid($_POST)) {
            return;
        }
        
        $flags = array('admin', 'codesign', 'orgadmin', 'ttpadmin', 'board',
            'locadmin', 'locked', 'assurer_blocked');
        $update = array(); // Make sure array is empty
        foreach ($flags as $flag) {
            if ($form->getElement($flag)->isChecked()) {
                $update[$flag] = 1;
            } else {
                $update[$flag] = 0;
            }
        }
        $this->db->update('users', $update, '`id` = '.$user['id']);
        
        return;
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
        
        $points = new Zend_Form_Element_Text('points');
        $points->setRequired(true)
                ->setLabel(I18n::_('Number of Points'))
                ->addFilter(new Zend_Filter_Int())
                ->addValidator(new Zend_Validate_GreaterThan(0));
        $form->addElement($points);
        
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
        $options =
            Default_Model_User::getAvailableChallengeVariants($this->db, 1);
        $variant->setMultiOptions($options)
            ->setRequired(true);
        $form->addElement($variant);
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Challenge Me'));
        $form->addElement($submit);
        
        return $form;
    }
    
    protected function getFlagsForm($user_id)
    {
        $form = new Zend_Form();
        $form->setAction('/manage-account/flags')
            ->setMethod('post');
        
        // Get the current setting of the flags
        $query = 'select `admin`, `codesign`, `orgadmin`, `ttpadmin`, `board`,
            `tverify`, `locadmin`, `locked`, `assurer_blocked` from `users`
            where `id` = :user';
        $query_params['user'] = $user_id;
        $result = $this->db->query($query, $query_params);
        if ($result->rowCount() !== 1) {
            throw new Exception(__METHOD__ . ': user ID not found in the data base');
        }
        $row = $result->fetch();
        
        // Add a checkbox for each flag
        $labels = array();
        $labels['admin']           = I18n::_('Support Engineer');
        $labels['codesign']        = I18n::_('Code Signing');
        $labels['orgadmin']        = I18n::_('Organisation Admin');
        $labels['ttpadmin']        = I18n::_('TTP Admin');
        $labels['board']           = I18n::_('Board Member');
        $labels['locadmin']        = I18n::_('Location Admin');
        $labels['locked']          = I18n::_('Lock Account');
        $labels['assurer_blocked'] = I18n::_('Block Assurer');
        
        foreach ($labels as $flag => $label) {
            $checkbox = new Zend_Form_Element_Checkbox($flag);
            $checkbox->setLabel($label)
                ->setChecked($row[$flag] === '1');
            $form->addElement($checkbox);
        }
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Save Flags'));
        $form->addElement($submit);
        
        return $form;
    }
}
