<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegOptionParse;

class FFMpegOptionParseTest extends TestCase {
  public function test_ffmpeg_option_parse_get_scale(){
    $opt = new FFMpegOptionParse('-vf scale=320:-1');
    $this->assertEquals('320:-1',$opt->scale());
    $opt = new FFMpegOptionParse('-s 320x-1');
    $this->assertEquals('320x-1',$opt->scale());
    $opt = new FFMpegOptionParse('-s 1280x720');
    $this->assertEquals('1280x720',$opt->scale());
    $opt = new FFMpegOptionParse('-c:v libx265 -tag:v hvc1 -c:a aac output.mp4');
    $this->assertEquals('hvc1',$opt->parse()['tag:v']);
    $opt = new FFMpegOptionParse('-c:v libx265 -c:a aac output.mp4');
    $this->assertEmpty($opt->parse()['tag:v']);
  }
}
