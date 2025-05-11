<?php

namespace Takuya\FFMpeg\QualityMetrics;

use Override;
use RuntimeException;
use function Takuya\Helpers\Array\array_insert_before;

/**
 * calculate PSNR by using `-lavfi psnr`
 */
class FFMpeg_PSNR_SSSIM extends FFMpegVideoQualityAssessment {
  #[Override]
  public function parseResult ( $str ): array {
    $this->quality = array_merge( $this->quality ?? [], self::parseLine( $str ) );
    return $this->quality;
  }

  public static function parseLine ( $line ): array {
    if ( FFMpegSSIM::match_result( $line ) ) {
      return ['ssim' => FFMpegSSIM::parseLine( $line )];
    }
    if ( FFMpegPSNR::match_result( $line ) ) {
      return ['psnr' => FFMpegPSNR::parseLine( $line )];
    }
    throw new RuntimeException( 'not a suitable result' );
  }

  public static function match_result ( $line ): bool {
    return FFMpegPSNR::match_result( $line ) || FFMpegSSIM::match_result( $line );
  }

  #[Override]
  protected function buildCommand ( $src, $ref ): array {
    $ffmpeg = parent::ffmpeg_compare_command( $src, $ref);
    $ffmpeg = array_insert_before( $ffmpeg, '-an', ['-filter_complex', 'ssim;[0:v][1:v]psnr'] );
    $this->last_cmd = join( " ", $ffmpeg );

    return $ffmpeg;
  }

}
