<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\FFMpeg\FFProbe;
use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegConcat;
use Takuya\FFMpeg\FFMpegResizeMovie;

class FFMpegResizeTest extends TestCase {
  public function test_ffmpeg_resize_movie_height_test(){
    $src = test_data('5sec.mp4');
    $height = 320;
    $ffmpeg = new FFMpegResizeMovie();
    $ffmpeg->resize($height,null,$src);
    $dst = $ffmpeg->getOutput();
    $this->assertEquals($height, (new FFProbe)->stream_info($dst)[0]['height']);
  }
}
