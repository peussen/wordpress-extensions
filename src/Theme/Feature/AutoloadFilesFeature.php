<?php
/*
 * @author: petereussen
 * @package: wordpress-extensions
 */

namespace HarperJones\Wordpress\Theme\Feature;

/**
 * Adds support for autoloading of custom files
 * Allows automatic loading of files in the 'after_setup_theme' action. Handy for autoloading
 * CPT and custom widgets etc.
 *
 * in your code you should add:
 *
 * <code>
 * add_theme_support('harperjones-autoload-files');
 * </code>
 *
 * Optionally you can specify a second argument which should be a path to the custom directory
 *
 * Including is done RECURSIVELY
 *
 * @package HarperJones\Wordpress\Theme\Feature
 */
class AutoloadCustomFeature implements FeatureInterface
{
    protected $FilesFolder = '';
    protected $production   = false;

    public function register($options = [])
    {
        $this->production = (getenv('WP_ENV') === 'production');

        if ( isset($options[0])) {
            $this->filesFolder = $options[0];
        } elseif (isset($options['path'])) {
            $this->filesFolder =  $options['path'];
        }

        if ( empty($this->filesFolder)) {
            $this->filesFolder = apply_filters('hj/autoload/filesfolder',get_template_directory() . '/src/files');
        }


        if ( $this->filesFolder[0] != '/' ) {
            $this->filesFolder = get_template_directory() . '/' . $this->filesFolder;
        }

        if ( substr($this->filesFolder,-1) != '/' ) {
            $this->filesFolder .= '/';
        }

        if ( did_action('after_setup_theme')) {
            $this->loadfilesFolder();
        } else {
            add_action('after_setup_theme',[ $this, 'loadFilesFolder' ]);
        }
    }

    public function loadFilesFolder()
    {
        if ( !is_dir($this->filesFolder)) {
            return;
        }

        $fileList = $this->getAllFiles();

        // Stop if we have no includes
        if ( !is_array($fileList) ) {
            return;
        }

        foreach( $fileList as $includeFile ) {
            require_once($includeFile);
        }
    }

    /**
     * Obtains a list of files (recursively) that should be included (**.php)
     *
     * @return array|bool
     */
    private function getAllFiles()
    {
        $fileList = $this->getCached();

        if ( $fileList !== false ) {
            return $fileList;
        }

        $dirIt    = new \RecursiveDirectoryIterator($this->filesFolder);
        $it       = new \RecursiveIteratorIterator($dirIt);
        $filterIt = new \RegexIterator($it, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $fileList = [];

        foreach( $filterIt as $file ) {
            $fileList[] = $file[0];
        }

        $this->updateCache($fileList);

        return $fileList;
    }

    /**
     * Stored information in the database to avoid directory lookups (in production)
     *
     * @return bool|array
     */
    private function getCached()
    {
        if ( !$this->production ) {
            return false;
        }

        $list = get_site_option('hj_autoload-cache-' . wp_get_theme());

        if ( is_array($list) && isset($list[0]) && substr($list[0],0,strlen($this->filesFolder)) != $this->filesFolder) {
            return false;
        }
        return $list;

    }

    /**
     * Updates cache (if in production)
     *
     * @param $fileList
     */
    private function updateCache($fileList)
    {
        if ( !$this->production) {
            return;
        }

        update_site_option('hj-autoload-cache-' . wp_get_theme(), $fileList);
    }
}