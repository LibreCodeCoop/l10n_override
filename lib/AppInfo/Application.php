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

namespace OCA\L10nOverride\AppInfo;

use OC;
use OCA\L10nOverride\Service\OverrideService;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * @codeCoverageIgnore
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'l10n_override';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function boot(IBootContext $context): void {
		$event = OC::$server->getEventDispatcher();
		$event->addListener(AppEnableEvent::class, [$this, 'onAppEnabled']);
		$event->addListener(AppDisableEvent::class, [$this, 'onAppDisabled']);
	}

	public function register(IRegistrationContext $context): void {
	}

	public function onAppEnabled(AppEnableEvent $event): void {
		/** @var OverrideService */
		$overrideService = OC::$server->get(OverrideService::class);
		$overrideService->updateAllLanguages($event->getAppId());
	}

	public function onAppDisabled(AppDisableEvent $event): void {
		/** @var OverrideService */
		$overrideService = OC::$server->get(OverrideService::class);
		$overrideService->deleteAllLanguages($event->getAppId());
	}
}
