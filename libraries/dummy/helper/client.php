<?php
/**
 * Created by Jerry Pham.
 * Date: 13/11/2015
 * Time: 14:16
 */


class DummyHelperClient
{

    private $api;

    private $email;

    private $pass;

    /*
    * Hold logged in account information
    */
    private $account;

    private $accountSession;


    private $error;

    public function error()
    {
        return $this->error;
    }

    public function __construct($api, $email, $pass)
    {
        $this->api = $api;
        $this->email = $email;
        $this->pass = $pass;
    }


    public function makeRequest($data)
    {
        $curl = \DummyHelperCurl::init($this->api)
            ->addHttpHeader('Content-type', 'application/json')
            ->setPost(TRUE)
            ->setTimeOut(30)
            ->setPostFields($data)
            ->setReturnTransfer(TRUE);

        $response = $curl->execute();
        $curl->close();
        if ($response) {
            return json_decode($response, 1);
        }

        return $response;
    }


    public function login()
    {
        $data = <<<JSON
{
    "method": "login",
    "id": "711",
    "params": [
        {"AccountIdentity": {"Email": "{$this->email}", "Phone": null, "Password": "{$this->pass}"}},
        ""
    ],
    "id": "1",
    "jsonrpc": "2.0"
}
JSON;

        $response = $this->makeRequest($data);
        $this->account = isset($response['result']['Account']) ? $response['result']['Account'] : null;
        $this->accountSession = isset($response['result']['AccountSession']) ? $response['result']['AccountSession'] : null;

        return $response;
    }

    public function isLoggedIn()
    {
        return $this->account || $this->accountSession;
    }


    /**
     * @param $policyId
     * @return mixed|null
     */
    public function getPolicy($policyId)
    {

        $sessionToken = $this->accountSession['SessionToken'];
        $data = <<<JSON
{
    "method": "getPolicy",
    "id" : null,
    "params": [
        {
            "Id": "$policyId"
        },
        "$sessionToken"
    ],
    "jsonrpc": "2.0"
}
JSON;

        $response = $this->makeRequest($data);
        if (is_array($response) && isset($response['result'])) {
            return $response['result'];
        }
        return $response;
    }


    /**
     *
     */
    public function getPolicies($limit = 5)
    {

        $sessionToken = $this->accountSession['SessionToken'];

        $data = <<<JSON
{
    "method": "getListPolicy",
    "params": [
        {
            "Pagination": {
                "CacheEnabled": true,
                "CurrentPageNumber": 1,
                "ItemCountPerPage": $limit,
                "TotalItemCount": null,
                "CurrentItems": null
            }
        },
        "{$sessionToken}"
    ],
    "id": "1",
    "jsonrpc": "2.0"
}
JSON;

        $response = $this->makeRequest($data);
        if( is_array( $response) && isset( $response['result'] ) ){
            return $response['result'];
        }
        return $response;
    }


    /**
     * @param $policyId
     * @param array $params
     * @return bool
     */
    public function createClaim($policyId, $params)
    {
        $sessionToken = $this->accountSession['SessionToken'];
        $policy = $this->getPolicy($policyId);

        if ($policy) {

            $accountId = $policy['Policy']['AccountId'];
            $productId = $policy['Policy']['ProductId'];

            $data = <<<JSON
{
    "method": "createClaim",
    "id": 1,
    "params": [
        {
            "Claim": {
                "PolicyId": "$policyId",
                "AgentId": null,
                "AgentType": null,
                "Id": 1,
                "AccountId": $accountId,
                "Status": 0,
                "CreateDate": "2015-10-30",
                "UpdateDate": null,
                "DateOfLoss": "{$params['DateOfLoss']}",
                "TimeOfLoss": "{$params['TimeOfLoss']}",
                "Place": "{$params['PlaceOfLoss']}",
                "PolicyOfficeInChargeOfLoss": "{$params['InChargeOfLoss']}",
                "CauseOfLoss": "{$params['CauseOfLoss']}",
                "SituationOfLoss": "{$params['SituationOfLoss']}",
                "UploadedFiles": null,
                "Policy": null,
                "ProductName": null,
                "Documents": null,
                "serviceData": null,
                "ExternalData": null,
                "ProductVersion": null,
                "Product": null,
                "Customer": null,
                "PolicyInternalId": null
            }
        },
        "$sessionToken"
    ],
    "jsonrpc": "2.0"
}
JSON;

            $response = $this->makeRequest($data);

            if (is_array($response) && isset($response['result']['Claim'])) {
                return $response['result']['Claim'];
            }

            if( isset( $response['error'] )){
                $this->error = $response['error'];
            }

            return false;
        }

        throw new \Exception('Couldnt get policy by id: ' . $policyId);
    }
}