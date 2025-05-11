<?php

namespace Takuya\Tests\Unit\FFMpeg;

use Takuya\FFMpeg\FFProbe;
use InvalidArgumentException;
use Takuya\Tests\TestCase;
use Takuya\FFMpeg\FFMpegSliceTime;
use function Takuya\Helpers\FileSystem\mktempfile;

class FFMpegSliceMovieTest extends TestCase {
  
  public function test_slice_move_from_start_to_duration() {
    $dur = 2;
    $src = test_data('5sec.mp4');
    $dst = mktempfile(__FUNCTION__).'.mp4';
    $ffmpeg = new FFMpegSliceTime($src, $dst, 0, $dur);
    $ffmpeg->start();
    $out_dur = intval(( new FFProbe() )->movie_info($dst)['format']['duration']);
    $this->assertEquals($dur, $out_dur);
  }
  
  public function test_slice_move_throws_exception_overtime() {
    $this->expectException(InvalidArgumentException::class);
    $dur = 10;
    $src = test_data('5sec.mp4');
    $dst = mktempfile(__FUNCTION__).'.mp4';
    new FFMpegSliceTime($src, $dst, 0, $dur);
  }
  
  public function test_slice_move_throws_exception_starttime() {
    $this->expectException(InvalidArgumentException::class);
    $dur = 2;
    $src = test_data('5sec.mp4');
    $dst = mktempfile(__FUNCTION__).'.mp4';
    new FFMpegSliceTime($src, $dst, 10, $dur);
  }
}
