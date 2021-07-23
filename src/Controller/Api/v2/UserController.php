<?php

namespace App\Controller\Api\v2;

use App\Entity\User;
use App\Manager\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/v2/user", service="App\Controller\Api\v2\UserController")
 */
class UserController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @Route("")
     * @Method("POST")
     */
    public function saveUserAction(Request $request): Response
    {
        $login = $request->request->get('login');
        $userId = $this->userManager->saveUser($login);
        [$data, $code] = $userId === null ?
            [['success' => false], 400] :
            [['success' => true, 'userId' => $userId], 200];

        return new JsonResponse($data, $code);
    }

    /**
     * @Route("")
     * @Method("GET")
     */
    public function getUsersAction(Request $request): Response
    {
        $perPage = $request->request->get('perPage');
        $page = $request->request->get('page');
        $users = $this->userManager->getUsers($page ?? 0, $perPage ?? 20);
        $code = empty($users) ? 204 : 200;

        return new JsonResponse(['users' => array_map(static fn(User $user) => $user->toArray(), $users)], $code);
    }

    /**
     * @Route("/by-login/{user_login}", priority=2)
     * @Method("GET")
     * @ParamConverter("user", options={"mapping": {"user_login": "login"}})
     */
    public function getUserByLoginAction(User $user): Response
    {
        return new JsonResponse(['user' => $user->toArray()], 200);
    }

    /**
     * @Route("/{user_id}")
     * @Method("DELETE")
     * @Entity("user", expr="repository.find(user_id)")
     */
    public function deleteUserAction(User $user): Response
    {
        $result = $this->userManager->deleteUser($user);

        return new JsonResponse(['success' => $result], $result ? 200 : 404);
    }

    /**
     * @Route("")
     * @Method("PATCH")
     */
    public function updateUserAction(Request $request): Response
    {
        $userId = $request->request->get('userId');
        $login = $request->request->get('login');
        $result = $this->userManager->updateUser($userId, $login);
        [$data, $code] = $result === null ? [null, 404] : [['user' => $result->toArray()], 200];

        return new JsonResponse($data, $code);
    }
}
