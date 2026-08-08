<?php

namespace App\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\UpdateThemeSetting;
use App\Models\GlobalSetting;
use App\Models\ThemeSetting;
use Illuminate\Support\Facades\DB;
use Storage;

class ThemeSettingController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.themeSettings';
        $this->activeSettingMenu = 'theme_settings';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('manage_theme_setting') !== 'all');

            return $next($request);
        });
    }

    public function index()
    {
        $themeSetting = ThemeSetting::get();

        // Get theme from single database query and then grouby panel as key
        $themes = $themeSetting->groupBy('panel');

        $this->adminTheme = $themes['admin'][0];
        $this->projectAdminTheme = $themes['project_admin'][0];
        $this->employeeTheme = $themes['employee'][0];
        $this->clientTheme = $themes['client'][0];

        return view('theme-settings.index', $this->data);
    }

    /**
     * @param UpdateThemeSetting $request
     * @return array
     */
    public function store(UpdateThemeSetting $request)
    {
        $setting = $this->company;
        $oldFiles = [];
        $newFiles = [];

        try {
            $this->prepareFileChange($request, $setting, 'logo', 'app-logo', $oldFiles, $newFiles);
            $this->prepareFileChange($request, $setting, 'light_logo', 'app-logo', $oldFiles, $newFiles);
            $this->prepareFileChange($request, $setting, 'login_background', 'login-background', $oldFiles, $newFiles);
            $this->prepareFileChange($request, $setting, 'favicon', 'favicon', $oldFiles, $newFiles);

            DB::transaction(function () use ($request, $setting) {
                $adminTheme = ThemeSetting::where('panel', 'admin')->first();
                $this->themeUpdate($adminTheme, $request->theme_settings[1], $request->primary_color[0]);

                $employeeTheme = ThemeSetting::where('panel', 'employee')->first();
                $this->themeUpdate($employeeTheme, $request->theme_settings[3], $request->primary_color[1]);

                $clientTheme = ThemeSetting::where('panel', 'client')->first();
                $this->themeUpdate($clientTheme, $request->theme_settings[4], $request->primary_color[2]);

                $setting->logo_background_color = $request->logo_background_color;
                $setting->auth_theme = $request->auth_theme;
                $setting->auth_theme_text = $request->auth_theme_text;
                $setting->app_name = $request->app_name;
                $setting->header_color = $request->global_header_color;
                $setting->sidebar_logo_style = $request->sidebar_logo_style;
                $setting->save();
            });
        } catch (\Throwable $exception) {
            foreach ($newFiles as $file) {
                Files::deleteFile($file['name'], $file['folder']);
            }

            throw $exception;
        }

        foreach ($oldFiles as $file) {
            Files::deleteFile($file['name'], $file['folder']);
        }

        session()->forget(['admin_theme', 'employee_theme', 'client_theme', 'company', 'companyOrGlobalSetting', 'user.company']);
        cache()->forget('global_setting');

        return Reply::redirect(route('theme-settings.index'), __('messages.updateSuccess'));
    }

    private function prepareFileChange($request, $setting, string $field, string $folder, array &$oldFiles, array &$newFiles): void
    {
        $oldFile = $setting->{$field};

        if ($request->hasFile($field)) {
            $newFile = Files::uploadLocalOrS3($request->file($field), $folder);
            $setting->{$field} = $newFile;
            $newFiles[] = ['name' => $newFile, 'folder' => $folder];

            if ($oldFile && $oldFile !== $newFile) {
                $oldFiles[] = ['name' => $oldFile, 'folder' => $folder];
            }

            return;
        }

        if ($request->input($field . '_delete') === 'yes') {
            $setting->{$field} = null;

            if ($oldFile) {
                $oldFiles[] = ['name' => $oldFile, 'folder' => $folder];
            }
        }
    }

    private function themeUpdate($updateObject, $themeSetting, $primaryColor)
    {
        $updateObject->header_color = $primaryColor;
        $updateObject->sidebar_theme = $themeSetting['sidebar_theme'];
        if (isset($themeSetting['sidebar_color'])) {
            $updateObject->sidebar_color = $themeSetting['sidebar_color'];
        }
        $updateObject->save();
        session()->forget(['admin_theme', 'employee_theme', 'client_theme']);
    }

}
