<?php
 
class Example_Brand_Model_Resource_Brand_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	/**
	 * Define a bunch of strings to be used as custom collection flags
	 */
	const FLAG_LOAD_CATEGORY_URLS = '_example_brand_load_category_urls';
	const FLAG_LOAD_CATEGORIES = '_example_brand_load_categories';
	const FLAG_LOAD_CATEGORY_PRODUCT_COUNT = '_example_brand_load_category_product_count';
	const FLAG_LOAD_PRODUCT_COUNT = '_example_brand_load_product_count';
	const FLAG_FILTER_ASSOCIATED_WITH_CATEGORY = '_example_brand_associated_filter_with_category';
	const FLAG_FILTER_ASSOCIATED_WITH_PRODUCT = '_example_brand_associated_filter_with_product';
	const FLAG_FILTER_ASSOCIATED_WITH_SPECIAL_PRICE = '_example_brand_associated_filter_with_product_width_special_price';

	/**
	 * Flag to check if the group by entity table has been set on the select object
	 * 
	 * @var bool
	 */
	protected $_grouped = false;

	/**
	 * Initialize collection with model and resource model to use
	 * 
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('example_brand/brand');
	}

	/**
	 * Return the option an option array of brands
	 *
	 * @return array
	 */
	public function toOptionArray()
	{
		return $this->_toOptionArray('entity_id', 'name');
	}

	/**
	 * Return an option hash of brands
	 * 
	 * @return array
	 */
	public function toOptionHash()
	{
		return $this->_toOptionHash('entity_id', 'name');
	}

	/**
	 * Set post load flag to set associated categories on the brand models
	 *
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function addCategories()
	{
		$this->setFlag(self::FLAG_LOAD_CATEGORIES, true);
		return $this;
	}

	/**
	 * Set post load flag to set the url's of associated categories on the brand models.
	 * This implies calling addCategories().
	 *
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function addCategoryUrls()
	{
		$this->setFlag(self::FLAG_LOAD_CATEGORY_URLS, true);
		return $this;
	}

	/**
	 * Set flag to load the number of products on the associated category
	 * 
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function addCategoryProductCount()
	{
		$this->setFlag(self::FLAG_LOAD_CATEGORY_PRODUCT_COUNT, true);
		$this->addAssociatedWithCategoryFilter();
		return $this;
	}

	/**
	 * Load the number of products associated with each brand, regardless of the category association
	 *
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function addProductCount()
	{
		if (! $this->getFlag(self::FLAG_LOAD_PRODUCT_COUNT))
		{
			$this->setFlag(self::FLAG_LOAD_PRODUCT_COUNT, true);

			$entityType = Mage_Catalog_Model_Product::ENTITY;
			$productAttribute = Mage::getSingleton('eav/config')->getAttribute($entityType, 'brand_id');
			$countAlias = '_' . $entityType . '_count_tbl';

			$this->getSelect()->joinLeft(
				array($countAlias => $productAttribute->getBackendTable()),
				sprintf("main_table.entity_id={$countAlias}.value AND {$countAlias}.attribute_id=%d", $productAttribute->getId()),
				array('product_count' => new Zend_Db_Expr("COUNT({$countAlias}.entity_id)"))
			);
			$this->_groupByEntityId();
		}
		return $this;
	}

	/**
	 * Set a random order
	 *
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function setRandomOrder()
	{
		$this->getSelect()->order('RAND() ASC');
		return $this;
	}

	/**
	 * Filter out brands that do not have at least one associated category
	 * 
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function addAssociatedWithCategoryFilter()
	{
		if (! $this->getFlag(self::FLAG_FILTER_ASSOCIATED_WITH_CATEGORY))
		{
			$this->setFlag(self::FLAG_FILTER_ASSOCIATED_WITH_CATEGORY, true);
			$this->_addAssociateWithCatalogEntityFilter(Mage_Catalog_Model_Category::ENTITY);
		}
		return $this;
	}

	/**
	 * Filter out brands that do not have at least one associated product
	 *
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function addAssociatedWithProductFilter()
	{
		if (! $this->getFlag(self::FLAG_FILTER_ASSOCIATED_WITH_PRODUCT))
		{
			$this->setFlag(self::FLAG_FILTER_ASSOCIATED_WITH_PRODUCT, true);
			$this->_addAssociateWithCatalogEntityFilter(Mage_Catalog_Model_Product::ENTITY);
		}
		return $this;
	}

	/**
	 * Add the SQL filter to only allow brands that are associated with at least one product with a special price
	 *
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	public function addAssociateWithSpecialPriceProductFilter()
	{
		if (! $this->getFlag(self::FLAG_FILTER_ASSOCIATED_WITH_SPECIAL_PRICE))
		{
			$this->setFlag(self::FLAG_FILTER_ASSOCIATED_WITH_SPECIAL_PRICE, true);
			$this->addAssociatedWithProductFilter();

			$entityType = Mage_Catalog_Model_Product::ENTITY;
			$specialPriceAttribute = Mage::getSingleton('eav/config')->getAttribute($entityType, 'special_price');
			$specialPriceAlias = '_' . $entityType . '_special_tbl';
			$brandIdAlias = '_' . $entityType . '_brand_id_tbl';
			
			$this->getSelect()->joinInner(
				array($specialPriceAlias => $specialPriceAttribute->getBackendTable()),
				sprintf("{$brandIdAlias}.entity_id={$specialPriceAlias}.entity_id AND {$specialPriceAlias}.attribute_id=%d AND {$specialPriceAlias}.value IS NOT NULL", $specialPriceAttribute->getId()),
				''
			);
		}
		return $this;
	}

	/**
	 * Add the SQL filters to only allow brands associated with the specified entity type
	 *
	 * @param string $entityType
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	protected function _addAssociateWithCatalogEntityFilter($entityType)
	{
		$entityAttribute = Mage::getSingleton('eav/config')->getAttribute($entityType, 'brand_id');
		$alias = '_' . $entityType . '_brand_id_tbl';
		$this->getSelect()->joinInner(
			array($alias => $entityAttribute->getBackendTable()),
			sprintf("{$alias}.value=main_table.entity_id AND {$alias}.attribute_id=%d", $entityAttribute->getId()),
			''
		);
		$this->_groupByEntityId();

		return $this;
	}

	/**
	 * Group this collections SQL result by entity id
	 *
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	protected function _groupByEntityId()
	{
		if (! $this->_grouped)
		{
			$this->getSelect()->group("main_table.entity_id");
			$this->_grouped = true;
		}
		return $this;
	}

	/**
	 * @return Example_Brand_Model_Resource_Brand_Collection
	 */
	protected function _afterLoad()
	{
		if ($this->getFlag(self::FLAG_LOAD_CATEGORY_URLS) || $this->getFlag(self::FLAG_LOAD_CATEGORIES))
		{
			/* @var $categories Mage_Catalog_Model_Resource_Category_Collection */
			$categories = Mage::getResourceModel('catalog/category_collection')
				->addIsActiveFilter()
				->addAttributeToFilter('brand_id', array('in' => array_keys($this->getItems())));
			if ($this->getFlag(self::FLAG_LOAD_CATEGORY_URLS))
			{
				$categories->addUrlRewriteToResult();
			}
			if ($this->getFlag(self::FLAG_LOAD_CATEGORY_PRODUCT_COUNT))
			{
				$categories->setLoadProductCount(true);
			}
			
			foreach ($this->getItems() as $brand)
			{
				$brand->setCategory($categories->getItemByColumnValue('brand_id', $brand->getId()));
			}
		}
		return parent::_afterLoad();
	}
}
