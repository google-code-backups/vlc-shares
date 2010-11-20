<?php

require_once 'X/Env.php';

class Application_Form_AutoConfigs extends Zend_Form
{
	private $configs = array();
	
	function __construct($configs = array(), $options = null) {
		$this->configs = $configs;
		parent::__construct($options);
	}
	
    public function init()
    {
        // Set the method for the display form to POST
        $this->setMethod('post');
        $this->setName('autoconfigs');
 
        $sections = Application_Model_ConfigsMapper::i()->fetchSections();
        
        // general section go on top

        $displayGroup = array('general' => array());
        
        $configs = $this->configs;
        
        foreach ( $configs as $config ) {
        	/* @var $config Application_Model_Config */
        	
        	$elementType = ''; 
        	
        	switch ($config->getType()) {
        		
        		case Application_Model_Config::TYPE_BOOLEAN: $elementType = 'radio'; break;
        		case Application_Model_Config::TYPE_TEXTAREA: $elementType = 'textarea'; break;
        		case Application_Model_Config::TYPE_SELECT: $elementType = 'select'; break;
				// case Application_Model_Config::TYPE_FILE: $elementType = 'file'; break; // TODO check for it        		
        		case Application_Model_Config::TYPE_TEXT:
        		default: $elementType = 'text';
        			break;
        	}
        	
        	$elementName = $config->getSection().'_'.str_replace('.', '_', $config->getKey());
        	
        	$elementLabel = ($config->getLabel() != null && $config->getLabel() != '' ? X_Env::_($config->getLabel()) : $config->getKey() );
        	$elementDescription = ($config->getDescription() ? X_Env::_($config->getDescription()) . '<br/>' : '' ) . ($config->getDefault() != null ?  "<br/><i>Default:</i> ".$config->getDefault() : ''); 
        	
        	$element = $this->createElement($elementType, $elementName, array(
        		'label'			=> $elementLabel,
        		'description'	=> $elementDescription,
        		/*
        		'options'		=> array(
        			'class'			=> $config->getClass()
        		)
        		*/
        	));
        	
        	$element->getDecorator('description')->setEscape(false);
        	$element->getDecorator('htmlTag')->setOption('class', $config->getClass());
        	$element->getDecorator('label')->setOption('class', $element->getDecorator('label')->getOption('class') . ' ' . $config->getClass());
        	
        	if ( $config->getType() == Application_Model_Config::TYPE_BOOLEAN) {
        		$element->setMultiOptions(array(1 => X_Env::_('configs_options_yes'), 0 => X_Env::_('configs_options_no') ));
        	}
        	
        	$this->addElement($element);
        	
        }
        
        // Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
            'label'    => X_Env::_('configs_form_submit'),
        	'decorators' => array('ViewHelper')
        ));
        
        // Add the submit button
        $this->addElement('button', 'abort', array(
        	'onClick'	=> 'javascript:history.back();',
            'ignore'   => true,
            'label'    => X_Env::_('configs_form_abort'),
        	'decorators' => array('ViewHelper')
        ));
 
        // And finally add some CSRF protection
        $this->addElement('hash', 'csrf', array(
        	'salt'	=> 'autoconfigs',
            'ignore' => true,
        	'decorators' => array('ViewHelper')
        ));
        
        $this->addDisplayGroup(array('submit', 'abort', 'hash'), 'buttons');
        
    }
}