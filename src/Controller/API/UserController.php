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
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $users = $this->userRepository->getAllUsers();

        return new JsonResponse($users, 200);
    }

    /**
     * @Route("/users/{id}", name="users.show", methods={"GET"})
     */
    public function show(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $data = \json_decode($request->getContent(), true);

        $userId = $data['user_id'];

        if (!$userId) {
            return new JsonResponse(['message' => 'User not found'], 400);
        }
        $user = $this->userRepository->getUserById($userId);

        $userData = $this->userService->transformUser($user);

        return new JsonResponse($userData, 200);
    }

    /**
     * @Route("/users", name="users.store", methods={"POST"})
     */
    public function store(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /*
         * Should probably validate the request data before processing...
         */

        $data = \json_decode($request->getContent(), true);

        $email = $data['email'];
        $password = $data['password'];

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $userData = $this->userService->transformUser($user);

        return new JsonResponse([
            'message' => 'User created',
            'data' => $userData
        ],
            204);
    }

    /**
     * @Route("/users/{id}", name="users.update", methods={"PUT"})
     */
    public function update(int $id, Request $request)
    {
        /*
         * Should validate data before processing...
         */

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $data = \json_decode($request->getContent(), true);

        $password = $data['password'] ?? null;
        $email = $data['email'] ?? null;

        $user = $this->userRepository->getUserById($id);
        $em = $this->getDoctrine()->getManager();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 400);
        }

        if ($password) {
            $user->setPassword($this->passwordEncoder->encodePassword($user, $password));
        }

        if ($email) {
            $user->setEmail($email);
        }

        $em->persist($user);
        $em->flush();

        $updatedUser = $this->userService->transformUser($user);

        return new JsonResponse([
            'message' => 'User updated',
            'data' => $updatedUser
        ], 204);
    }

    /**
     * @Route("/users/{id}", name="users.delete", methods={"DELETE"})
     */
    public function delete(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $data = \json_decode($request->getContent(), true);

        $userId = $data['user_id'];

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
