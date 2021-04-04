<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Form\DeleteAllNotifications;
use App\Form\DeleteNotification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    /**
     * @Route("/notifications", name="notifications")
     */
    public function index(Request $request): Response
    {

        $deleteNotificationForm = $this->createForm(DeleteNotification::class);
        $deleteNotificationForm->handleRequest($request);
        $notificationRepository = $this->getDoctrine()
            ->getRepository(Notification::class);
        $entityManager = $this->getDoctrine()->getManager();

        if ($deleteNotificationForm->isSubmitted() && $deleteNotificationForm->isValid()) {

            $deleteNotification = $notificationRepository->find($deleteNotificationForm->get('id')->getData());

            if (!empty($deleteNotification)) {
                $deleteNotification->setStatus(true);
            }

            $entityManager->persist($deleteNotification);

            $entityManager->flush();
            return $this->redirect($request->getUri());
        }


        $deleteAllNotificationsForm = $this->createForm(DeleteAllNotifications::class);
        $deleteAllNotificationsForm->handleRequest($request);

        if ($deleteAllNotificationsForm->isSubmitted() && $deleteAllNotificationsForm->isValid()) {

            $deleteNotifications = $notificationRepository->findBy(['user' => $this->getUser()]);

            if (!empty($deleteNotifications)) {
                foreach ($deleteNotifications as $deleteNotification) {
                    $deleteNotification->setStatus(true);
                    $entityManager->persist($deleteNotification);
                }
            }

            $entityManager->flush();
            return $this->redirect($request->getUri());
        }

        return $this->render('page/notification/notifications.html.twig', [
            'deleteForm' => $deleteNotificationForm,
            'deleteAllNotifications' => $deleteAllNotificationsForm->createView(),
            'notifications' => $this->getUser()->getUnseenNotifications(),
            'notificationTypesToBootstrapClass' => [
                Notification::TYPE_DEFAULT => 'info',
                Notification::TYPE_IMPORT => 'primary'
            ]
        ]);
    }
}
