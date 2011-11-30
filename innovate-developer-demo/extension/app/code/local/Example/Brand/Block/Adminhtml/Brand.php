<?php
 
class Example_Brand_Block_Adminhtml_Brand extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	protected function _construct()
	{
		$this->_blockGroup = 'example_brand';
		$this->_controller = 'adminhtml_brand';
		$this->_headerText = $this->__('List Brands');

		parent::_construct();
	}

}
