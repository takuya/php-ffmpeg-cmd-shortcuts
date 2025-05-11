<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\FFMpeg\FFProbe;
use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegRemuxFormat;

class FFMpegRemuxTest extends TestCase {
  
  public function test_remux_to_matroska() {
    $path = test_data('1sec.mp4');
    $ffmpeg = new FFMpegRemuxFormat();
    $out_path = $ffmpeg->remux($path, null, 'matroska');
    $ffprobe = new FFProbe();
    $ret = $ffprobe->is_mkv($out_path);
    $this->assertTrue($ret);
  }
}
