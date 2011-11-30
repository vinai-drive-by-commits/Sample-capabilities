<?php

class Example_Brand_Block_List extends Mage_Core_Block_Template
{
	/**
	 * Return the brand collection for use in the template
	 * 
	 * @return Example_Brand_Model_Resource_Brand_Collection|mixed|Object
	 */
	public function getBrands()
	{
		$brands = $this->getData('brands');
		if (is_null($brands))
		{
			/* @var $brands Example_Brand_Model_Resource_Brand_Collection */
			$brands = Mage::getResourceModel('example_brand/brand_collection');
			$this->_prepareBrandCollection($brands, 50);
			$this->setBrands($brands);
		}
		return $brands;
	}

	/**
	 * Add all filters and options to the brand collection.
	 * 
	 * @param Example_Brand_Model_Resource_Brand_Collection $brands
	 * @param int $limit
	 * @return Example_Brand_Block_List
	 */
	protected function _prepareBrandCollection(Example_Brand_Model_Resource_Brand_Collection $brands, $limit)
	{
		$brands->addCategories()
			->addAssociatedWithCategoryFilter()
			->addAssociateWithSpecialPriceProductFilter()
			->addCategoryProductCount()
			->setRandomOrder()
			->setPageSize($limit);

		return $this;
	}
}
