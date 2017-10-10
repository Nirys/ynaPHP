<?php
namespace YNAPHP\Budget;

class Category {
  protected $_id, $_obsolete, $_internalName, $_parentId;
  protected $_name, $_note, $_isMaster, $_categoryType;

  public function __construct($jsonData = null){
    if($jsonData){
      $this->_id =$jsonData->id;
      $this->_obsolete = $jsonData->is_tombstone;
      $this->_internalName = $jsonData->internal_name;
      $this->_deletable = $jsonData->deletable;
      $this->_name = $jsonData->name;
      $this->_note = $jsonData->note;
      $this->_hidden = $jsonData->is_hidden;
      $this->_isMaster = true;

      if(property_exists($jsonData, 'entities_master_category_id')){
        $this->_isMaster = false;
        $this->_categoryType = $jsonData->type;
        $this->_parentId = $jsonData->entities_master_category_id;
      }
    }
  }

  public function getId(){
    return $this->_id;
  }

  public function getName(){
    return $this->_name;
  }

  public function isMaster(){
    return $this->_isMaster;
  }

  public function getParentId(){
    return $this->_parentId;
  }
}