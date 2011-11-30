<?php
 
class Example_Brand_Block_Adminhtml_Brand_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
	/**
	 * Prepare the inner form wrapper
	 */
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form(array(
				'id' => 'edit_form',
				'action' => $this->getUrl('*/*/save',
						array('id' => $this->getRequest()->getParam('id'))),
				'method' => 'post',
				'enctype' => 'multipart/form-data',
		));
		
		$form->setUseContainer(true);

		$fieldset = $form->addFieldset('general_form', array(
			'legend' => $this->__('General Setup')
		));

		if (Mage::registry('current_brand')->getId()) {
			$fieldset->addField('entity_id', 'label', array(
				'label' => $this->__('Entity ID')
			));
		}

		$fieldset->addField('name', 'text', array(
			'label'     => $this->__('Name'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'name',
		));

		$fieldset->addField('logo', 'text', array(
			'label'     => $this->__('Brand Image'),
			'class'     => 'required-entry',
			'required'  => true,
			'name'      => 'logo',
			'note'      => $this->__('Logo File Name in %s', Mage::getBaseUrl('media') . Mage::getStoreConfig('example_brand/config/logo_dir')),
		));

		$form->addValues($this->_getFormData());

		$this->setForm($form);

		return parent::_prepareForm();
	}

	/**
	 *
	 * @return array
	 */
	protected function _getFormData()
	{
		$data = Mage::getSingleton('adminhtml/session')->getFormData();

		if (! $data && Mage::registry('current_brand')->getData()) {
			$data = Mage::registry('current_brand')->getData();
		}

		return (array) $data;
	}
}
