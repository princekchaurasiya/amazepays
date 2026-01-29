<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Setting;

class SettingsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file.
     */
    public function run()
    {
        $setting = $this->findSetting('site.title');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.site.title'),
                'value'        => __('voyager::seeders.settings.site.title'),
                'details'      => '',
                'type'         => 'text',
                'order'        => 1,
                'group'        => 'Site',
            ])->save();
        }

        $setting = $this->findSetting('site.description');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.site.description'),
                'value'        => __('voyager::seeders.settings.site.description'),
                'details'      => '',
                'type'         => 'text',
                'order'        => 2,
                'group'        => 'Site',
            ])->save();
        }

        $setting = $this->findSetting('site.logo');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.site.logo'),
                'value'        => '',
                'details'      => '',
                'type'         => 'image',
                'order'        => 3,
                'group'        => 'Site',
            ])->save();
        }

        $setting = $this->findSetting('site.google_analytics_tracking_id');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.site.google_analytics_tracking_id'),
                'value'        => '',
                'details'      => '',
                'type'         => 'text',
                'order'        => 4,
                'group'        => 'Site',
            ])->save();
        }

        $setting = $this->findSetting('admin.bg_image');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.admin.background_image'),
                'value'        => '',
                'details'      => '',
                'type'         => 'image',
                'order'        => 5,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.title');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.admin.title'),
                'value'        => 'Voyager',
                'details'      => '',
                'type'         => 'text',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.description');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.admin.description'),
                'value'        => __('voyager::seeders.settings.admin.description_value'),
                'details'      => '',
                'type'         => 'text',
                'order'        => 2,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.loader');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.admin.loader'),
                'value'        => '',
                'details'      => '',
                'type'         => 'image',
                'order'        => 3,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.icon_image');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.admin.icon_image'),
                'value'        => '',
                'details'      => '',
                'type'         => 'image',
                'order'        => 4,
                'group'        => 'Admin',
            ])->save();
        }

        $setting = $this->findSetting('admin.google_analytics_client_id');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => __('voyager::seeders.settings.admin.google_analytics_client_id'),
                'value'        => '',
                'details'      => '',
                'type'         => 'text',
                'order'        => 1,
                'group'        => 'Admin',
            ])->save();
        }

        // Woohoo API Settings
        $setting = $this->findSetting('api.woohoo_url');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Woohoo API Base URL',
                'value'        => 'sandbox.woohoo.in',
                'details'      => json_encode(['description' => 'Base URL for Woohoo API (without https://)']),
                'type'         => 'text',
                'order'        => 1,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.clientId');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Woohoo Client ID',
                'value'        => '',
                'details'      => json_encode(['description' => 'Client ID for Woohoo API authentication']),
                'type'         => 'text',
                'order'        => 2,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.qs_username');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Woohoo Username',
                'value'        => '',
                'details'      => json_encode(['description' => 'Username for Woohoo API authentication']),
                'type'         => 'text',
                'order'        => 3,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.qs_password');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Woohoo Password',
                'value'        => '',
                'details'      => json_encode(['description' => 'Password for Woohoo API authentication']),
                'type'         => 'password',
                'order'        => 4,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.qs_clientSecret');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Woohoo Client Secret',
                'value'        => '',
                'details'      => json_encode(['description' => 'Client Secret for Woohoo API']),
                'type'         => 'text',
                'order'        => 5,
                'group'        => 'API',
            ])->save();
        }

        // KGen API Settings
        $setting = $this->findSetting('api.kgen_base_url');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'KGen API Base URL',
                'value'        => 'https://stage-platform.getkgen.com',
                'details'      => json_encode(['description' => 'Base URL for KGen API']),
                'type'         => 'text',
                'order'        => 10,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.kgen_api_key');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'KGen API Key',
                'value'        => '',
                'details'      => json_encode(['description' => 'API Key for KGen authentication']),
                'type'         => 'text',
                'order'        => 11,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.kgen_tenant_id');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'KGen Tenant ID',
                'value'        => '',
                'details'      => json_encode(['description' => 'Tenant ID for KGen API']),
                'type'         => 'text',
                'order'        => 12,
                'group'        => 'API',
            ])->save();
        }

        // Lysto API Settings
        $setting = $this->findSetting('api.lysto_base_url');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Lysto API Base URL',
                'value'        => '',
                'details'      => json_encode(['description' => 'Base URL for Lysto API']),
                'type'         => 'text',
                'order'        => 20,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.lysto_api_key');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Lysto API Key',
                'value'        => '',
                'details'      => json_encode(['description' => 'API Key for Lysto authentication']),
                'type'         => 'text',
                'order'        => 21,
                'group'        => 'API',
            ])->save();
        }

        // Value Design API Settings  
        $setting = $this->findSetting('api.value_design_base_url');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Value Design API Base URL',
                'value'        => '',
                'details'      => json_encode(['description' => 'Base URL for Value Design API']),
                'type'         => 'text',
                'order'        => 30,
                'group'        => 'API',
            ])->save();
        }

        $setting = $this->findSetting('api.value_design_api_key');
        if (!$setting->exists) {
            $setting->fill([
                'display_name' => 'Value Design API Key',
                'value'        => '',
                'details'      => json_encode(['description' => 'API Key for Value Design authentication']),
                'type'         => 'text',
                'order'        => 31,
                'group'        => 'API',
            ])->save();
        }
    }

    /**
     * [setting description].
     *
     * @param [type] $key [description]
     *
     * @return [type] [description]
     */
    protected function findSetting($key)
    {
        return Setting::firstOrNew(['key' => $key]);
    }
}
