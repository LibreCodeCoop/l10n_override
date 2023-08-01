<?php

namespace OCA\L10n\AppInfo;

use OC;
use OCA\L10n\Service\OverrideService;
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
	public const APP_ID = 'l10n';

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
