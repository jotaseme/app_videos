<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


use Symfony\Component\Validator\Constraints as Assert;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }


    /**
     * @Route("/login", name="login")
     * @Method({"POST"})
     */
    public function loginAction(Request $request)
    {
        $helpers=$this->get("app.helpers");
        $jwt_auth = $this->get("app.jwt_auth");

        $json = $request->get("json",null);

        if($json!=null){
            $params = json_decode($json);

            $email = (isset($params->email)) ? $params->email : null;
            $password = (isset($params->password)) ? $params->password : null;
            $getHash = (isset($params->getHash)) ? $params->getHash : null;

            $email_constraint = new Assert\Email();
            $email_constraint->message = "Formato de correo invalido";

            $email_validator = $this->get("validator")->validate($email, $email_constraint);

            if((count($email_validator) == 0) && ($password != null)){
                if($getHash==null){
                    $signup = $jwt_auth->signup($email,$password);
                }else{
                    $signup = $jwt_auth->signup($email,$password,true);

                }
                return new JsonResponse($signup);
            }else{
                return $helpers->json(
                    array(
                        "status"=>"error",
                        "data"=>"Validacion incorrecta"
                    )
                );
            }
        }else{
            return $helpers->json(
                array(
                    "status"=>"error",
                    "data"=>"Validacion incorrecta"
                )
            );
        }
    }

    /**
     * @Route("/pruebas", name="pruebas")
     * @Method({"POST"})
     */
    public function pruebasAction(Request $request)
    {
        $helpers = $this->get("app.helpers");


        //$users = $this->getDoctrine()
        //   ->getRepository('BackendBundle:User')
        //    ->findAll();

        $hash = $request->get("authorization",null);
        $check = $helpers->auth_check($hash);

        var_dump($check);die;
        //return $helpers->json($users);

    }

}
