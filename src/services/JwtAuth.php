<?php

namespace App\services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth{

    public $manager;
    public $key;
    public function __construct($manager){
        $this->manager = $manager;
        $this->key = 'Esta_es_una_clave_ultrasecreta_992500_yugioh';
    }
    public function signup($email, $password, $gettoken = null){
        //comprobar si existe el usuario

        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);

        $signup= false;

        //si existe generar token de jwt
        if(is_object($user)){
            $signup = true;
            if($signup){
                $token = [
                    'sub' => $user->getId(),
                    'name' => $user->getName(),
                    'surname' => $user->getSurname(),
                    'email' => $user->getEmail(),
                    'iat' => time(),
                    'exp' => time()+ (1 * 24 *60 * 60)
                ];
                //comprobar si esta el gettoken
                $jwt = JWT::encode($token,$this->key, 'HS256');
                if($gettoken){
                    
                    $data = $jwt;
                }else{
                    $decodificado = JWT::decode($jwt,$this->key, ['HS256']);
                    $data = $decodificado;
                }
                
            }          
        }else{
            $data = [
                'status' => 'error',
                'mensaje' => 'no se pudo loguear'
            ];
        }
        ///devolver token
        return $data;

    }

    public function check_token($jwt, $identity = false){
        try {
            $decoded = JWT::decode($jwt, $this->key, ['HS256']); 
        } catch (\UnExpectedValueException $e) {
            $auth = false;
        }catch (\DomainException $e) {
            $auth = false;
        } 

        if($decoded && !empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }

        if($identity != false){
            return $decoded;
        }else{
            return $auth;
        }
    }
}