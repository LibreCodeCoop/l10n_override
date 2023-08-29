<?php

declare(strict_types=1);

namespace OCA\L10nOverride\Command;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\L10nOverride\Service\OverrideService;
use OCP\Files\NotFoundException;
use Symfony\Component\Console\Exception\InvalidArgumentException as ConsoleExceptionInvalidArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListTranslations extends Base {
	public function __construct(
		private OverrideService $overrideService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('l10n-override:list')
			->setDescription('Override a translation text')
			->addOption('theme',
				null,
				InputOption::VALUE_REQUIRED,
			)
			->addOption('appid',
				null,
				InputOption::VALUE_REQUIRED,
			)
			->addOption('language',
				null,
				InputOption::VALUE_REQUIRED,
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$list = $this->overrideService->list(
				(string) $input->getOption('theme'),
				(string) $input->getOption('appid'),
				(string) $input->getOption('language'),
			);
		} catch (InvalidArgumentException | NotFoundException $e) {
			// Convert to Symfony Console Exception to don't display the row number
			throw new ConsoleExceptionInvalidArgument($e->getMessage());
		}
		$this->writeArrayInOutputFormat($input, $output, $list);
		return 0;
	}
}
