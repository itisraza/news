<?php

namespace App\Command;

use App\Repository\NewsRepository;
use App\Entity\News;
use Doctrine\Persistence\ManagerRegistry;

use App\Repository\CommentRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExtractNewsFromRSSFeedCommand extends Command{
    protected static $defaultName = 'app:news:extract';
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entityManager = $this->doctrine->getManager();
        $rss = simplexml_load_file('https://highload.today/category/novosti/feed/');
        $newsRepository = $this->doctrine->getRepository(News::class);

        foreach($rss->channel->item as $item){
            if(empty($item->title))continue;//skip news without title
            $news = $newsRepository->findOneBy(['title' => $item->title]);
            if(!$news){//if it doesn't exist in DB
                $news = new News();
                $news->setTitle($item->title);
                $news->setShortDescription($item->description);
                $news->setPicture($item->link);
                $news->setDateAdded(new \DateTime(date("Y-m-d", strtotime($item->pubDate))));

                // tell Doctrine you want to (eventually) save the news (no queries yet)
                $entityManager->persist($news);

                // actually executes the queries (i.e. the INSERT query)
                $entityManager->flush();
            }
            else{//update just the updated date
                $news->setDateUpdated(new \DateTime(date("Y-m-d H:i:s")));

                // actually executes the queries (i.e. the UPDATE query)
                $entityManager->flush();
            }
        }

        $io->success(sprintf('News extraction is done.'));

        return 0;
    }
}