<?php
/**
 * @author Michael Tänzer
 */

class AddPointsController extends Zend_Controller_Action
{
    public function init()
    {
        /* Initialize action controller here */
    }
    
    public function indexAction()
    {
        $this->view->assurance_form = $this->getAssuranceForm();
        $this->render('index');
    }
    
    public function assuranceAction()
    {
        /* Validate form */
        if (!$this->getRequest()->isPost()) {
            return $this->_forward('index');
        }
        
        $form = $this->getAssuranceForm();
        if (!$form->isValid($_POST)) {
            $this->view->assurance_form = $form;
            return $this->render('index');
        }
        
        
        /* Form is valid -> get values and process them */
        $values = $form->getValues();
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
        
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel(I18n::_('Assure Me'));
        $form->addElement($submit);
        
        return $form;
    }
}
