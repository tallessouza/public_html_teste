<?php

namespace App\Services\Theme\Traits;

use App\Helpers\Classes\Helper;
use App\Models\Extension;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\SettingTwo;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Services\Theme\ThemeService;
use App\Repositories\Contracts\ExtensionRepositoryInterface;

trait InstallTheme
{ 
    public function install(string $extensionSlug): bool|array
    {
		if($extensionSlug == 'default'){
			setting([
                'front_theme' => 'default',
                'dash_theme' => 'default'
            ])->save();

			Artisan::call('optimize:clear');

			return [
				'success' => true,
				'status' => true,
				'message' => trans('Theme installed successfully')
			];
		}
		
        $dbExtension = Extension::query()->where('slug', $extensionSlug)
            ->where('is_theme', true)
            ->firstOrFail();


        $responseExtension = $this->extensionRepository->find($dbExtension->getAttribute('slug'));

        $version = data_get($responseExtension, 'version');

        $response = $this->extensionRepository->install(
            $dbExtension->getAttribute('slug'),
            $version
        );

        if ($response->failed()) {
            return [
                'status' => false,
                'message' => trans('Failed to download the theme file')
            ];
        }

        $zipContent = $response->body();

        Storage::disk('local')->put('file.zip', $zipContent);

        $checkZip = $this->zipArchive->open(
            Storage::disk('local')->path('file.zip')
        );

        if ($checkZip) {

            $this->zipExtractPath = storage_path('app/zip-extract');

            $this->zipArchive->extractTo($this->zipExtractPath);

            $this->zipArchive->close();

            Storage::disk('local')->delete('file.zip');

            try {
                # index json
                $this->getIndexJson();

                if (empty($this->indexJsonArray)) {
                    return [
                        'status' => false,
                        'message' => trans('index.json not found')
                    ];
                }

                $theme = data_get($this->indexJsonArray, 'slug');

                if (! $theme) {
                    return [
                        'status' => false,
                        'message' => trans('index.json not found')
                    ];
                }

                $files = Storage::disk('local')->allFiles('zip-extract');

                foreach ($files as $file) {
					if(Str::contains($file, 'zip-extract/public/')) {
						$this->copyThemeFile($file, '/'.$theme.'/', 'themes');
					}
					elseif (Str::contains($file, "zip-extract/theme/")) {
                        $this->copyThemeFile($file, '/'.$theme.'/');
                    } else {
                        $this->copyThemeFile($file, '/'.$theme.'/');
                    }
                }

                # delete zip extract dir
                (new Filesystem)->deleteDirectory($this->zipExtractPath);

                Extension::query()->where('slug', $extensionSlug)
                    ->update([
                        'installed' => 1,
                        'version' => $version,
                    ]);
			
				$item = $this->extensionRepository->find($extensionSlug);
				ThemeService::MergeThemeBuild($theme);
				if($item['theme_type'] == 'Frontend'){
					setting(['front_theme' => $extensionSlug])->save();
				}
				else if($item['theme_type'] == 'Dashboard'){
					setting(['dash_theme' => $extensionSlug])->save();
				}else{
					setting(['front_theme' => $extensionSlug, 'dash_theme' => $extensionSlug])->save();
				}
				
				Artisan::call('optimize:clear');

                return [
                    'success' => true,
                    'status' => true,
                    'message' => trans('Theme installed successfully')
                ];

            }catch (Exception $e) {
                return [
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
    }

	public function copyThemeFile(string $path = '', string $replace = '', string $disk = 'views'): void
	{
		$newPath = str_replace(['zip-extract/theme/', 'zip-extract/public/', 'zip-extract/'], $replace, $path);
		// For other files, simply add them
		$content = Storage::disk('local')->get($path);
		Storage::disk($disk)->put($newPath, $content);
	}


    /**
     * Check license function
     *
     * @param string|null $licenseKey
     * @return bool|string|null
     */
    public function checkLicense(?string $licenseKey = null): null|bool|string
    {
        $licenseKey = $licenseKey ?? Helper::settingTwo('liquid_license_domain_key');

        $response = Http::get('https://portal.liquid-themes.com/api/license/' . DIRECTORY_SEPARATOR . $licenseKey);

        if ($response->failed()) {
            return false;
        }

        return $response->json('owner.email');
    }
}