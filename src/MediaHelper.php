<?php

namespace Origami\Media;

use League\Glide\Urls\UrlBuilder;

class MediaHelper {

    /**
     * @var UrlBuilder
     */
    private $url;
    /**
     * @var array
     */
    private $config;

    public function __construct(UrlBuilder $url, array $config)
    {
        $this->url = $url;
        $this->config = $config;
    }

    public function url($path)
    {
        return $this->config['url'].'/'.trim($path,'/');
    }

    public function imageUrl($path, $width = null, $height = null, array $extra = [])
    {
        if ( ! is_null($width) && ! is_numeric($width) ) {
            return $this->presetImageUrl($path, $preset = $width);
        }

        $path = trim($path, '/');
        $params = $extra;

        if ( is_null($width) && is_null($height) ) {
            return $this->url->getUrl($path, $params);
        }

        if ( ! is_null($width) ) {
            $params['w'] = $width;
        }

        if ( ! is_null($height) ) {
            $params['h'] = $height;
        }

        return $this->url->getUrl($path, $params);
    }

    public function presetImageUrl($path, $preset, array $extra = [])
    {
        $path = trim($path, '/');
        $params = $extra;

        $presets = config('media.presets', []);

        if ( ! array_key_exists($preset, $presets) ) {
            throw new MediaException('Preset ' . $preset . ' does not exist');
        }

        $params['p'] = $preset;

        return $this->url->getUrl($path, $params);
    }

}
