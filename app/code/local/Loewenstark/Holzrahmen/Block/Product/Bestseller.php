<?php

class Loewenstark_Holzrahmen_Block_Product_Bestseller extends Mage_Catalog_Block_Product_Abstract {

    public function getProductCollection() {
        $storeId = Mage::app()->getStore()->getId();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = "SELECT SUM(order_items.qty_ordered) AS `ordered_qty`, `order_items`.`name` AS `order_items_name`, `order_items`.`product_id` AS `entity_id`, `e`.`entity_type_id`, `e`.`attribute_set_id`, `e`.`type_id`, `e`.`sku`, `e`.`has_options`, `e`.`required_options`, `e`.`created_at`, `e`.`updated_at` FROM `sales_flat_order_item` AS `order_items` INNER JOIN `sales_flat_order` AS `order` ON `order`.entity_id = order_items.order_id AND `order`.state <> 'cancelled' LEFT JOIN `catalog_product_entity` AS `e` ON (e.type_id NOT IN ('grouped', 'configurable', 'bundle')) AND e.entity_id = order_items.product_id AND e.entity_type_id = " . $storeId . " WHERE (parent_item_id IS NULL) GROUP BY `order_items`.`product_id` HAVING (SUM(order_items.qty_ordered) > 0) ORDER BY `ordered_qty` desc LIMIT ".$this->getProductCount();
        $results = $readConnection->fetchAll($query);
        $productIds = array();
        foreach ($results as $value) {
            $productIds[] = $value['entity_id'];
        }
        $products = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addStoreFilter()
                ->addAttributeToFilter('entity_id', array('in' => $productIds));
        return $products;
    }

}
