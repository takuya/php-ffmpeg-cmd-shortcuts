<?php

namespace Takuya\Progress;

class FFMpegProgressParse {
  
  public static function parseFfmpegProgress($str) {
    $str = explode("\r", $str)[0];
    preg_match_all('/ \w+=\s*[\w.:\/]+/', ' '.$str, $m);
    
    
    $a = array_map(fn($e) =>array_map(fn($i)=>trim($i),explode('=', trim($e))), $m[0]);
    return array_combine(array_column($a, 0),array_column($a, 1));
    //return collect($m[0])->map(fn($e) => trim($e))->map(fn($e) => explode('=', $e))->map(
    //  fn($e) => [$e[0] => trim($e[1])])->flatMap(fn($e) => $e)->toArray();
  }
}
