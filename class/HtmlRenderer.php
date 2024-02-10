<?php

namespace StudioDemmys\pjmail;

class HtmlRenderer extends \Smarty
{
    const SMARTY_TEMPLATE_DIR = "./smarty/templates";
    const SMARTY_CONFIG_DIR = "./smarty/configs";
    const SMARTY_COMPILE_DIR = "./smarty/templates_c";
    const SMARTY_CACHE_DIR = "./smarty/cache";
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplateDir(Common::getAbsolutePath(self::SMARTY_TEMPLATE_DIR));
        $this->setConfigDir(Common::getAbsolutePath(self::SMARTY_CONFIG_DIR));
        $this->setCompileDir(Common::getAbsolutePath(self::SMARTY_COMPILE_DIR));
        $this->setCacheDir(Common::getAbsolutePath(self::SMARTY_CACHE_DIR));
        
        $cache = Config::getConfig("html_cache");
        if ($cache == 0) {
            $this->setCaching(\Smarty::CACHING_OFF);
        } else {
            $this->setCaching(\Smarty::CACHING_LIFETIME_CURRENT);
            $this->setCacheLifetime($cache);
        }
        
    }
}