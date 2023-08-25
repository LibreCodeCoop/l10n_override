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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\L10nOverride\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1000Date20230731165751 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('l10n_text')) {
			$table = $schema->createTable('l10n_text');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('theme', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => 'default',
			]);
			$table->addColumn('app', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			// necessary to be used at unique key
			$table->addColumn('original_text_md5', Types::STRING, [
				'notnull' => false,
				'length' => 32,
			]);
			$table->addColumn('original_text', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('new_text', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('new_language', Types::STRING, [
				'notnull' => false,
				'length' => 10,
			]);
			$table->addColumn('not_found', Types::SMALLINT, [
				'notnull' => false,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['theme', 'app', 'original_text_md5'], 'unique_key');
		}
		return $schema;
	}
}
