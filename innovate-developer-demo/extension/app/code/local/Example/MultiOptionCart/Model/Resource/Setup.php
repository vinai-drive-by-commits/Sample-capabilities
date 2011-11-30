<?php
 
class Example_MultiOptionCart_Model_Resource_Setup extends Mage_Catalog_Model_Resource_Setup
{
	/**
	 * @var Mage_Tax_Model_Class
	 */
	protected $_taxClass;

	/**
	 * Return the sort order array used to create option attributes from the passed values array
	 *
	 * Flip the option key and values and prefix the values with a string so they don't
	 * qualify as an integer (e.g. "28 mm")
	 *
	 * @param array $optionValues
	 * @return array
	 * @see self::getOptionValueArray()
	 */
	public function getOptionOrderArray(array $optionValues)
	{
		$order = array();
		foreach ($optionValues as $k => $v)
		{
			// Make sure the new key doesn't qualify as an integer
			$order['NO INT' . $v] = $k;
		}
		return $order;
	}

	/**
	 * Return the option value array used to create option attributes from the passed values array
	 * Prefix the key with a string so they don't qualify as an integer (e.g. "28 mm")
	 *
	 * @param array $optionValues
	 * @return array
	 * @see self::getOptionOrderArray()
	 */
	public function getOptionValueArray(array $optionValues)
	{
		$arr = array();
		$adminStoreId = Mage::app()->getStore(Mage_Core_Model_Store::ADMIN_CODE)->getId();
		foreach ($optionValues as $option)
		{
			// Make sure the key doesn't qualify as an integer
			$arr['NO INT' . $option] = array($adminStoreId => $option);
		}
		return $arr;
	}

	/**
	 * Return the ids of all first level categories from all websites
	 *
	 * @return array
	 */
	public function getProductCategoryIds()
	{
		$categoryIds = array();
		/* @var $category Mage_Catalog_Model_Category */
		$category = Mage::getModel('catalog/category');;
		/* @var $website Mage_Core_Model_Website */
		foreach (Mage::app()->getWebsites() as $website)
		{
			$storeRootCategoryId = $website->getDefaultGroup()->getRootCategoryId();
			$category->clearInstance()->load($storeRootCategoryId);
			$firstLevelCategoryIds = explode(',', $category->getChildren());
			$categoryIds = array_merge($categoryIds, $firstLevelCategoryIds);
		}
		return array_unique($categoryIds);
	}

	/**
	 * Return the product tax class model
	 *
	 * @return Mage_Tax_Model_Class
	 */
	public function getProductTaxClass()
	{
		if (is_null($this->_taxClass))
		{
			/* @var $collection Mage_Tax_Model_Resource_Class_Collection */
			$collection = Mage::getResourceModel('tax/class_collection')->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)->load();
			$this->_taxClass = $collection->getFirstItem();
		}
		return $this->_taxClass;
	}

	/**
	 * Return the ID of the first product tax class
	 *
	 * @return int
	 * @throwException Mage_Core_Exception
	 */
	public function getProductTaxClassId()
	{
		$id = $this->getProductTaxClass()->getId();
		if (! $id)
		{
			Mage::throwException('No product tax class found!');
		}
		return $id;
	}

	/**
	 * Return the skeleton array to specify the attributes while creating
	 * configurable products.
	 * 
	 * @param array $attributes
	 * @return array
	 */
	public function initConfigAttributeData(array $attributes)
	{
		$attributeData = array();
		foreach ($attributes as $attribute)
		{
			$attributeData[$attribute->getAttributeCode()] = array(
				'attribute_id' => $attribute->getId(),
				'label' => $attribute->getFrontendLabel(),
				'default_label' => $attribute->getFrontendLabel(),
				'store_label' => $attribute->getFrontendLabel(),
				'use_default' => true,
				'values' => array(),
			);
		}
		return $attributeData;
	}
}
