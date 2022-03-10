<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'register')]
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
                // $validate = $form->getData();
                $user = new User();
                $user->setEmail($data->getEmail());
                $user->setName($data->getName());
                $user->setPlz($data->getPlz());
                $user->setOrt($data->getOrt());
                $user->setTelefon($data->getTelefon());
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $data->getPassword())
                );

                // var_dump($user);

                $em = $doctrine->getManager();
                $em->persist($user);
                $em->flush();

                $signatureComponents = $verifyEmailHelper->generateSignature(
                    'app_verify_email',
                    $user->getId(),
                    $user->getEmail(),
                    // ['id' => $user->getId()]
                );

                $email = new TemplatedEmail();
                $email->from('bjoernihoerni@gmail.com');
                $email->to($user->getEmail());
                $email->htmlTemplate('registration/signup.html.twig');
                $email->context(['signedUrl' => $signatureComponents->getSignedUrl()]);

                $mailer->send($email);

                $this->addFlash('successEmail', 'Wir haben dir eine Email zugeschickt. Bitte klicke auf den Link, um deinen Account zu aktivieren!');

                // var_dump($signatureComponents);
                // return $this->redirect($this->generateUrl('app_login'));
            }

        return $this->render('registration/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/verify', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, VerifyEmailHelperInterface $verifyEmailHelper): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        try {
            $verifyEmailHelper->validateEmailConfirmation($request->getUri(), $user->$this->getId(), $user->$this->getEmail());
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('verify_email_error', $e->getReason());

            return $this->redirectToRoute('register');
        }

        $user->$this->setIsVerified(true);

        $this->addFlash('verify_email_success', 'Deine Email wurde verifiziert und dein Account aktiviert');

        return $this->redirectToRoute('app_login');
        
        // return $this->json([
        //     'message' => '$signatureComponents'
        // ]);
    }
}
