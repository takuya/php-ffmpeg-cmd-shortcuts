<?php

namespace Takuya\Tests\Unit\FFMpeg\QualityMetrics;

use Takuya\FFMpeg\FFProbe;
use Takuya\FFMpeg\FFMpegEncode;
use Takuya\FFMpeg\QualityMetrics\FFMpeg_PSNR_SSSIM;
use Takuya\FFMpeg\QualityMetrics\FFMpegVideoQualityAssessment;
use function Takuya\Helpers\FileSystem\mktempfile;
use Takuya\Tests\TestCase;
use function Takuya\Helpers\FileSystem\get_extension;

class FFMpegSSIMPSNR_filter_complexTest extends TestCase {
  public function test_calc_sampleing_duration_and_sample_start_time(){
    [$ss,$t] = FFMpegVideoQualityAssessment::SamplingTimes(3600);
    $this->assertEquals(true,3600>$ss+$t);
    //
    [$ss,$t] = FFMpegVideoQualityAssessment::SamplingTimes(7200);
    $this->assertEquals(true,7200>$ss+$t);
    //
    [$ss,$t] = FFMpegVideoQualityAssessment::SamplingTimes(1500);
    $this->assertEquals(true,1500>$ss+$t);
    //
    [$ss,$t] = FFMpegVideoQualityAssessment::SamplingTimes(700);
    $this->assertEquals(true,700>$ss+$t);
    //
    [$ss,$t] = FFMpegVideoQualityAssessment::SamplingTimes(250);
    $this->assertEquals(true,700>$ss+$t);

  }
  public function test_ffmpeg_quality_check_ssim_psnr_full_by_filter_complex() {
    $src = test_data('5sec.mp4');
    $dst = mktempfile('ffmpeg-encode',get_extension($src));
    $ffmpeg = new FFMpegEncode($src, $dst, '', '-f mp4 -codec:v libx265 -preset veryfast -crf 40');
    $ffmpeg->start();
    $ffmpeg = new FFMpeg_PSNR_SSSIM($src, $dst);
    $q = $ffmpeg->getQuality();
    $ssim = $q['ssim'];
    $this->assertArrayHasKey('Y',$ssim);
    $this->assertArrayHasKey('U',$ssim);
    $this->assertArrayHasKey('V',$ssim);
    $this->assertArrayHasKey('All',$ssim);
    $psnr = $q['psnr'];
    $this->assertArrayHasKey('y',$psnr);
    $this->assertArrayHasKey('u',$psnr);
    $this->assertArrayHasKey('v',$psnr);
    $this->assertArrayHasKey('average',$psnr);
    $this->assertArrayHasKey('min',$psnr);
    $this->assertArrayHasKey('max',$psnr);
  }

  public function test_ffmpeg_quality_check_ssim_psnr_partial_by_filter_complex() {
    $src = test_data('5sec.mp4');
    $dst = mktempfile('ffmpeg-encode',get_extension($src));
    $ffmpeg = new FFMpegEncode($src, $dst, '', '-f mp4 -codec:v libx265 -preset veryfast -crf 40');
    $ffmpeg->start();
    $ffmpeg = new FFMpeg_PSNR_SSSIM($src, $dst,0,4);
    $q = $ffmpeg->getQuality();
    $ssim = $q['ssim'];
    $this->assertArrayHasKey('Y',$ssim);
    $this->assertArrayHasKey('U',$ssim);
    $this->assertArrayHasKey('V',$ssim);
    $this->assertArrayHasKey('All',$ssim);
    $psnr = $q['psnr'];
    $this->assertArrayHasKey('y',$psnr);
    $this->assertArrayHasKey('u',$psnr);
    $this->assertArrayHasKey('v',$psnr);
    $this->assertArrayHasKey('average',$psnr);
    $this->assertArrayHasKey('min',$psnr);
    $this->assertArrayHasKey('max',$psnr);
  }
}
