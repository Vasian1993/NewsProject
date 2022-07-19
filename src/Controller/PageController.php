<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class PageController extends AbstractController
{

   /**
   * @Route("/page/number")
    */
    public function number(): Response
    {
        $number = random_int(0, 100);

        return $this->render('page/number.html.twig', [
            'number' => $number,
        ]);
    }
}