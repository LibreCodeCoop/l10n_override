<?php

declare(strict_types=1);

namespace OCA\L10nOverride\Model;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Text>
 */
class TextMapper extends QBMapper {
	public const TABLE_NAME = 'l10n_text';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME);
	}

	public function insertOrUpdate(Entity $entity): Entity {
		$entity = $this->getIdOfEntity($entity);
		return parent::insertOrUpdate($entity);
	}

	public function delete(Entity $entity): Entity {
		$entity = $this->getIdOfEntity($entity);
		return parent::delete($entity);
	}

	private function getIdOfEntity(Entity $entity): Entity {
		if (!$entity->getId()) {
			$qb = $this->db->getQueryBuilder();
			$qb->select('id')
				->from(self::TABLE_NAME, 't')
				->where($qb->expr()->eq('theme', $qb->createNamedParameter($entity->getTheme())))
				->andWhere($qb->expr()->eq('app', $qb->createNamedParameter($entity->getApp())))
				->andWhere($qb->expr()->eq('original_text_md5', $qb->createNamedParameter($entity->getOriginalTextMd5())))
				->andWhere($qb->expr()->eq('new_language', $qb->createNamedParameter($entity->getNewLanguage())));
			$stmt = $qb->executeQuery();
			if ($id = $stmt->fetchOne()) {
				$entity->setId($id);
			}
		}
		return $entity;
	}

	/**
	 * @param Text $entity
	 * @return Text[]
	 */
	public function getRelatedTranslations(Text $entity): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from(self::TABLE_NAME, 't')
			->where($qb->expr()->eq('theme', $qb->createNamedParameter($entity->getTheme())))
			->andWhere($qb->expr()->eq('app', $qb->createNamedParameter($entity->getApp())))
			->andWhere($qb->expr()->eq('new_language', $qb->createNamedParameter($entity->getNewLanguage())))
			->andWhere($qb->expr()->eq('not_found', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	public function getAllLanguagesOfThemeAndAp(string $theme, string $appId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('t.new_language')
			->from(self::TABLE_NAME, 't')
			->where($qb->expr()->eq('theme', $qb->createNamedParameter($theme)))
			->andWhere($qb->expr()->eq('app', $qb->createNamedParameter($appId)))
			->andWhere($qb->expr()->eq('not_found', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$languages = [];
		while ($language = $result->fetchOne()) {
			$languages[] = $language;
		}
		return $languages;
	}

	public function flagAllAsDeleted(string $theme, string $appId, string $newLanguage): void {
		$qb = $this->db->getQueryBuilder();
		$qb->update(self::TABLE_NAME)
			->set('not_found', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('theme', $qb->createNamedParameter($theme)))
			->andWhere($qb->expr()->eq('app', $qb->createNamedParameter($appId)))
			->andWhere($qb->expr()->eq('new_language', $qb->createNamedParameter($newLanguage)));
		$qb->executeStatement();
	}
}
