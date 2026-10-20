<?php

namespace privatus\privatusanalytics\models;

use Craft;
use craft\base\Model;
use craft\elements\User;
use craft\helpers\App;

/**
 * Privatus Analytics plugin settings.
 */
class Settings extends Model
{
    public const DEFAULT_HOST = 'https://privatusstats.com';

    public const MODULES = ['engage', 'auto', 'vitals', 'clicks', 'ask'];

    public const SPA_MODES = ['auto', 'history', 'hash', 'off'];

    /**
     * @var string Public site id, for example pa_AbC123. Can be an environment variable such as $PRIVATUS_ANALYTICS_SITE_ID.
     */
    public string $siteId = '';

    /**
     * @var string Origin that serves /js/pa.js.
     */
    public string $scriptHost = self::DEFAULT_HOST;

    /**
     * @var bool Skip users who can access the control panel.
     */
    public bool $excludeCpUsers = true;

    /**
     * @var string[] Handles of user groups that are not tracked.
     */
    public array $excludedUserGroups = [];

    /**
     * @var string Comma-separated paths that are never tracked.
     */
    public string $excludedPaths = '';

    public string $spa = 'auto';

    public bool $manual = false;

    /**
     * @var string[] Tracker modules to load.
     */
    public array $modules = ['engage'];

    public string $domains = '';

    public string $pathMasks = '';

    public string $extraParams = '';

    public bool $canonical = false;

    public bool $hash = false;

    protected function defineRules(): array
    {
        return [
            [['siteId', 'scriptHost', 'excludedPaths', 'spa', 'domains', 'pathMasks', 'extraParams'], 'string'],
            [['excludeCpUsers', 'manual', 'canonical', 'hash'], 'boolean'],
            [['excludedUserGroups', 'modules'], 'each', 'rule' => ['string']],
            ['spa', 'in', 'range' => self::SPA_MODES],
            ['modules', 'each', 'rule' => ['in', 'range' => self::MODULES]],
            [
                'siteId',
                function($attribute) {
                    $value = $this->$attribute;
                    // An environment variable reference is checked when it is read.
                    if ($value !== '' && !str_starts_with($value, '$') && !preg_match('/^pa_[A-Za-z0-9_-]{1,64}$/', $value)) {
                        $this->addError($attribute, Craft::t('privatus-analytics', 'The site id must look like pa_XXXX.'));
                    }
                },
            ],
            [
                'scriptHost',
                function($attribute) {
                    $value = trim((string)$this->$attribute);
                    if ($value === '' || str_starts_with($value, '$')) {
                        return;
                    }
                    $parts = parse_url($value);
                    $path = rtrim($parts['path'] ?? '', '/');
                    if (
                        !in_array($parts['scheme'] ?? '', ['https', 'http'], true) ||
                        empty($parts['host']) ||
                        $path !== '' ||
                        isset($parts['query']) ||
                        isset($parts['fragment'])
                    ) {
                        $this->addError($attribute, Craft::t('privatus-analytics', 'The script host must be an address such as https://privatusstats.com, with no path.'));
                    }
                },
            ],
        ];
    }

    /**
     * Returns the site id with any environment variable resolved.
     */
    public function getSiteId(): string
    {
        $value = trim((string)App::parseEnv($this->siteId));
        return preg_match('/^pa_[A-Za-z0-9_-]{1,64}$/', $value) ? $value : '';
    }

    /**
     * Returns the script host with any environment variable resolved and no trailing slash.
     */
    public function getScriptHost(): string
    {
        $value = rtrim(trim((string)App::parseEnv($this->scriptHost)), '/');
        return $value !== '' ? $value : self::DEFAULT_HOST;
    }

    /**
     * Whether the tracker should be added for the given user (null for a visitor).
     */
    public function shouldTrack(?User $user): bool
    {
        if ($this->getSiteId() === '') {
            return false;
        }
        if ($user === null) {
            return true;
        }
        if ($this->excludeCpUsers && $user->can('accessCp')) {
            return false;
        }
        foreach ($this->excludedUserGroups as $handle) {
            if ($user->isInGroup($handle)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Builds the script tag attributes. The `src` key holds the script URL.
     *
     * @return array<string, mixed>
     */
    public function scriptAttributes(): array
    {
        $attributes = [
            'src' => $this->getScriptHost() . '/js/pa.js',
            'defer' => true,
            'data-site' => $this->getSiteId(),
        ];
        if ($this->spa !== 'auto' && in_array($this->spa, self::SPA_MODES, true)) {
            $attributes['data-spa'] = $this->spa;
        }
        if ($this->manual) {
            $attributes['data-manual'] = 'true';
        }
        $modules = array_values(array_intersect(self::MODULES, $this->modules));
        if ($modules !== ['engage']) {
            // An empty string is meaningful: it turns every module off.
            $attributes['data-modules'] = implode(',', $modules);
        }
        $lists = [
            'domains' => 'data-domains',
            'excludedPaths' => 'data-exclude',
            'pathMasks' => 'data-mask',
            'extraParams' => 'data-params',
        ];
        foreach ($lists as $property => $attribute) {
            $value = trim($this->$property);
            if ($value !== '') {
                $attributes[$attribute] = $value;
            }
        }
        if ($this->canonical) {
            $attributes['data-canonical'] = 'true';
        }
        if ($this->hash) {
            $attributes['data-hash'] = 'true';
        }
        return $attributes;
    }
}
