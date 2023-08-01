<?php

declare(strict_types=1);

namespace OCA\L10nOverride\Service;

use InvalidArgumentException;
use OCA\L10nOverride\Model\Text;
use OCA\L10nOverride\Model\TextMapper;
use OCP\App\IAppManager;
use OCP\Files\NotFoundException;

class OverrideService {
	private string $themeFolder;
	private string $appId;
	private string $serverRoot;
	private array $rootL10nFiles;
	private Text $text;
	/** @var Text[] */
	private array $toOverride = [];
	private array $translations = [];
	public function __construct(
		private IAppManager $appManager,
		private TextMapper $textMapper,
	) {
		$this->serverRoot = \OC::$SERVERROOT;
		$this->text = new Text();
	}

	public function add(string $theme, string $appId, string $originalText, string $newText, string $newLanguage): void {
		$this->parseTheme($theme)
			->parseAppId($appId)
			->parseNewLanguage($newLanguage)
			->parseOriginalText($originalText);
		$this->text->setNewText($newText);
		$this->textMapper->insertOrUpdate($this->text);
		$this->updateFiles();
	}

	public function delete(string $theme, string $appId, string $originalText, string $newLanguage): void {
		$this->parseTheme($theme)
			->parseAppId($appId)
			->parseNewLanguage($newLanguage)
			->parseOriginalText($originalText);
		$this->textMapper->delete($this->text);
		$this->updateFiles();
	}

	private function updateFiles(): void {
		$this->updateInMemory();
		if (!count($this->toOverride)) {
			$this->removeFiles();
			return;
		}
		$this->updateJsonFile();
		$this->updateJsFile();
	}

	private function removeFiles(): void {
		if (!file_exists($this->rootL10nFiles['newFiles']['js'])) {
			return;
		}
		$file = $this->rootL10nFiles['newFiles']['js'];
		exec('rm -rf ' . escapeshellarg($file));
		$file = $this->rootL10nFiles['newFiles']['json'];
		exec('rm -rf ' . escapeshellarg($file));
		$dir = dirname($file);
		while (!(new \FilesystemIterator($dir))->valid() && $dir !== $this->serverRoot . '/themes') {
			exec('rm -rf ' . escapeshellarg($dir));
			$dir = dirname($dir);
		}
	}

	private function updateJsonFile(): void {
		$content = "{ \"translations\": {\n    ";
		$texts = [];
		foreach ($this->translations['translations'] as $id => $val) {
			$texts[] =
				json_encode($id, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' : ' .
				json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		$content .= implode(",\n    ", $texts);
		$content .= "\n},\"pluralForm\" :\"{$this->translations['pluralForm']}\"\n}";

		$this->write('json', $content);
	}

	private function updateJsFile() {
		$content = "OC.L10N.register(\n    \"{$this->appId}\",\n    {\n    ";
		$texts = [];
		foreach ($this->translations['translations'] as $id => $val) {
			$texts[] =
				json_encode($id, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' : ' .
				json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		$content .= implode(",\n    ", $texts);
		$content .= "\n},\n\"{$this->translations['pluralForm']}\");\n";

		$this->write('js', $content);
	}

	private function write(string $format, string $content): void {
		$path = dirname($this->rootL10nFiles['newFiles'][$format]);
		if (!is_dir($path)) {
			exec('mkdir -p ' . escapeshellarg($path));
		}
		exec('mkdir -p ' . escapeshellarg($path));
		file_put_contents($this->rootL10nFiles['newFiles'][$format], $content);
	}

	public function updateInMemory(): void {
		$this->toOverride = $this->textMapper->getRelatedTranslations($this->text);
		foreach ($this->toOverride as $text) {
			$this->translations['translations'][$text->getOriginalText()] = $text->getNewText();
		}
	}

	private function parseTheme(string $theme): self {
		$theme = escapeshellcmd($theme);
		if (!file_exists($this->serverRoot . '/themes/' . $theme)) {
			mkdir($this->serverRoot . '/themes/' . $theme);
		}
		$this->text->setTheme($theme);
		$this->themeFolder = $this->serverRoot . '/themes/' . $theme;
		return $this;
	}

	private function parseAppId(string $appId): self {
		if (!in_array($appId, ['core', 'lib']) && !$this->appManager->isInstalled($appId)) {
			throw new InvalidArgumentException(sprintf('Is necessary to have an app with id %s installed or use core or lib as appId', $appId));
		}
		$this->text->setApp($appId);
		$this->appId = $appId;
		return $this;
	}

	private function parseNewLanguage(string $newLanguage): self {
		if ($this->appManager->isInstalled($this->appId)) {
			$rootL10nPath = $this->appManager->getAppPath($this->appId) . '/l10n/' . $newLanguage;
			$newPath = str_replace($this->serverRoot, '', $rootL10nPath);
		} else {
			$rootL10nPath = $this->serverRoot . '/' . $this->appId . '/l10n/' . $newLanguage;
			$newPath = $this->themeFolder . '/' . $this->appId . '/l10n/' . $newLanguage;
		}
		if (!file_exists($rootL10nPath . '.js')) {
			throw new NotFoundException(sprintf('Translation file not found: %s', $rootL10nPath . '.js'));
		}
		if (!file_exists($rootL10nPath . '.json')) {
			throw new NotFoundException(sprintf('Translation file not found: %s', $rootL10nPath . '.json'));
		}
		$this->text->setNewLanguage($newLanguage);
		$this->rootL10nFiles = [
			'originalFiles' => [
				'js' => $rootL10nPath . '.js',
				'json' => $rootL10nPath . '.json',
			],
			'newFiles' => [
				'js' => $newPath . '.js',
				'json' => $newPath . '.json',
			],
		];
		return $this;
	}

	private function parseOriginalText(string $originalText): self {
		$jsonContent = file_get_contents($this->rootL10nFiles['originalFiles']['json']);
		$this->translations = json_decode($jsonContent, true);
		if (!isset($this->translations['translations'])) {
			throw new InvalidArgumentException(sprintf(
				'Invalid translation file: %s. Property translation not found.', $this->rootL10nFiles['originalFiles']['json']
			));
		}
		if (!isset($this->translations['translations'][$originalText])) {
			throw new InvalidArgumentException(sprintf(
				"Text not found in originalText at file %s:\n%s",
				$this->rootL10nFiles['originalFiles']['json'],
				$originalText
			));
		}
		$this->text->setOriginalText($originalText);
		return $this;
	}
}
