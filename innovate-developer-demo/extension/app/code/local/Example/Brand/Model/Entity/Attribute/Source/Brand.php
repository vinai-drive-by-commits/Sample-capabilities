<?php

class Example_Brand_Model_Entity_Attribute_Source_Brand extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	/**
	 * Return the option array
	 * 
	 * @return array
	 */
	public function getAllOptions()
	{
		if (is_null($this->_options))
		{
			$this->_options = Mage::getResourceModel('example_brand/brand_collection')->toOptionArray();
			array_unshift($this->_options, array('value' => '', 'label' => ''));
		}
		return $this->_options;
	}

	/**
	 * Retrieve flat column definition
	 *
	 * @return array
	 */
	public function getFlatColums()
	{
		$attributeCode = $this->getAttribute()->getAttributeCode();
		$column = array(
			'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
			'length' => 11,
			'nullable' => true,
			'unsigned' => true,
			'default' => null,
			'extra' => null,
			'comment' => $attributeCode . ' column',
		);

		return array($attributeCode => $column);
	}

	/**
	 * Retrieve Indexes(s) for Flat
	 *
	 * @return array
	 */
	public function getFlatIndexes()
	{
		$indexes = array();

		$index = 'IDX_' . strtoupper($this->getAttribute()->getAttributeCode());
		$indexes[$index] = array(
			'type' => 'index',
			'fields' => array($this->getAttribute()->getAttributeCode())
		);

		return $indexes;
	}

	/**
	 * Retrieve Select For Flat Attribute update
	 *
	 * @param int $store
	 * @return Varien_Db_Select|null
	 */
	public function getFlatUpdateSelect($store)
	{
		return Mage::getResourceModel('eav/entity_attribute')
				->getFlatUpdateSelect($this->getAttribute(), $store);
	}
}
