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


class Client{

    protected $_user, $_password, $_cookieJar, $_userId;
    protected $_session, $_defaultBudget, $_id;
    protected $_url = 'https://app.youneedabudget.com/api/v1/catalog';
    protected $_httpClient = null;

    public function __construct(){
        $this->_id = UUID::v4();
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

    public function syncData(){
        $requestData = json_encode(array(
            "user_id" => $this->_userId,
            'schema_version' => 1,
            'schema_version_of_knowledge'=>1,
            "starting_device_knowledge"=>0,
            "ending_device_knowledge"=>0,
            "device_knowledge_of_server"=>0,
            "changed_entities"=>new \stdClass()
        ));
        $request = array(
            'operation_name' => 'syncCatalogData',
            'request_data' => $requestData
        );
        $data = $this->httpPOST($this->_url, $request);
        return $data;
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
        echo "POSTing " . print_r($newData, true) . "\n\n";
        $client = $this->getHttpClient();
        $client->setPost($newData);
        $client->setHeader('X-YNAB-Device-Id', $this->_id);
        $client->setHeader('X-YNAB-Client-App-Version','v1.18349');
        $client->setHeader('X-Session-Token',$this->_session);

        $client->setHeader('User-Agent','phpAPI');
        $data = $client->createCurl($url);

        echo "Result:\n";
        echo $data;
        echo "\n\n";

        return json_decode($data);
    }
}