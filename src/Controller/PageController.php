<?php


namespace App\Controller;


use App\Entity\Article;
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

    private function parseArticle($url): array
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

            $article['href'] = $url;

            $this->saveArticle($article);
        }

        return $article;
    }

    private function saveArticle($info){
        $doctrine = $this->getDoctrine();
        $entityManager = $doctrine->getManager();

        $article = new Article();
        $article->setHeader($info['header']);
        if($info['image']){
            $article->setImage($info['image'][0]);
        }
        if($info['overview']){
            $article->setOverview($info['overview'][0]);
        }
        $article->setText($info['text']);

        if($info['href']){
            $article->setHref($info['href']);
        }

        $article->setRating(rand(1, 11));

        $entityManager->persist($article);

        $entityManager->flush();

        return new Response('Saved new product with id '.$article->getId());
    }

    /**
     * @Route("/show/news")
     */
    public function showMainPage(): Response
    {
        $doctrine = $this->getDoctrine();
        $repository = $doctrine->getRepository(Article::class);
        $links = $repository->findAll();
//dd($links);
        return $this->render('page/show.news.html.twig', [
            'links' => $links
        ]);
    }

    /**
     * @Route("/news/edit/{id}/{rating}", methods={"GET","HEAD"})
     */
    public function updateRating($id, $rating): Response
    {
        $doctrine = $this->getDoctrine();
        $entityManager = $doctrine->getManager();
        $repository = $doctrine->getRepository(Article::class);
        $article = $repository->find($id);

        if (!$article) {
            throw $this->createNotFoundException(
                'No article found for id '.$id
            );
        }

        $article->setRating($rating);
        $entityManager->flush();

        return new Response('Saved new product with id '.$article->getId().' and rating '.$article->getRating());
    }
}