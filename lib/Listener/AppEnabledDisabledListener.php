<?php

declare(strict_types=1);

namespace OCA\L10nOverride\Listener;

use OCA\L10nOverride\Service\OverrideService;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\EventDispatcher\Event;

use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<AppEnableEvent|AppDisableEvent>
 */
class AppEnabledDisabledListener implements IEventListener {

	public function __construct(
		private readonly OverrideService $overrideService,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof AppEnableEvent) {
			$this->overrideService->updateAllLanguages($event->getAppId());
		} elseif ($event instanceof AppDisableEvent) {
			$this->overrideService->deleteAllLanguages($event->getAppId());
		}
	}
}
