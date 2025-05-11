<?php

namespace Takuya\Tests\Unit\Parser;

use Takuya\FFMpeg\FFProbe;
use Takuya\Tests\TestCase;

class FFMpegEncodersTest extends TestCase {
  
  public function test_parse_ffmpeg_encoder_by_codec() {
    $encoders = ( new FFProbe() )->getEncodersByCodec('hevc');
    $this->assertEquals("libx265", $encoders[0]['encoder']);
    $encoders = ( new FFProbe() )->getEncodersByCodec('h264');
    $this->assertEquals("libx264", $encoders[0]['encoder']);
    $encoders = ( new FFProbe() )->getEncodersByCodec('hevcccc');
    $this->assertEmpty($encoders);
  }
  
  public function test_parse_ffmpeg_codec_by_encoder() {
    $encoders = ( new FFProbe() )->getCodecByEncoder("libx265");
    $this->assertEquals("libx265", $encoders['encoder']);
    $this->assertEquals("hevc", $encoders['codec']);
    $encoders = ( new FFProbe() )->getCodecByEncoder("libx264");
    $this->assertEquals("libx264", $encoders['encoder']);
    $this->assertEquals("h264", $encoders['codec']);
  }
}
