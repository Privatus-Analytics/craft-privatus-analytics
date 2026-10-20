<?php

namespace privatus\privatusanalytics;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\web\View;
use privatus\privatusanalytics\models\Settings;
use yii\base\Event;

/**
 * Privatus Analytics plugin.
 *
 * Adds the cookieless Privatus Analytics tracking script to front-end pages.
 * The plugin only prints the browser script. It never sends events from the
 * server and sets no cookies.
 *
 * @property-read Settings $settings
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';

    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [],
        ];
    }

    public function init(): void
    {
        parent::init();

        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('privatus-analytics/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
            'userGroups' => Craft::$app->getUserGroups()->getAllGroups(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function() {
                $this->registerTracker();
            },
        );
    }

    /**
     * Registers the tracking script for the current request, when it applies.
     */
    private function registerTracker(): void
    {
        $request = Craft::$app->getRequest();
        if ($request->getIsConsoleRequest() || !$request->getIsSiteRequest()) {
            return;
        }
        if ($request->getIsPreview() || $request->getIsLivePreview() || $request->getIsAjax()) {
            return;
        }

        /** @var Settings $settings */
        $settings = $this->getSettings();
        if (!$settings->shouldTrack(Craft::$app->getUser()->getIdentity())) {
            return;
        }

        $view = Craft::$app->getView();
        $attributes = $settings->scriptAttributes();
        $url = (string)$attributes['src'];
        unset($attributes['src']);
        $view->registerJsFile($url, array_merge($attributes, ['position' => View::POS_HEAD]), 'privatus-analytics');
    }
}
