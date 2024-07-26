<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
	#[Route('/', name: 'app_home')]
	public function index(): Response
	{
		$items = [];
		$items[] = '/webhook/github';
		$items[] = '/webhook/gitlab';
		$items[] = '/webhook/glpi';
		$items[] = '/board';
		$items[] = '/test';

		return $this->render('home/index.html.twig', [
			'items' => $items,
		]);
	}
}
