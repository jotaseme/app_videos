<?php
namespace AppBundle\Services;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;


class Helpers
{
    public $jwt_auth;

    public function __construct($jwt_auth)
    {
        $this->jwt_auth = $jwt_auth;
    }

    public function auth_check($hash, $getIdentity = false)
    {
        $jwt_auth = $this->jwt_auth;
        $auth = false;

        if($hash != null){
            if(!$getIdentity){
                $check_token = $jwt_auth->check_token($hash);
                if($check_token){
                    $auth = true;
                }
            }else{
                $check_token = $jwt_auth->check_token($hash,true);
                if(is_object($check_token)){
                    $auth = $check_token;
                }
            }
        }
        return $auth;
    }

    public function json ($data)
    {
        $normalizers = array(new GetSetMethodNormalizer());
        $encoders = array("json" => new JsonEncoder());

        $serializer = new Serializer($normalizers,$encoders);

        $json = $serializer->serialize($data,'json');

        $response = new Response();
        $response->setContent($json);
        $response->headers->set("Content-Type","application/json");

        return $response;
    }
}