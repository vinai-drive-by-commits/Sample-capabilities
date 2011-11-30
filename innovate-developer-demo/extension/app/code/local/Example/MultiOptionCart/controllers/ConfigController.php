<?php

// Include the parent class manually because controller class names do not map to file names
require_once 'Mage/Catalog/controllers/ProductController.php';

class Example_MultiOptionCart_ConfigController extends Mage_Catalog_ProductController
{
	/**
	 * Set the config option to enable the product option layout switch in the layout xml.
	 * Then use the view action method of the parent class
	 *
	 * @return void
	 */
	public function viewAction()
	{
		Mage::app()->getStore()->setConfig('example_multioption/config/enabled', 1);
		
		return parent::viewAction();
	}
}
