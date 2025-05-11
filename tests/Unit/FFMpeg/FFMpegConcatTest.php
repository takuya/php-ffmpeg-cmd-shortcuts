<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\FFMpeg\FFProbe;
use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegConcat;

class FFMpegConcatTest extends TestCase {
  public function test_ffmpeg_concat_files_test(){
    $src = test_data('5sec.mp4');
    $ffmpeg = new FFMpegConcat();
    foreach (range(0,2) as $idx){
      $ffmpeg->addSrcFile($src);
    }
    $dst = $ffmpeg->concat([$src]);
    $dur = intval( (new FFProbe)->duration($dst));
    $this->assertEquals(20,$dur);
  }
  public function test_ffmpeg_concat_args_failed(){
    $this->expectException(\InvalidArgumentException::class);
    $ffmpeg = new FFMpegConcat();
    $ffmpeg->concat();
  }
}
