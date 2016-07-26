<?php

namespace AppBundle\Controller;

use BackendBundle\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BackendBundle\Entity\User;
use BackendBundle\Entity\Video;
use Symfony\Component\Validator\Constraints\Date;

class CommentController extends Controller
{

    /**
     * @Route("/videos/{id_video}/comments", name="new_comment")
     * @Method({"POST"})
     */
    public function newAction(Request $request,$id_video)
    {
        $helpers = $this->get("app.helpers");
        $hash = $request->get("authorization", null);
        $auth_check = $helpers->auth_check($hash, true);

        //check el token para ver si el token contiene los datos decodificados o no existe dicho token
        if ($auth_check) {
            $json = $request->get("json",null);
            if($json != null){
                $params = json_decode($json);
                $created_at = new \DateTime("now");
                $user_id = $auth_check->sub;
                //$video_id = (isset($params->video_id)) ? $params->video_id : null;
                $body = (isset($params->body)) ? $params->body : null;

                if($user_id && $id_video ){
                    $user  = $this->getDoctrine()
                        ->getRepository('BackendBundle:User')
                        ->findOneBy(
                            array(
                                'id' => $user_id
                            )
                        );

                    $video  = $this->getDoctrine()
                        ->getRepository('BackendBundle:Video')
                        ->findOneBy(
                            array(
                                'id' => $id_video
                            )
                        );
                    $comment = new Comment();
                    $comment->setUser($user);
                    $comment->setVideo($video);
                    $comment->setBody($body);
                    $comment->setCreatedAt($created_at);

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();

                    $data = array(
                        "status" => "Success",
                        "code"  => 201,
                        "message" => "Comentario creado correctamente"
                    );

                }else{
                    $data = array(
                        "status" => "Error",
                        "code"  => 400,
                        "message" => "Comentario no creado"
                    );

                }

            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 400,
                    "message" => "Comentario no creado. Missing parameters"
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
     * @Route("/videos/{id_video}/comments/{id_comment}/delete", name="delete_comment")
     * @Method({"POST"})
     */
    public function deleteAction(Request $request, $id_video, $id_comment)
    {
        $helpers = $this->get("app.helpers");
        $hash = $request->get("authorization", null);
        $auth_check = $helpers->auth_check($hash, true);

        //check el token para ver si el token contiene los datos decodificados o no existe dicho token
        if ($auth_check) {
            $user_id = $auth_check->sub;
            if($user_id != null && $id_comment != null){
                $comment = $this->getDoctrine()
                    ->getRepository('BackendBundle:Comment')
                    ->findOneBy(
                        array(
                            'id' => $id_comment
                        )
                    );

                //Solo puede borrar el comentario el dueño del video o el usuario que ha escrito
                // el comentario
                if(is_object($comment)){
                    if(isset($auth_check->sub) && ($auth_check->sub == $comment->getUser()->getId()
                            || $auth_check->sub == $comment->getVideo()->getUser()->getId())){

                        $em = $this->getDoctrine()->getManager();
                        $em->remove($comment);
                        $em->flush();

                        $data = array(
                            "status" => "Success",
                            "code"  => 200,
                            "message" => "Comentario eliminado con exito"
                        );
                    }else{
                        $data = array(
                            "status" => "Error",
                            "code"  => 400,
                            "message" => "No tienes permisos para borrar el comentario"
                        );
                    }



                }else{
                    $data = array(
                        "status" => "Error",
                        "code"  => 400,
                        "message" => "Error borrando comentario"
                    );
                }
            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 400,
                    "message" => "Error borrando comentario"
                );
            }
        }
        else{
            $data = array(
                "status" => "Error",
                "code"  => 400,
                "message" => "Autorizacion incorrecta"
            );
        }
        return $helpers->json($data);
    }
}
