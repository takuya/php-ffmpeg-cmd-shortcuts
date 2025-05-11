<?php

namespace Takuya\FFMpeg\QualityMetrics;

use Override;
use function Takuya\Helpers\Array\array_insert_before;

/**
 * calculate PSNR by using `-lavfi psnr`
 */
class FFMpegPSNR extends FFMpegVideoQualityAssessment {

  #[Override]
  public static function parseLine ( $line ): array {
    return static::parsePSNR( $line );
  }

  public static function parsePSNR ( $str ): array {
    // sample
    //  "[Parsed_psnr_0 @ 0x557406d1f500] PSNR y:35.169108 u:46.120751 v:46.434170 average:36.764856 min:15.246958 max:46.002477";
    preg_match_all(
      "/\[Parsed_psnr_.+] PSNR "."y:(?<y>[.\d]+).+ u:(?<u>[.\d]+).+ v:(?<v>[.\d]+).+ "
      ."average:(?<average>[.\d]+).+ min:(?<min>[.\d]+).+ max:(?<max>.+)"."/",
      $str,
      $m );
    $m = array_filter( $m, fn( $key ) => is_string( $key ), ARRAY_FILTER_USE_KEY );
    $m = array_map( fn( $e ) => $e[0] ?? null, $m );

    return $m;
  }

  #[Override]
  public static function match_result ( $line ): bool {
    return preg_match( '/\[Parsed_psnr_/', $line ) > 0;
  }

  #[Override]
  protected function buildCommand ( $src, $ref ): array {
    $ffmpeg = parent::ffmpeg_compare_command( $src, $ref );
    $ffmpeg = array_insert_before( $ffmpeg, '-an', ['-lavfi', 'psnr'] );
    $this->last_cmd = join( " ", $ffmpeg );

    return $ffmpeg;
  }
}
