<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\FFMpeg\FFProbe;
use Takuya\Tests\TestCase;
use function Takuya\Helpers\FileSystem\mktempdir;

class FFProbeTest extends TestCase {
  public function test_ffprobe_on_no_exists(){
    $this->expectException(\RuntimeException::class);
    $ffprobe = new FFProbe();
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.base64_encode(random_bytes(30));
    $ffprobe->movie_info($path);
  }


  public function test_ffprobe_run_on_invalid_movie(){
    $path = mktempdir(__FUNCTION__).'/corrupted_movie_'.mt_rand(0,1024*1024).'.mp4';
    $content = file_get_contents(test_data('1sec.mp4'));
    $invalid_content = random_bytes(1024).$content.random_bytes(1024);
    file_put_contents($path,$invalid_content);
    $ffprobe = new FFProbe();
    $ret = $ffprobe->movie_info($path);
    $this->assertNull($ret);
  }
  public function test_get_common_extensions(){
    $this->assertContains('ts',FFProbe::CommonExtensions('mpegts'));
    $this->assertContains('mkv',FFProbe::CommonExtensions('matroska'));
    $this->assertContains('mov',FFProbe::CommonExtensions('mov'));
    $this->assertContains('mp4',FFProbe::CommonExtensions('mp4'));
  }
  public function test_get_content_type(){
    $this->assertEquals("video/MP2T",FFProbe::ContentType('mpegts'));
    $this->assertEquals("video/mp4",FFProbe::ContentType('mp4'));
    $this->assertEquals("video/x-matroska",FFProbe::ContentType('matroska'));
    $this->assertEquals("video/x-msvideo",FFProbe::ContentType('avi'));
  }

  public function test_start_ffprobe_get_info_json() {
    $path = test_data('1sec.mp4');
    $ffprobe = new FFProbe();
    $ret = $ffprobe->movie_info($path);
    $this->assertIsArray($ret);
    $this->assertArrayHasKey('format', $ret);
    //
    $ret = $ffprobe->movie_info(__FILE__);
    $this->assertNull($ret);
  }
  public function test_start_ffprobe_get_info_path_has_space() {
    $path = mktempdir(__FUNCTION__).'/a work space char'.mt_rand(0,1024*1024).'.mp4';
    copy(test_data('1sec.mp4'),$path);
    $ffprobe = new FFProbe();
    $ret = $ffprobe->movie_info($path);
    $this->assertIsArray($ret);
    $this->assertArrayHasKey('format', $ret);
    @unlink($path);
  }
  public function test_start_ffprobe_get_info_path_apostrophes() {
    $path = mktempdir(__FUNCTION__)."/sample's char".mt_rand(0,1024*1024).'.mp4';
    copy(test_data('1sec.mp4'),$path);
    $ffprobe = new FFProbe();
    $ret = $ffprobe->movie_info($path);
    $this->assertIsArray($ret);
    $this->assertArrayHasKey('format', $ret);
    @unlink($path);
  }

  public function test_ffprobe_check_movflags() {
    $path = test_data('1sec.mp4');
    $ffprobe = new FFProbe();
    $ret = $ffprobe->has_movflag_faststart($path);
    $this->assertFalse($ret);
  }

  public function test_start_ffprobe_mpegts_get_info() {
    $path = test_data('1sec.ts');
    $ffprobe = new FFProbe();
    $ret = $ffprobe->movie_info($path);
    $this->assertEquals('mpegts', $ret['format']['format_name']);
    $ret = $ffprobe->is_mpegts($path);
    $this->assertTrue($ret);
  }

  public function test_ffprobe_get_movie_codec() {
    $path = test_data('1sec.mp4');
    $ffprobe = new FFProbe();
    $ret = $ffprobe->movie_codec($path);
    $this->assertEquals('h264', $ret);
    $ret = $ffprobe->movie_codec($path, true);
    $this->assertEquals('h264', $ret['codec_name']);
  }

  public function test_ffprobe_video_stream_info() {
    $path = test_data('1sec.mp4');
    $ffprobe = new FFProbe();
    $streams = $ffprobe->video_stream_info($path);
    $this->assertGreaterThan(0, sizeof($streams));
    foreach ($streams as $stream) {
      $this->assertEquals('video', $stream['codec_type']);
    }
  }

  public function test_ffprobe_audio_stream_info() {
    $path = test_data('1sec.mp4');
    $ffprobe = new FFProbe();
    $streams = $ffprobe->audio_stream_info($path);
    $this->assertGreaterThan(0, sizeof($streams));
    foreach ($streams as $stream) {
      $this->assertEquals('audio', $stream['codec_type']);
    }
  }
}
