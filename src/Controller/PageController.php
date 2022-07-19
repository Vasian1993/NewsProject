<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class PageController extends AbstractController
{
    /**
     * @Route("/parse/news")
     */
    public function parse(): Response
    {
        $url = "https://nsk.rbc.ru/";

        $client = HttpClient::create();

        $response = $client->request('GET', $url);

        $statusCode = $response->getStatusCode();

        if($statusCode == '200'){
            $content = $response->getContent();

            $crawler = new Crawler($content);

            $links = $crawler->filter('a.main__feed__link')->each(function ($node){
                $href = $node->attr('href');
                $title = $node->filter('span.main__feed__title')->text();

                return compact('href', 'title');
            });
        }

        return $this->render('page/news.html.twig', [
            'links' => $links,
        ]);
    }
}