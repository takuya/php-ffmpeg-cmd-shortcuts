<?php

namespace Takuya\FFMpeg;

use SplTempFileObject;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use UnexpectedValueException;
use function Takuya\Helpers\Shell\shell_args_cleanup;

class FFProbe {

  protected string $cmd = 'ffprobe';

  public function __construct () { }

  public static function CommonExtensions ( $muxer = 'mp4' ): array {
    $muxer_info = ( new FFMpegInformation() )->defaultMuxer( $muxer );
    return explode( ',', $muxer_info["Common extensions"] );
  }

  public static function ContentType ( $muxer = 'mp4' ): ?string {
    try {
      $muxer_info = ( new FFMpegInformation() )->defaultMuxer( $muxer );
      return $muxer_info["Mime type"];
    } catch (UnexpectedValueException) {
      return null;
    }
  }

  public function is_mkv ( $path ): bool {
    $fm = $this->getFormat( $path );
    if(is_null($fm)){
      throw new \RuntimeException("{$path} cannot detect format");
    }
    $ret = array_diff( explode( ',', "matroska,webm" ), $fm );

    return sizeof( $ret ) === 0;
  }

  public function getFormat ( $path ): ?array {
    $ret = $this->movie_info( $path );
    return !empty( $ret ) ? explode( ',', $ret['format']["format_name"] ) : null;
  }

  public function movie_info ( $path, $opts = null ) {
    $opts = $opts ?? ' -v quiet -print_format json -show_format';
    $ret = $this->run( $path, $opts );
    $arr = json_decode( $ret[1], JSON_OBJECT_AS_ARRAY );
    return sizeof( $arr ) ? $arr : null;
  }

  public function run ( $path, $opts ): array {
    $args = $this->buildCmd( $path, $opts );
    if ( !is_readable($path)){
      throw new \RuntimeException("path is not readable ( {$path} ) ");
    }
    $p = new ProcessExecutor( $args );
    $p->start();
    return [1 => $p->getOutput(), 2 => $p->getErrout()];
  }

  protected function buildCmd ( $path, $opts ) {
    $cmd = $this->cmd.' '.$opts.' -i ';
    $cmd = shell_args_cleanup( $cmd );
    $cmd[] = $path;
    $args = new ExecArgStruct( ...$cmd );
    // TODO:: ファイル作りすぎるからバッファリングにする。PIPE_SIZEで詰まることがある。
    $buffMBs = 10 * 1024 * 1024;
    $args->setStdout(fopen("php://temp/maxmemory:{$buffMBs}",'w+'));

    return $args;
  }

  public function is_movie_file ( $path ): bool {
    $ret = $this->movie_info( $path );
    return !empty( $ret ) && !empty( $ret['format'] ?? '' );
  }

  public function is_mp4 ( $path ): bool {
    $ret = $this->movie_info( $path );
    $fm = explode( ',', $ret['format']["format_name"] ?? '' );
    $ret = array_diff( explode( ',', "mov,mp4,m4a,3gp,3g2,mj2" ), $fm );

    return sizeof( $ret ) === 0;
  }

  public function is_mpegts ( $path ): bool {
    $ret = $this->movie_info( $path );
    $fm = explode( ',', $ret['format']["format_name"] ?? '' );

    return sizeof( preg_grep( '/mpegts/', $fm ) ) > 0;
  }

  public function duration ( $path ) {
    return $this->movie_info( $path )['format']['duration'];
  }

  public function getEncodersByCodec ( $codec ) {
    $encoders = $this->videoEncoders();

    return array_values( array_filter( $encoders, fn( $e ) => $e['codec'] == $codec ) );
  }

  public function videoEncoders () {
    return ( new FFMpegInformation )->videoEncoders();
  }

  public function getCodecByEncoder ( $encoder ) {
    $encoders = $this->videoEncoders();
    $encoders = array_values( array_filter( $encoders, fn( $e ) => $e['encoder'] == $encoder ) );

    return !empty( $encoders ) ? $encoders[0] : null;
  }

  public function getMuxers () {
    return ( new FFMpegInformation )->Muxers();
  }

  public function getDefaultVideoEncoder ( $format ) {
    return $this->getDefaultEncoder( $format )['video'];
  }

  public function getDefaultEncoder ( $format ): array {
    $ret = $this->getDefaultMuxer( $format );
    $codec_v = $ret["Default video codec"];
    $codec_a = $ret["Default audio codec"];

    // 最初のやつがデフォルトだと思われる。
    return [
      'video' => array_column( ( new FFMpegInformation )->encoder( $codec_v ), 'name' )[0],
      'audio' => array_column( ( new FFMpegInformation )->encoder( $codec_a ), 'name' )[0],
    ];
  }

  public function getDefaultMuxer ( $muxer = 'mp4' ) {
    return ( new FFMpegInformation )->defaultMuxer( $muxer );
  }

  public function has_movflag_faststart ( $path, $opts = '' ): bool {
    $pat = "/type:'(mdat|moov)'/";
    //
    $opts = '-loglevel +trace '.$opts;
    $cmd = $this->buildCmd( $path, $opts );
    $cmd->setStderr( $fp_error = fopen( "php://temp", 'w' ) );
    $p = new ProcessExecutor( $cmd );
    $p->start();
    $str_io = new SplTempFileObject( strlen( $p->getErrout() ) );
    $str_io->fwrite( $p->getErrout() );
    fclose($fp_error);
    //
    $ret = preg_grep( $pat, iterator_to_array( $str_io ) );
    $ret = array_map( function( $e ) use ( $pat ) {preg_match( $pat, $e, $m );return $m[1];}, $ret );
    $ret = array_values( $ret );

    //moov,mdatで、moov が先に来たら、faststart
    return $ret == ["moov", "mdat"];
  }

  public function video_stream_info ( $path, $long = false ) {
    return $this->stream_info( $path, 'video', $long );
  }

  public function stream_info ( $path, $stream_type = 'video', $long = false, $index = null ) {
    return array_values(
      array_filter( $this->list_streams( $path, $long ) ?? [],
        fn( $e ) =>
          !empty($e['codec_type'])
          && $e['codec_type'] == $stream_type
          && ( is_null( $index ) || $e['index'] === $index )
      )
    );
  }

  public function list_streams ( $path, $long = false ) {
    $ret = $this->movie_info( $path, ' -v quiet -print_format json -show_format -show_streams' );

    return $ret['streams'] ?? null;
  }

  public function audio_stream_info ( $path, $long = false ) {
    return $this->stream_info( $path, 'audio', $long );
  }

  public function movie_codec ( $path, $long = false ) {
    $ret = $this->stream_info( $path, 'video', $long );
    if ( empty( $ret[0] ) ) {
      return null;
    }
    if ( $long ) {
      return [
        'codec_name'      => $ret[0]['codec_name'],
        'codec_long_name' => $ret[0]['codec_long_name'],
      ];
    } else {
      return $ret[0]['codec_name'];
    }
  }
}
