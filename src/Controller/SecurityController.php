<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    public $em;
    public $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }
    
    #[Route('/signup', name: 'signup', methods: ['POST'])]
    public function signup(Request $request){
        $user = new User();

        $username = $request->get('username');
        if (is_null($username) || empty($username)) {
            return new JsonResponse('Username cannot be blank', Response::HTTP_BAD_REQUEST);
        }

        $found = $this->em->getRepository(User::class)->findOneBy([
            "username" => $username
        ]);
        if ($found) {
            return new JsonResponse('Username already used!', Response::HTTP_BAD_REQUEST);
        }

        $user->setUsername($username);

        $password = $request->get('password');
        if (is_null($password) || empty($password)) {
            return new JsonResponse('Password cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $user->setRoles(['USER']);

        $this->em->persist($user);
        $this->em->flush();
        
        return new JsonResponse(['code' => 200, 'message' => "User with username '".$request->get('username')."' was created successfully!"], Response::HTTP_OK);
    }
}