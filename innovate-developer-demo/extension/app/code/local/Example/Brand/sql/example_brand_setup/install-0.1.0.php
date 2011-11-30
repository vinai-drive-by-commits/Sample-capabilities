<?php

 /* @var $installer Example_Brand_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tableName = $installer->getTable('example_brand/brand');

// Make reinstalls of this module possible, even if the db wasn't cleaned up completely
if ($installer->getConnection()->isTableExists($tableName))
{
	$installer->getConnection()->dropTable($tableName);
}

$table = $installer->getConnection()->newTable($tableName)
	->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
		'unsigned' => true,
		'nullable' => false,
		'primary' => true,
		'identity' => true,
		), 'ID')

	->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 124, array(
		'nullable'  => false,
		'default'   => '',
		), 'Name')

	->addColumn('logo', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
		'nullable'  => false,
		'default'   => '',
		), 'Logo')

	->setComment('Innovate Example Brand Entity Table');
$installer->getConnection()->createTable($table);

// If attribute exists remove it - just to enable me to rerun this script without removing attributes by hand
$installer->getConnection()->delete(
	$installer->getTable('eav/attribute'),
	array('attribute_code = ?' => 'brand_id')
);

// Add product brand id attribute
$installer->addAttribute('catalog_product', 'brand_id', array(
	'label' => 'Brand',
	'type' => 'int',
	'used_in_product_listing' => 1,
	'is_configurable' => 0,
	'searchable' => 1,
	'visible_in_advanced_search' => 1,
	'filterable' => 1,
	'comparable' => 1,
	'visible_on_front' => 1,
	'input' => 'select',
	'source' => 'example_brand/entity_attribute_source_brand',
	'required' => 0,
));

// Add category brand id attribute
$installer->addAttribute('catalog_category', 'brand_id', array(
	'label' => 'Brand',
	'type' => 'int',
	'group' => 'General Information',
	'input' => 'select',
	'source' => 'example_brand/entity_attribute_source_brand',
	'required' => 0,
));

$installer->endSetup();