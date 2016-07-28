<?php

namespace AppBundle\Controller;

use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends Controller
{

    /**
     * @Route("/usuarios", name="new_usuarios")
     * @Method({"POST"})
     */
    public function newAction(Request $request)
    {
        $helpers=$this->get("app.helpers");
        $json = $request->get("json",null);
        $jwt_auth = $this->get("app.jwt_auth");

        $params = json_decode($json);

        if($json != null){
            $created_at = new \DateTime("now");
            $image = null;
            $role = "1";
            $email = (isset($params->email)) ? $params->email : null;
            $name = (isset($params->name)) ? $params->name : null;
            $password = (isset($params->password)) ? $params->password : null;

            $email_constraint = new Assert\Email();
            $email_constraint->message = "Formato de correo invalido";
            $email_validator = $this->get("validator")->validate($email, $email_constraint);

            if($email != null && count($email_validator) ==0 && $password != null && $name != null){
                $user = new User();
                $user->setCreatedAt($created_at);
                $user->setEmail($email);
                $user->setName($name);
                $user->setImage($image);
                $user->setRole($role);

                //Cifrar password
                $pass = hash('sha256', $password);
                $user->setPassword($pass);

                $isset_user  = $this->getDoctrine()
                    ->getRepository('BackendBundle:User')
                    ->findBy(
                        array(
                            'email' => $email
                        )
                    );
                if(count($isset_user)==0){
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();
                    $user_identity = $jwt_auth->signup($email,$pass);
                    $user_token = $jwt_auth->signup($email,$pass,true);
                    $data = array(
                        "status" => "Success",
                        "code"  => 201,
                        "token" => $user_token,
                        "identity" => new JsonResponse($user_identity),
                        "message" => "Usuario creado con exito"
                    );
                }else{
                    $data = array(
                        "status" => "Error",
                        "code"  => 400,
                        "message" => "El email de registro ya existe"
                    );
                }
            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 400,
                    "message" => "Usuario no creado"
                );
            }


        }else{
            $data = array(
                "status" => "Error",
                "code"  => 400,
                "message" => "Usuario no creado"
            );
        }
        return $helpers->json($data);
    }


    /**
     * @Route("/usuario", name="update_usuario")
     * @Method({"POST"})
     */
    public function updateAction(Request $request)
    {
        $helpers=$this->get("app.helpers");
        $jwt_auth = $this->get("app.jwt_auth");

        $hash =  $request->get("authorization", null);
        $auth_check = $helpers->auth_check($hash,true);

        //check el token para ver si el token contiene los datos decodificados o no existe dicho token
        if($auth_check){
            $user  = $this->getDoctrine()
                ->getRepository('BackendBundle:User')
                ->findOneBy(
                    array(
                        'id' => $auth_check->sub
                    )
                );

            $json = $request->get("json",null);
            $params = json_decode($json);

            if($json != null){
                $updated_at = new \DateTime("now");
                $image = null;
                $role = "1";
                $email = (isset($params->email)) ? $params->email : null;
                $name = (isset($params->name)) ? $params->name : null;
                $password = (isset($params->password)) ? $params->password : null;

                $email_constraint = new Assert\Email();
                $email_constraint->message = "Formato de correo invalido";
                $email_validator = $this->get("validator")->validate($email, $email_constraint);

                if($email != null && count($email_validator) == 0 && $name != null){

                    $user->setUpdatedAt($updated_at);
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setImage($image);
                    $user->setRole($role);

                    if($password != null && !empty($password))
                    {
                        //Cifrar password
                        $pass = hash('sha256', $password);
                        $user->setPassword($pass);
                    }

                    $isset_user  = $this->getDoctrine()
                        ->getRepository('BackendBundle:User')
                        ->findBy(
                            array(
                                'email' => $email
                            )
                        );

                    // Update valido si el nuevo correo introducido no existe
                    // O si no se ha introducido ningun correo y por defecto se pasa el email que ya se tenia
                    if(count($isset_user)==0 || $auth_check->email == $email){
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($user);
                        $em->flush();
                        $user_identity = $jwt_auth->signup($email,$user->getPassword());
                        $user_token = $jwt_auth->signup($email,$user->getPassword(),true);
                        $data = array(
                            "status" => "Success",
                            "code"  => 200,
                            "identity" => new JsonResponse($user_identity),
                            "token" => $user_token,
                            "message" => "Usuario actualizado con exito"
                        );
                    }else{
                        $data = array(
                            "status" => "Error",
                            "code"  => 400,
                            "message" => "Usuario no actualizado, el email de registro introducido ya existe"
                        );
                    }
                }else{
                    $data = array(
                        "status" => "Error",
                        "code"  => 400,
                        "message" => "Usuario no actualizado. Datos no validos"
                    );
                }
            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 400,
                    "message" => "Usuario no actualizado"
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
     * @Route("/usuario/images", name="usuario/images")
     * @Method({"POST"})
     */
    public function uploadImageAction(Request $request)
    {
        $helpers=$this->get("app.helpers");

        $hash =  $request->get("authorization", null);
        $auth_check = $helpers->auth_check($hash,true);

        //check el token para ver si el token contiene los datos decodificados o no existe dicho token
        if($auth_check){
            $user  = $this->getDoctrine()
                ->getRepository('BackendBundle:User')
                ->findOneBy(
                    array(
                        'id' => $auth_check->sub
                    )
                );
            // Upload foto perfil usuario
            $file = $request->files->get('image');

            if(!empty($file) && $file != null){
                $extension  = $file->guessExtension();

                if($extension == "jpeg" || $extension == "png" || $extension == "jpg" ||
                    $extension == "gif"){
                    $file_name = time() . "." . $extension;
                    $file->move("uploads/users/", $file_name);

                    $user->setImage($file_name);
                    $user->setUpdatedAt(new \DateTime("now"));

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();
                    $data = array(
                        "status" => "Success",
                        "code"  => 200,
                        "message" => "Imagen usuario actualizada con exito"
                    );
                }else{
                    $data = array(
                        "status" => "Error",
                        "code"  => 400,
                        "message" => "Extension no soportada"
                    );
                }
            }else{
                $data = array(
                    "status" => "Error",
                    "code"  => 400,
                    "message" => "Imagen no valida"
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
     * @Route("/channel/{id_usuario}", name="channel")
     * @Method({"GET"})
     */
    public function showAction(Request $request, $id_usuario)
    {
        $helpers = $this->get("app.helpers");
        $user  = $this->getDoctrine()
            ->getRepository('BackendBundle:User')
            ->findOneBy(
                array(
                    'id' => $id_usuario
                )
            );
        if($user){
            $em = $this->getDoctrine()->getManager();
            $dql = "SELECT v FROM BackendBundle:Video v ".
                "WHERE v.user = $id_usuario ORDER BY v.id DESC";
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
                "code"  => 200,
                "message" => "Listado de videos correcto"
            );
            $data["data"]["videos"]=$pagination;
            $data["data"]["user"]=$user;
        }else{
            $data = array(
                "status" => "Error",
                "code"  => 400,
                "message" => "Usuario no encontrado"
            );
        }
        return $helpers->json($data);
    }


}
