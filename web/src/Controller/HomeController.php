<?php
namespace App\Controller;

use App\View\View;

class HomeController 
{
    public function __construct(private View $view) {}

    public function hello(): string
    {
        return $this->view->render('hello.twig', ['name' => 'Twig']);
    }
}