<?php

namespace Takuya\Tests\Unit\FFMpeg;

use UnexpectedValueException;
use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegInformation;
use function Takuya\Helpers\Array\array_flatten;

class FFMpegInformationTest extends TestCase {
  
  public function test_ffmpeg_get_encoder_by_codec() {
    $matched_encoder_list = ( new FFMpegInformation() )->encoder('h264');
    $names = array_column($matched_encoder_list, 'name');
    $encoder = $matched_encoder_list[0];
    $this->assertTrue(in_array('libx264', $names));
    $this->assertArrayHasKey('name', $encoder);
    $this->assertArrayHasKey('options', $encoder);
  }
  
  public function test_ffmpeg_get_encoder_will_throw_exception() {
    $this->expectException(UnexpectedValueException::class);
    ( new FFMpegInformation() )->encoder('mkv');
  }
  
  public function test_ffmpeg_container_formats() {
    $formats = ( new FFMpegInformation() )->Formats('mp4');
    $this->assertTrue(in_array('Encode', array_flatten($formats)));
    $this->assertTrue(in_array('Decode', array_flatten($formats)));
    //
    $formats = ( new FFMpegInformation() )->Formats('matroska');
    $this->assertTrue(in_array('Encode', array_flatten($formats)));
    $this->assertTrue(in_array('Decode', array_flatten($formats)));
  }
}
