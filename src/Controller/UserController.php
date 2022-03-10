<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/user', name: 'user.')]
class UserController extends AbstractController
{
    #[Route('/', name: 'index')]    
    /**
     * index
     *
     * @param  mixed $userRepository
     * @return Response
     */
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();


        return $this->render('user/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/create', name: 'create')]
    public function createUser(Request $request, ManagerRegistry $doctrine) {
        //create a new user
        $user = new User();

        // $user->setEmail('bjarne.97@gmx.de');
        // $user->setName('Peter Maier');
        // $user->setPlz(12536);
        // $user->setOrt('Berlin');
        // $user->setTelefon(347234284434);
        // $user->setPassword('passwordTest');
        // $user->setStatus('Inaktiv');
        // $user->setActCode('5385234582345834535');

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        $form->getErrors();

        if ($form->isSubmitted() /*&& $form->isValid()*/) {
            $em = $doctrine->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('user.index'));
        }

        //entityt manager
        // // $em2 = $this->getEntityManager;
        // // $em2 = $this->getDoctrine()->getEntityManager();

        return $this->render('user/create.html.twig', [
            'form' => $form->createView()
        ]);

        //return a response
    }

    #[Route('/show/{id}', name: 'show')]
    public function showUser(User $user) {
        // $user = $doctrine->getRepository('User::class')->find($id);
        // $user = $userRepository->find($id);
        // echo $user;

        // if (!$user) {
        //     throw $this->createNotFoundException('No User found for id' . $id);
        // }

        // dump($user); die;

        return $this->render('user/show.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function remove(User $user, ManagerRegistry $doctrine) {
        $em = $doctrine->getManager();

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User was removed');

        return $this->redirect($this->generateUrl('user.index'));
    }

    #[Route(path: '/users', name: 'user_list')]  
    public function listAction(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response {
        $dql = "SELECT p FROM App\Entity\User p";
        $query = $em->createQuery($dql);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render('user/users.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route(path: '/self', name: 'self')]  
    public function showSelf(): Response {
        return $this->render('user/self.html.twig');
    }

    #[Route(path: '/search', name: 'search')] 
    public function search(Request $request): Response {


        return $this->render('user/search.html.twig');
    }
}
