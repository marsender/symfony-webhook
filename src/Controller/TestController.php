<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/test')]
class TestController extends AbstractController
{
	#[Route('/', name: 'app_test_index')]
	public function index(): Response
	{
		// List of test with 'app_test_' path prefix
		$items = [
			'board',
		];

		return $this->render('test/index.html.twig', [
			'items' => $items,
		]);
	}

	#[Route(path: '/board', name: 'app_test_board')]
	public function board(): Response
	{
		return $this->render('test/board.html.twig');
	}
}
