<?php
 
class Example_Brand_Model_Brand extends Mage_Core_Model_Abstract
{
	/**
	 * The configuration xpath to the logo image directory setting
	 */
	const XML_PATH_LOGO_DIR = 'example_brand/config/logo_dir';

	/**
	 * The base URL path to the brand logo images
	 * 
	 * @var $_logoPath string
	 */
	protected $_logoPath;

	/**
	 * Initialize the resource model and the path to the logo directory
	 * 
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('example_brand/brand');
		$this->_logoPath = Mage::getBaseUrl('media') . Mage::getStoreConfig(self::XML_PATH_LOGO_DIR);
	}

	/**
	 * Return the URL to the brands logo file
	 * 
	 * @return string
	 */
	public function getImageUrl()
	{
		return $this->_logoPath . $this->getLogo();
	}

	/**
	 * Return the first category associated with this brand
	 * 
	 * @return Mage_Catalog_Model_Category
	 */
	public function getCategory()
	{
		$category = $this->getData('category');
		if (is_null($category))
		{
			/* @var $category Mage_Catalog_Model_Category */
			$category = Mage::getResourceModel('catalog/category_collection')
				->addAttributeToFilter('brand_id', $this->getId())
				->addUrlRewriteToResult()
				->getFirstItem();
			$this->setData('category', $category);
		}
		return $category;
	}

	/**
	 * Return the URL to the first category with a matching brand_id
	 *
	 * @return string
	 */
	public function getCategoryUrl()
	{
		$category = $this->getCategory();
		return $category ? $category->getUrl() : '';
	}

	/**
	 * Return the number of products in the first associated category
	 * 
	 * @return bool|int
	 */
	public function getCategoryProductCount()
	{
		$category = $this->getCategory();
		return $category ? $category->getProductCount() : false;
	}

	/**
	 * Return the number of products associated with the brand entity, regardless
	 * of product to category association
	 * 
	 * @return bool|int
	 */
	public function getProductCount()
	{
		$count = $this->getData('product_count');
		if (is_null($count))
		{
			$count = $this->getResource()->getProductCount($this);
			$this->setData('product_count', $count);
		}
		return $count;
	}
}
