<?php

// src/Command/ImportPostsCommand.php

namespace App\Command;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

error_reporting(E_ALL ^ E_DEPRECATED);

class ImportPostsCommand extends Command
{
    protected static $defaultName = 'app:import-posts';
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setDescription('Imports posts from https://jsonplaceholder.typicode.com/posts and saves them to the database with author names');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $httpClient = HttpClient::create();

        // Pobierz dane wszystkich użytkowników
        $usersData = $httpClient->request('GET', 'https://jsonplaceholder.typicode.com/users')->toArray();

        // Mapowanie danych użytkowników do tablicy userId => userData
        $usersMap = [];
        foreach ($usersData as $userData) {
            $userId = $userData['id'];
            $usersMap[$userId] = $userData;
        }

        // Pobierz dane wszystkich postów
        $postsData = $httpClient->request('GET', 'https://jsonplaceholder.typicode.com/posts')->toArray();

        foreach ($postsData as $postData) {
            $postId = $postData['id'];

            // Sprawdź, czy post o danym postId już istnieje w bazie danych
            $existingPost = $this->entityManager->getRepository(Post::class)->findOneBy(['id' => $postId]);
            if ($existingPost !== null) {
                // Jeśli post już istnieje, przejdź do następnego
                continue;
            }
        
            $userId = $postData['userId'];
        
            // Sprawdź, czy dane użytkownika są dostępne w mapie
            if (isset($usersMap[$userId])) {
                $userData = $usersMap[$userId];
                $username = $userData['name'];
            } else {
                // Dane użytkownika nie zostały pobrane wcześniej, pobierz je teraz
                $userData = $httpClient->request('GET', 'https://jsonplaceholder.typicode.com/users/'.$userId)->toArray();
                $username = $userData['name'];
                $usersMap[$userId] = $userData;
            }

            // Utwórz nowy post
            $post = new Post();
            $post->setId($postId); // Ustaw id posta
            $post->setUserId($userId); // Ustaw id użytkownika jako userId
            $post->setUsername($username); // Ustaw nazwę użytkownika jako username
            $post->setTitle($postData['title']);
            $post->setBody($postData['body']);

            // Zapisz nowego posta do bazy danych
            $this->entityManager->persist($post);
        }
    
        $this->entityManager->flush();
    
        $output->writeln('Posts imported successfully.');
    
        return Command::SUCCESS;
    }
}