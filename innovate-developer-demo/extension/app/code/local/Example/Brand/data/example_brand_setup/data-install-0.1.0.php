<?php

 /* @var $installer Example_Brand_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

// Insert brand entity records
$installer->getConnection()->insertMultiple(
	$installer->getTable('example_brand/brand'), $installer->getBrands()
);

$currentStore = Mage::app()->getStore();
Mage::app()->setCurrentStore('admin');

// Product model to use for creating the new products
/* @var $product Mage_Catalog_Model_Product */
$product = Mage::getModel('catalog/product');

/* @var $indexer Mage_Index_Model_Indexer */
$indexer = Mage::getSingleton('index/indexer');

// Create brand categories
$brandRootCategoryName = 'Brands';
$brandCategoryIds = $specialPriceBrandCategoryIds = array();
/* @var $brandCollection Example_Brand_Model_Resource_Brand_Collection */
$brandCollection = Mage::getResourceModel('example_brand/brand_collection');
$brandCount = $brandCollection->count();

// Avoid all the ugly exceptions from Varien_File_Uploader in the exception.log
if (! isset($_FILES) || empty($_FILES))
{
	$_FILES = array('thumbnail' => array('tmp_name' => null), 'image' => array('tmp_name' => null));
}

// Set indexer mode to manual to speed up product creation a bit
$currentIndexerModes = array();
foreach ($indexer->getProcessesCollection() as $process)
{
	$currentIndexerModes[$process->getIndexerCode()] = $process->getMode();
	$process->setMode(Mage_Index_Model_Process::MODE_MANUAL)->save();
}

// Create CMS brand list block
/* @var $listBlock Mage_Cms_Model_Block */
$listBlock = Mage::getModel('cms/block')->load('example_brand_list');
if (! $listBlock->getId())
{
	$listBlock->setContent('{{block type="example_brand/list" template="example/brand/listbrands.phtml"}}')
		->setIdentifier('example_brand_list')
		->setTitle('Example Brand List')
		->setIsActive(1)
		->setStores(array(Mage_Core_Model_App::ADMIN_STORE_ID))
		->save();
}

Mage::log('Finished base setup, now beginning to create categories');

foreach (Mage::app()->getWebsites() as $website)
{
	/* @var $website Mage_Core_Model_Website */
	/* @var $rootCategory Mage_Catalog_Model_Category */
	$rootCategoryId = $website->getDefaultGroup()->getRootCategoryId();
	$rootCategory = Mage::getModel('catalog/category')->load($rootCategoryId);
	$brandsRootCategory = null;

	foreach ($rootCategory->getChildrenCategories() as $category)
	{
		if ($category->getName() === $brandRootCategoryName)
		{
			$brandsRootCategory = $category;
			break;
		}
	}
	// The brands category branch seems to exist on this category tree
	if (! $brandsRootCategory)
	{
		// Create a root "Brands" category displaying the cms brand list block
		$brandsRootCategory = Mage::getModel('catalog/category')->setData(array(
				'name' => $brandRootCategoryName,
				'path' => $rootCategory->getPath(),
				'include_in_menu' => 1,
				'is_active' => 1,
				'is_anchor' => 1,
				'page_layout' => 'two_columns_right',
				'custom_use_parent_settings' => 0,
				'custom_apply_to_products' => 0,
				'display_mode' => Mage_Catalog_Model_Category::DM_PAGE,
				'landing_page' => $listBlock->getId(),
		))
		->save();
		Mage::log(sprintf('Created category %s in website %s', $brandsRootCategory->getName(), $website->getName()));

		// Create alphabetic grouping categories
		$groupingMap = $installer->getAlphabetGrouping();
		foreach ($installer->getAlphabetGrouping(true) as $group)
		{
			$groupCategory = Mage::getModel('catalog/category')->setData(array(
				'name' => $group,
				'path' => $brandsRootCategory->getPath(),
				'include_in_menu' => 1,
				'is_active' => 1,
				'is_anchor' => 1,
				'custom_use_parent_settings' => 0,
				'custom_apply_to_products' => 0,
				'display_mode' => Mage_Catalog_Model_Category::DM_PRODUCT,
			))
			->save();
			Mage::log(sprintf('Created category %s in website %s', $groupCategory->getName(), $website->getName()));

			// Store the group category in the map so it can be referenced later when the
			// individual brand categories are created
			foreach ($groupingMap as $chr => $groupName)
			{
				if ($groupName === $group)
				{
					$groupingMap[$chr] = $groupCategory;
				}
			}
		}

		// Create special price branch
		$dealsCategory = Mage::getModel('catalog/category')->setData(array(
			'name' => 'Special Deals',
			'path' => $brandsRootCategory->getPath(),
			'include_in_menu' => 1,
			'is_active' => 1,
			'is_anchor' => 1,
			'custom_use_parent_settings' => 0,
			'custom_apply_to_products' => 0,
			'display_mode' => Mage_Catalog_Model_Category::DM_PRODUCT,
		))
		->save();
		Mage::log(sprintf('Created category %s in website %s', $dealsCategory->getName(), $website->getName()));

		// Create the deals category in the brands root category
		$specialPriceGroupingMap = $installer->getAlphabetGrouping();
		foreach ($installer->getAlphabetGrouping(true) as $group)
		{
			$groupCategory = Mage::getModel('catalog/category')->setData(array(
				'name' => $group,
				'path' => $dealsCategory->getPath(),
				'include_in_menu' => 1,
				'is_active' => 1,
				'is_anchor' => 1,
				'custom_use_parent_settings' => 0,
				'custom_apply_to_products' => 0,
				'display_mode' => Mage_Catalog_Model_Category::DM_PRODUCT,
			))
			->save();
			Mage::log(sprintf('Created category %s in website %s', $groupCategory->getName(), $website->getName()));

			// Store the special price group category in the map so it can be referenced later when the
			// individual brand categories are created
			foreach ($specialPriceGroupingMap as $chr => $groupName)
			{
				if ($groupName === $group)
				{
					$specialPriceGroupingMap[$chr] = $groupCategory;
				}
			}
		}

		// Create the individual brand categories
		$counter = 0;
		foreach ($brandCollection as $brand)
		{
			/* @var $brand Example_Brand_Model_Brand */
			$name = $brand->getName();
			foreach (array(
				'Regular Group' => $groupingMap,
				'Special Price Group' => $specialPriceGroupingMap
			) as $title => $groupMap)
			{
				$parent = array_key_exists($name{0}, $groupMap) ? $groupMap[$name{0}] : false;
				if (! $parent)
				{
					// Just to be on the safe side
					continue;
				}
				if ('Special Price Group' === $title)
				{
					$name .= ' Deals';
				}

				$brandCategory = Mage::getModel('catalog/category')->setData(array(
					'name' => $name,
					'path' => $parent->getPath(),
					'attribute_set_id' => $rootCategory->getDefaultAttributeSetId(),
					'include_in_menu' => 1,
					'is_active' => 1,
					'is_anchor' => 0,
					'custom_use_parent_settings' => 0,
					'custom_apply_to_products' => 0,
					'display_mode' => Mage_Catalog_Model_Category::DM_PRODUCT,
					'brand_id' => $brand->getId(),
				))
				->save();
				Mage::log(sprintf('Created brand category %03d/%03d %s in website %s',
						++$counter, $brandCount*2, $brandCategory->getName(), $website->getName()));

				// Store brand category ids for product creation
				if ('Regular Group' === $title)
				{
					$brandCategoryIds[$brand->getId()][] = $brandCategory->getId();
				}
				else
				{
					$specialPriceBrandCategoryIds[$brand->getId()][] = $brandCategory->getId();
				}
			}

		}
	}
}

