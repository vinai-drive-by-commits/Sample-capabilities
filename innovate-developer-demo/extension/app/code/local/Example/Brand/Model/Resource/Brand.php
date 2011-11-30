<?php
 
class Example_Brand_Model_Resource_Brand extends Mage_Core_Model_Resource_Db_Abstract
{
	/**
	 * Initialize the resource model with the entity table and primary key field name
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('example_brand/brand', 'entity_id');
	}

	/**
	 * Return the number of associated products for the given brand
	 * 
	 * @param Example_Brand_Model_Brand $object
	 * @return int
	 */
	public function getProductCount(Varien_Object $object)
	{
		$brandIdAttribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'brand_id');
		
		$select = $this->_getReadAdapter()->select()->from(
			$brandIdAttribute->getBackendTable(), array(new Zend_Db_Expr('COUNT(*)'))
		)
		->where("attribute_id=?", $brandIdAttribute->getId())
		->where('value=?', $object->getId());

		return (int) $this->_getReadAdapter()->fetchOne($select);
	}
}
