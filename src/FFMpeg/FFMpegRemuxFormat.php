<?php

namespace Takuya\FFMpeg;

use InvalidArgumentException;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Array\array_replace_entry;
use function Takuya\Helpers\Shell\shell_args_cleanup;

class FFMpegRemuxFormat {

  protected string $last_cmd;
  protected ?int $exitCode;
  protected string|false $error_out;

  public function remux ( $src, $dst = null, $format = null, $decode_opt = '', $encode_opt = '' ) {
    $this->checkSrc( $src );
    $dst = $dst ?? mktempfile( 'ffmpeg-remux-work' );
    $cmd = new ExecArgStruct( ...$this->buildCommand( $src, $dst, $format, $decode_opt, $encode_opt ) );
    $cmd->setStderr(fopen('/dev/null','w'));
    $p = new ProcessExecutor( $cmd );
    $p->start();

    if ( 0 !== ( $this->exitCode = $p->getExitCode() ) ) {
      $this->error_out = $p->getErrout();
    }

    return $dst;
  }

  protected function checkSrc ( $src ) {
    if ( !file_exists( $src ) ) {
      throw new InvalidArgumentException( "ファイルがない。" );
    }
  }

  protected function buildCommand ( $src, $dst, $format, $decode_opt, $encode_opt ): array {
    $ffmpeg = [
      find_path( 'ffmpeg' ),
      '-y -i SRC -codec copy',
      $decode_opt,
      $encode_opt,
      ( $format ? ' -f CONTAINER' : '' ),
      'OUTPUT',
    ];
    $ffmpeg = shell_args_cleanup( $ffmpeg );
    $ffmpeg = array_replace_entry( $ffmpeg, 'SRC', $src );
    $ffmpeg = array_replace_entry( $ffmpeg, 'CONTAINER', $format );
    $ffmpeg = array_replace_entry($ffmpeg,'OUTPUT',$dst);
    $this->last_cmd = join( ' ', $ffmpeg );

    return $ffmpeg;
  }

  public function getErrout () {
    return $this->error_out;
  }

  public function isSuccessful () {
    return $this->exitCode === 0;
  }
}
