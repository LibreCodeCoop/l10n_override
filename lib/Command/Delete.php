<?php

declare(strict_types=1);

namespace OCA\L10n\Command;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\L10n\Service\OverrideService;
use OCP\Files\NotFoundException;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleExceptionInvalidArgument;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Base {
	public function __construct(
		private OverrideService $overrideService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('l10n-override:delete')
			->setDescription('Remove overwritten translation.')
			->addArgument('theme',
				InputArgument::REQUIRED,
				'Theme code.'
			)
			->addArgument('appid',
				InputArgument::REQUIRED,
				'AppId to be removed. If you wish to override the core or lib translation, use "core" or "lib" as appid.'
			)

			->addArgument('originalText',
				InputArgument::REQUIRED,
				'Original text to remove'
			)
			->addArgument('newLanguage',
				InputArgument::REQUIRED,
				'Language to remove'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->overrideService->delete(
				(string) $input->getArgument('theme'),
				(string) $input->getArgument('appid'),
				(string) $input->getArgument('originalText'),
				(string) $input->getArgument('newLanguage'),
			);
		} catch (InvalidArgumentException | NotFoundException $e) {
			// Convert to Symfony Console Exception to don't display the row number
			throw new ConsoleExceptionInvalidArgument($e->getMessage());
		}
		$output->writeln('Text removed with success.');
		return 0;
	}
}
