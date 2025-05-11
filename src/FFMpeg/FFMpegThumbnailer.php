<?php

namespace Takuya\FFMpeg;

use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Array\array_flatten;
use function Takuya\Helpers\String\empty_str;
use function Takuya\Helpers\Array\array_insert_after;

class FFMpegThumbnailer {

  public mixed $last_cmd;
  protected mixed $image;

  /**
   * @param string   $src
   * @param int|null $width 省略可能。
   * @param int      $height
   * @param float    $start_time
   */
  public function __construct ( protected string $src,
                                protected ?int   $width = 160,
                                protected int    $height = 90,
                                protected float  $start_time = 0.1 ) {
  }

  public function getImage () {
    if ( empty( $this->image ) ) {
      $this->start();
    }

    return $this->image;
  }

  protected function start () {
    $cmd = $this->buildCommand( $this->src, $this->width, $this->height, $this->start_time );
    $arg = new ExecArgStruct( $cmd );
    $arg->setStderr(fopen('/dev/null','w'));
    $out = mktempfile( '.jpg', 'out' );
    $arg->setStdout( fopen( $out, 'w' ) );
    $p1 = new ProcessExecutor( $arg );
    $p1->start();

    return $this->image = file_get_contents( $out );
  }

  protected function buildCommand ( $src, ?int $width, int $height, float $time_offset ): array {
    //
    $width = $width ?? '-1';
    $ffmpeg = [find_path( 'ffmpeg' ), '-ss', '-i',
      '-vframes 1 ', '-f image2', '-vf', "scale={$width}:{$height}", 'pipe:1'];
    $ffmpeg = array_flatten( array_map( fn( $e ) => preg_split( '/\s+/', trim( $e ) ), array_flatten( $ffmpeg ) ) );
    $ffmpeg = array_filter( $ffmpeg, fn( $e ) => !empty_str( $e ) );
    $ffmpeg = array_insert_after( $ffmpeg, '-i', $src );
    $ffmpeg = array_insert_after( $ffmpeg, '-ss', $time_offset );
    $this->last_cmd = join( " ", $ffmpeg );
    return $ffmpeg;
  }
}
