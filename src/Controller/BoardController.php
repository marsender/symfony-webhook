<?php

namespace App\Controller;

use App\Form\ExportBoardType;
use App\Service\MattermostBoardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/board')]
class BoardController extends AbstractController
{
	public function __construct(
		private readonly MattermostBoardService $mattermostBoardService,
		private readonly TranslatorInterface $translator,
		private readonly string $boardExportPath
	) {
	}

	#[Route('/', name: 'app_board_index')]
	public function index(): Response
	{
		// List of board with 'app_board_' path prefix
		$items = [
			'export',
		];

		return $this->render('board/index.html.twig', [
			'items' => $items,
		]);
	}

	#[Route(path: '/export', name: 'app_board_export')]
	public function board(Request $request): Response
	{
		$repsitories = [];
		$repsitories['Garnier'] = 'Garnier/projects';
		$repsitories['Facility'] = 'Facility/projects';
		$repsitories['Alveos'] = 'Alveos/easylibrary_back';
		$options['repository'] = $this->mattermostBoardService->getRepositories();

		$form = $this->createForm(ExportBoardType::class, null, $options);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$content = $this->getDownloadExportContent($data);

			$this->saveContent($data, $content);

			// Create a new filesystem object
			$filesystem = new Filesystem();
			$tempFile = $filesystem->tempnam('/tmp', 'board_', '.txt');
			$filesystem->dumpFile($tempFile, $content);

			return $this->serveFile($tempFile, 'text/plain');

			// $response = new Response($content);
			// $disposition = HeaderUtils::makeDisposition(
			// 	HeaderUtils::DISPOSITION_ATTACHMENT,
			// 	'cra.txt'
			// );
			// $response->headers->set('Content-Disposition', $disposition);
			// $response->headers->set('Content-Type', 'text/plain');
			// // No cache headers
			// $response->headers->set('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
			// $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s').' GMT');
			// $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
			// $response->headers->set('Cache-Control', 'post-check=0, pre-check=0', false);
			// $response->headers->set('Pragma', 'no-cache');
			// return $response;
		}

		return $this->render('board/export.html.twig', [
			'form' => $form,
		]);
	}

	private function getDownloadExportContent(array $data): string
	{
		$title = $data['title'];
		$repository = $data['repository'];
		$dateMin = $data['dateMin'];
		$dateMax = $data['dateMax'];

		$items = $this->mattermostBoardService->exportRepository($repository, $dateMin, $dateMax);
		if (null === $items) {
			$error = sprintf('Repository export error: %s', $repository);
			throw new \LogicException($error);
		}

		// Compute activity period
		$firsDayOfMonth = new \DateTime('first day of this month');
		$lastDayOfMonth = new \DateTime('last day of this month');
		if ($dateMin->format('Y-m-d') == $firsDayOfMonth->format('Y-m-d') && $dateMax->format('Y-m-d') == $lastDayOfMonth->format('Y-m-d')) {
			$period = $dateMin->format('m/Y');
		} else {
			$period = sprintf('from %s to %s', $dateMin->format('j M Y'), $dateMax->format('j M Y'));
		}

		$header = [];
		$lineSep = '## =============================================================================';
		$header[] = $lineSep;
		$parts = explode('/', (string) $repository);
		$repoName = $parts[0];
		$title = empty($title) ? $repoName : $title;
		$line = sprintf('## %s %s %s', $title, $this->translator->trans('board.export.activity'), $period);
		$header[] = $line;
		$header[] = $lineSep;
		$header[] = '';

		// Format items into lines
		$lastWeek = '';
		$totalDuration = 0;
		$content = [];
		$displayWeek = ('Facility' === $repoName);
		foreach ($items as $item) {
			$timestamp = $item['timestamp'];
			$date = $item['date'];
			$duration = $item['duration'];
			if (isset($duration)) {
				$totalDuration += $duration;
			}
			// Add week line
			if ($displayWeek) {
				$week = sprintf('%s %s', $this->translator->trans('board.export.week'), $date->format('W'));
				if ($week !== $lastWeek) {
					$line = sprintf('%-5s%s', '0', $week);
					$content[$timestamp - 1] = $line;
					$lastWeek = $week;
				}
			}
			// Add item line
			$durationInfo = isset($duration) ? number_format($duration, 1, ',') : '';
			$durationInfo = str_replace(',0', '', $durationInfo);
			$line = sprintf('%-5s%s', $durationInfo, trim((string) $item['title']));
			$content[$timestamp] = $line;
		}
		ksort($content);

		// Add total duration
		$footer = [];
		$footer[] = '';
		$totalDurationInfo = str_replace(',0', '', number_format($totalDuration, 1, ','));
		$footer[] = sprintf('# Total: %s', $totalDurationInfo);
		$footer[] = '';

		return implode("\n", array_merge($header, $content, $footer));
	}

	private function saveContent(array $data, string $content): bool
	{
		if ('' === $this->boardExportPath) {
			return false;
		}

		$filesystem = new Filesystem();

		// Init path
		$repository = $data['repository'];
		$parts = explode('/', (string) $repository);
		$repoName = $parts[0];
		$path = sprintf('%s/%s/cra', $this->boardExportPath, $repoName);
		if (!$filesystem->exists($path)) {
			return false;
		}

		// Init file path
		$dateTime = $data['dateMin'];
		$filePath = sprintf('%s/Cra_%s.txt', $path, $dateTime->format('Y-m'));

		$filesystem->dumpFile($filePath, $content);

		return true;
	}

	private function serveFile(string $filePath, string $contentType): BinaryFileResponse
	{
		$response = new BinaryFileResponse($filePath);
		$file = new \SplFileInfo($filePath);

		// Set headers
		// $response->headers->set('Cache-Control', 'private');
		$response->headers->set('Content-Type', $contentType);
		$response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			$file->getBaseName()
		));

		return $response;
	}
}
