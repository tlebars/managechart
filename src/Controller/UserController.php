<?php

namespace App\Controller;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Form\User\AdminEditFormType;
use App\Form\User\RegistrationFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    /**
     * Affiche l'utilisateur
     */
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function show(#[MapEntity] User $user)
    {
        /* Si l'utilisateur n'est pas admin, affiche son profile */
        if (!$this->isGranted('ROLE_ADMIN') && $user->getId() != $this->getUser()->getId()) {
            throw new AccessDeniedHttpException("Accès interdit");
        } else {
            return $this->render('users/show.html.twig', array('user' => $user));
        }
    }

    /**
     * Affiche la liste des utilisateurs
     */
    #[IsGranted('ROLE_ADMIN')]
    public function index()
    {
        $repository = $this->doctrine->getRepository(User::class);
        $users = $repository->findAll();

        return $this->render('users/index.html.twig', array('listUsers' => $users));
    }

    /**
    * Création d'utilisateurs
     */
    #[IsGranted('ROLE_ADMIN')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $passwordHasher->hashPassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            $em = $this->doctrine->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('user_list'));
        }

        return $this->render('users/register.html.twig', array(
            'form' => $form->createView(),
            'user' => $user
        ));
    }

    /**
    * Edition d'utilisateurs
     */
    #[IsGranted('ROLE_ADMIN')]
    public function edit(#[MapEntity] User $user, Request $request)
    {
        $form = $this->createForm(AdminEditFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('user_list'));
        }

        return $this->render('users/edit.html.twig', array(
            'form' => $form->createView(),
            'user' => $user
        ));
    }

    /**
     * Suppression d'utilisateurs
     */
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function delete(#[MapEntity] User $user, TranslatorInterface $translator, Request $request): Response
    {
        /* l'utilisateur ne peut pas supprimer son compte */
        if ($user === $this->getUser()) {
            $msgError = $translator->trans('userlistcontroller.deleteitself.msgError');

            $request->getSession()->getFlashBag()->add('deleteItSelf', $msgError);
        } else {
            $em = $this->doctrine->getManager();
            $em->remove($user);
            $em->flush();
        }
        return $this->redirect($this->generateUrl('user_list'));
    }
}
