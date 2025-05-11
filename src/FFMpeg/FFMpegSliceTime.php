<?php

namespace Takuya\FFMpeg;

use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use InvalidArgumentException;
use Takuya\FFMpeg\Exceptions\SliceTimeFailedException;
use function Takuya\Helpers\FileSystem\get_extension;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\Shell\shell_args_cleanup;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Array\array_insert_after;
use function Takuya\Helpers\Array\array_replace_entry;

class FFMpegSliceTime {

  public const  FORCE = true;
  public mixed $last_cmd;
  protected array $exitCode;

  public static function cut_movie ( string $src,
                                     int    $ss,
                                     int    $t,
                                     string $output = null,
                                     string $extension =null,
                                     string $basename = null
  ): ?string {
    $basename = $basename?? basename($src,get_extension($src));
    $extension = $extension ?? get_extension($src);
    try {
      $out  = $output ?? mktempfile( $basename.$extension??get_extension($src),'ffmpeg_cut_movie' );
      $slicer = new FFMpegSliceTime( $src, $out, $ss, $t, FFMpegSliceTime::FORCE );
      $slicer->start();
      if ( !$slicer->isSuccessful() ) {
        throw new SliceTimeFailedException( 'ffmpeg command error' );
      }
      return $out;

    }catch (SliceTimeFailedException){
      return null;
    }
  }
  public function __construct ( protected string $source_path,
                                protected string $output_path,
                                protected int    $start_sec,
                                protected int  $duration_sec,
                                protected bool $force = false
  ) {
    if ( !$force && !$this->available() ) {
      throw new InvalidArgumentException( 'duration is short.' );
    }
  }

  protected function available () {
    $ret = ( new FFProbe() )->movie_info( $this->source_path );
    return isset( $ret['format']['duration'] ) ? $ret['format']['duration'] > $this->start_sec + $this->duration_sec : null;
  }

  public function start () {
    $cmd = $this->buildCommand( $this->source_path, $this->output_path, $this->start_sec, $this->duration_sec );
    $args = new ExecArgStruct( $cmd );
    $args->setStderr(fopen('php://temp','w'));
    $p1 = new ProcessExecutor( $args );
    $p1->start();
    $this->exitCode['ffmpeg'] = $p1->getExitCode();
    if ( !$this->isSuccessful() ) {
      throw $p1->getLastException() ?? new \RuntimeException('slice failed');
    }
  }

  protected function buildCommand ( $src, $out, $ss, $dur ): array {
    $ffmpeg = [find_path( 'ffmpeg' ), '-y -ss -i INPUT -t -c copy OUTPUT'];
    $ffmpeg = shell_args_cleanup( $ffmpeg );
    $ffmpeg = array_insert_after( $ffmpeg, '-ss', $ss );
    $ffmpeg = array_replace_entry($ffmpeg ,'INPUT',$src);
    $ffmpeg = array_replace_entry($ffmpeg,'OUTPUT',$out);
    $ffmpeg = array_insert_after( $ffmpeg, '-t', $dur );
    $this->last_cmd = join( " ", $ffmpeg );

    return $ffmpeg;
  }

  public function isSuccessful (): bool {
    return $this->exitCode['ffmpeg'] === 0;
  }
}
