<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Eav
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Eav_Model_Entity_Attribute_Source_Boolean extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    /**
     * Option values
     */
    const VALUE_YES = 1;
    const VALUE_NO = 0;


    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = array(
                array(
                    'label' => Mage::helper('eav')->__('Yes'),
                    'value' => self::VALUE_YES
                ),
                array(
                    'label' => Mage::helper('eav')->__('No'),
                    'value' => self::VALUE_NO
                ),
            );
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = array();
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
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
            'unsigned'  => false,
            'default'   => null,
            'extra'     => null
        );

        if (Mage::helper('core')->useDbCompatibleMode()) {
            $column['type']     = 'tinyint(1)';
            $column['is_null']  = true;
        } else {
            $column['type']     = Varien_Db_Ddl_Table::TYPE_SMALLINT;
            $column['length']   = 1;
            $column['nullable'] = true;
            $column['comment']  = $attributeCode . ' column';
        }

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
            'type'      => 'index',
            'fields'    => array($this->getAttribute()->getAttributeCode())
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

    /**
     * Get a text for index option value
     *
     * @param  string|int $value
     * @return string|bool
     */
    public function getIndexOptionText($value)
    {
        switch ($value) {
            case self::VALUE_YES:
                return 'Yes';
            case self::VALUE_NO:
                return 'No';
        }

        return parent::getIndexOptionText($value);
    }
    
    /***Added custom code**/
    public function addValueSortToCollection($collection, $dir = 'asc')
	{
		$attributeCode  = $this->getAttribute()->getAttributeCode();
		$attributeId    = $this->getAttribute()->getId();
		$attributeTable = $this->getAttribute()->getBackend()->getTable();

		if ($this->getAttribute()->isScopeGlobal()) {
			$tableName = $attributeCode . '_t';
			$collection->getSelect()
				->joinLeft(
					array($tableName => $attributeTable),
					"e.entity_id={$tableName}.entity_id"
						. " AND {$tableName}.attribute_id='{$attributeId}'"
						. " AND {$tableName}.store_id='0'",
					array());
			$valueExpr = $tableName . '.value';
		}
		else
		{
			$valueTable1 = $attributeCode . '_t1';
			$valueTable2 = $attributeCode . '_t2';
			$collection->getSelect()
				->joinLeft(
					array($valueTable1 => $attributeTable),
					"e.entity_id={$valueTable1}.entity_id"
						. " AND {$valueTable1}.attribute_id='{$attributeId}'"
						. " AND {$valueTable1}.store_id='0'",
					array())
				->joinLeft(
					array($valueTable2 => $attributeTable),
					"e.entity_id={$valueTable2}.entity_id"
						. " AND {$valueTable2}.attribute_id='{$attributeId}'"
						. " AND {$valueTable2}.store_id='{$collection->getStoreId()}'",
					array()
				);
				$valueExpr = $collection->getConnection()->getCheckSql(
					$valueTable2 . '.value_id > 0',
					$valueTable2 . '.value',
					$valueTable1 . '.value'
				);
		}
		$collection->getSelect()->order($valueExpr . ' ' . $dir);
		return $this;
	} 
}
