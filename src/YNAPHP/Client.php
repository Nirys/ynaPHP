<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 10/4/17
 * Time: 8:06 AM
 */

namespace YNAPHP;
use YNAPHP\HttpClient;
use YNAPHP\UUID;
use YNAPHP\Budget\Budget;


class Client{
    protected $_user, $_password, $_cookieJar, $_userId;
    protected $_session, $_defaultBudget, $_id;
    protected $_url = 'https://app.youneedabudget.com/api/v1/catalog';
    protected $_httpClient = null;
    protected $_budgets = null;

    public function __construct(){
        $this->_id = UUID::v4();
    }

    public function getBudget($id = null){
      $budget = new Budget();
      $budget->loadData(json_decode(file_get_contents('budget_data.txt')));
      return $budget;

      if(!$id) $id = $this->_defaultBudget;
      if(!$this->_budgets) $this->syncCatalogData();
      $budget = $this->_budgets->get($id);
      if($budget){
        if(!$budget->isLoaded()){
          $budgetData = $this->syncBudgetData($budget->getVersion());
          $budget->loadData($budgetData);
        }
      }
      return $budget;
    }

    public function login($user, $password){
        $data = array(
            'operation_name'=>'loginUser',
            'request_data' => json_encode(array(
                'email' => $user,
                'password' => $password,
                'remember_me'=>false,
                'device_id'=>$this->_id
            ))
        );
        $data = $this->httpPOST($this->_url, $data);

        if($data->error){
            throw new \Exception($data->error['message']);
        }else{
            $this->_user = $user;
            $this->_password = $password;
            $this->_session = $data->session_token;
            $this->_userId = $data->user->id;
            $this->_defaultBudget = $data->user_budget->budget_id;
            return true;
        }
    }

    protected function syncBudgetData($id){
      $requestData = array(
        'budget_version_id' => $id,
        'starting_device_knowledge' => 0,
        'ending_device_knowledge' => 0,
        'device_knowledge_of_server' => 0,
        'calculated_entities_included' => false,
        'schema_version' => 4,
        'schema_version_of_knowledge' => 4,
        'changed_entities' => new \stdClass()
      );
      $data = $this->httpPOST($this->_url, array('operation_name'=>'syncBudgetData', 'request_data'=>json_encode($requestData)));
      file_put_contents('budget_data.txt', json_encode($data));
      file_put_contents('budget_readable.txt', print_r($data, true));
      return $data;
    }

    protected function syncCatalogData(){
        $requestData = json_encode(array(
            "user_id" => $this->_userId,
            'schema_version' => 1,
            'schema_version_of_knowledge'=>1,
            "starting_device_knowledge"=>0,
            "ending_device_knowledge"=>0,
            "device_knowledge_of_server"=>0,
            "changed_entities"=>new \stdClass()
        ));
        $request = array( 'operation_name' => 'syncCatalogData', 'request_data' => $requestData );
        $data = $this->httpPOST($this->_url, $request);
        $this->parseCatalogData($data);
        return $data;
    }

    public function parseCatalogData($data){
      $budgetVersions = array();
      foreach($data->changed_entities->ce_budget_versions as $key=>$value){
        $budgetVersions[$value->budget_id] = $value->id;
      }

      $this->_budgets = new AbstractCollection();
      foreach($data->changed_entities->ce_budgets as $key=>$value){
        $budget = new Budget($value);
        $budget->setVersion($budgetVersions[$budget->getId()]);
        $this->_budgets->add($budget, $budget->getId());        
      }
    }

    protected function getHttpClient(){
        if(!$this->_httpClient) $this->_httpClient = new HttpClient('', true);
        return $this->_httpClient;
    }

    public function getSessionToken(){
        return $this->_session;
    }

    public function getDeviceId(){
        return $this->_id;
    }

    protected function httpPOST($url, $data, $headers = null){

        $newData = '';
        foreach($data as $key=>$value){
            $newData .= $key . '=' . urlencode($value). '&';
        }
        $newData = substr($newData, 0, strlen($newData)-1) ;
        $client = $this->getHttpClient();
        $client->setPost($newData);
        $client->setHeader('X-YNAB-Device-Id', $this->_id);
        $client->setHeader('X-YNAB-Client-App-Version','v1.18349');
        $client->setHeader('X-Session-Token',$this->_session);

        $client->setHeader('User-Agent','phpAPI');
        $data = $client->createCurl($url);

        return json_decode($data);
    }
}