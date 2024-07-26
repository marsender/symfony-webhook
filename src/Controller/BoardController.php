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
	private const cTempPath = '/tmp';

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
	public function export(Request $request): Response
	{
		$options['repository'] = $this->mattermostBoardService->getRepositories();

		$form = $this->createForm(ExportBoardType::class, null, $options);

		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$data = $form->getData();
			$content = $this->getBoardContent($data);
			// Save content to repository path
			$this->saveToRepoExportPath($data, $content);
			// Save content for download
			$fileName = 'board.txt';
			$filesystem = new Filesystem();
			$tempFile = sprintf('%s/%s', self::cTempPath, $fileName);
			$filesystem->dumpFile($tempFile, $content);

			return $this->redirectToRoute('app_board_export_download', ['fileName' => $fileName]);
		}

		return $this->render('board/export.html.twig', [
			'form' => $form,
		]);
	}

	#[Route(path: '/export/download/{fileName}', name: 'app_board_export_download')]
	public function boardExportDownload(string $fileName): BinaryFileResponse
	{
		$tempFile = sprintf('%s/%s', self::cTempPath, $fileName);

		return $this->serveFile($tempFile, 'text/plain');
	}

	/**
	 * Export the board of a repo and format content.
	 */
	private function getBoardContent(array $data): string
	{
		$repository = $data['repository'];
		$dateMin = $data['dateMin'];
		$dateMax = $data['dateMax'];
		$week = $data['week'];

		$items = $this->mattermostBoardService->exportRepository($repository, $dateMin, $dateMax);
		if (null === $items) {
			$error = sprintf('Repository export error: %s', $repository);
			throw new \LogicException($error);
		}

		// Compute board activity period
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
		$line = sprintf('## %s %s %s', $repoName, $this->translator->trans('board.export.activity'), $period);
		$header[] = $line;
		$header[] = $lineSep;
		$header[] = '';

		// Format items into lines
		$lastWeek = '';
		$totalDuration = 0;
		$content = [];
		foreach ($items as $item) {
			$timestamp = $item['timestamp'];
			$date = $item['date'];
			$duration = $item['duration'];
			if (isset($duration)) {
				$totalDuration += $duration;
			}
			// Add week line
			if ($week) {
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

	/**
	 * Save content into the repository export path.
	 */
	private function saveToRepoExportPath(array $data, string $content): bool
	{
		if ('' === $this->boardExportPath) {
			return false;
		}

		$filesystem = new Filesystem();

		// Init path
		$repository = $data['repository'];
		$parts = explode('/', (string) $repository);
		$repoName = $parts[0];
		$path = sprintf('%s/%s', $this->boardExportPath, $repoName);
		if (!$filesystem->exists($path)) {
			return false;
		}

		// Init file path
		$dateTime = $data['dateMin'];
		$filePath = sprintf('%s/Board_%s.txt', $path, $dateTime->format('Y-m'));

		$filesystem->dumpFile($filePath, $content);

		return true;
	}

	private function serveFile(string $filePath, string $contentType): BinaryFileResponse
	{
		$file = new \SplFileInfo($filePath);
		$response = new BinaryFileResponse($file);

		// Set headers
		$response->headers->set('Content-Type', $contentType);
		$response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
			ResponseHeaderBag::DISPOSITION_ATTACHMENT,
			$file->getBaseName()
		));

		return $response;
	}
}
