<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegThumbnailer;
use function Takuya\Helpers\FileSystem\mktempdir;

class FFMpegThumbnailerTest extends TestCase {

  public function test_ffmpeg_get_thumbnail() {
    $path = test_data('5sec.mp4');
    $thumbnail = new FFMpegThumbnailer($path);
    $img = $thumbnail->getImage();
    $gd = imagecreatefromstring($img);
    $this->assertNotFalse($gd);
    $this->assertEquals(160, imagesx($gd));
    $this->assertEquals(90, imagesy($gd));
  }

  public function test_ffmpeg_1sec_movie_get_thumbnail() {
    $path = test_data('1sec.mp4');
    $thumbnailer = new FFMpegThumbnailer($path);
    $img = $thumbnailer->getImage();
    $gd = imagecreatefromstring($img);
    $this->assertNotFalse($gd);
    $this->assertEquals(160, imagesx($gd));
    $this->assertEquals(90, imagesy($gd));
  }
  public function test_ffmpeg_thumbnailer_space_name_file() {
    $path = mktempdir(__FUNCTION__).'/a work space char'.mt_rand(0,1024*1024).'.mp4';
    copy(test_data('1sec.mp4'),$path);
    $thumbnailer = new FFMpegThumbnailer($path);
    $img = $thumbnailer->getImage();
    $gd = imagecreatefromstring($img);
    $this->assertNotFalse($gd);
    $this->assertEquals(160, imagesx($gd));
    $this->assertEquals(90, imagesy($gd));
    @unlink($path);
  }

  public function test_ffmpeg_thumbnail_receive_only_height() {
    $path = test_data('1sec.mp4');
    $thumbnailer = new FFMpegThumbnailer($path,null,720);
    $img = $thumbnailer->getImage();
    $gd = imagecreatefromstring($img);
    $this->assertNotFalse($gd);
    $this->assertEquals(1280, imagesx($gd));
    $this->assertEquals(720, imagesy($gd));
  }

}
