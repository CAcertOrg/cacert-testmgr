<?php
/**
 * @author markus
 * $Id: IndexController.php 6 2009-11-18 14:52:50Z markus $
 */

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    	/**
    	 * get bootstrap, get resource from bootstrap
    	 * resources are created when an bootstrap _init method returns an object
    	$bootstrap = $this->getInvokeArg('bootstrap');
        $view = $bootstrap->getResource('view');
        */
    }

    public function indexAction()
    {
        // action body
    }


}

