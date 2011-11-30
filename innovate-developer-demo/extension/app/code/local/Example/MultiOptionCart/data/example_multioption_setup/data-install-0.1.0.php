<?php

/* @var $installer Example_MultiOptionCart_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

Mage::log(sprintf('Starting setup script %s', basename(__FILE__)));

$currentStore = Mage::app()->getStore();
Mage::app()->setCurrentStore('admin');

/* @var $indexer Mage_Index_Model_Indexer */
$indexer = Mage::getSingleton('index/indexer');

// Create product attributes with these options
// Also use these to create all options for the configurable test product
$widthValues = array('18 mm', '20 mm', '22 mm', '24 mm', '26 mm', '28 mm', '30 mm', '35 mm', '40 mm', '45 mm');
$diameterValues = array('27 mm', '30 mm', '32 mm', '34 mm', '36 mm', '40 mm', '45 mm', '65 mm', '80 mm', '90 mm', '100 mm', '115 mm', '130 mm', '150 mm', '170 mm', '195 mm');
$manufacturerValues = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');

$attributes = array(
	'width' => array(
		'label' => 'Width',
		'type' => 'int',
		'input' => 'select',
		'source' => 'eav/entity_attribute_source_table',
		'required' => 0,
		'user_defined' => 1,
		'group' => 'General',
		'filterable' => 1,
		'is_filterable_in_search' => 1,
		'option' => array(
			'order' => $installer->getOptionOrderArray($widthValues),
			'value' => $installer->getOptionValueArray($widthValues),
		)
	),
	'diameter' => array(
		'label' => 'Diameter',
		'type' => 'int',
		'input' => 'select',
		'source' => 'eav/entity_attribute_source_table',
		'required' => 0,
		'user_defined' => 1,
		'group' => 'General',
		'filterable' => 1,
		'is_filterable_in_search' => 1,
		'option' => array(
			'order' => $installer->getOptionOrderArray($diameterValues),
			'value' => $installer->getOptionValueArray($diameterValues),
		)
	),
	'manufacturer' => array(
		'label' => 'Manufacturer',
		'type' => 'int',
		'input' => 'select',
		'source' => 'eav/entity_attribute_source_table',
		'required' => 0,
		'user_defined' => 1,
		'group' => 'General',
		'filterable' => 1,
		'is_filterable_in_search' => 1,
		'option' => array(
			'order' => $installer->getOptionOrderArray($manufacturerValues),
			'value' => $installer->getOptionValueArray($manufacturerValues),
		)
	),
);

// If attribute exists remove it - just to enable me to rerun this script without removing attributes by hand
$installer->getConnection()->delete(
	$installer->getTable('eav/attribute'),
	array('attribute_code IN (?)' => array_keys($attributes))
);

// Create attributes
foreach ($attributes as $attrCode => $info)
{
	$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $attrCode, $info);
	$id = $installer->getAttributeId(Mage_Catalog_Model_Product::ENTITY, $attrCode);
	$attributes[$attrCode] = Mage::getModel('catalog/entity_attribute')->load($id);
}

// Set indexer mode to manual to speed up product creation a bit
$currentIndexerModes = array();
foreach ($indexer->getProcessesCollection() as $process)
{
	$currentIndexerModes[$process->getIndexerCode()] = $process->getMode();
	$process->setMode(Mage_Index_Model_Process::MODE_MANUAL)->save();
}
Mage::log('Finished base setup, now beginning to create simple products');

// Create the simple products
/* @var $product Mage_Catalog_Model_Product */
$product = Mage::getModel('catalog/product');
$baseSku = 'example-config';
$baseName = 'Example Configurable';
$initialQty = 10000;
$taxClassId = $this->getProductTaxClassId();
$categoryIds = implode(',', $this->getProductCategoryIds());
$websiteIds = array_keys(Mage::app()->getWebsites());
$associatedProducts = array();
$configAttributeData = $installer->initConfigAttributeData($attributes);
$configPrice = 10;

$attribOne = 'width';
$attribTwo = 'diameter';
$attribThree = 'manufacturer';

