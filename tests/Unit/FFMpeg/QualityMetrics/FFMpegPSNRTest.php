<?php

namespace Takuya\Tests\Unit\FFMpeg\QualityMetrics;

use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegEncode;
use Takuya\FFMpeg\QualityMetrics\FFMpegPSNR;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\FileSystem\get_extension;

class FFMpegPSNRTest extends TestCase {
  
  public function test_ffmpeg_psnr_compare_movie() {
    $src = test_data('5sec.mp4');
    $dst = mktempfile('ffmpeg-encode').get_extension($src);
    $ffmpeg = new FFMpegEncode($src, $dst, '', '-f mp4 -codec:v libx265 -preset veryfast -crf 40');
    $ffmpeg->start();
    $ffmpeg = new FFMpegPSNR($src, $dst,0,4);
    $psnr = $ffmpeg->getQuality();
    $this->assertArrayHasKey('y',$psnr);
    $this->assertArrayHasKey('u',$psnr);
    $this->assertArrayHasKey('v',$psnr);
    $this->assertArrayHasKey('average',$psnr);
    $this->assertArrayHasKey('min',$psnr);
    $this->assertArrayHasKey('max',$psnr);
  }
  public function test_parsing_psnr_result(){
    $str = "[Parsed_psnr_0 @ 0x557406d1f500] PSNR y:35.169108 u:46.120751 v:46.434170 average:36.764856 min:15.246958 max:46.002477";
    $ret = FFMpegPSNR::parsePSNR($str);
    $this->assertArrayHasKey('y',$ret);
    $this->assertArrayHasKey('u',$ret);
    $this->assertArrayHasKey('v',$ret);
    $this->assertArrayHasKey('average',$ret);
    $this->assertArrayHasKey('min',$ret);
    $this->assertArrayHasKey('max',$ret);
    $str = "[Parsed_psnr_1 @ 0x55a327073380] PSNR y:53.802384 u:54.266086 v:56.289143 average:54.209751 min:47.631246 max:inf\n";
    $ret = FFMpegPSNR::parsePSNR($str);
    $this->assertNotEmpty($ret['y']);
    $this->assertNotEmpty($ret['u']);
    $this->assertNotEmpty($ret['v']);
    $this->assertNotEmpty($ret['average']);
    $this->assertNotEmpty($ret['min']);
    $this->assertNotEmpty($ret['max']);
  }
  
}
