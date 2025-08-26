<?php

declare(strict_types=1);

namespace OCA\L10nOverride\Service;

use InvalidArgumentException;
use OCA\L10nOverride\Model\Text;
use OCA\L10nOverride\Model\TextMapper;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Files\NotFoundException;
use OCP\IConfig;

class OverrideService {
	private string $themeFolder;
	private string $appId;
	private string $serverRoot;
	private Text $text;
	/** @var Text[] */
	private array $toOverride = [];
	private array $translations = [];
	private string $newLanguage;

	public function __construct(
		private IAppManager $appManager,
		private TextMapper $textMapper,
		private IConfig $config,
	) {
		$this->serverRoot = \OC::$SERVERROOT;
		$this->text = new Text();
	}

	public function add(string $theme, string $appId, string $originalText, string $newText, string $newLanguage): void {
		$this->newLanguage = $newLanguage;
		$this->setNewLanguage($newLanguage)
			->parseTheme($theme)
			->parseAppId($appId)
			->parseNewLanguage()
			->parseOriginalText($originalText);
		$this->text->setNewText($newText);
		$this->text->setNotFound(0);
		$this->textMapper->insertOrUpdate($this->text);
		$this->updateFiles();
	}

	public function list(string $theme = '', string $appId = '', string $language = ''): array {
		return $this->textMapper->listLanguages(
			theme: $theme,
			appId: $appId,
			language: $language,
		);
	}

	public function delete(string $theme, string $appId, string $originalText, string $newLanguage): void {
		$this->setNewLanguage($newLanguage)
			->parseTheme($theme)
			->parseAppId($appId)
			->parseNewLanguage()
			->parseOriginalText($originalText);
		$this->textMapper->delete($this->text);
		$this->updateFiles();
	}

	public function deleteAllLanguages(string $appId): void {
		$this->parseTheme();
		$this->parseAppId($appId);
		$this->updateFiles();
	}

	public function updateAllLanguages(string $appId): void {
		$this->parseTheme();
		$languages = $this->textMapper->listLanguages(
			theme: $this->text->getTheme(),
			appId: $appId,
		);
		foreach ($languages as $language) {
			$this->update($appId, $language["new_language"]);
		}
	}

	private function update(string $appId, string $newLanguage): void {
		try {
			$this->newLanguage = $newLanguage;
			$this->parseAppId($appId)
				->parseNewLanguage();
		} catch (NotFoundException $e) {
			$this->notifyNotFoundAllTexts($appId);
		}
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
		$extensions = ['js', 'json'];
		foreach ($extensions as $extension) {
			$file = $this->getThemeL10nFolder() . $this->text->getNewLanguage() . '.' . $extension;
			if (is_file($file)) {
				unlink($file);
			}
		}

		$dir = $this->getThemeL10nFolder();
		// Remove empty folders
		while (is_dir($dir) && !(new \FilesystemIterator($dir))->valid() && $dir !== $this->serverRoot . '/themes') {
			rmdir($dir);
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
		if (!is_dir($this->getThemeL10nFolder())) {
			mkdir($this->getThemeL10nFolder(), 0755, true);
		}
		$fileName = $this->getThemeL10nFolder() . $this->text->getNewLanguage() . '.' . $format;
		file_put_contents($fileName, $content);
	}

	private function updateInMemory(): void {
		$this->toOverride = $this->textMapper->getRelatedTranslations($this->text);
		foreach ($this->toOverride as $key => $text) {
			$originalText = $text->getOriginalText();
			if (empty($this->translations['translations'][$originalText]) && $this->newLanguage !== 'en') {
				$this->notifyNotFoundText($text);
				unset($this->toOverride[$key]);
				continue;
			}
			$this->translations['translations'][$originalText] = $text->getNewText();
		}
	}

	private function notifyNotFoundText(Text $textNotFound): void {
		$textNotFound->setNotFound(1);
		$this->textMapper->insertOrUpdate($textNotFound);
	}

	private function notifyNotFoundAllTexts(string $appId): void {
		$this->textMapper->flagAllAsDeleted($this->text->getTheme(), $appId, $this->newLanguage);
	}

	private function parseTheme(?string $theme = null): self {
		if (!$theme) {
			if (!$theme = $this->config->getSystemValue('theme')) {
				return $this;
			}
		}

		if (!file_exists($this->serverRoot . '/themes/' . $theme)) {
			mkdir($this->serverRoot . '/themes/' . $theme);
		}
		$this->text->setTheme($theme);
		$this->themeFolder = $this->serverRoot . '/themes/' . $theme;
		return $this;
	}

	private function parseAppId(string $appId): self {
		if (!in_array($appId, ['core', 'lib'])) {
			try {
				$this->appManager->getAppPath($appId);
			} catch (AppPathNotFoundException $e) {
				throw new InvalidArgumentException(sprintf('Is necessary to have an app with id %s or use core or lib as appId', $appId));
			}
		}
		$this->text->setApp($appId);
		$this->appId = $appId;
		return $this;
	}

	private function parseNewLanguage(): self {
		if ($this->newLanguage === 'en') {
			$this->translations = ['translations' => []];
			return $this;
		}
		if ($this->appManager->isInstalled($this->appId)) {
			$rootL10nPath = $this->appManager->getAppPath($this->appId) . '/l10n/' . $this->newLanguage;
		} else {
			$rootL10nPath = $this->serverRoot . '/' . $this->appId . '/l10n/' . $this->newLanguage;
		}
		if (!file_exists($rootL10nPath . '.js')) {
			throw new NotFoundException(sprintf('Translation file not found: %s', $rootL10nPath . '.js'));
		}
		if (!file_exists($rootL10nPath . '.json')) {
			throw new NotFoundException(sprintf('Translation file not found: %s', $rootL10nPath . '.json'));
		}

		$jsonContent = file_get_contents($rootL10nPath . '.json');
		$this->translations = json_decode($jsonContent, true);

		return $this;
	}

	private function getThemeL10nFolder(): string {
		if (in_array($this->appId, ['core', 'lib'])) {
			return $this->themeFolder . '/' . $this->appId . '/l10n/';
		}
		return $this->themeFolder . '/apps/' . $this->appId . '/l10n/';
	}

	private function parseOriginalText(string $originalText): self {
		if (!isset($this->translations['translations'][$originalText]) && $this->newLanguage !== 'en') {
			throw new InvalidArgumentException(sprintf(
				"Text not found in translations file of app %s:\n%s",
				$this->appId,
				$originalText
			));
		}
		$this->text->setOriginalText($originalText);
		return $this;
	}

	private function setNewLanguage(string $newLanguage): self {
		$this->newLanguage = $newLanguage;
		$this->text = new Text();
		$this->text->setNewLanguage($this->newLanguage);
		return $this;
	}
}
