<?php

namespace Takuya\Tests\Unit\Parser;

use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegOptionParse;

class FFMpegOptionParseTest extends TestCase {
  
  public function test_parse_encode_opt_format_and_size() {
    $sample_opt = '-f mp4 -s 1280x720 -vsync 1 -movflags faststart -threads 6';
    $parser = new FFMpegOptionParse($sample_opt);
    $ret = $parser->parse();
    $this->assertEquals('mp4', $ret['f']);
    $this->assertEquals('1280x720', $ret['s']);
  }
  public function test_parse_encode_movflags(){
    $sample_opt = '-movflags faststart,frag_keyframe';
    $ret = (new FFMpegOptionParse($sample_opt))->parse()['movflags'];
    $this->assertEquals('faststart,frag_keyframe',$ret);
  }
  
  public function test_parse_encode_opt_get_encoder() {
    $sample_opt = '-s 1280x720 -vsync 1 -movflags faststart -threads 6';
    $parser = new FFMpegOptionParse($sample_opt);
    $ret = $parser->encoder('aaaa.mkv');
    $this->assertEquals('libx264', $ret);
    $ret = $parser->encoder('aaaa.ts');
    $this->assertEquals('mpeg2video', $ret);
    $ret = $parser->encoder('aaaa.mp4');
    $this->assertEquals('libx264', $ret);
  }
  public function test_parse_detect_format() {
    // 拡張子から判定されることを確認
    $ret = ( new FFMpegOptionParse('') )->detectEncodeFormat('aaaa.mkv');
    $this->assertEquals("matroska", $ret);
    $ret = ( new FFMpegOptionParse('') )->detectEncodeFormat('aaaa.ts');
    $this->assertEquals("mpegts", $ret);
    $ret = ( new FFMpegOptionParse('') )->detectEncodeFormat('aaaa.m4a');
    $this->assertEquals("mp4", $ret);
    // -f が優先されることを確認
    $ret = ( new FFMpegOptionParse('-f mp4') )->detectEncodeFormat('aaaa.mkv');
    $this->assertEquals("mp4", $ret);
    $ret = ( new FFMpegOptionParse('-f matroska') )->detectEncodeFormat('aaaa.mp4');
    $this->assertEquals("matroska", $ret);
  }
  public function test_parse_detect_movflags() {
    $sample_opt = '-s 1280x720 -vsync 1 -movflags faststart -threads 6';
    $parser = new FFMpegOptionParse($sample_opt);
    $this->assertTrue($parser->movflags_start());
  }
  
}
