<?php
/**
 * Created by PhpStorm.
 * User: kath.young
 * Date: 10/4/17
 * Time: 8:06 AM
 */

namespace YNAPHP;
use YNAPHP\Budget\Budget;
use YNAPHP\Budget\BudgetCollection;


class Client{
    protected $_user, $_password, $_cookieJar, $_userId;
    protected $_session, $_defaultBudget, $_id;
    protected $_url = 'https://app.youneedabudget.com/api/v1/catalog';
    protected $_loginUrl = 'https://app.youneedabudget.com/users/login';
    protected $_httpClient = null;
    /** @var BudgetCollection $_budgets */
    protected $_budgets;

    public function __construct(){
        $this->_id = UUID::v4();
        $this->_budgets = new BudgetCollection();

        // Get the version
        $data = $this->httpGet($this->_loginUrl);
        file_put_contents("loginpage.txt", $data);
        $data = str_replace("\r\n", "", $data);
        $data = str_replace("\n", "", $data);
        preg_match("/ynab_client_constants[^>]+>([^<]+)</", $data, $matches);
        $matches = json_decode($matches[1]);
        $this->_appVersion = $matches->YNAB_APP_VERSION;
    }

    public function getBudget($id = null){
      if(!$id) $id = $this->_defaultBudget;
      if(!$this->_budgets || sizeof($this->_budgets)==0 ) $this->syncCatalogData();

      /** @var Budget $budget */
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
        $data = $this->httpPOST($this->_url, $data, null);
        file_put_contents("login_response.txt", json_encode($data));

        if($data->error){
            throw new \Exception($data->error->message);
        }else{
            $this->_user = $user;
            $this->_password = $password;
            $this->_session = $data->session_token;
            $this->_userId = $data->user->id;
            $this->_defaultBudget = $data->user_budget->budget_id;
            $this->syncCatalogData();
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
        file_put_contents("catalog_data.txt", json_encode($data));
        $this->parseCatalogData($data);
        return $data;
    }

    public function parseCatalogData($data){
      $budgetVersions = array();
      foreach($data->changed_entities->ce_budget_versions as $key=>$value){
        $budgetVersions[$value->budget_id] = $value->id;
      }

      $this->_budgets = new BudgetCollection();
      foreach($data->changed_entities->ce_budgets as $key=>$value){
        $budget = new Budget($value);
        $budget->setVersion($budgetVersions[$budget->getId()]);
        $this->_budgets->add($budget);
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

    protected function httpGet($url){
        return $this->getHttpClient()->createCurl($url);
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
        $client->setHeader('X-YNAB-Client-App-Version', $this->_appVersion);
        $client->setHeader('X-Session-Token',$this->_session);

        $client->setHeader('User-Agent','phpAPI');
        $data = $client->createCurl($url);

        return json_decode($data);
    }
}