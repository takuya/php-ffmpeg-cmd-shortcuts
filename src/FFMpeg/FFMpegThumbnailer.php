<?php

namespace Takuya\FFMpeg;

use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Array\array_flatten;
use function Takuya\Helpers\String\empty_str;
use function Takuya\Helpers\Array\array_insert_after;
use function Takuya\Helpers\Shell\build_cmd;
use function Takuya\Helpers\String\cjoin;

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

    $cmd_struct = build_cmd(
    'ffmpeg -ss %SS% -i %SRC% -vframes 1 -f %TYPE% -vf scale=%W%:%H%  pipe:1', [
      "%SRC%"  => $src,
      "%SS%"   => $time_offset,
      "%TYPE%" => "image2",
      "%W%"    => $width??-1,
      "%H%"    => $height,
    ]);
    //
    $this->last_cmd = cjoin($cmd_struct, " ");
    return $cmd_struct;
  }
}
