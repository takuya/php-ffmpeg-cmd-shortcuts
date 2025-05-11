<?php

namespace Takuya\Tests\Unit\Parser;

use Takuya\Tests\TestCase;
use Takuya\Progress\FFMpegProgressParse;

class FFMpegProgressTest extends TestCase {
  
  //public function test_parse_error_ffmpeg_progress (): void {
  //
  //}
  public function test_parse_ffmpeg_progress():void {
    $str = 'frame=  983 fps= 92 q=34.7 size=    1280kB time=00:00:33.96 bitrate= 308.8kbits/s speed=3.17x';
    $progress = FFMpegProgressParse::parseFfmpegProgress($str);
    $this->assertEquals("983", $progress["frame"]);
    $this->assertEquals("92", $progress["fps"]);
    $this->assertEquals("34.7", $progress["q"]);
    $this->assertEquals("1280kB", $progress["size"]);
    $this->assertEquals("00:00:33.96", $progress["time"]);
    $this->assertEquals("308.8kbits/s", $progress["bitrate"]);
    $this->assertEquals("3.17x", $progress["speed"]);
  }
  
  public function test_parse_multilined_ffmpeg_progress():void {
    $str =
      "frame= 1274 fps= 60 q=29.0 size=    8960kB time=00:00:44.37 bitrate=1654.2kbits/s dup=16 drop=0 speed=2.11x    \r"
      ."frame= 1305 fps= 60 q=29.0 size=    9216kB time=00:00:45.41 bitrate=1662.3kbits/s dup=16 drop=0 speed= 2.1x    \r";
    $progress = FFMpegProgressParse::parseFfmpegProgress($str);
    $this->assertEquals("1274", $progress["frame"]);
    $this->assertEquals("60", $progress["fps"]);
    $this->assertEquals("29.0", $progress["q"]);
    $this->assertEquals("8960kB", $progress["size"]);
    $this->assertEquals("00:00:44.37", $progress["time"]);
    $this->assertEquals("1654.2kbits/s", $progress["bitrate"]);
    $this->assertEquals("2.11x", $progress["speed"]);
  }
  
  public function test_parse_chopped_ffmpeg_progress() {
    $str = <<<EOF
    ut=40 intra_refresh=0 rc_lookahead=40 rc=crf mbtree=1 crf=23.0 qcomp=0.60 qpmin=0 qpmax=69 qpstep=4 ip_ratio=1.40 aq=1:1.00\n
    Output #0, mp4, to '/mnt/c/Users/takuya/Desktop/ffmpeg-job/storage/app/work/ffmpeg-217602b0-enc_service.mp4':\n
      Metadata:\n
        major_brand     : isom\n
        minor_version   : 512\n
        compatible_brands: isomiso2avc1mp41\n
        encoder         : Lavf60.3.100\n
      Stream #0:0(und): Video: h264 (avc1 / 0x31637661), yuv420p(tv, bt709, progressive), 1280x720 [SAR 1:1 DAR 16:9], q=2-31, 29.97 fps, 30k tbn (default)\n
        Metadata:\n
          handler_name    : VideoHandler\n
          vendor_id       : [0][0][0][0]\n
          encoder         : Lavc60.3.100 libx264\n
        Side data:\n
          cpb: bitrate max/min/avg: 0/0/0 buffer size: 0 vbv_delay: N/A\n
      Stream #0:1(und): Audio: aac (LC) (mp4a / 0x6134706D), 48000 Hz, stereo, fltp, 128 kb/s (default)\n
        Metadata:\n
          handler_name    : SoundHandler\n
          vendor_id       : [0][0][0][0]\n
          encoder         : Lavc60.3.100 aac\n
    frame=    0 fps=0.0 q=0.0 size=       0kB time=00:00:
    EOF;
    $progress = FFMpegProgressParse::parseFfmpegProgress($str);
    $this->assertEquals("0.0", $progress["fps"]);
    $this->assertEquals("0.0", $progress["q"]);
    $this->assertEquals("0kB", $progress["size"]);
    $this->assertEquals("00:00:", $progress["time"]);
  }
}
