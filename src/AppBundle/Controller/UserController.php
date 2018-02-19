<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Board;
use AppBundle\Entity\User;
use AppBundle\Form\UserPostType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class UserController
 * @package AppBundle\Controller
 */
class UserController extends Controller
{
    /**
     * @Route("/users", methods={"GET"})
     */
    public function listAction()
    {
        $users = $this->getDoctrine()->getRepository(User::class)
            ->findAll();

        return $this->serialize($users);
    }


    /**
     * @Route("/user/{id}", methods={"GET"}, requirements={"id": "\d+"})
     * @param $id
     * @return JsonResponse
     */
    public function showAction($id)
    {
        $user = $this->getDoctrine()->getRepository(User::class)
            ->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id: ' . $id
            );
        }

        return $this->serialize($user);
    }


    /**
     * @Route("/user", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function postAction(Request $request)
    {
        $form = $this->createForm(UserPostType::class);
        $form->submit([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ]);

        if (!$form->isValid()) {
            throw $this->createNotFoundException(
                'invalid form data'
            );
        }

        // UserPostType 으로 생성된 폼 default configure option 에 'data_class' = User Entity 로 되어있기 때문에
        // $form->getData 는 User 엔티티의 데이터가 리턴된다.
        $user = $this->get('app.service.user_service')->create($form->getData());
        return $this->serialize($user);
    }


    /**
     * @Route("/user/{id}", methods={"PUT"}, requirements={"id": "\d+"})
     * @param User $user
     * @param Request $request
     * @return JsonResponse
     */
    public function putAction(User $user, Request $request)
    {
        $form = $this->createForm(UserPostType::class, $user);
        $form->submit([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ]);

        if (!$form->isValid()) {
            throw $this->createNotFoundException(
                'invalid form data'
            );
        }

        // $user 에 id 가 존재하는경우 update 문이 실행된다.
        $user = $this->get('app.service.user_service')->update($form->getData());
        return $this->serialize($user);
    }


    /**
     * @Route("/user/{id}", methods={"DELETE"}, requirements={"id": "\d+"})
     * @param User $user
     * @return JsonResponse
     */
    public function deleteAction(User $user)
    {
        $this->get('app.service.user_service')->delete($user);
        return $this->serialize($user);
    }


    /**
     * @Route("/user/{id}/boards", methods={"GET"}, requirements={"id": "\d+"})
     * @param User $user
     * @return JsonResponse
     */
    public function listBoardsAction(User $user)
    {
        $boards = $this->getDoctrine()->getRepository(Board::class)
            ->findBy(
                ['user' => $user]
            );

        if (!$boards) {
            throw $this->createNotFoundException(
                'No boards found for user_id: ' . $user->getId()
            );
        }

        return $this->serialize($boards);
    }


    /**
     * @param $entity
     * @return JsonResponse
     */
    public function serialize($entity)
    {
        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        $jsonContent = $serializer->serialize($entity, 'json');

        return new JsonResponse(
            json_decode($jsonContent)
        );
    }

    // TODO: 3. FOS 사용하기 +Object Manager 사용하기
}
