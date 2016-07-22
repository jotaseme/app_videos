<?php

namespace AppBundle\Controller;

use BackendBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

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
                    $data = array(
                        "status" => "Success",
                        "code"  => 201,
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
     * @Route("/usuarios", name="get_usuarios")
     * @Method({"GET"})
     */
    public function getUsuariosAction()
    {
        echo "Hello get world!";die();
    }
}
