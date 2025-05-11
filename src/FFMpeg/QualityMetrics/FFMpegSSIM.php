<?php

namespace Takuya\FFMpeg\QualityMetrics;

use Override;
use function Takuya\Helpers\Array\array_insert_before;

class FFMpegSSIM extends FFMpegVideoQualityAssessment {

  protected array $ssim;

  #[Override]
  public static function parseLine ( $line ): array {
    return static::parseSSIM( $line );
  }

  public static function parseSSIM ( $str ): array {
    // sample
    // "[Parsed_ssim_0 @ 0x7f85a8d9d4c0] SSIM Y:0.907041 (10.317070) U:0.901775 (10.077778) V:0.896568 (9.853462) All:0.904418 (10.196223)"
    preg_match_all(
      "/\[Parsed_ssim_.+] SSIM Y:(?<Y>0.\d+).+ U:(?<U>0.\d+).+ V:(?<V>0.\d+).+ All:(?<All>0.\d+)/",
      $str,
      $m );
    $m = array_filter( $m, fn( $key ) => is_string( $key ), ARRAY_FILTER_USE_KEY );
    $m = array_map( fn( $e ) => $e[0] ?? null, $m );
    return $m;
  }

  #[Override]
  public static function match_result ( $line ): bool {
    return preg_match( '/\[Parsed_ssim/', $line ) > 0;
  }

  #[Override]
  protected function buildCommand ( $src, $ref ): array {
    $ffmpeg = parent::ffmpeg_compare_command( $src, $ref);
    $ffmpeg = array_insert_before( $ffmpeg, '-an', ['-lavfi', 'ssim'] );
    $this->last_cmd = join( " ", $ffmpeg );

    return $ffmpeg;
  }
}
