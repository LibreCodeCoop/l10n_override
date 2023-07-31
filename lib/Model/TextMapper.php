<?php

declare(strict_types=1);

namespace OCA\L10nOverride\Model;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Text>
 */
class TextMapper extends QBMapper {
	public const TABLE_NAME = 'l10n_override_text';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME);
	}

	public function insertOrUpdate(Entity $entity): Entity {
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
		return parent::insertOrUpdate($entity);
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
			->andWhere($qb->expr()->eq('new_language', $qb->createNamedParameter($entity->getNewLanguage())));
		return $this->findEntities($qb);
	}
}
