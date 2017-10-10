<?php
namespace YNAPHP\Budget;

use YNAPHP\AbstractCollection;

class Budget {
  protected $_id, $_name, $_obsolete, $_createdAt, $_version;
  protected $_isLoaded = false;
  protected $_accounts = null;
  protected $_categories = null;

  public function __construct($jsonData = null){
    if($jsonData){
      $this->_id = $jsonData->id;
      $this->_name = $jsonData->budget_name;
      $this->_obsolete = $jsonData->is_tombstone;
      $this->_createdAt = strtotime($jsonData->created_at);
    }
  }

  public function accounts(){
    return $this->_accounts;
  }

  public function isLoaded(){
    return $this->_isLoaded;
  }

  public function loadData($data){
    $this->_accounts = new AbstractCollection();
    $accountCalcs = array();

    foreach($data->changed_entities->be_account_calculations as $key=>$value){
      $accountCalcs[$value->entities_account_id] = $value;
    }

    foreach($data->changed_entities->be_accounts as $key=>$value){
      $account = new Account($value);
      $account->setCalculatedBalances($accountCalcs[$account->getId()]);
      $this->_accounts->add($account, $account->getId());
    }
    $this->_isLoaded = true;

    foreach($data->changed_entities->be_master_categories as $key=>$value){
      $category = new Category($value);
      $this->_categories->add($category, $category->getId());
    }

    foreach($data->changed_entities->be_subcategories as $key=>$value){
      $category = new Category($value);
      $this->_categories->add($category, $category->getId());
    }

  }

  public function setVersion($id){
    $this->_version = $id;
  }

  public function getVersion(){
    return $this->_version;
  }

  public function getId(){
    return $this->_id;
  }
}