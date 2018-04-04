<?php

namespace Origami\Media;

use Illuminate\Routing\Controller;
use League\Glide\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureException;

class MediaController extends Controller {

    protected $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function view($filename, Request $request)
    {
        $path = config('media.folder').'/'.$filename;

        if ( ! Storage::has($path) ) {
            return $this->missingResponse();
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ( in_array($extension, ['jpg','jpeg','png','gif']) ) {
            return $this->imageResponse($path);
        }

        if ( $extension == 'mp4' ) {
            return $this->videoResponse($path);
        }

        return $this->response($path);
    }

    protected function missingResponse()
    {
        return abort(404);
    }

    protected function response($file)
    {
        $contents = Storage::read($file);
        $mime = Storage::getMimetype($file);

        return (new Response($contents, 200))
            ->header('Content-Type', $mime);
    }

    protected function videoRangeRequest($file)
    {
        $fp = Storage::readStream($file);
        $mime = Storage::getMimetype($file);
        $size = Storage::getSize($file);
        $length = $size;
        $start  = 0;
        $end    = $size - 1;

        header("Content-type: $mime");
        header("Accept-Ranges: 0-$length");

        if( isset($_SERVER['HTTP_RANGE']) ) {
            $c_start = $start;
            $c_end   = $end;
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }

            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            } else {
                $range   = explode('-', $range);
                $c_start = $range[0];
                $c_end   = ( isset($range[1]) && is_numeric($range[1]) ) ? $range[1] : $size;
            }

            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $start  = $c_start;
            $end    = $c_end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }

        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: $length");

        $buffer = 1024 * 8;
        while( !feof($fp) && ($p = ftell($fp)) <= $end ) {
            if( $p + $buffer > $end ) { $buffer = $end - $p + 1; }
            set_time_limit(0);
            echo fread($fp, $buffer);
            flush();
        }

        fclose($fp);
    }

    protected function videoResponse($file)
    {
        if (isset($_SERVER['HTTP_RANGE'])) {
            return $this->videoRangeRequest($file);
        }

        $contents = Storage::read($file);
        $mime = Storage::getMimetype($file);
        $length = Storage::getSize($file);

        return (new Response($contents, 200))
            ->header('Content-Type', $mime)
            ->header('Content-Length', $length);
    }

    protected function imageResponse($file)
    {
        try {

            $config = config('media');
            $key = array_get($config, 'signkey');
            $request = app('request');

            if ( $request->query() && $key) {
                SignatureFactory::create($key)->validateRequest($file, $request->query());
            }

            return $this->server->getImageResponse($file, $request->query());

        } catch (SignatureException $e) {
            return response('Invalid image signature', 403);
        }
    }

}
