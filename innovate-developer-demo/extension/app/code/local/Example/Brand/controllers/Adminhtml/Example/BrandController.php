<?php
 
class Example_Brand_Adminhtml_Example_BrandController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Redirect to list action because it's nicer
	 */
	public function indexAction()
	{
		/*
		 * Redirect user via 302 http redirect (the browser url changes)
		 */
		$this->_redirect('*/*/list');
	}

	/**
	 * Display grid
	 */
	public function listAction()
	{
		$this->_getSession()->setFormData(array());

		$this->_title($this->__('Catalog'))->_title($this->__('Brands'));

		$this->loadLayout();

		$this->_setActiveMenu('catalog/example_brand');

		$this->renderLayout();
	}

	/**
	 * Check admin ACL permissions
	 */
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('catalog/example_brand');
	}

    /**
     * Grid action for ajax requests
     */
    public function gridAction()
    {
		$this->loadLayout();
		$this->renderLayout();
    }

	/**
	 * Redirect to edit action, handle the differences between
	 * edit and new in the php code
	 */
	public function newAction()
	{
		/*
		 * Redirect the user via a magento internal redirect
		 */
		$this->_forward('edit');
	}

	/**
	 * Create or edit entity
	 */
	public function editAction()
	{
		$model = Mage::getModel('example_brand/brand');
		Mage::register('current_brand', $model);
		$id = $this->getRequest()->getParam('id');

		try {
			if ($id) {
				if (! $model->load($id)->getId()) {
					Mage::throwException($this->__('No record with ID "%s" found.', $id));
				}
			}

			/*
			 * Build the page title
			 */
			if ($model->getId()) {
				$pageTitle = $this->__('Edit Brand %s', $model->getName());
			} else {
				$pageTitle = $this->__('New Brand');
			}
			$this->_title($this->__('Catalog'))->_title($this->__('Brands'))->_title($pageTitle);

			$this->loadLayout();

			$this->_setActiveMenu('catalog/example_brand');

			$this->renderLayout();
		}
		catch (Exception $e) {
			Mage::logException($e);
			$this->_getSession()->addError($e->getMessage());
			$this->_redirect('*/*/list');
		}
	}

	/**
	 * Process form post
	 */
	public function saveAction()
	{
		if ($data = $this->getRequest()->getPost()) {
			$this->_getSession()->setFormData($data);
			$model = Mage::getModel('example_brand/brand');
			$id = $this->getRequest()->getParam('id');

			try {
				if ($id) $model->load($id);
				$model->addData($data);

				$model->save();

				if (! $model->getId()) {
					Mage::throwException($this->__('Error saving brand'));
				}

				$this->_getSession()->addSuccess($this->__('Brand was successfully saved'));
				$this->_getSession()->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$params = array('id' => $model->getId());
					$this->_redirect('*/*/edit', $params);
				} else {
					$this->_redirect('*/*/list');
				}
			} catch (Exception $e) {
				$this->_getSession()->addError($e->getMessage());
				if ($model && $model->getId()) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
				} else {
					$this->_redirect('*/*/new');
				}
			}

			return;
		}

		$this->_getSession()->addError($this->__('No data found to save'));
		$this->_redirect('*/*');
	}

	/**
	 * Delete entity
	 */
	public function deleteAction()
	{
		$model = Mage::getModel('example_brand/brand');
		$id = $this->getRequest()->getParam('id');

		try
		{
			if ($id)
			{
				if (! $model->load($id)->getId())
				{
					Mage::throwException($this->__('No record with ID "%s" found.', $id));
				}
				$name = $model->getName();
				$model->delete();
				$this->_getSession()->addSuccess($this->__('"%s" (ID %d) was successfully deleted', $name, $id));
				$this->_redirect('*/*');
			}
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			$this->_getSession()->addError($e->getMessage());
			$this->_redirect('*/*');
		}
	}
}
