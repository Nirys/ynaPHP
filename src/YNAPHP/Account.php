<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 10/4/17
 * Time: 9:06 AM
 */

namespace YNAPHP;

use YNAPHP\AbstractCollection;

class Account
{
    protected $_id, $_name, $_type, $_data, $_transactions;

    public function __construct($rawData){
        $this->_data = $rawData;
        $this->_id = $rawData['id'];
        $this->_name = $rawData['account_name'];
        $this->_type = $rawData['account_type'];

        $this->_transactions = new AbstractCollection();
    }

    public function addTransaction($trans){
        $this->_transactions[] = $trans;
    }

    public function transactions(){
        return $this->_transactions;
    }

    public function getId(){
        return $this->_id;
    }

    public function getName(){
        return $this->_name;
    }
}