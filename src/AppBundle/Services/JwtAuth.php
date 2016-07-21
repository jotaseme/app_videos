<?php

namespace AppBundle\Services;

use Firebase\JWT\JWT;

class JwtAuth
{
    public $manager;
    public $key = "clave_secreta";

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function signup($email,$password,$getHash=null)
    {
        $key = $this->key;

        $user = $this->manager->getRepository('BackendBundle:User')
            ->findOneBy(
                array(
                    "email"=>$email,
                    "password"=>$password
                )
            );

        $auth = false;
        if(is_object($user)){
            $auth = true;
        }
        if($auth){
            $token = array(
                "sub" => $user->getId(),
                "email" => $user->getEmail(),
                "name" => $user->getName(),
                "password" => $user->getPassword(),
                "image" => $user->getImage(),
                "iat" => time(),
                "exp" => time() + (7 * 24 * 60 * 60)
            );

            $jwt = JWT::encode($token,$key, 'HS256');
            $decode = JWT::decode($jwt,$key, array('HS256'));

            if($getHash != null){
                return $jwt;
            }else{
                return $decode;
            }
        }else{
            return array("status" => "error","data"=>"Validacion fallida");
        }
    }

    public function check_token($jwt, $getIdentity = false)
    {
        $key = $this->key;
        $auth = false;

        try{
            $decoded = JWT::decode($jwt,$key, array('HS256'));

        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }

        if(isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }

        if($getIdentity){
            return $decoded;
        }else{
            return $auth;
        }
    }

}