<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Roles;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/user', name: 'user.')]
class UserController extends AbstractController
{
    #[Route('/', name: 'index')]    
    /**
     * shows the index of the private site
     *
     * @param  mixed $userRepository
     * @return Response
     */
    public function index(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $logged_in_user = $this->getUser();
        if ($logged_in_user->getIsVerified()) {
            $users = $userRepository->findAll();

            return $this->render('user/index.html.twig', [
                'users' => $users
            ]);
        } else {
            return $this->redirect($this->generateUrl('user.verify'));
        }
    }

    #[Route('/show/{id}', name: 'show')]    
    /**
     * shows user information of one particular user. If the logged in user is an admin, he can edit the shown user. if not, user
     * information is just shown on the screen.
     *
     * @param  mixed $userRepository
     * @param  mixed $id
     * @param  mixed $doctrine
     * @param  mixed $request
     * @param  mixed $passwordHasher
     * @return void
     */
    public function showUser(UserRepository $userRepository, int $id, ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $passwordHasher) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $logged_in_user = $this->getUser();
        if ($logged_in_user->getIsVerified()) {
            $user = $userRepository->find($id);
            $role = new Roles($user);
    
            $form = $this->createFormBuilder($user)
                    ->add('email', TextType::class, [
                        'attr' => [
                            'value' => $user->getEmail(),
                            'required' => true
                        ]
                    ])
                    ->add('name', TextType::class, [
                        'attr' => [
                            'value' => $user->getName(),
                            'required' => true
                        ]
                    ])
                    ->add('plz', IntegerType::class, [
                        'attr' => [
                        'placeholder' => $user->getPlz(),
                        ]
                    ])
                    ->add('ort', TextType::class, [
                        'attr' => [
                        'placeholder' => $user->getOrt(),
                        ]
                    ])
                    ->add('telefon', IntegerType::class, [
                        'attr' => [
                        'placeholder' => $user->getTelefon(),
                        ]
                    ])
                    ->add('password', RepeatedType::class, [
                        'type' => PasswordType::class,
                        'first_options'  => [
                            'label' => 'Passwort',
                            'empty_data' => $user->getPassword(),
                            'required' => false,
                        ],
                        'second_options' => ['label' => 'Passwort bestätigen', 
                            'empty_data' => $user->getPassword(), 
                            'required' => false
                    ],
                    ])
                    ->add('roles', ChoiceType::class, [
                            'choices' => ['ROLE_ADMIN' => 'ROLE_ADMIN', 'ROLE_USER' => 'ROLE_USER'],
                            'expanded' => true,
                            'multiple' => true
                    ])
                    ->add('aktualisieren', SubmitType::class, [
                        'attr' => [
                            'class' => 'btn btn-primary float-end'
                        ]
                    ])
                    ->add('loeschen', ButtonType::class, [
                        'attr' => [
                            'class' => 'btn btn-danger float-end',
                            'data-toggle' => 'modal',
                            'data-target' => '#deleteModal',
                            // 'onclick' => 'preventDefault(e)'
                        ]
                    ])
                    ->getForm();
    
                    $user_password = $user->getPassword();
    
                    $form->handleRequest($request);
                    $em = $doctrine->getManager();
                    $button_update = $form->get('aktualisieren');
                    if ($form->isSubmitted() && $form->isValid() && $button_update->isClicked()) {
                        $data = $form->getData();
                        if ($data->getPassword() === null) {
                            $user->setEmail($data->getEmail());
                            $user->setName($data->getName());
                            $user->setPlz($data->getPlz());
                            $user->setOrt($data->getOrt());
                            $user->setTelefon($data->getTelefon());
                            $user->setPassword($data->getPassword());   
                        } 
                        else {
                            $user->setEmail($data->getEmail());
                            $user->setName($data->getName());
                            $user->setPlz($data->getPlz());
                            $user->setOrt($data->getOrt());
                            $user->setTelefon($data->getTelefon()); 
                            if ($user_password == $data->getPassword()) {
                                $user->setPassword($data->getPassword());
                            } else {
                                $user->setPassword(
                                    $passwordHasher->hashPassword($user, $data->getPassword())
                                );
                            }
                            foreach ($form->get('roles')->getData() as $value) {
                                $role->setName($value);
                                $em->persist($role);
                            }
                        }
                        $user->addRole($role);
                        
                        $em->persist($user);
                        $em->flush();
                        $this->addFlash('update_success', 'The user has been updated');
                        return $this->redirect($this->generateUrl('user.index'));
                    }

            return $this->render('user/show.html.twig', [
                'user' => $user,
                'form' => $form->createView()
            ]);
        } else {
            return $this->redirect($this->generateUrl('user.verify'));
        }
    }

    #[Route('/delete/{id}', name: 'delete')]    
    /**
     * delete a specific user. reserved for admin users.
     *
     * @param  mixed $user
     * @param  mixed $doctrine
     * @return void
     */
    public function remove(User $user, ManagerRegistry $doctrine) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $logged_in_user = $this->getUser();
        if ($logged_in_user->getIsVerified()) {
            $em = $doctrine->getManager();

            $em->remove($user);
            $em->flush();
    
            $this->addFlash('success', 'User was removed');
    
            return $this->redirect($this->generateUrl('user.index'));
        } else {
            return $this->redirect($this->generateUrl('user.verify'));
        }
    }

    #[Route(path: '/users', name: 'user_list')]      
    /**
     * shows a list of all users with functions to paginate and sort the list.
     *
     * @param  mixed $em
     * @param  mixed $paginator
     * @param  mixed $request
     * @return Response
     */
    public function listAction(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $logged_in_user = $this->getUser();
        if ($logged_in_user->getIsVerified()) {
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
        } else {
            return $this->redirect($this->generateUrl('user.verify'));
        }
    }

    #[Route(path: '/self', name: 'self')]      
    /**
     * shows user information of the logged in user. The user can edit these informations or delete his account.
     *
     * @param  mixed $request
     * @param  mixed $userRepository
     * @param  mixed $passwordHasher
     * @param  mixed $doctrine
     * @return Response
     */
    public function showSelf(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): Response {

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $logged_in_user = $this->getUser();
        if ($logged_in_user->getIsVerified()) {
            $logged_in_user = $this->getUser();
            $user_id = $logged_in_user->getId();
            $user = $userRepository->find($user_id);
    
            $form = $this->createFormBuilder($user)
                ->add('email', TextType::class, [
                    'attr' => [
                        'value' => $user->getEmail()
                    ]
                ])
                ->add('name', TextType::class, [
                    'attr' => [
                        'value' => $user->getName()
                    ]
                ])
                ->add('plz', IntegerType::class, [
                    'attr' => [
                        'value' => $user->getPlz()
                    ]
                ])
                ->add('ort', TextType::class, [
                    'attr' => [
                        'value' => $user->getort()
                    ]
                ])
                ->add('telefon', IntegerType::class, [
                    'attr' => [
                        'value' => $user->getTelefon()
                    ]
                ])
                ->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'first_options'  => [
                        'label' => 'Passwort',
                        'empty_data' => $user->getPassword(),
                        'attr' => [
                            'required' => false
                        ]
                    ],
                    'second_options' => [
                        'label' => 'Passwort bestätigen',
                        'empty_data' => $user->getPassword(),
                        'attr' => [
                            'required' => false
                        ]
                    ],
                ])
                ->add('loeschen', ButtonType::class, [
                    'attr' => [
                        'class' => 'btn btn-danger float-end',
                        'data-toggle' => 'modal',
                        'data-target' => '#deleteModal',
                    ]
                ])
                ->add('aktualisieren', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn-primary float-end'
                    ]
                ])
                ->getForm();

            $user_password = $user->getPassword();
            $form->handleRequest($request);
            $em = $doctrine->getManager();
            $button_update = $form->get('aktualisieren');

            if ($button_update->isClicked() && $form->isValid() && $form->isSubmitted()) {
                $data = $form->getData();
                if ($data->getPassword() === null) {
                    $user->setEmail($data->getEmail());
                    $user->setName($data->getName());
                    $user->setPlz($data->getPlz());
                    $user->setOrt($data->getOrt());
                    $user->setTelefon($data->getTelefon());   
                } else {
                    $user->setEmail($data->getEmail());
                    $user->setName($data->getName());
                    $user->setPlz($data->getPlz());
                    $user->setOrt($data->getOrt());
                    $user->setTelefon($data->getTelefon()); 
                    var_dump($data->getPassword());
                    if ($user_password == $data->getPassword()) {
                        $user->setPassword($data->getPassword());
                    } else {
                        $user->setPassword(
                            $passwordHasher->hashPassword($user, $data->getPassword())
                        );
                    }
                }
                $em->persist($user);
                $em->flush();
                $this->addFlash('update_success', 'The user has been updated');
                return $this->redirect($this->generateUrl('user.index'));
            }

        return $this->render('user/self.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
        } else {
            return $this->redirect($this->generateUrl('user.verify'));
        }
        
    }

    #[Route(path: '/search', name: 'search')]     
    /**
     * simple search form for finding other users.
     *
     * @param  mixed $request
     * @param  mixed $userRepository
     * @return Response
     */
    public function search(Request $request, UserRepository $userRepository): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $logged_in_user = $this->getUser();
        if ($logged_in_user->getIsVerified()) {
            $form = $this->createFormBuilder()
            ->add('input', TextType::class)
            ->add('suchen', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary float-end'
                ]
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->get('input')->getData();

            return $this->render('search/result.html.twig', [
                'emails' => $userRepository->findByEmail($data),
                'names' => $userRepository->findByName($data),
                'plzs' => $userRepository->findByPlz($data),
                'orte' => $userRepository->findByOrt($data),
                'telefone' => $userRepository->findByTelefon($data),
            ]);
        }

        return $this->render('user/search.html.twig', [
            'form' => $form->createView(),
        ]);
        } else {
            return $this->redirect($this->generateUrl('user.verify'));
        }
        
    }

    #[Route(path: '/verify', name: 'verify')]     
    /**
     * site is to be called if the user is not verified.
     *
     * @return Response
     */
    public function verify(): Response {
        return $this->render('user/verify.html.twig');
    }
}
