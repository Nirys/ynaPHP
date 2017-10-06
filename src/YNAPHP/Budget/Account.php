<?php

namespace YNAPHP\Budget;

class Account {
  protected $_id, $_name, $_obsolete, $_clearedBalance, $_unclearedBalance;
  protected $_accountType;
  protected $_isLoaded = false;
  protected $_accounts = null;

  public function __construct($jsonData = null){
    if($jsonData){
      $this->_id = $jsonData->id;
      $this->_obsolete = $jsonData->is_tombstone;
      $this->_accountType = $jsonData->account_type;
      $this->_name = $jsonData->account_name;
    }
  }

  public function setCalculatedBalances($data){
    $this->_clearedBalance = $data->cleared_balance / 1000;
    $this->_unclearedBalance = $data->uncleared_balance / 1000;/*
                            [entities_account_id] => 2c059d98-f7c6-4166-8811-36d419ec9249
                            [is_tombstone] => 
                            [cleared_balance] => -384620
                            [uncleared_balance] => 0
                            [info_count] => 0
                            [warning_count] => 0
                            [error_count] => 0
                            [transaction_count] => 5*/
  }

  public function getId(){
    return $this->_id;
  }

  public function getName(){
    return $this->_name;
  }

  public function getClearedBalance(){
    return $this->_clearedBalance;
  }

  public function getUnclearedBalance(){
    return $this->_unclearedBalance;
  }
}