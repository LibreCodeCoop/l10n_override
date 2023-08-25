<?php

declare(strict_types=1);
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
 *
 */

namespace OCA\L10nOverride\Model;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * Class Texts
 *
 * @package OCA\L10nOverride\Model
 *
 * @method void setId()
 * @method int getId()
 * @method void setTheme()
 * @method string getTheme()
 * @method void setApp()
 * @method string getApp()
 * @method void setOriginalText()
 * @method string getOriginalText()
 * @method void setOriginalTextMd5()
 * @method string getOriginalTextMd5()
 * @method void setNewText()
 * @method string getNewText()
 * @method void setNewLanguage()
 * @method string getNewLanguage()
 * @method void setNotFound()
 * @method int getNotFound()
 */
class Text extends Entity {
	/** @var int */
	public $id;
	/** @var string */
	public $theme;
	/** @var string */
	public $app;
	/** @var string */
	public $originalText;
	/** @var string */
	public $originalTextMd5;
	/** @var string */
	public $newText;
	/** @var string */
	public $newLanguage;
	/** @var int */
	public $notFound;

	public function __construct() {
		$this->addType('id', Types::STRING);
		$this->addType('theme', Types::STRING);
		$this->addType('app', Types::STRING);
		$this->addType('original_text', Types::STRING);
		$this->addType('new_text', Types::STRING);
		$this->addType('new_language', Types::STRING);
		$this->addType('not_found', Types::SMALLINT);
	}

	public function setOriginalText(string $originalText): void {
		$this->originalText = $originalText;
		$this->originalTextMd5 = md5($originalText);
		$this->markFieldUpdated('originalText');
		$this->markFieldUpdated('originalTextMd5');
	}
}
