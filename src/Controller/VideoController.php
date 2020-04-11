<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\User;
use App\Entity\Video;
use App\services\JwtAuth;

class VideoController extends AbstractController
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
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }

    public function create(Request $solicitud, JwtAuth $jwt, $id= null){
     
        //recoger token 
        $token = $solicitud->headers->get('Authorization', null);

        //chequear token

        $check_token = $jwt->check_token($token);

      
        if($check_token){
        //recoger datos json

        $json = $solicitud->get('json', null);

        $json_array = json_decode($json);

        //comprobar usuario
        $identity = $jwt->check_token($token, true);
        
        

         $data = [
            'status' => 'error',
            'code' => 404,
            'mensaje' => 'no se pudo crear el videoo',
            'token' => $json_array

        ];
         
        //comprobar y validar datos
        if(!empty($json)){

            $user_id = (!empty($identity->sub))?$identity->sub: null;
            $title = (!empty($json_array->title))? $json_array->title: null;
            $description = (!empty($json_array->description))? $json_array->description: null;
            $url = (!empty($json_array->url))? $json_array->url: null;

            if(!empty($user_id) && !empty($title)){
                $em = $this->getDoctrine()->getManager();

                $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                    'id' => $user_id
                ]); 
                //si no hay id en parametro guardar
                if($id == null){
                    //guardar el video
                    $video = new Video();
                    $video->setUser($user);
                    $video->setTitle($title);
                    $video->setDescription($description);
                    $video->setUrl($url);
                    $video->setStatus('Normal xd');

                    $createdAt =  new \DateTime('now');
                    $updatedAt =  new \DateTime('now'); 
                    $video->setCreatedAt( $createdAt);
                    $video->setUpdatedAt($updatedAt);

                    //dar respuesta

                    $em->persist($video);
                    $em->flush();


                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'mensaje' => 'se ha creado el video',
                        'video' => $video
                    ];
                }else{
                    $video= $this->getDoctrine()->getRepository(Video::class)->findOneBy([
                        'id' => $id,
                        'user' => $identity->sub
                    ]);

                    if($video && is_object($video)){
                        $video->setUser($user);
                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setUrl($url);  
                        $updatedAt =  new \DateTime('now');
                        $video->setUpdatedAt($updatedAt);

                        $em->persist($video);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'mensaje' => 'se ha actualizado el video',
                            'video' => $video
                        ];

                    }


                }                
            }
        }

       
        }
        return $this->resjson($data);
    }

    public function list_videos(Request $solicitud, JwtAuth $jwt, PaginatorInterface $paginador){

        //recoger la cabecera de autenticacion
        $token = $solicitud->headers->get('Authorization');
        //comprobar token
        $check_token = $jwt->check_token($token);

        //si es valido
        if($check_token){
        //obtener el identity
        $identity = $jwt->check_token($token, true);

        $em = $this->getDoctrine()->getManager();


        //consulta para paginar
        $dql = "SELECT v from App\Entity\Video v where v.user = {$identity->sub} ORDER BY v.id DESC ";
        $query = $em->createQuery($dql);    
        //recoger parametro paginador
         $page = $solicitud->query->getInt('page', 1);  
         $item_per_page = 5; 


        //invocar paginacion
        $paginator = $paginador->paginate($query, $page, $item_per_page);
        $total = $paginator->getTotalItemCount();
        //alistar respuesta y devolver

        //si falla responder

        $data = [
            'status' => 'success',
            'code' => 200,
            'mensaje' => 'se cargaron los datos',
            'total_item' => $total,
            'pagina' => $page,
            'item_por_pagina' => $item_per_page,
            'total_pages' => ceil($total/$item_per_page),
            'videos' => $paginator,
            'user' => $identity->sub
        ];

        
        }else{
            $data = [
                'status' => 'error',
                'code' => 404,
                'mensaje' => 'fallo la cabecera de autenticacion '
            ];
        }
        return $this->resjson($data);
    }

    public function video_find(Request $solicitud, JwtAuth $jwt, $id=null){

        $token = $solicitud->headers->get('Authorization');
        //comprobar token
        $check_token = $jwt->check_token($token);
        
        //respuesta default
        $data = [
            'status' => 'error',
            'code' => 404,
            'mensaje' => 'no se encuentra el video '
        ];

        if($check_token){

         //chequear la identidad
         $identity = $jwt->check_token($token, true);

        //buscar el video segun la id
         $video = $this->getDoctrine()->getRepository(Video::class)->findOneBy([
             'id' => $id
         ]);
            //comprobar si el video existe y es propiedad del usuario
         if($video && is_object($video) && $identity->sub == $video->getUser()->getId()){

            //respuesta
            $data = [
                'status' => 'success',
                'code' => 200,
                'video' => $video
            ];

         }
        
        }    

        return $this->resjson($data);
    }

    public function remove(Request $solicitud, JwtAuth $jwt, $id=null){

        $token = $solicitud->headers->get('Authorization');
        //comprobar token
        $check_token = $jwt->check_token($token);

        //respuesta default
        $data = [
            'status' => 'error',
            'code' => 404,
            'mensaje' => 'no se encuentra el video '
        ];

        if ($check_token) {

            //chequear la identidad
            $identity = $jwt->check_token($token, true);

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            $video = $doctrine->getRepository(Video::class)->findOneBy([    
                'id' =>$id
            ]);

            if($video && is_object($video) && $identity->sub == $video->getUser()->getId()){

                $em->remove($video);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'mensaje' => 'se ha eliminado el video',
                    'video' => $video
                ];
            }
        }

        return $this->resjson($data);
    }


}
