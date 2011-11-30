<?php
 
class Example_Brand_Block_Adminhtml_Brand_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
	/**
	 * Create the outer form wrapper
	 */
	protected function _construct()
	{
		parent::_construct();

		$this->_objectId = 'id';
		$this->_blockGroup = 'example_brand';
		$this->_controller = 'adminhtml_brand';
		$this->_mode = 'edit';
    }

	protected function  _prepareLayout()
	{
		$this->_updateButton('save', 'label', $this->__('Save Brand'));
		$this->_updateButton('delete', 'label', $this->__('Delete Brand'));

		$this->_addButton('save_and_continue', array(
				'label' => Mage::helper('adminhtml')->__('Save And Continue Edit'),
				'onclick' => 'saveAndContinueEdit()',
				'class' => 'save',
		), -100);

		$this->_formScripts[] = "
			function saveAndContinueEdit(){
				editForm.submit($('edit_form').action+'back/edit/');
			}
		";
		parent::_prepareLayout();
	}

	/**
	 * Return the title string to show above the form
	 *
	 * @return string
	 */
	public function getHeaderText()
	{
		$model = Mage::registry('current_brand');
		if ($model && $model->getId()) {
			return $this->__('Edit Brand %s', $this->htmlEscape($model->getName()));
		} else {
			return $this->__('New Brand');
		}
	}
}