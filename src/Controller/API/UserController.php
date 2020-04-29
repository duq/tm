<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class UserController extends AbstractController
{
    private $userRepository;
    private $passwordEncoder;
    private $userService;

    public function __construct(UserService $userService, UserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->userService = $userService;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @Route("/users", name="users", methods={"GET"})
     */
    public function index(): JsonResponse
    {
        $users = $this->userRepository->getAllUsers();

        return new JsonResponse($users, 200);
    }

    /**
     * @Route("/users/{id}", name="users.show", methods={"GET"})
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->getUserById($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 400);
        }
        $userData = $this->userService->transformUser($user);

        return new JsonResponse($userData, 200);
    }

    /**
     * @Route("/users", name="users.store", methods={"POST"})
     */
    public function store(Request $request)
    {
        /*
         * Should probably validate the request data before processing...
         */

        $user = new User();
        $user->setEmail($request->get('email'));
        $user->setPassword($this->passwordEncoder->encodePassword($user, $request->get('password')));
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'message' => 'User created'
        ],
            204);
    }

    /**
     * @Route("/users/{id}", name="users.update", methods={"PUT"})
     */
    public function update(int $id, Request $request)
    {
        $user = $this->userRepository->getUserById($id);

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 400);
        }

        if ($request->get('password')) {
            $user->setPassword($this->passwordEncoder->encodePassword($user, $request->get('password')));
        }

        if ($request->get('email')) {
            $user->setEmail($request->get('email'));
        }

        return new JsonResponse([
            'message' => 'User updated'
        ], 204);
    }

    /**
     * @Route("/users/{id}", name="users.delete", methods={"DELETE"})
     */
    public function delete(int $id)
    {
        $userId = $id;

        $user = $this->userRepository->find($userId);

        if ($user) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();


            return new JsonResponse([
                'message' => 'User deleted'
            ], 204);
        }
        return new JsonResponse([
            'error' => 'User not found'
        ], 400);
    }
}
