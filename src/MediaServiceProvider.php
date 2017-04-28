<?php

namespace Origami\Media;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use League\Glide\Responses\LaravelResponseFactory;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;

class MediaServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/media.php' => config_path('media.php')
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/media.php', 'media'
        );

        $config = $this->app->config['media'];

        $this->app->singleton(MediaHelper::class, function ($app) use ($config) {

            $prefix = $config['url'] ?: asset('media');

            $url = UrlBuilderFactory::create($prefix, $config['signkey']);

            return new MediaHelper($url, $config);
        });

        $this->app->alias(MediaHelper::class, 'media');

        $this->app->singleton('League\Glide\Server', function ($app) use ($config) {

            $filesystem = $app->make(Filesystem::class);

            $folder = array_get($config, 'folder');

            $server = ServerFactory::create([
                'source' => $filesystem->getDriver(),
                'cache' => $filesystem->getDriver(),
                'response' => new LaravelResponseFactory,
                'source_path_prefix' => $folder,
                'cache_path_prefix' => $folder.'/.cache',
                'base_url' => 'media/',
            ]);

            if ($defaults = array_get($config, 'defaults')) {
                $server->setDefaults($defaults);
            }

            if ($presets = array_get($config, 'presets', [])) {
                $presets = array_map(function ($preset) {
                    $preset['q'] = 100;
                    return $preset;
                }, $presets);
                $server->setPresets($presets);
            }

            return $server;

        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [MediaHelper::class, 'League\Glide\Server'];
    }

}
