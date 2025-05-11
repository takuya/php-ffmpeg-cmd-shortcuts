<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\FFMpeg\FFProbe;
use Takuya\FFMpeg\FFMpegEncode;
use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegMovFlag;
use function Takuya\Helpers\FileSystem\mktempfile;

class FFMpegEncodeTest extends TestCase {
  public function test_ffmpeg_encoding_suspend_resume(){
    $src = test_data('5sec.mp4');
    $dst = mktempfile('ffmpeg-encode');
    $inm = ( new FFMpegMovFlag() )->addMovflag($src);
    $ffmpeg = new FFMpegEncode($inm, $dst);
    $suspend_cnt = 0;
    $resume_cnt = 0;
    $ffmpeg->suspend_if(function()use(&$suspend_cnt){
      return $suspend_cnt++<10;
    });
    $ffmpeg->resume_if(function()use(&$resume_cnt){
      return $resume_cnt++<10;
    });
    $ffmpeg->start();
    $this->assertEquals(10,$resume_cnt);
  }

  public function test_ffmpeg_encoding_progress_parse() {
    $src = test_data('5sec.mp4');
    $dst = mktempfile('ffmpeg-encode');
    $inm = ( new FFMpegMovFlag() )->addMovflag($src);
    $ffmpeg = new FFMpegEncode($inm, $dst);
    $ffmpeg->setPvOpts(['-L 2M']);
    $on_pv_called = false;
    $pv_stats = [];
    $ffmpeg_stats = [];
    $on_ffmpeg_called = false;
    $ffmpeg->setOnPvProgress(function ( $e ) use ( &$on_pv_called, &$pv_stats ) {
      $on_pv_called = true;
      $pv_stats = $e;
    });
    $ffmpeg->setOnFFMpegProgress(function ( $e ) use ( &$on_ffmpeg_called, &$ffmpeg_stats ) {
      $on_ffmpeg_called = true;
      $ffmpeg_stats = $e;
    });
    $ffmpeg->start();
    $ffprobe = new FFProbe();
    $info = $ffprobe->movie_info($dst);
    $this->assertGreaterThan(5, $info['format']['duration']);
    $this->assertTrue($on_pv_called);
    $this->assertTrue($on_ffmpeg_called);
    $this->assertEquals(100, $pv_stats['percent']);
    $this->assertArrayHasKey('speed', $ffmpeg_stats);
    $this->assertNotNull($ffmpeg->last_cmd);
  }
}
