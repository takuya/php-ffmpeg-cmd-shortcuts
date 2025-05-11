<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\FFMpeg\FFProbe;
use InvalidArgumentException;
use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegMovFlag;
use function Takuya\Helpers\FileSystem\mktempdir;
use function Takuya\Helpers\String\str_rand;

class FFMpegMovFlagTest extends TestCase {
  
  public function test_add_movflag_to_mp4() {
    $path = test_data('1sec.mp4');
    $ffmpeg = new FFMpegMovFlag();
    $out_path = $ffmpeg->addMovflag($path);
    $ffprobe = new FFProbe();
    $ret = $ffprobe->has_movflag_faststart($out_path);
    $this->assertTrue($ret);
  }
  public function test_add_movflag_space_file_name_to_mp4() {
    $tmpdir = mktempdir(__METHOD__.str_rand());
    $spaced_file_name = 'a work space file char'.mt_rand(0,1024*1024).'.mp4';
    $path = $tmpdir.DIRECTORY_SEPARATOR.$spaced_file_name;
    copy(test_data('1sec.mp4'),$path);
    $ffmpeg = new FFMpegMovFlag();
    $out_path = $ffmpeg->addMovflag($path);
    $ffprobe = new FFProbe();
    $ret = $ffprobe->has_movflag_faststart($out_path);
    $this->assertTrue($ret);
    @unlink($path);
  }
  
  public function test_add_movflag_to_already_exists() {
    $path = test_data('1sec.mp4');
    $ffmpeg = new FFMpegMovFlag();
    $out1 = $ffmpeg->addMovflag($path);
    $out2 = $ffmpeg->addMovflag($out1);
    //
    $this->assertNotEquals($out1, $out2);
    $this->assertNotEquals(md5_file($path), md5_file($out1));
    $this->assertEquals(md5_file($out1), md5_file($out2));
  }
  
  public function test_add_movflag_for_mpeg_ts() {
    $this->expectException(InvalidArgumentException::class);
    $path = test_data('1sec.ts');
    $ffmpeg = new FFMpegMovFlag();
    $ffmpeg->addMovflag($path);
  }
}
