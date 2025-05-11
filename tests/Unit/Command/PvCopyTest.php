<?php

namespace Takuya\Tests\Unit\Command;

use Takuya\PvCopy;
use function Takuya\Helpers\FileSystem\mktempfile;
use Takuya\Tests\TestCase;

class PvCopyTest extends TestCase {
  
  public function test_pv_copy_file_check_progress() {
    $src = test_data('1sec.mp4');
    $dst = mktempfile('out');
    $pv_copy = new PvCopy($src, $dst);
    $percent = null;
    $pv_copy->on_pv_progress(function($v) use (&$percent) { $percent = $v['percent']; });
    $pv_copy->start();
    $this->assertEquals(filesize($src), filesize($dst));
    $this->assertEquals(md5_file($src), md5_file($dst));
    $this->assertEquals(100, $percent);
  }
}
