<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BackendBundle\Entity\User;
use BackendBundle\Entity\Video;
use Symfony\Component\Validator\Constraints\Date;

class VideoController extends Controller
{
    /**
     * @Route("/videos", name="new_videos")
     * @Method({"POST"})
     */
    public function newAction(Request $request)
    {
        $helpers=$this->get("app.helpers");

        $hash =  $request->get("authorization", null);
        $auth_check = $helpers->auth_check($hash,true);

        //check el token para ver si el token contiene los datos decodificados o no existe dicho token
        if($auth_check){
            $json = $request->get("json",null);
            $params = json_decode($json);
            if($json != null){
                $created_at = new \DateTime("now");
                $updated_at = new \DateTime("now");
                $image = null;
                $video_path = null;
                $user_id = $auth_check->sub;
                $title = (isset($params->title)) ? $params->title : null;
                $description = (isset($params->description)) ? $params->description : null;
                $status = (isset($params->status)) ? $params->status : null;

                if($user_id != null && $title != null){
                    $user  = $this->getDoctrine()
                        ->getRepository('BackendBundle:User')
                        ->findOneBy(
                            array(
                                'id' => $user_id
                            )
                        );
                    $video = new Video();
                    $video->setUser($user);
                    $video->setTitle($title);
                    $video->setDescription($description);
                    $video->setStatus($status);
                    $video->setCreatedAt($created_at);
                    $video->setUpdatedAt($updated_at);

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($video);
                    $em->flush();

                    // Consulta a la base de datos para obtener el video
                    $video  = $this->getDoctrine()
                        ->getRepository('BackendBundle:Video')
                        ->findOneBy(
                            array(
                                'user' => $user,
                                'createdAt' => $created_at
                            )
                        );
                    $data = array(
                        "status" => "Success",
                        "code"  => 200,
                        "data" => $video
                    );
                }else{
                    $data = array(
                        "status" => "Error",
                        "code"  => 400,
                        "message" => "Video no creado. Falta titulo"
                    );
                }

            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 400,
                    "message" => "Video no creado. Missing parameters"
                );
            }
        }else{
            $data = array(
                "status" => "Error",
                "code"  => 400,
                "message" => "Autorizacion incorrecta"
            );
        }
        return $helpers->json($data);
    }


    /**
     * @Route("/video/{id_video}", name="update_video")
     * @Method({"POST"})
     */
    public function updateAction(Request $request, $id_video = null)
    {
        $helpers = $this->get("app.helpers");

        $hash = $request->get("authorization", null);
        $auth_check = $helpers->auth_check($hash, true);

        //check el token para ver si el token contiene los datos decodificados o no existe dicho token
        if($auth_check){
            $json = $request->get("json",null);
            $params = json_decode($json);
            if($json != null){
                $updated_at = new \DateTime("now");
                $image = null;
                $video_path = null;
                $user_id = $auth_check->sub;
                $title = (isset($params->title)) ? $params->title : null;
                $description = (isset($params->description)) ? $params->description : null;
                $status = (isset($params->status)) ? $params->status : null;

                if($user_id != null && $title != null){
                    $video  = $this->getDoctrine()
                        ->getRepository('BackendBundle:Video')
                        ->findOneBy(
                            array(
                                'id' => $id_video
                            )
                        );
                    if(!empty($video)){
                        if(isset($auth_check->sub) && $auth_check->sub == $video->getUser()->getId()){
                            $video->setTitle($title);
                            $video->setDescription($description);
                            $video->setStatus($status);
                            $video->setUpdatedAt($updated_at);

                            $em = $this->getDoctrine()->getManager();
                            $em->persist($video);
                            $em->flush();

                            $data = array(
                                "status" => "Success",
                                "code"  => 201,
                                "message" => "Video actualizado correctamente"
                            );
                        }else{
                            $data = array(
                                "status" => "Error",
                                "code"  => 400,
                                "message" => "Video no actualizado. Error de autenticacion de usuario"
                            );
                        }
                    }else{
                        $data = array(
                            "status" => "Error",
                            "code"  => 404,
                            "message" => "Video no encontrado"
                        );
                    }

                }else{
                    $data = array(
                        "status" => "Error",
                        "code"  => 400,
                        "message" => "Video no actualizado. Falta titulo"
                    );
                }

            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 400,
                    "message" => "Video no actualizado. Missing parameters"
                );
            }
        }else{
            $data = array(
                "status" => "Error",
                "code"  => 400,
                "message" => "Autorizacion incorrecta"
            );
        }
        return $helpers->json($data);
    }

    /**
     * @Route("/videos/{id_video}/upload_image", name="upload_image")
     * @Route("/videos/{id_video}/upload_video", name="upload_video")
     * @Method({"POST"})
     */
    public function uploadAction(Request $request, $id_video)
    {
        $helpers = $this->get("app.helpers");
        $hash = $request->get("authorization");
        $auth_check = $helpers->auth_check($hash, true);

        //check el token para ver si el token contiene los datos decodificados o no existe dicho token
        if($auth_check){
            $video  = $this->getDoctrine()
                ->getRepository('BackendBundle:Video')
                ->findOneBy(
                    array(
                        'id' => $id_video
                    )
                );
            if(!empty($video) && $auth_check->sub == $video->getUser()->getId()){
                $file_image = $request->files->get('image',null);
                $file_video = $request->files->get('video',null);
                $updated_at = new \DateTime("now");
                if($file_image != null && !empty($file_image)){
                    $extension  = $file_image->guessExtension();
                    if($extension == "jpeg" || $extension == "png" || $extension == "jpg" ||
                        $extension == "gif"){
                        $file_name = time() . "." . $extension;
                        $file_image->move("uploads/video_images/video_" . $id_video, $file_name);
                        $video->setImage($file_name);
                        $video->setUpdatedAt($updated_at);
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($video);
                        $em->flush();

                        $data = array(
                            "status" => "Success",
                            "code"  => 200,
                            "message" => "Imagen actualizada con exito"
                        );
                    }else{
                        $data = array(
                            "status" => "Error",
                            "code"  => 400,
                            "message" => "Extension de imagen no soportada"
                        );
                    }
                }else{
                    if($file_video != null && !empty($file_video)){
                        $extension  = $file_video->guessExtension();
                        if($extension == "mp4" || $extension == "avi" || $extension == "wmv"){
                            $file_name = time() . "." . $extension;
                            $file_video->move("uploads/video_files/video_" . $id_video, $file_name);
                            $video->setVideoPath($file_name);
                            $video->setUpdatedAt($updated_at);
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($video);
                            $em->flush();

                            $data = array(
                                "status" => "Success",
                                "code"  => 200,
                                "message" => "Video actualizado con exito"
                            );
                        }else{
                            $data = array(
                                "status" => "Error",
                                "code"  => 400,
                                "message" => "Extension de video no soportada"
                            );
                        }
                    }else{
                        $data = array(
                            "status" => "Error",
                            "code"  => 400,
                            "message" => "No se ha adjuntado ningun fichero"
                        );
                    }
                }
            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 404,
                    "message" => "Video no encontrado"
                );
            }
        }else{
            $data = array(
                "status" => "Error",
                "code"  => 400,
                "message" => "Autorizacion incorrecta"
            );
        }
        return $helpers->json($data);
    }

    /**
     * @Route("/videos", name="videos"))
     * @Method({"GET"})
     */
    public function showAction(Request $request)
    {
        $helpers = $this->get("app.helpers");

        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";
        $query = $em->createQuery($dql);
        $page = $request->query->getInt("page",1);
        $paginator = $this->get("knp_paginator");
        $items_per_page = 6;

        $pagination = $paginator->paginate($query,$page,$items_per_page);
        $total_items_cont = $pagination->getTotalItemCount();

        $data = array(
            "status" => "Success",
            "total_items_count" => $total_items_cont,
            "page_actual" => $page,
            "items_per_page" => $items_per_page,
            "total_pages" => ceil($total_items_cont / $items_per_page),
            "data" => $pagination,
            "code"  => 400,
            "message" => "Autorizacion incorrecta"
        );

        return $helpers->json($data);
    }



}
