<?php

namespace Takuya\FFMpeg;

use InvalidArgumentException;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Shell\shell_args_cleanup;
use function Takuya\Helpers\Array\array_insert_after;
use function Takuya\Helpers\Array\array_replace_entry;
use function Takuya\Helpers\Shell\build_cmd;

class FFMpegMovFlag {

  protected string $last_cmd;

  public function addMovflag ( $src, $dst = null, $decode_opt = '', $encode_opt = '-f mp4' ) {
    $this->checkSrc( $src );
    // TODO: /tmp は tmpfs(ramdisk)とは限らない。
    // https://askubuntu.com/questions/173094/how-can-i-use-ram-storage-for-the-tmp-directory-and-how-to-set-a-maximum-amount
    $dst = $dst ?? mktempfile( 'ffmpeg-mov-work' );
    $cmd = new ExecArgStruct( ...$this->buildCommand( $src, $dst, $decode_opt, $encode_opt ) );
    $errout = fopen('php://temp','w');
    $cmd->setStderr($errout);
    $p = new ProcessExecutor( $cmd );
    //$p->onStderr(fn($e)=>$e);////stderrは捨てる。以前のエンコードの不備でメッセージが出ることがある。
    $p->start();
    fclose($errout);

    return $dst;
  }

  protected function checkSrc ( $src ) {
    if ( !(new FFProbe())->is_mp4( $src ) ) {
      throw new InvalidArgumentException( "mp4 以外にmovflagつけても意味がない。" );
    }
  }
  
  protected function buildCommand($src, $dst, $decode_opt, $encode_opt):array{
    $ffmpeg = build_cmd(
      "ffmpeg -y -i %SRC% -c:a copy -c:v copy -movflags faststart {$decode_opt} {$encode_opt} %OUT%",
      ['%OUT%' => $dst, '%SRC%' => $src]
    );
    $ffmpeg = array_insert_after($ffmpeg, '-y', '-hide_banner');
    //
    $this->last_cmd = join(' ', $ffmpeg);
    return $ffmpeg;
  }
}
