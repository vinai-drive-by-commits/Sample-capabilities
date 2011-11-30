<?php

// Include the parent class manually because controller class names do not map to file names
require_once 'Mage/Checkout/controllers/CartController.php';

class Example_MultiOptionCart_CartController extends Mage_Checkout_CartController
{
	/**
	 * Add the specified configurations to the cart
	 *
	 * @return null
	 */
	public function addAction()
	{
		$cart = $this->_getCart();
		$protoProduct = $this->_initProduct();
		$options = $this->_getMultiOptionData();

		if (!$protoProduct || !$options)
		{
			$this->_goBack();
			return;
		}

		try
		{
			foreach ($options as $params)
			{
				$product = clone $protoProduct;
				$cart->addProduct($product, $params);
			}

			$cart->save();
			$this->_getSession()->setCartWasUpdated(true);

			if (!$cart->getQuote()->getHasError())
			{
				$message = $this->__('%s was added to your shopping cart.', Mage::helper('core')
							->escapeHtml($product->getName()));
				$this->_getSession()->addSuccess($message);
			}
		}
		catch (Exception $e)
		{
			$this->_getSession()->addException($e, $this->__('Error adding items to shopping cart.'));
			Mage::logException($e);
		}

		$this->_goBack();
	}

	/**
	 * Get selected options from the post data.
	 * Filter out options with no qty or without option id specifications.
	 *
	 * @return array
	 */
	protected function _getMultiOptionData()
	{
		$options = array();
		$param = $this->getRequest()->getParam('multioption', array());

		foreach ($param as $buyInfo)
		{
			if (!is_array($buyInfo))
			{
				continue;
			}
			if (!isset($buyInfo['qty']) || 0 >= intval($buyInfo['qty']))
			{
				continue;
			}
			if (!isset($buyInfo['super_attribute']) || !is_array($buyInfo['super_attribute']))
			{
				continue;
			}
			$options[] = $buyInfo;
		}

		return $options;
	}
}