foreach ($attributes[$attribOne]->getSource()->getAllOptions() as $optionOne)
{
	if (! $optionOne['value'])
	{
		// Skip the empty "not selected" option
		continue;
	}
	foreach ($attributes[$attribTwo]->getSource()->getAllOptions() as $optionTwo)
	{
		if (! $optionTwo['value'])
		{
			// Skip the empty "not selected" option
			continue;
		}
		foreach ($attributes[$attribThree]->getSource()->getAllOptions() as $optionThree)
		{
			if (! $optionThree['value'])
			{
				// Skip the empty "not selected" option
				continue;
			}
			Mage::log(sprintf('%s %s/%s/%s', $baseName, $optionOne['label'], $optionTwo['label'], $optionThree['label']));

			$attributeValueMap = array($attribOne => $optionOne, $attribTwo => $optionTwo, $attribThree => $optionThree);
			$product->clearInstance()
				->setData(array(
					'name' => sprintf('%s %s/%s/%s', $baseName, $optionOne['label'], $optionTwo['label'], $optionThree['label']),
					'sku' => sprintf('%s-%s-%s-%s', $baseSku, $optionOne['value'], $optionTwo['value'], $optionThree['value']),
					'status' => true,
					'type_id' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
					'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE,
					'price' => $configPrice,
					'website_ids' => $websiteIds,
					'stock_data' => array(
						'qty' => $initialQty,
						'is_in_stock' => 1
					),
					'attribute_set_id' => $product->getDefaultAttributeSetId(),
					'tax_class_id' => $taxClassId,
					'width' => $attributeValueMap['width']['value'],
					'diameter' => $attributeValueMap['diameter']['value'],
					'manufacturer' => $attributeValueMap['manufacturer']['value'],
				))
				->save();
			// This is how the product model expects the input data *shrug*
			$associatedProducts[$product->getId()] = true;
			
			// Store data for creating the associations on the configurable product
			$configAttributeData[$attribThree]['values']['NO INT' . $optionThree['label']] = array(
				'value_index'       => $optionThree['value'],
				'label'             => $optionThree['label'],
				'is_percent'        => 0,
				'pricing_value'     => '', // Set price delta here if needed
				'use_default_value' => true
			);
		}
		$configAttributeData[$attribTwo]['values']['NO INT' . $optionTwo['label']] = array(
			'value_index'       => $optionTwo['value'],
			'label'             => $optionTwo['label'],
			'is_percent'        => 0,
			'pricing_value'     => '', // Set price delta here if needed
			'use_default_value' => true
		);
	}
	$configAttributeData[$attribOne]['values']['NO INT' . $optionOne['label']] = array(
		'value_index'       => $optionOne['value'],
		'label'             => $optionOne['label'],
		'is_percent'        => 0,
		'pricing_value'     => '', // Set price delta here if needed
		'use_default_value' => true
	);
}
Mage::log(sprintf('Finished creating %d simple products', count($associatedProducts)));

// Create the configurable product and assign the simple products
$product = Mage::getModel('catalog/product')
	->setData(array(
		'name' => $baseName,
		'sku' => $baseSku,
		'category_ids' => $categoryIds,
		'status' => true,
		'type_id' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
		'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
		'price' => $configPrice,
		'website_ids' => $websiteIds,
		'stock_data' => array(
			'is_in_stock' => 1
		),
		'attribute_set_id' => $product->getDefaultAttributeSetId(),
		'tax_class_id' => $taxClassId,
		'description' => 'A test configurable product with many associated products',
		'short_description' => 'A test configurable product'
	))
	->setConfigurableProductsData($associatedProducts)
    ->setConfigurableAttributesData(array_values($configAttributeData))
    ->setCanSaveConfigurableAttributes(true)
    ->setCanSaveCustomOptions(true)
	->save();
Mage::log('Finished creating configurable product');

// Save the configurable product id to add the multioption link to the top.links
$installer->setConfigData('example_multioption/config/product_id', $product->getId());

// Revert indexed process modes
foreach ($indexer->getProcessesCollection() as $process)
{
	$process->setMode($currentIndexerModes[$process->getIndexerCode()])
			->setStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX)
			->save();
}

// The config cache needs to be marked as invalid so the header link works
Mage::app()->getCacheInstance()->invalidateType(array('config'));

// Revert current store
Mage::app()->setCurrentStore($currentStore->getCode());
Mage::log('Finished cleaning up');

$installer->endSetup();

