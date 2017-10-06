<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 10/4/17
 * Time: 8:48 AM
 */

namespace YNAPHP;

class AbstractCollection implements \IteratorAggregate
{
    protected $_items = [];

    public function __construct($items = [])
    {
        $this->_items = $items;
    }

    public function add($item, $key=null){
      if($key){
        $this->_items[$key] = $item;
      }else{
        $this->_items[] = $item;
      }
    }

    public function get($key){
      return isset($this->_items[$key]) ? $this->_items[$key] : null;
    }

    public function find($key, $keyName = 'id'){
        foreach($this as $itemKey => &$item){
            if($item->{$keyName}==$key) return $item;
        }
        return null;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_items);
    }
}
