<?php
/**
 * Created by PhpStorm.
 * User: Kath
 * Date: 20/11/2017
 * Time: 6:25 AM
 */

namespace YNAPHP\Budget;

use YNAPHP\AbstractCollection;

class BudgetCollection extends AbstractCollection {
    /**
     * @param $item Budget
     */
    public function add($item, $key = null){
        parent::add($item, $item->getId());
    }

    /**
     * @param $key
     * @return Budget
     */
    public function get($key){
        return isset($this->_items[$key]) ? $this->_items[$key] : null;
    }

    /**
     * @param $key
     * @param string $keyName
     * @return Budget
     */
    public function find($key, $keyName = 'id'){
        $method = 'get' . ucwords($keyName, '_');
        foreach($this as $itemKey => &$item){
            try{
                if($item->$method()==$key) return $item;
            }catch(\Exception $e){

            }
        }
        return null;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_items);
    }
}