<?php

namespace Takuya\FFMpeg\QualityMetrics;

use Takuya\FFMpeg\FFMpegSliceTime;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use RuntimeException;
use Exception;
use Closure;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\String\str_lines;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Shell\shell_args_cleanup;
use function Takuya\Helpers\Array\array_replace_entry;

abstract class FFMpegVideoQualityAssessment {

  public mixed $last_cmd;
  protected array $exitCode;
  protected array $quality;
  public string $output = '';

  /* @var callable */
  public function __construct ( protected string $source_original,
                                protected string $reference_encoded,
                                protected ?int   $sampling_start = null,
                                protected ?int   $sampling_duration = null ) {

    $this->prepare();
  }
  protected function prepare(){
    if(!empty($this->sampling_start) && !empty($this->sampling_duration)){
      $this->source_original = $this->sliceMovie($this->source_original);
      $this->reference_encoded = $this->sliceMovie($this->reference_encoded);
    }
    $this->output='';
  }
  protected function sliceMovie($in):string {
    $out = mktempfile('xxx.mp4','ssim_check_sliced');
    $slicer = new FFMpegSliceTime($in,$out,$this->sampling_start,$this->sampling_duration,FFMpegSliceTime::FORCE);
    $slicer->start();
    if(!$slicer->isSuccessful()){
      throw new RuntimeException('ffmpeg command error');
    }
    return $out;
  }

  public static function SamplingTimes ( int $duration ) {
    // サンプルデータの取得範囲を設定する。
    // 設定が不適切な場合は適宜調整する。
    if ( $duration > 1800 ) {
      //真ん中あたり 0.45-0.55を対象にする。
      $ss = $duration*0.45;
      $t = $duration*0.1;
    } else {
      if ( $duration > 300 ) {
        $ss = $duration*0.45;//真ん中あたり
        $t = 60*( intval( $duration/900 ) + 1 );
      } else { // 5分未満ならほぼ全部比較する
        $ss = 0;
        $t = intval( floatval( $duration )*0.9 ) + 1;
      }
    }

    return [$ss, $t];
  }

  /**
   * @throws RuntimeException;
   */
  public function getQuality (): ?array {
    if ( false == isset( $this->exitCode['ffmpeg'] ) || false === isset( $this->quality ) ) {
      $this->start();
    }
    return $this->quality ?? null;
  }

  /**
   * @return void
   * @throws RuntimeException;
   */
  public function start (): void {
    try {
      $cmd = $this->buildCommand( $this->reference_encoded, $this->source_original );
      $args = new ExecArgStruct( $cmd );
      $errout = fopen('php://temp','w+');
      $args->setStderr($errout);
      $p1 = new ProcessExecutor( $args );
      $p1->start();
      $this->exitCode['ffmpeg'] = $p1->getExitCode();
      if( false === $this->isSuccessful() ) {
        throw new RuntimeException( "ffmpeg exit non zero." );
      }
      rewind($errout) && ($this->output = stream_get_contents($errout)) && fclose($errout);
      $retrieveQuality = function($e){
        $lines = array_filter($e,fn( $line ) => $this->isResult( $line ));
        return array_map(fn($e) => $this->parseResult( $e ),$lines);
      };
      //
      $retrieveQuality(str_lines( $this->output));
    } catch (Exception $e) {
      $msg = [
        get_class( $e ) => [$e->getMessage(), $e->getTraceAsString()],
        'ffmpeg'        => $p1->getErrout(),
        'cmd'           => join(' ',$cmd),
      ];
      throw new RuntimeException( json_encode( $msg ) );
    }
  }

  abstract protected function buildCommand ( $src, $ref ): array;

  public function isSuccessful (): bool {
    return $this->exitCode['ffmpeg'] === 0;
  }

  protected function isResult ( $str ): bool {
    return static::match_result( $str );
  }

  abstract public static function match_result ( $line ): bool;

  public function parseResult ( $str ): array {
    $this->quality = static::parseLine( $str );
    return $this->quality;
  }

  abstract static function parseLine ( $line ): array;

  protected function ffmpeg_compare_command ( $ref, $src): array {
    // ffmpeg -i 調べたい動画 -i 元の動画 -filter_complex と順番を留意。
    $ffmpeg = [find_path( 'ffmpeg' ), ' -i ENC -i ORIG -an -f null pipe:1 -threads 7'];
    $ffmpeg = shell_args_cleanup( $ffmpeg );
    // -i が２つあるのでややこしい。
    $ffmpeg = array_replace_entry( $ffmpeg, 'ENC', $ref );
    $ffmpeg = array_replace_entry( $ffmpeg, 'ORIG', $src );
    //
    $this->last_cmd = join( " ", $ffmpeg );

    return $ffmpeg;
  }
}
