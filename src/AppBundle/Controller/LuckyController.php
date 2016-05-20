<?php
namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LuckyController extends Controller
{
    public function numberAction($count = 1)
    {
        $numbers = [];
        for($i = 0; $i < $count; $i++)
            $numbers[] = rand(0,100);
        $number = implode(", ", $numbers);

        return $this->render(
            'lucky/number.html.twig',
            array('luckylist' => $number)
        );
    }
}