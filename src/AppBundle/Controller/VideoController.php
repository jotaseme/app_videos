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
}
