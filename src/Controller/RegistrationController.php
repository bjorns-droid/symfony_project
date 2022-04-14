<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Roles;
use Twig\Environment;
use Symfony\Component\Mime\Email;
use Twig\Loader\FilesystemLoader;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register')]    
    /**
     * Registers a new user. Creates a Form to fill out and adds a user with the given input to the database.
     * Sends an email to verify the email adress of the given user.
     *
     * @param  mixed $request
     * @param  mixed $doctrine
     * @param  mixed $passwordHasher
     * @param  mixed $verifyEmailHelper
     * @param  mixed $mailer
     * @return Response
     */
    public function register(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher, VerifyEmailHelperInterface $verifyEmailHelper, MailerInterface $mailer): Response
    {
        $user = new User();
        $form = $this->createFormBuilder($user)
            ->add('email')
            ->add('name')
            ->add('plz', IntegerType::class)
            ->add('ort')
            ->add('telefon', IntegerType::class)
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options'  => ['label' => 'Passwort'],
                'second_options' => ['label' => 'Passwort bestätigen'],
            ])
            ->add('termsAccepted', CheckboxType::class, [
                // 'value' => 'Ich habe die Nutzungsbedingungen gelesen',
                'attr' => [
                    'label' => 'Ich stimme den Nutzungsbedingungen zu und habe die Datenschutzerklärung gelesen.'
                ],
                'mapped' => false,
                'constraints' => new IsTrue(),
            ])
            ->add('registrieren', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary float-end'
                ]
            ])
            ->getForm()
            ;

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $user = new User();
                $user->setEmail($data->getEmail());
                $user->setName($data->getName());
                $user->setPlz($data->getPlz());
                $user->setOrt($data->getOrt());
                $user->setTelefon($data->getTelefon());
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $data->getPassword())
                );
                $role = new Roles($user);
                $role->setName('ROLE_USER');
                $user->addRole($role);

                $em = $doctrine->getManager();
                $em->persist($user);
                $em->persist($role);
                $em->flush();

                $signatureComponents = $verifyEmailHelper->generateSignature(
                    'app_verify_email',
                    $user->getId(),
                    $user->getEmail(),
                    ['id' => $user->getId()]
                );

                $transport = Transport::fromDsn($this->getParameter('app.dsnmailer'));
                $mailer = new Mailer($transport);
                $email = new TemplatedEmail();
                $email->from('bjoernihoerni@gmail.com');
                $email->to($user->getEmail());
                $email->htmlTemplate('registration/signup.html.twig');
                $email->subject('Accountaktivierung!');
                $email->context(['signedUrl' => $signatureComponents->getSignedUrl()]);

                $loader = new FilesystemLoader('../templates/');

                $twigEnv = new Environment($loader);

                $twigBodyRenderer = new BodyRenderer($twigEnv);

                $twigBodyRenderer->render($email);

                $mailer->send($email);

                $this->addFlash('successEmail', 'Wir haben dir eine Email zugeschickt. Bitte klicke auf den Link, um deinen Account zu aktivieren!');
            }

        return $this->render('registration/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/verify', name: 'app_verify_email')]    
    /**
     * verifies the user with the link sent to his email. If everything is correct, user account is activated.
     *
     * @param  mixed $request
     * @param  mixed $verifyEmailHelper
     * @param  mixed $userRepository
     * @param  mixed $doctrine
     * @return Response
     */
    public function verifyUserEmail(Request $request, VerifyEmailHelperInterface $verifyEmailHelper, UserRepository $userRepository, ManagerRegistry $doctrine): Response {
        $id = $request->get('id');

        $user = $userRepository->find($id);

        try {
            $verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->getId(), $user->getEmail());
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('verify_email_error', $e->getReason());

            return $this->redirectToRoute('register');
        }

        $user->setIsVerified(true);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();

        $this->addFlash('verify_email_success', 'Deine Email wurde verifiziert und dein Account aktiviert');

        return $this->render('registration/verify.html.twig');
    }
}
