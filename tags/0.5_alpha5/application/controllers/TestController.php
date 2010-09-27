<?php

require_once 'Zend/Controller/Action.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Config.php';
require_once 'Zend/Translate.php';
require_once 'X/Env.php';
require_once 'X/VlcShares.php';


class TestController extends X_Controller_Action
{

    public function indexAction()
    {
    	
    	$tests = $this->doSystemTests();

    	if ( $this->options ) {
	    	$tests = array_merge($tests, X_VlcShares_Plugins::broker()->preGetTestItems($this->options, $this));
	    	// normal links
	    	$tests = array_merge($tests, X_VlcShares_Plugins::broker()->getTestItems($this->options, $this));
	    	// bottom links
			$tests = array_merge($tests, X_VlcShares_Plugins::broker()->postGetTestItems($this->options, $this));
    		
			$debugPath = sys_get_temp_dir().'/vlcShares.debug.log';
			if ( $this->options->general->debug->path != null && trim($this->options->general->debug->path) != '' ) {
				$debugPath = $this->options->general->debug->path;
			}
    		$this->view->log = @file_get_contents($debugPath);
    	}
    	$this->view->tests = $tests;
    	
    }

    public function doSystemTests() {
    	
    	$tests = array();
    	
    	$tests[] = $this->_check('VLCShares version', null, X_VlcShares::VERSION);
    	$tests[] = $this->_check('VLC path is valid ('.$this->options->vlc->path.')', $this->_vlcPathCheck($this->options->vlc->path));
    	
    	$tests[] = $this->_check('Language file is valid ('.$this->options->general->languageFile.')', $this->_languageCheck($this->options->general->languageFile));
    	
    	$tests[] = $this->_check('Mediainfo helper enabled', (boolean) $this->options->helpers->mediainfo->enabled);
    	if ( $this->options->helpers->mediainfo->enabled ) {
    		$tests[] = $this->_check('Mediainfo path is valid ('.$this->options->helpers->mediainfo->path.')', file_exists($this->options->helpers->mediainfo->path));
    	}

    	$tests[] = $this->_check('FFMpeg helper enabled', (boolean) $this->options->helpers->ffmpeg->enabled);
    	if ( $this->options->helpers->ffmpeg->enabled ) {
    		$tests[] = $this->_check('FFMpeg path is valid ('.$this->options->helpers->ffmpeg->path.')', file_exists($this->options->helpers->ffmpeg->path));
    	}
    	
    	return $tests;
    	
    }
    
    
    private function _check($name, $test, $success = 'Success', $failure = 'Failure') {
    	return array($name, $test, ( ($test === true || $test === null) ? $success : $failure));
    }
    
	private function _vlcPathCheck($vlcPath) {
		$vlcPath = trim($vlcPath, '"');
        $exists = file_exists($vlcPath);
        return ( $exists == null ? false : $exists); 
    }
    
    private function _languageCheck($file) {
        $exists = file_exists(APPLICATION_PATH."/../languages/".$file);
        return ( $exists == null ? false : $exists); 
	}
    
}
