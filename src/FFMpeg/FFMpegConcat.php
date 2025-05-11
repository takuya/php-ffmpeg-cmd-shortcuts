<?php

namespace Takuya\FFMpeg;

use InvalidArgumentException;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use function Takuya\Helpers\FileSystem\mktempfile;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Shell\shell_args_cleanup;
use function Takuya\Helpers\Array\array_replace_entry;
use function Takuya\Helpers\Array\array_insert_before;

class FFMpegConcat {

  protected string $last_cmd;
  /**
   * @var string[]
   */
  protected array $files;
  protected string $dst;
  protected string $format='mp4';
  protected bool $codec_copy=false;


  public function addSrcFile(string $path): void {
    if(false===(new FFProbe)->is_movie_file($path)){
      throw new InvalidArgumentException('should be movie.');
    }
    $this->files = $this->files ??[];
    $this->files[] = $path;
  }
  public function getOutput(): string {
    return $this->dst;
  }
  public function enableCodecCopy(): void {
    $this->codec_copy=true;
  }

  public function concat ( ?array $src=null, $dst = null ) {
    array_map(fn($e)=>$this->addSrcFile($e),$src??[]);
    if(empty($this->files)){
      throw new InvalidArgumentException('no input movies');
    }
    $src = $this->files;
    $dst = $dst ?? mktempfile( 'ffmpeg-concat' );
    $cmd = new ExecArgStruct( ...$this->buildCommand( $src,$dst, $this->format ) );
    $p = new ProcessExecutor( $cmd );
    $p->start();
    //
    return $this->dst=$dst;
  }

  protected function buildCommand (array $inputs,string $output,string $format): array {
    $inputs = array_map(fn($e)=>['-i',$e],$inputs);
    $size = count($inputs);
    $ffmpeg = [find_path( 'ffmpeg' ),'-y',$inputs,['-filter_complex',"concat=n={$size}:v=1:a=1"],'-f FORMAT OUTPUT'];
    $ffmpeg = shell_args_cleanup( $ffmpeg );
    $ffmpeg = array_replace_entry($ffmpeg,'OUTPUT',$output);
    $ffmpeg = array_replace_entry($ffmpeg,'FORMAT',$format);
    $this->codec_copy && $ffmpeg = array_insert_before($ffmpeg,'-f','-codec copy');
    $this->last_cmd = join(' ',$ffmpeg);
    return $ffmpeg;
  }

  public function setFormat ( string $format='mp4' ): void {
    $this->format = $format;
  }
}
