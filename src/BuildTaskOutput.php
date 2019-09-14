<?php

namespace MySiteDigital;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleResourceLoader;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DB;

trait BuildTaskOutput
{
    protected $is_list = true;

    protected $has_started = false;

    protected $file_name = '';

    protected $file_path = '';

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
        $this->increaseMemoryAndTimeLimit();
        $loader = Injector::inst()->get(ModuleResourceLoader::class);
        $css = $loader->resolveURL('MySiteDigital/silverstripe-build-task-output: client/css/style.css');
        echo '
                <link href="' . $css . '" rel="stylesheet">
                <ul class="build">';
        $this->has_started = true;
    }

    public function complete()
    {
        $this->is_list = false;
        $this->message('</ul>', '');
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

        if (Director::is_cli()) {
            $message = strip_tags($message);
        }

        if ($this->is_list) {
            if(! $this->has_started){
                $this->begin();
            }
            DB::alteration_message(
                $this->wrapMessageWithTag($message, $tag),
                $this->getMessageType($type)
            );
        } else {
            if (Director::is_cli()) {
                echo $message;
            }
            else {
                echo $this->wrapMessageWithTag($message, $tag);
            }
        }
    }

    public function wrapMessageWithTag($tag){
        if($tag){
            return '<' . $tag . '>' . $message . '</' . $tag . '>';
        }
        return $message;
    }

    public function getMessageType($type){
        switch ($type) {
            case "info":
            case "blue":
                return 'changed';
                break;
            case "success":
            case "green":
                return 'created';
                break;
            case "warning":
            case "yellow":
            case "orange":
                return 'notice';
                break;
            case "error":
            case "red":
                return 'deleted';
                break;
            default:
                return '';
        }
    }
}
