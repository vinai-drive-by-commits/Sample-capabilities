<?php
 
class Example_Brand_Block_Adminhtml_Brand_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	protected $_defaultLimit = 50;
	protected $_defaultDir   = 'asc';

	/**
	 * Initialize grid settings
	 *
	 */
	protected function _construct()
	{
		parent::_construct();

		$this->setId('example_brand_list');
		$this->setDefaultSort('id');

		/*
		 * Override method getGridUrl() in this class to provide URL for ajax
		 */
		$this->setUseAjax(true);
	}

	/**
	 * Prepare brand collection
	 *
	 * @return Example_Brand_Block_Adminhtml_Brand_Grid
	 */
	protected function _prepareCollection()
	{
		$collection = Mage::getResourceModel('example_brand/brand_collection');
		$this->setCollection($collection);

		return parent::_prepareCollection();
	}

	/**
	 * Prepare grid columns
	 *
	 * @return Example_Brand_Block_Adminhtml_Brand_Grid
	 */
	protected function _prepareColumns()
	{
		$this->addColumn('id', array(
			'header' => $this->__('ID'),
			'sortable' => true,
			'width' => '60px',
			'index' => 'entity_id'
		));

		$this->addColumn('name', array(
			'header' => $this->__('Name'),
			'index' => 'name',
		));

		$this->addColumn('logo', array(
			'header' => $this->__('Image File'),
			'width' => '250px',
			'index' => 'logo',
		));

		$this->addColumn('action', array(
			'header' => $this->__('Action'),
			'width' => '100px',
			'type' => 'action',
			'getter' => 'getId',
			'actions' => array(
				array(
					'caption' => $this->__('Edit'),
					'url' => array('base' => '*/*/edit'),
					'field' => 'id',
				),
			),
			'filter' => false,
			'sortable' => false,
		));

		return parent::_prepareColumns();
	}

	/**
	 * Return URL where to send the user when he clicks on a row
	 */
	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}

    /**
	 * Return Grid URL for AJAX query
	 *
	 * @return string
	 */
	public function getGridUrl()
	{
        return $this->getUrl('*/*/grid', array('_current'=>true));
	}

}
