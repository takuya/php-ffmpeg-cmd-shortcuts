<?php

namespace Takuya\FFMpeg;

use InvalidArgumentException;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Shell\shell_args_cleanup;
use function Takuya\Helpers\Array\array_replace_entry;
use function Takuya\Helpers\Array\array_insert_after;
use function Takuya\Helpers\FileSystem\get_extension;

class FFMpegResizeMovie {

  public string $stderr_string;
  protected string $last_cmd;
  /**
   * @var string[]
   */
  protected array  $files;
  protected string $dst;
  protected string $src;
  protected int    $height;
  protected int    $width;
  protected string $format;
  
  public function getOutput (): string {
    return $this->dst;
  }

  public function setSrc ( string $src ): void {
    $this->src = $src;
  }

  public function setHeight ( int $height ): void {
    $this->height = $height;
  }

  public function setWidth ( int $width ): void {
    $this->width = $width;
  }


  public function resize ( $height = null, $width = null, $src = null, $dst = null, $format = null,
                           $encode_opt = null, $decode_opt = null ) {
    $this->src = $src ?? $this->src ?? throw new InvalidArgumentException( 'src not specified' );
    $this->dst = $dst ?? mktempfile( 'ffmpeg-resize', get_extension($src) );
    $this->height = $height ?? $this->height ?? -1;
    $this->width = $width ?? $this->width ?? -1;
    $cmd = new ExecArgStruct( ...$this->buildCommand( $this->height, $this->width, $this->src, $this->dst, $format, $encode_opt, $decode_opt ) );
    $p = new ProcessExecutor( $cmd );
    $p->start();
    if ( !$p->isSuccessful() ) {
      $this->stderr_string = $p->getErrout();
    }
    //
    return $this->dst;
  }

  protected function buildCommand (
    int          $h,
    int          $w,
    string       $input,
    string       $output,
    ?string       $format,
    string|array $decode_opt = null,
    string|array $encode_opt = null ): array {
    $ffmpeg = [find_path( 'ffmpeg' ), '-y', $decode_opt,['-i INPUT'], ['-vf', "scale={$w}:{$h}"], $encode_opt, 'OUTPUT'];
    $ffmpeg = shell_args_cleanup( $ffmpeg );
    $ffmpeg = array_replace_entry( $ffmpeg, 'INPUT', $input );
    $ffmpeg = array_replace_entry( $ffmpeg, 'OUTPUT', $output );
    !empty( $format ) && array_insert_after( $ffmpeg, 'OUTPUT', $format );
    $this->last_cmd = join( ' ', $ffmpeg );
    return $ffmpeg;
  }

  public function setFormat ( string $format = 'mp4' ): void {
    $this->format = $format;
  }
}
