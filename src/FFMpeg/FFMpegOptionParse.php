<?php

namespace Takuya\FFMpeg;

use function Takuya\Helpers\FileSystem\get_extension;

class FFMpegOptionParse {

  public function __construct ( public string $encode_opt ) {
  }

  public function parse () {
    $str = $this->encode_opt;

    return [
      'codec:v'  => $this->encode_video_codec( $str ),
      'codec:a'  => $this->encode_audio_codec( $str ),
      'f'        => $this->format_opt( $str ),
      's'        => $this->scale_opt( $str ),
      't'        => $this->duration_opt( $str ),
      'movflags' => $this->movflags( $str ),
      'tag:v'    => $this->tag_video($str),
    ];
  }
  protected function tag_video($str){
    preg_match( '/-tag:v\s+\K\S+/', $str, $m );
    return sizeof( $m ) > 0 ? $m[0] : '';
  }

  protected function encode_video_codec ( $str ) {
    preg_match( '/-(?:codec:v|c:v)\s+(\S+)/', $str, $m );

    return sizeof( $m ) > 0 ? $m[1] : '';
  }

  protected function encode_audio_codec ( $str ) {
    preg_match( '/-(?:codec:a|c:a)\s+(\S+)/', $str, $m );

    return sizeof( $m ) > 0 ? $m[1] : '';
  }

  protected function format_opt ( $str ) {
    preg_match( '/-f\s+(\S+)/', $str, $m );

    return sizeof( $m ) > 0 ? $m[1] : '';
  }

  protected function scale_opt ( $str ) {
    preg_match( '/-s\s+(\S+)/', $str, $m1 );
    if (sizeof( $m1 ) > 0){
      return $m1[1] ;
    }
    preg_match('/scale=(([-\d]+):([-\d]+))/',$str ,$m2);
    if (sizeof( $m2 ) > 0){
      return $m2[1] ;
    }
    return '';
  }

  protected function duration_opt ( $str ) {
    preg_match( '/-t\s+(\S+)/', $str, $m );

    return sizeof( $m ) > 0 ? $m[1] : '';
  }

  protected function movflags ( $str ) {
    preg_match_all( '/-movflags\s+(\S+)/', $str, $m );
    return sizeof( $m ) > 0 ? $m[1][0] ?? '' : '';
  }

  public function encoder ( $output_filename ) {
    $ret = $this->encode_video_codec( $this->encode_opt );
    if($ret =='copy'){
      return null;
    }
    if ( !empty( $ret ) ) {
      return $ret;
    }
    $ret = $this->detectEncodeFormat( $output_filename );
    if ( !empty( $ret ) ) {
      return ( new FFProbe() )->getDefaultVideoEncoder( $ret );
    }

    return null;
  }

  public function detectEncodeFormat ( $output_filename = '' ) {
    $ret = $this->format();
    if ( !empty( $ret ) ) {
      return $ret;
    }
    $ret = get_extension( $output_filename, false );
    if ( !empty( $ret ) ) {
      return match ( $ret ) {
        // よく使う拡張子を登録。
        "ts" => 'mpegts',
        "mkv" => 'matroska',
        "m4a" => 'mp4',
        default => $ret,
      };
    }

    return null;
  }

  public function format () {
    return $this->format_opt( $this->encode_opt );
  }

  public function movflags_start () {
    return in_array( 'faststart', explode( ',', $this->movflags( $this->encode_opt ) ) );
  }

  public function duration () {
    return $this->duration_opt( $this->encode_opt );
  }

  public function scale () {
    return $this->scale_opt( $this->encode_opt );
  }
}
