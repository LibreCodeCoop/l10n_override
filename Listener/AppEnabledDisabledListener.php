<?php

declare(strict_types=1);

namespace OCA\L10nOverride\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppDisableEvent;

use OCA\L10nOverride\Service\OverrideService;

class AppEnabledDisabledListener implements IEventListener {

	public function __construct(
		private readonly OverrideService $overrideService,
	) {}

	public function handle(Event $event): void {
		if ($event instanceof AppEnableEvent) {
			$this->overrideService->updateAllLanguages($event->getAppId());
		} else if ($event instanceof AppDisableEvent) {
			$this->overrideService->deleteAllLanguages($event->getAppId());
		}
	}
}