// Create brand products
$counter = 0;
foreach ($brandCollection as $brand)
{
	// Just to be on the safe side...
	if (array_key_exists($brand->getId(), $brandCategoryIds))
	{
		// Create a random number of products for this brand
		// Some brands might have no products associated with them on purpose
		$numProducts = mt_rand(0, 25);
		Mage::log(sprintf('Creating %d products for brand %s (%03d/%03d)', $numProducts, $brand->getName(), ++$counter, $brandCount));
		for ($i = 1; $i <= $numProducts; $i++)
		{
			// Create simple sample products associated with the brand
			$product->clearInstance()->setData(array(
				'name' => sprintf('%s Product %d', $brand->getName(), $i),
				'attribute_set_id' => $product->getDefaultAttributeSetId(),
				'price' => 100,
				'sku' => sprintf('sample-%d-%d', $brand->getId(), $i),
				'status' => true,
				'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
				'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
				'website_ids' => $installer->getProductWebsiteIds(),
				'stock_data' => array(
					'qty' => 100,
					'is_in_stock' => 1,
					'min_sale_qty' => 1,
					'use_config_min_sale_qty' => 0,
				),
				'tax_class_id' => $installer->getProductTaxClassId(),
				'category_ids' => implode(',', $brandCategoryIds[$brand->getId()]),
				'brand_id' => $brand->getId(),
			));
			// Assign about a third of the products a special price
			if (mt_rand(0, 2) % 2)
			{
				$product->setSpecialPrice(98.99);
				$product->setCategoryIds(array_merge($product->getCategoryIds(), $specialPriceBrandCategoryIds[$brand->getId()]));
			}
			$product->save();
			Mage::log(sprintf('%02d/%02d Created brand product %s', $i, $numProducts, $product->getName()));
		}
	}
}
// Revert indexed process modes
foreach ($indexer->getProcessesCollection() as $process)
{
	$process->setMode($currentIndexerModes[$process->getIndexerCode()])
			->setStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX)
			->save();
}

// The config cache needs to be marked as invalid so the cached brand root category id is read
Mage::app()->getCacheInstance()->invalidateType(array('config'));

// Revert current store
Mage::app()->setCurrentStore($currentStore->getCode());

Mage::log(sprintf('Done setting up %s %s', strval($installer->getResourceConfig()->setup->module), basename(__FILE__)));

$installer->endSetup();