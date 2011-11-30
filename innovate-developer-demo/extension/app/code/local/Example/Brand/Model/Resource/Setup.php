<?php
 
class Example_Brand_Model_Resource_Setup extends Mage_Catalog_Model_Resource_Setup
{
	/**
	 * Cache the website ids to assign products to
	 *
	 * @var array
	 */
	protected $_productWebsiteIds;

	/**
	 * Cache the product tax class id for newly created products
	 * 
	 * @var int
	 */
	protected $_productTaxClassId;

	/**
	 * Return the protected resource config property
	 * 
	 * @return Varien_Simplexml_Object
	 */
	public function getResourceConfig()
	{
		return $this->_resourceConfig;
	}

	/**
	 * Return an array of brands based on the brand logos in the media directory
	 * 
	 * @return array
	 */
	public function getBrands()
	{
		$brands = array();
		$brandLogoDir = Mage::getBaseDir('media') . Mage::getStoreConfig(Example_Brand_Model_Brand::XML_PATH_LOGO_DIR);
		if (file_exists($brandLogoDir) && is_dir($brandLogoDir))
		{
			$files = glob($brandLogoDir . 'logo-*.gif');
			if ($files)
			{
				foreach ($files as $logo)
				{
					$logo = basename($logo);
					if (preg_match('#logo-([^.]+).gif$#', $logo, $m))
					{
						$name = str_replace('-', ' ', $m[1]);
						if (strlen($name) < 4)
						{
							$name = strtoupper($name); // assume acronym
						}
						else
						{
							$name = ucwords($name);
						}
						$brands[] = array('name' => $name, 'logo' => $logo);
					}
				}
			}
		}
		return $brands;
	}

	/**
	 * Return the list of alphabetic brand groups.
	 *
	 * @param bool $grouped
	 * @return array
	 */
	public function getAlphabetGrouping($grouped = false)
	{
		$groups = array(
			'A' => 'Brands A-D',
			'Ä' => 'Brands A-D',
			'B' => 'Brands A-D',
			'C' => 'Brands A-D',
			'D' => 'Brands A-D',
			'E' => 'Brands E-H',
			'F' => 'Brands E-H',
			'G' => 'Brands E-H',
			'H' => 'Brands E-H',
			'I' => 'Brands I-L',
			'J' => 'Brands I-L',
			'K' => 'Brands I-L',
			'L' => 'Brands I-L',
			'M' => 'Brands M-P',
			'N' => 'Brands M-P',
			'O' => 'Brands M-P',
			'Ö' => 'Brands M-P',
			'P' => 'Brands M-P',
			'Q' => 'Brands Q-U',
			'R' => 'Brands Q-U',
			'S' => 'Brands Q-U',
			'T' => 'Brands Q-U',
			'U' => 'Brands Q-U',
			'Ü' => 'Brands Q-U',
			'V' => 'Brands V-Z',
			'W' => 'Brands V-Z',
			'X' => 'Brands V-Z',
			'Y' => 'Brands V-Z',
			'Z' => 'Brands V-Z',
		);
		if ($grouped)
		{
			$groups = array_unique($groups);
		}

		return $groups;
	}

	/**
	 * Return an array website ids to assign new products to
	 * 
	 * @return array
	 */
	public function getProductWebsiteIds()
	{
		if (is_null($this->_productWebsiteIds))
		{
			$this->_productWebsiteIds = array_keys(Mage::app()->getWebsites());
		}
		return $this->_productWebsiteIds;
	}

	/**
	 * Return the first found product tax class id for new products
	 * 
	 * @return int
	 */
	public function getProductTaxClassId()
	{
		if (is_null($this->_productTaxClassId))
		{
			/* @var $collection Mage_Tax_Model_Resource_Class_Collection */
			$collection = Mage::getResourceModel('tax/class_collection')->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)->load();
			$this->_productTaxClassId = $collection->getFirstItem()->getId();
		}
		return $this->_productTaxClassId;
	}
}
