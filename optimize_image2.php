<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Intervention\Image\ImageManagerStatic as Image;


class OptimizeImage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:image {--path=default : Đường dẫn nếu có, nếu ko dùng mặc định trong optimize_image config}
                            {--max_width=default : độ rộng lớn nhất}
                            {--quality=default: chất lượng ảnh, 0-100 hoặc default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'nén các ảnh jpeg, jpg, png, gif, svg theo chất lượng tùy chỉnh, chỉnh kích cỡ ảnh,
                                có thể duyệt đệ quy tất cả các ảnh trong folder con';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->option('path');
        $max_width = $this->option('max_width');
        $quality = $this->option('quality');
        if($quality == 'default'){
            $quality = config('optimize_image.quality');
        }
        if($max_width == 'default'){
            $max_width = config('optimize_image.max_size.width');
        }
        $this->info('max width: '.$max_width);
        $this->info('path: '.$path);

        if($path == 'default'){
            foreach (config('optimize_image.paths') as $path){
                $this->recursiveGetFile($path, $max_width, $quality);
            }
        }else{
            if(is_dir($path)){
                $this->recursiveGetFile($path, $max_width, $quality);
            }else if(in_array(pathinfo($path, PATHINFO_EXTENSION), ["png", "jpeg", "jpg", "gif", "svg"])){
                $this->info($path);
                $this->line("file size before optimize: ".$this->formatSizeUnits(filesize($path)));
                $this->optimize($path, $max_width, $quality);
                $this->line("after optimize: ".$this->formatSizeUnits(filesize($path)));
            }
        }
    }
    private function optimize($file, $max_width, $quality){
        // resize
        if(getimagesize($file)[0] > $max_width){
            $this->resize($max_width, $file);
        }
        // optimize
        $optimizerChain = (new OptimizerChain)
            ->addOptimizer(new Jpegoptim([
                '-m'.$quality,
                '--strip-all',
                '--all-progressive',
            ]))
            ->addOptimizer(new Pngquant([
                '--force',
                '--skip-if-larger',
                '--quality 0-'.$quality,
            ]))
            ->addOptimizer(new Optipng([
                '-i0',
                '-o2',
            ]))
            ->addOptimizer(new Svgo([
            ]))
            ->addOptimizer(new Gifsicle([
                '-O3'
            ]));
//        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($file);


    }
    private function recursiveGetFile($path, $max_width, $quality){
        $it = new \RecursiveDirectoryIterator($path);
        foreach(new \RecursiveIteratorIterator($it) as $file) {
            if (in_array($file->getExtension(), ["png", "jpeg", "jpg", "gif", "svg"])) {
                $this->info($file->getPathname());
                $this->line("file size before optimize: ".$this->formatSizeUnits(filesize($file->getPathname())));
                $this->optimize($file->getPathname(), $max_width, $quality);
                $this->line("after optimize: ".$this->formatSizeUnits(filesize($file->getPathname())));
            }
        }
    }
    private function resize($newWidth, $originalFile){
        Image::configure(array('driver' => 'imagick'));
        $img = Image::make($originalFile);
        $img->resize($newWidth, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        if (file_exists($originalFile)) {
            unlink($originalFile);
        }
        $img->save($originalFile);
    }
//    private function resize($newWidth, $originalFile) {
//
//        $info = getimagesize($originalFile);
//        $mime = $info['mime'];
//
//        switch ($mime) {
//            case 'image/jpeg':
//                $image_create_func = 'imagecreatefromjpeg';
//                $image_save_func = 'imagejpeg';
//                break;
//
//            case 'image/png':
//                $image_create_func = 'imagecreatefrompng';
//                $image_save_func = 'imagepng';
//                break;
//
//            case 'image/gif':
//                $image_create_func = 'imagecreatefromgif';
//                $image_save_func = 'imagegif';
//                break;
//
//            default:
//                throw new \Exception('Unknown image type.');
//        }
//
//        $img = $image_create_func($originalFile);
//        list($width, $height) = getimagesize($originalFile);
//
//        $newHeight = ($height / $width) * $newWidth;
//        $tmp = imagecreatetruecolor($newWidth, $newHeight);
//        imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
//
//        if (file_exists($originalFile)) {
//            unlink($originalFile);
//        }
//        $image_save_func($tmp, $originalFile, 9);
//    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
