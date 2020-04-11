<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use App\Entity\User;
use App\Entity\Video;
use App\services\JwtAuth;

class UserController extends AbstractController
{
   private function resjson($dati){
       //  serializar datos con serializer
        $json = $this->get('serializer')->serialize($dati, 'json');
         //response http foundation
        $response = new Response();
        //asignar contenido de resppuesto
        $response->setContent($json);
         //formato respuesta
        $response->headers->set('Content-Type', 'application/json');
         //devolver respuesta
        return $response;
    }
    public function index()
    {
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $video_repo = $this->getDoctrine()->getRepository(Video::class);

        $users = $user_repo->findAll();
        $videos = $video_repo->findAll();


        return $this->resjson($videos);
    }

    public function register(Request $solicitud){

        //recoger datos json
        $datos = $solicitud->get('json', null);

        //decodificar json
        $json_decodificado = json_decode($datos);

        //respuesta defecto
        $data = [
            'status' => 'error',
            'code' => 404,
            'mensaje' => 'no se pudo crear el usuario'
        ];

        //comprobar y validar datos
        if($json_decodificado != null){
            $name = (!empty($json_decodificado->name))? $json_decodificado->name: null;
            $surname = (!empty($json_decodificado->surname))? $json_decodificado->surname: null;
            $email = (!empty($json_decodificado->email))? $json_decodificado->email: null;
            $password = (!empty($json_decodificado->password))? $json_decodificado->password: null;

            $validador = Validation::createvalidator();
            $validar_email = $validador->validate($email, [
                new Email()
            ]);

            if(!empty($email) && count($validar_email) == 0 && !empty($password) 
            && !empty($name) && !empty($surname)){

                    $user = new User();
                    $user->setName($name);
                    $user->setSurname($surname);
                    $user->setEmail($email);
                    $user->setRole('Role_user');
                    $user->setCreatedAt(new \Datetime('now'));
                    //cifrar clave
                    $pwd = hash('sha256', $password);
                    $user->setPassword($pwd);

                    //chequear si existe usuario
                    $doctrine =$this->getDoctrine();
                    $em = $doctrine->getManager();

                    $user_repo = $doctrine->getRepository(User::class);

                    $isset_repot = $user_repo->findBy(array(
                        'email' => $email
                    ));

                    //si no guardar 
                    if(count($isset_repot) == 0){
                        $em->persist($user);
                        $em->flush();

                    //respuesta en json
                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'mensaje' => 'usuario guardado exitosamente',
                        'user' => $user
                    ];                        
                    }else{
                        $data = [
                            'status' => 'error',
                            'code' => 400,
                            'mensaje' => 'ya existe un usuario con ese correo'
                        ];
                    }                

                  
            }else{
                $data = [
                    'status' => 'error',
                    'code' => 500,
                    'mensaje' => 'los datos no se han podido validar'
                ];
            }
        }else{
            $data = [
                'status' => 'error',
                'code' => 404,
                'mensaje' => 'envia bien los datos'
            ];
        }

      
        return new JsonResponse($data);
    }

    public function login(Request $solicitud, JwtAuth $jwt){
        //recibir datos por post
        $json = $solicitud->get('json', null);

        $json_array = json_decode($json);

        //array para devolver
    

        //comprobar datos 
        if($json != null){
            $email = (!empty($json_array->email))? $json_array->email: null;
            $password = (!empty($json_array->password))? $json_array->password: null;
            $gettoken = (!empty($json_array->gettoken))? $json_array->gettoken: null;

            
            $validador = Validation::createvalidator();
            $validar_email = $validador->validate($email, [
                new Email()
            ]);

            if(!empty($email) && !empty($password) && count($validar_email) == 0){
                //cifrar contraseña 
                $pwd = hash('sha256', $password);

                //llamar al servicio jwt-php seguun si hay token
                if($gettoken){
                    $signup = $jwt->signup($email, $pwd, $gettoken);
                }else{
                    $signup = $jwt->signup($email, $pwd);
                }
                $data = $signup;

            }else{
                $data = [
                    'status' => 'error',
                    'code' => 404,
                    'mensaje' => 'no se paso la validacion'
                ];
            }
        }else{
            $data = [
                'status' => 'error',
                'code' => 500,
                'mensaje' => 'por favor envia bien los datos'
            ];
        }

      
        //respuesta en json
        return new JsonResponse($data);
    }

    public function edit(Request $solicitud, JwtAuth $jwt){
      
        //recoger cabecera
        $token = $solicitud->headers->get('Authorization');      

        //crear metodo para ver si login es correcto
        $authcheck = $jwt->check_token($token);

       
        $data = [
            'status' => 'error',
            'code' => 400,
            'mensaje' => 'Usuario no actualizado',
            'respuesta' => $authcheck
        ];
        //si es correcto hacer actualizacion del usuario
        if($authcheck){
            //Actualizar el usuario

            //conseguir entity manager
            $em = $this->getDoctrine()->getManager();

            //conseguir datos usuario
            $identity = $jwt->check_token($token, true);

            //conseguir datos del usuario a actualizar completo
            $user_repo = $this->getDoctrine()->getRepository(User::class);
            $user = $user_repo->findOneBy ([
                'id' => $identity->sub
            ]);
            //recoger datos 
            $json = $solicitud->get('json', null);
            $json_array = json_decode($json);
            //comprobar y validar los datos
            $name = (!empty($json_array->name))? $json_array->name: null;
            $surname = (!empty($json_array->surname))? $json_array->surname: null;
            $email = (!empty($json_array->email))? $json_array->email: null;

             
            $validador = Validation::createvalidator();
            $validar_email = $validador->validate($email, [
                new Email()
            ]);

            if(!empty($email) && count($validar_email) == 0 && !empty($name) && !empty($surname)){
                //asginar datos del nuevo usuario
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                //comprobar duplicados 
                $isset_user = $user_repo->findBy ([
                    'email' => $email
                ]);
                if(count($isset_user) == 0 || $identity->email == $email){

                //guardar cambios
                $em->persist($user);
                $em->flush();
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'mensaje' => 'usuario actualizado exitosamente',
                    'user' => $user
                ];

                }else{
                    $data = [
                        'status' => 'error',
                        'code' => 400,
                        'mensaje' => 'el correo ya esta siendo usado por otro usuario'
                    ];
                }
            }else{
                $data = [
                    'status' => 'error',
                    'code' => 400,
                    'mensaje' => 'los datos no han sido enviado bien'
                ];
            }
           
        }
       
        return $this->resjson($data);
    }
}
