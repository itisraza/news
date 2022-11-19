<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Twig\Environment;

use App\Entity\News;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    #[Route('/createNews', name: 'create_news')]
    public function createNews(ManagerRegistry $doctrine): Response
    {
        $entityManager = $doctrine->getManager();

        $news = new News();
        $news->setTitle('Keyboard');
        $news->setShortDescription('Ergonomic and stylish!');
        $news->setPicture('');
        $news->setDateAdded(new \DateTime(date("Y-m-d")));

        // tell Doctrine you want to (eventually) save the news (no queries yet)
        $entityManager->persist($news);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return new Response('Saved new news with id '.$news->getId());
    }
    #[Route('/deleteNews/{id}', name: 'deleteNews')]
    public function deleteNews(Request $request, NewsRepository $newsRepository, int $id): Response
    {
        $news = $newsRepository->findOneBy(['id' => $id]);
        if($news != null){
            $newsRepository->remove($news, true);
            return new Response('Entry has been deleted.');
        }
        else
        return new Response('Unable to delete news entry.');
    }
    #[Route('/news/{offset}', name: 'news')]
    public function news(Request $request, Environment $twig, NewsRepository $newsRepository, int $offset): Response
     {
        $offset = max(0, $offset);
        $paginator = $newsRepository->getNewsPaginator($offset);

        return new Response($twig->render('news/index.html.twig', [
            'news' => $paginator,
            'offset' => $offset,
            'previous' => $offset - NewsRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + NewsRepository::PAGINATOR_PER_PAGE),
         ]));
     }
}
