<?php
class Xcommerce_Cse_AdminController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
           {
       		$this->loadLayout()
       			->_addContent($this->getLayout()->createBlock('cse/listProducts'))
       			->renderLayout();
           }
	
}