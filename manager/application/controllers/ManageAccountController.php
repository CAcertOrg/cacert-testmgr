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
        $actions['batch-assurance'] = I18n::_('Batch Assurance');
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
    
    public function batchAssuranceAction() {
    	// Validate form
        $form = $this->getBatchAssuranceForm();
        if (!$this->getRequest()->isPost() || !$form->isValid($_POST)) {
            $this->view->batch_assurance_form = $form;
            return $this->render('batch-assurance-form');
        }
        
        // Form is valid -> get values for processing
        $values = $form->getValues();
        
        $user = Default_Model_User::findCurrentUser();
        
        $location = $values['location'];
        $date = $values['date'];
        
        $this->view->assurances = array();
        
        for ($i = 0; $i < intval($values['quantity']); $i++) {
            $assuree = $user->findNewAssuree();
            
            if ($values['percentage'] === 'percentage') {
                $points = ($user->maxpoints() * intval($values['points'])) /100;
            }elseif ($values['percentage'] === 'absolute') {
                $points = intval($values['points']);
            }
            
            $user->assure($assuree, $points, $location, $date);
            
            $this->view->assurances[] = array(
                    'assuree'=>$assuree->getPrimEmail(),
                    'points'=>$points,
                    'location'=>$location,
                    'date'=>$date);
        }
        
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
        $user = Default_Model_User::findCurrentUser();
        
        // Validate form
        $form = $this->getFlagsForm($user);
        $this->view->flags_form = $form;
        if (!$this->getRequest()->isPost() || !$form->isValid($_POST)) {
            return;
        }
        
        $flags = $user->getFlags();
        foreach ($flags as $flag => $value) {
            $element = $form->getElement($flag);
            if ($element !== null) {
                $flags[$flag] = $element->isChecked();
            }
        }
        
        $user->setFlags($flags);
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
    
    protected function getBatchAssuranceForm() {
    	$form = new Zend_Form();
        $form->setAction('/manage-account/batch-assurance')->setMethod('post');
        
        $quantity = new Zend_Form_Element_Text('quantity');
        $quantity->setRequired(true)
                ->setLabel(I18n::_('Number of Assurances'))
                ->setValue('25')
                ->addFilter(new Zend_Filter_Int())
                ->addValidator(new Zend_Validate_Between(0, 100));
        $form->addElement($quantity);
        
        $percentage = new Zend_Form_Element_Select('percentage');
        $percentage->setRequired(true)
                ->setLabel(I18n::_('Are the points specified absolute?'))
                ->setValue('percentage')
                ->setMultiOptions(array(
                	    'percentage' => I18n::_('Percentage'),
                        'absolute' => I18n::_('Absolute'),
                    ));
        $form->addElement($percentage);
        
        $points = new Zend_Form_Element_Text('points');
        $points->setRequired(true)
            ->setLabel(I18n::_('Points per Assurance'))
            ->setValue('100')
            ->addFilter(new Zend_Filter_Int())
            ->addValidator(new Zend_Validate_Between(0, 100));
        $form->addElement($points);
        
        $location = new Zend_Form_Element_Text('location');
        $location->setRequired(true)
                ->setLabel(I18n::_('Location'))
                ->setValue(I18n::_('CAcert Test Manager Batch Assurance'))
                ->addValidator(new Zend_Validate_StringLength(1,255));
        $form->addElement($location);
        
        $date = new Zend_Form_Element_Text('date');
        $date->setRequired(true)
            ->setLabel(I18n::_('Date of Assurance'))
            ->setValue(date('Y-m-d H:i:s'))
            ->addValidator(new Zend_Validate_StringLength(1,255));
        $form->addElement($date);
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Make Batch Assurance'));
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
    
    protected function getFlagsForm(Default_Model_User $user)
    {
        $form = new Zend_Form();
        $form->setAction('/manage-account/flags')
            ->setMethod('post');
        
        $flags = $user->getFlags();
        
        // Add a checkbox for each flag
        $labels = array();
        $labels['admin']           = I18n::_('Support Engineer');
        $labels['codesign']        = I18n::_('Code Signing');
        $labels['orgadmin']        = I18n::_('Organisation Admin');
        $labels['ttpadmin']        = I18n::_('TTP Admin');
        $labels['board']           = I18n::_('Board Member');
        $labels['locadmin']        = I18n::_('Location Admin');
        $labels['tverify']         = I18n::_('TVerify');
        $labels['locked']          = I18n::_('Lock Account');
        $labels['assurer_blocked'] = I18n::_('Block Assurer');
        
        foreach ($labels as $flag => $label) {
            $checkbox = new Zend_Form_Element_Checkbox($flag);
            $checkbox->setLabel($label)
                ->setChecked($flags[$flag]);
            $form->addElement($checkbox);
        }
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Save Flags'));
        $form->addElement($submit);
        
        return $form;
    }
}
