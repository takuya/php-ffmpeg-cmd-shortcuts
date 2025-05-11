<?php

namespace Takuya\Tests\Unit\Parser;

use Takuya\Tests\TestCase;
use Takuya\Progress\PvPraser;

class PvProgressTest extends TestCase {
  
  public function test_parse_pv_progress_with_empty_string():void {
    $sample = "";
    $progress = PvPraser::parsePvProgress($sample);
    //
    $this->assertEquals("0", $progress["transferred"]);
    $this->assertEquals("0:00:00", $progress["elapsed_time"]);
    $this->assertEquals("0", $progress["bytes_rate"]);
    $this->assertEquals("0", $progress["percent"]);
    $this->assertEquals("0:00:00", $progress["estimated_time_of_arrival"]);
  }
  
  public function test_parse_natural_pv_progress():void {
    $sample = "9.25MiB 0:00:23 [3.06MiB/s] [====================>             ] 64% ETA 0:00:01";
    $progress = PvPraser::parsePvProgress($sample);
    $this->assertEquals("9.25MiB", $progress["transferred"]);
    $this->assertEquals("0:00:23", $progress["elapsed_time"]);
    $this->assertEquals("3.06MiB/s", $progress["bytes_rate"]);
    $this->assertEquals("64", $progress["percent"]);
    $this->assertEquals("0:00:01", $progress["estimated_time_of_arrival"]);
  }
  public function test_parse_sample_slow_pv_progress():void {
    $sample = '1.76KiB 0:00:02 [ 885  B/s] [==>                              ]  10% ETA 0:00:17';
    $progress = PvPraser::parsePvProgress($sample);
    $this->assertEquals("1.76KiB", $progress["transferred"]);
    $this->assertEquals("0:00:02", $progress["elapsed_time"]);
    $this->assertEquals("885  B/s", $progress["bytes_rate"]);
    $this->assertEquals("10", $progress["percent"]);
    $this->assertEquals("0:00:17", $progress["estimated_time_of_arrival"]);
  }
  
  public function test_parse_mulitlined_pv_progress():void {
    //preg_match('/[\d:]+/m','0:00:01\r',$m);
    //dd($m);
    $sample =
      "9.25MiB 0:00:23 [3.06MiB/s] [====================>             ] 64% ETA 0:00:01\r"
      ."9.26MiB 0:00:26 [3.06MiB/s] [====================>             ] 65% ETA 0:00:00\r";
    $progress = PvPraser::parsePvProgress($sample);
    $this->assertEquals("9.25MiB", $progress["transferred"]);
    $this->assertEquals("0:00:23", $progress["elapsed_time"]);
    $this->assertEquals("3.06MiB/s", $progress["bytes_rate"]);
    $this->assertEquals("64", $progress["percent"]);
    $this->assertEquals("0:00:01", $progress["estimated_time_of_arrival"]);
  }
  
  public function test_parse_100percent_pv_progress():void {
    $sample = "1.05MiB 0:00:04 [ 251KiB/s] [================================>] 100%            ";
    $progress = PvPraser::parsePvProgress($sample);
    $this->assertEquals("1.05MiB", $progress["transferred"]);
    $this->assertEquals("0:00:04", $progress["elapsed_time"]);
    $this->assertEquals("251KiB/s", $progress["bytes_rate"]);
    $this->assertEquals("100", $progress["percent"]);
    $this->assertEquals("0:00:00", $progress["estimated_time_of_arrival"]);
  }

  public function test_parse_pv_progress_100M (): void {
    $samples = [
      ["str"         => "95.1MiB 0:00:43 [2.45MiB/s] [=========>                        ] 31% ETA 0:01:34",
       "expectation" => [
         "transferred"               => "95.1MiB",
         "elapsed_time"              => "0:00:43",
         "bytes_rate"                => "2.45MiB/s",
         "percent"                   => "31",
         "estimated_time_of_arrival" => "0:01:34",],
      ],
      ["str"         => "97.6MiB 0:00:44 [2.40MiB/s] [=========>                        ] 32% ETA 0:01:32",
       "expectation" => [
         "transferred"               => "97.6MiB",
         "elapsed_time"              => "0:00:44",
         "bytes_rate"                => "2.40MiB/s",
         "percent"                   => "32",
         "estimated_time_of_arrival" => "0:01:32",],
      ],
      ['str'         => " 100MiB 0:00:45 [2.56MiB/s] [=========>                        ] 32% ETA 0:01:31",
       'expectation' =>
         [
           "transferred"               => "100MiB",
           "elapsed_time"              => "0:00:45",
           "bytes_rate"                => "2.56MiB/s",
           "percent"                   => "32",
           "estimated_time_of_arrival" => "0:01:31",
         ],
      ],
      ['str'         => " 102MiB 0:00:46 [2.60MiB/s] [==========>                       ] 33% ETA 0:01:30",
       'expectation' => [
         "transferred"               => "102MiB",
         "elapsed_time"              => "0:00:46",
         "bytes_rate"                => "2.60MiB/s",
         "percent"                   => "33",
         "estimated_time_of_arrival" => "0:01:30",
       ]],
    ];
    foreach ( $samples as $sample ) {
      $str = $sample['str'];
      $exp = $sample['expectation'];
      $keys = array_keys($exp);
      $result = PvPraser::parsePvProgress($str);
      foreach ( $keys as $key ) {
        $this->assertEquals($exp[$key], $result[$key]);
      }
    }
  }
}
