<?php

namespace MySiteDigital;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Control\Director;
use Dompdf\Dompdf;

trait BuildTaskOutput
{
    protected $is_list = true;

    protected $has_started = false;

    protected $html_output = '';
    
    protected $file_name = ''; //without extension

    protected $file_path = ''; //full path excluding trailing slash

    public function setIsList($bool){
        $this->is_list = $bool;
    }

    public function setFileName($name){
        $this->file_name = $name;
    }

    public function setFilePath($path){
        $this->file_path = $path;
    }

    public function increaseMemoryAndTimeLimit($memory_limit = '1024M', $time_limit = 7200)
    {
        Environment::increaseMemoryLimitTo($memory_limit);
        Environment::increaseTimeLimitTo($time_limit);
    }

    public function begin()
    {
        $this->has_started = true;
        $this->increaseMemoryAndTimeLimit();
        $output = '<ul class="build">';

        if (!Director::is_cli()) {
            echo '<link href="' . $this->getCSSFile() . '" rel="stylesheet">';
            echo $output;
        }
        
        $this->html_output = '<link href="' . $this->getCSSFile(true) . '" rel="stylesheet">';
        $this->html_output .= $output;
    }

    public function complete()
    {
        $this->is_list = false;
        $this->message('</ul>', '');

        if($this->file_name){
            $outputPath = $this->file_path ?: BASE_PATH;
            $fileLocation = $outputPath . '/' . $this->file_name . '-' . date('Y-m-d') . '.pdf';

            $dompdf = new Dompdf();
            $dompdf->loadHtml($this->html_output);
            $dompdf->render();

            $output = $dompdf->output();
            file_put_contents(
                $fileLocation, 
                $output
            );
        }
    }

    public function getCSSFile($fullPath = false){
        $loader = Injector::inst()->get(ModuleResourceLoader::class);
        if($fullPath){
            return BASE_PATH . '/' . $loader->resolvePath('mysite-digital/silverstripe-build-task-output: client/css/style.css');
        }
        else {
            return $loader->resolveURL('mysite-digital/silverstripe-build-task-output: client/css/style.css');
        }
    }

    /**
     * Show a message about task currently running
     *
     * @param string $message to display
     * @param string $type one of:
     *                      info        #0073c1
     *                      blue        #0073c1
     *                      success     #2b6c2d
     *                      green       #2b6c2d
     *                      warning     #8a6d3b
     *                      yellow      #8a6d3b
     *                      orange      #8a6d3b
     *                      error       #d30000
     *                      red         #d30000
     * @param boolean $tag html tag if required: h1, h2, h3, h4, p, etc (only works for non self closing tags)
     *
     **/
    public function message($message, $type = '', $tag = '')
    {
        echo '';
        // check that buffer is actually set before flushing
        if (ob_get_length()) {
            @ob_flush();
            @flush();
            @ob_end_flush();
        }
        @ob_start();

        $html = $this->getBrowserMessage($message, $type, $tag);
        if (Director::is_cli()) {
            $mesage = $this->getCliMessage($message, $type);
        }
        else {
            $mesage = $html;
        }
        echo $mesage;

        $this->addMessageToPDFOutput($html);
    }

    public function getCliMessage($message, $type = ''){
        $sign = $this->getMessageType($type);
        $message = strip_tags($message);
        echo "  $sign $message\n";
    }

    public function getBrowserMessage($message, $type = '', $tag = ''){
        $message = $this->wrapMessageWithTag($message, $tag);

        if ($this->is_list){
            if(! $this->has_started){
                $this->begin();
            }
            $class = $this->getMessageType($type);
            return "<li class=\"$class\">$message</li>";
        }
        else {
            return $message;
        }
    }

    public function addMessageToPDFOutput($message){
        $this->html_output .= $message;
    }

    public function wrapMessageWithTag($message, $tag){
        if($tag){
            return '<' . $tag . '>' . $message . '</' . $tag . '>';
        }
        return $message;
    }

    public function getMessageType($type){
        $class = '';
        $sign = ' ';
        switch ($type) {
            case "info":
            case "blue":
                $sign = '-';
                $class = "info";
                break;
            case "success":
            case "green":
                $sign = "+";
                $class = "success";
                break;
            case "warning":
            case "yellow":
            case "orange":
                $sign = '*';
                $class = "warning";
                break;
            case "error":
            case "red":
                $sign = "!";
                $class = "error";
                break;
        }

        if (Director::is_cli()) {
            return $sign;
        }
        return $class;
    }
}
