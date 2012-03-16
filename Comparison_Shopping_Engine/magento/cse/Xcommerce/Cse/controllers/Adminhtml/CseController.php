<?php

class Xcommerce_Cse_Adminhtml_CseController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
	{
		$this->loadLayout()
			->_addContent($this->getLayout()->createBlock('cse/listProducts'))
			->renderLayout();
	}

	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('xcommerce/cse');
	}
}