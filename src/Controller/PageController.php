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
    public function parseMainPage(): Response
    {
        $url = "https://nsk.rbc.ru/";

        $client = HttpClient::create();

        $response = $client->request('GET', $url);

        $statusCode = $response->getStatusCode();


        if ($statusCode == '200') {
            $content = $response->getContent();

            $crawler = new Crawler($content);

            $links = $crawler->filter('a.main__feed__link')
                ->reduce(function (Crawler $node, $i) {
                    return ($i < 15);
                })
                ->each(function ($node) {
                    $href = $node->attr('href');
                    $title = $node->filter('span.main__feed__title')->text();
                    $article = $this->parseArticle($href);
                    return compact('href', 'title', 'article');
                });
        }

        return $this->render('page/news.html.twig', [
            'links' => $links
        ]);
    }

    public function parseArticle($url): array
    {
        $client = HttpClient::create();
        $response = $client->request('GET', $url);
        $statusCode = $response->getStatusCode();
        $article = [];

        if ($statusCode == '200') {
            $content = $response->getContent();

            $crawler = new Crawler($content);

            $article['header'] = $crawler->filter('.article__header__title-in')->text();
            $article['image'] = $crawler->filter('.article__main-image .article__main-image__wrap img')
                ->each(function ($node) {
                    return $node->attr('src');
                });
            $article['overview'] = $crawler->filter('.article__text__overview')->each(function ($node) {
                return $node->text();
            });
            $article['text'] = $crawler->filter('.article__text p')->each(function ($node) {
                return $node->text();
            });

            $article['text'] = implode(" ", $article['text']);
        }

        return $article;
    }
}