<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

error_reporting(E_ALL ^ E_DEPRECATED);

class TestController extends AbstractController
{
    #[Route('/', name: 'app_test')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Zadanie dla Cogitech',
            'path' => 'src/Controller/TestController.php',
        ]);
    }
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/posts', name: 'app_posts', methods: ['GET'])]
    public function getPosts(): JsonResponse
    {
        // Pobierz wszystkie posty z bazy danych
        $posts = $this->entityManager->getRepository(Post::class)->findAll();

        // Przygotuj tablicę z danymi postów
        $postData = [];
        foreach ($posts as $post) {
            $postData[] = [
                'id' => $post->getId(),
                'user_id' => $post->getUserID(),
                'title' => $post->getTitle(),
                'body' => $post->getBody(),
            ];
        }

        // Zwróć dane postów jako odpowiedź JSON
        return new JsonResponse($postData);
    }
    #[Route('/lista', name: 'list_posts')]
    public function listPosts(): Response
    {
        $posts = $this->entityManager->getRepository(Post::class)->findAll();

        $postData = [];
        foreach ($posts as $post) {
            $postData[] = [
                'id' => $post->getId(),
                'username' => $post->getUsername(),
                'title' => $post->getTitle(),
                'body' => $post->getBody(),
            ];
        }
        return $this->render('list.html.twig', ['posts' => $postData]);
    }

    #[Route('/post/{id}', name: 'delete_post', methods: ['DELETE'])]
    public function deletePost(Request $request, int $id): Response
    {
        // Pobierz post do usunięcia
        $post = $this->entityManager->getRepository(Post::class)->find($id);

        // Sprawdź, czy post istnieje
        if (!$post) {
            // Zwróć odpowiedź 404 Not Found, jeśli post nie istnieje
            return new Response(null, Response::HTTP_NOT_FOUND);
        }else{
            // Usuń post z bazy danych
            $this->entityManager->remove($post);
            $this->entityManager->flush();

            // Zwróć odpowiedź 204 No Content, aby poinformować o pomyślnym usunięciu
            return new Response(null, Response::HTTP_NO_CONTENT);
        }
    }   
}
