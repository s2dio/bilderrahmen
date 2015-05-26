<?php

class Loewenstark_Holzrahmen_Model_Mysql4_Product_Collection extends Mage_Reports_Model_Resource_Product_Collection
{
public function addOrderedQty($from = '', $to = '', $getComplexProducts=false, $getComplexChildProducts = true, $getRemovedProducts = true)
    {
    Mage::log('repots collection');
        $qtyOrderedTableName = $this->getTable('sales/order_item');
        $qtyOrderedFieldName = 'qty_ordered';

        $productIdFieldName = 'product_id';

        if (!$getComplexProducts) {
            $compositeTypeIds = Mage::getSingleton('catalog/product_type')->getCompositeTypes();
            $productTypes = $this->getConnection()->quoteInto(' AND (e.type_id NOT IN (?))', $compositeTypeIds);
        } else {
            $productTypes = '';
        }

        if ($from != '' && $to != '') {
            $dateFilter = " AND `order`.created_at BETWEEN '{$from}' AND '{$to}'";
        } else {
            $dateFilter = "";
        }

        $this->getSelect()->reset()->from(
            array('order_items' => $qtyOrderedTableName),
            array(
                'ordered_qty' => "SUM(order_items.{$qtyOrderedFieldName})",
                'order_items_name' => 'order_items.name'
            )
        );

         $_joinCondition = $this->getConnection()->quoteInto(
                'order.entity_id = order_items.order_id AND order.state<>?', Mage_Sales_Model_Order::STATE_CANCELED
         );
         $_joinCondition .= $dateFilter;
         $this->getSelect()->joinInner(
            array('order' => $this->getTable('sales/order')),
            $_joinCondition,
            array()
         );

         // Add join to get the parent id for configurables
         $this->getSelect()->joinLeft(
             array('cpsl' => $this->getTable('catalog/product_super_link')),
             'cpsl.product_id = order_items.product_id',
             'cpsl.parent_id'
         );

        if(!$getComplexChildProducts)
            $this->getSelect()->having('parent_id IS NULL');

        if($getRemovedProducts)
        {
             $this->getSelect()
                ->joinLeft(array('e' => $this->getProductEntityTableName()),
                    "e.entity_id = order_items.{$productIdFieldName} AND e.entity_type_id = {$this->getProductEntityTypeId()}{$productTypes}")
                ->group('order_items.product_id');
        }
        else
        {
            $this->getSelect()
                ->joinInner(array('e' => $this->getProductEntityTableName()),
                    "e.entity_id = order_items.{$productIdFieldName} AND e.entity_type_id = {$this->getProductEntityTypeId()}{$productTypes}")
                ->group('e.entity_id');
        }


        $this->getSelect()->having('ordered_qty > 0');

        // This line is for debug purposes, in case you'd like to see what the SQL looks like
        // $x = $this->getSelect()->__toString();

        return $this;
    }
 
}
