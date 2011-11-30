<?php
 
class Example_MultiOptionCart_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getMultiOptionProductUrl()
	{
		return Mage::getUrl('multioption/config/view', array(
			'id' => Mage::getStoreConfig('example_multioption/config/product_id')
		));
	}

	/**
	 * Return the attributes sorted and aggregated into an array ready for display in the template
	 *
	 * @param array $products
	 * @param Mage_Catalog_Model_Resource_Product_Type_Configurable_Attribute_Collection $attributes
	 * @return array
	 */
	public function getSortedAttributeOptions(array $products, $attributes)
	{
		$options = array();
		$firstAttr = $attributes->getFirstItem();

		foreach ($products as $product)
		{
			/* @var $product Mage_Catalog_Model_Product */
			$row = $idx = $selectAttr = array();
			foreach ($attributes as $attribute)
			{
				$attrCode = $attribute->getProductAttribute()->getAttributeCode();
				$attrId = $attribute->getProductAttribute()->getId();
				$value = $product->getData($attrCode);
				if ($attribute === $firstAttr)
				{
					$idx[] = $value;
					$row['options'][$attrId] = array('value' => $value, 'label' => $product->getAttributeText($attrCode));
				}
				else
				{
					$selectAttr[$attrId] = array('option_id' => $value, 'label' => $product->getAttributeText($attrCode));
				}
			}
			$idx = implode('-', $idx);
			if (isset($options[$idx]))
			{
				$row = $options[$idx];
			}
			else
			{
				$row['name'] = $product->getName();
			}

			foreach ($selectAttr as $attrId => $data)
			{
				// Add last attribute as configurable option
				$row['options'][$attrId]['value'][$data['option_id']] = $data['label'];
			}
			$options[$idx] = $row;
		}
		$options = array_values($options);
		return $options;
	}

	/**
	 * Return the HTML for a select element with the specified options
	 *
	 * @param string $name
	 * @param array $options
	 * @return string
	 */
	public function getOptionSelectHtml($name, $options)
	{
		$select = new Varien_Data_Form_Element_Select(array('name' => $name, 'values' => $options));
		$select->setForm(new Varien_Object(array('html_id_prefix' => '')));
		return $select->getElementHtml();
	}
}
