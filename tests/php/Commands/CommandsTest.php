<?php
/**
 * @copyright Copyright (c) 2023 Vitor Mattos <vitor@php.rio>
 *
 * @author Vitor Mattos <vitor@php.rio>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace OCA\L10nOverride\Tests\lib\Command\Signaling;

use OCA\L10nOverride\Command\Add;
use OCA\L10nOverride\Command\Delete;
use OCA\L10nOverride\Command\ListTranslations;
use OCA\L10nOverride\Service\OverrideService;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ListTranslationsTest extends TestCase {
	/** @var OverrideService|MockObject */
	private $overrideService;

	/** @var Add|MockObject */
	private $add;

	/** @var ListTranslations|MockObject */
	private $listTranslations;

	/** @var Delete|MockObject */
	private $delete;

	private array $original = [];
	private static string $themeDir = __DIR__ . '/../../../../../themes/l10n_override_test_theme/';

	public function setUp(): void {
		parent::setUp();

		$this->overrideService = Server::get(OverrideService::class);

		$this->add = new Add($this->overrideService);
		$this->listTranslations = new ListTranslations($this->overrideService);
		$this->delete = new Delete($this->overrideService);

		if (!is_dir(self::$themeDir)) {
			mkdir(self::$themeDir, 0700);
		}
	}

	public static function tearDownAfterClass(): void {
		if (is_dir(self::$themeDir)) {
			exec('rm -rf ' . escapeshellarg(self::$themeDir));
		}
	}

	private function readTextToTranslate(int $count): void {
		$json = file_get_contents(__DIR__ . '/../../../../../core/l10n/pt_BR.json');
		$array = json_decode($json, true);
		for ($i = 0; $i < $count; $i++) {
			$this->original[] = key($array['translations']);
			next($array['translations']);
		}
	}

	/**
	 * @dataProvider addCommandProvider
	 */
	public function testAddCommand(...$texts): void {
		$this->readTextToTranslate(count($texts));

		for ($i = 0; $i < count($texts); $i++) {
			$input = [
				'theme' => 'l10n_override_test_theme',
				'appid' => 'core',
				'originalText' => $this->original[$i],
				'newText' => $texts[$i],
				'newLanguage' => 'pt_BR',
			];
			$addInput = new ArrayInput($input);
			$addOutput = new BufferedOutput();
			$this->add->run($addInput, $addOutput);
			$this->assertStringContainsString('Text replaced with success.', $addOutput->fetch());
		}
	}

	public function addCommandProvider(): array {
		return [
			['a', 'b', 'c'],
		];
	}

	public function testListTranslations(): void {
		$this->testAddCommand('a', 'b', 'c');
		$listInput = new ArrayInput([
			'--output' => 'json',
		]);
		$listOutput = new BufferedOutput();
		$this->listTranslations->run($listInput, $listOutput);
		$output = json_decode($listOutput->fetch(), true);
		$this->assertIsArray($output);
		$this->assertGreaterThan(0, count($output));
		$filtered = array_filter($output, function ($row) {
			foreach ($this->original as $original) {
				if ($row['original_text'] === $original
					&& $row['theme'] === 'l10n_override_test_theme'
					&& $row['app'] === 'core'
					&& $row['new_language'] === 'pt_BR'
				) {
					return true;
				}
			}
		});
		$this->assertCount(3, $filtered);
	}

	/**
	 * @depends testListTranslations
	 */
	public function testDeleteCommand(): void {
		// create extra theme file
		file_put_contents(self::$themeDir . '/.ignore', '');
		$this->testAddCommand('a', 'b', 'c');
		foreach ($this->original as $original) {
			$deleteInput = new ArrayInput([
				'theme' => 'l10n_override_test_theme',
				'appid' => 'core',
				'originalText' => $original,
				'newLanguage' => 'pt_BR',
			]);
			$deleteOutput = new BufferedOutput();
			$this->delete->run($deleteInput, $deleteOutput);
			$this->assertStringContainsString('Text removed with success.', $deleteOutput->fetch());
		}
		$this->assertFileExists(self::$themeDir . '/.ignore');
	}
}
