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

class Add extends Base {
	public function __construct(
		private OverrideService $overrideService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('l10n-override:add')
			->setDescription('Override a translation text')
			->addArgument('theme',
				InputArgument::REQUIRED,
				'Theme code. If havent a folder with this code inside the folder /themes, will be created'
			)
			->addArgument('appid',
				InputArgument::REQUIRED,
				'AppId to be override. If you wish to override the core or lib translation, use "core" or "lib" as appid.'
			)

			->addArgument('originalText',
				InputArgument::REQUIRED,
				'Original text to override'
			)
			->addArgument('newText',
				InputArgument::REQUIRED,
				'New text to use in place of original text'
			)
			->addArgument('newLanguage',
				InputArgument::REQUIRED,
				'Language to override'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->overrideService->add(
				(string) $input->getArgument('theme'),
				(string) $input->getArgument('appid'),
				(string) $input->getArgument('originalText'),
				(string) $input->getArgument('newText'),
				(string) $input->getArgument('newLanguage'),
			);
		} catch (InvalidArgumentException | NotFoundException $e) {
			// Convert to Symfony Console Exception to don't display the row number
			throw new ConsoleExceptionInvalidArgument($e->getMessage());
		}
		$output->writeln('Text replaced with success.');
		return 0;
	}
}
