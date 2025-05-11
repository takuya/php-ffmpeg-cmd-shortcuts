# ffmpeg in short hand.

This package is shortcut to ffmpeg movie convert.

# Installation
```
```

# Sample

1. Convert mp4 to mkv
2. Generate Image Thumbnail
3. Concat movie 
4. Cut by time
5. Resize  movie 
6. Movie info
7. 


## Covert mp4 to mkv
```php
$ffmpeg = new FFMpegEncode('5sec.mp4', '5sec.mkv');
$ffmpeg->start();
```
## Generate Thumbnail
```php
$thumbnailer = new FFMpegThumbnailer('a.mp4');
$img = $thumbnailer->getImage();
```
## Concatenate Movies.
```php
$ffmpeg = new FFMpegConcat();
$ffmpeg->addSrcFile('a.mp4');
$ffmpeg->addSrcFile('b.mp4');
$dst = $ffmpeg->concat(['c.mp4','d.mp4']);
```

## Cut by time.
```php
$duration = 20;
$start = 60;
$ffmpeg = new FFMpegSliceTime('in.mp4', 'out.mp4', $start, $duration);
$ffmpeg->start();
```
## Resize movie 
```php
$height = 320;//320p
$ffmpeg = new FFMpegResizeMovie();
$ffmpeg->resize($height,null,$src);
$dst = $ffmpeg->getOutput();
```
## Retrieve info 
```php
$path = 'sample.mp4';
$ffprobe = new FFProbe();
$ret = $ffprobe->movie_info($path);
$streams = $ffprobe->list_streams($path)
$codec = $ffprobe->movie_codec($path);
```

