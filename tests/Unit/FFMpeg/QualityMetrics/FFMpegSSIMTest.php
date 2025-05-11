<?php

namespace Takuya\Tests\Unit\FFMpeg\QualityMetrics;


use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegEncode;
use Takuya\FFMpeg\QualityMetrics\FFMpegSSIM;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\FileSystem\get_extension;

class FFMpegSSIMTest extends TestCase {
  
  public function test_ffmpeg_ssim_compare_movie() {
    $src = test_data('5sec.mp4');
    $dst = mktempfile('ffmpeg-encode',get_extension($src));
    $ffmpeg = new FFMpegEncode($src, $dst, '', '-f mp4 -codec:v libx265 -preset veryfast -crf 40');
    $ffmpeg->start();
    $ffmpeg = new FFMpegSSIM($src, $dst,0,4);
    $ssim = $ffmpeg->getQuality();
    $this->assertArrayHasKey('Y',$ssim);
    $this->assertArrayHasKey('U',$ssim);
    $this->assertArrayHasKey('V',$ssim);
    $this->assertArrayHasKey('All',$ssim);
  }
  public function test_parsing_ssim_result(){
    $str = "[Parsed_ssim_0 @ 0x7f85a8d9d4c0] SSIM ".
           "Y:0.907041 (10.317070) U:0.901775 (10.077778) V:0.896568 (9.853462) All:0.904418 (10.196223)";
    $ret = FFMpegSSIM::parseSSIM($str);
    $this->assertArrayHasKey('Y',$ret);
    $this->assertArrayHasKey('U',$ret);
    $this->assertArrayHasKey('V',$ret);
    $this->assertArrayHasKey('All',$ret);
  }
}
