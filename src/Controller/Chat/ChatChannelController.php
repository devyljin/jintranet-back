<?php

namespace App\Controller\Chat;

use App\Entity\Chat\ChatChannel;
use App\Form\Chat\ChatChannelForm;
use App\Repository\Chat\ChatChannelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('api/v1/chat/channel')]
final class ChatChannelController extends AbstractController
{
    public function __construct(private SerializerInterface $serializer)
    {

    }
    #[Route(name: 'api_chat_chat_channel_index', methods: ['GET'])]
    public function index(ChatChannelRepository $chatChannelRepository): JsonResponse
    {
        $data = $chatChannelRepository->findChannels();
        $json = $this->serializer->serialize($data, 'json', ["groups" => "chatChannel"]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);

    }

    #[Route('/new', name: 'api_chat_chat_channel_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ChatChannelRepository $chatChannelRepository): JsonResponse
    {
        $data = $request->toArray();
        $chatChannel = new ChatChannel();
        $chatChannel
            ->setName($data['name'] ?? "Nouveau Topic")
            ->setVisibility($data['visibility'] ?? 'private')
            ->setStatus('online')
        ;
        if(isset($data["parent"])){
            $parent = $chatChannelRepository->find($data['parent']);
            $chatChannel->setParentChannel($parent ?? null);
        }
        $entityManager->persist($chatChannel);
        $entityManager->flush();
        $json = $this->serializer->serialize($chatChannel, 'json', ["groups" => "chatChannel"]);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_chat_chat_channel_show', methods: ['GET'])]
    public function show(ChatChannel $chatChannel): Response
    {
        return $this->render('chat/chat_channel/show.html.twig', [
            'chat_channel' => $chatChannel,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_chat_chat_channel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ChatChannel $chatChannel, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChatChannelForm::class, $chatChannel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_chat_chat_channel_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('chat/chat_channel/edit.html.twig', [
            'chat_channel' => $chatChannel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_chat_chat_channel_delete', methods: ['POST'])]
    public function delete(Request $request, ChatChannel $chatChannel, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$chatChannel->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($chatChannel);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_chat_chat_channel_index', [], Response::HTTP_SEE_OTHER);
    }
}
