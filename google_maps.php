<?php
class modules_google_maps {
    
    /**
    * @brief The base URL to the datepicker module.  This will be correct whether it is in the 
    * application modules directory or the xataface modules directory.
    *
    * @see getBaseURL()
    */
   private $baseURL = null;
   private $pathsRegistered = false;
	
	
    /**
     * @brief Initializes the datepicker module and registers all of the event listener.
     *
     */
    function __construct(){
        $app = Dataface_Application::getInstance();


        // Now work on our dependencies
        $mt = Dataface_ModuleTool::getInstance();

        // We require the XataJax module
        // The XataJax module activates and embeds the Javascript and CSS tools
        $mt->loadModule('modules_XataJax', 'modules/XataJax/XataJax.php');


        // Register the tagger widget with the form tool so that it responds
        // to widget:type=tagger
        import('Dataface/FormTool.php');
        $ft = Dataface_FormTool::getInstance();
        $ft->registerWidgetHandler('map', dirname(__FILE__).DIRECTORY_SEPARATOR.'widget.php', 'Dataface_FormTool_map');

        if ( !@$app->_conf['modules_google_maps'] or !@$app->_conf['modules_google_maps']['key']){
            $msg = <<<END
                 <p>Google Maps Module is installed but no API key is specified.</p>
                 <p>For information about obtaining your API key see <a href="https://developers.google.com/maps/documentation/javascript/tutorial#api_key">this page</a>.</p>
                 <p>After obtaining your key, add the following section to your application's conf.ini file:</p>
                 <p><code><pre>
[modules_google_maps]
    key=YOUR_API_KEY_HERE
                    </pre></code></p>
END;
                    
            die($msg);
        }
        $app->addHeadContent('<script>XF_GOOGLE_MAPS_API_KEY="'.htmlspecialchars($app->_conf['modules_google_maps']['key']).'";</script>');
        
        foreach ( Dataface_Table::loadTable('', df_db(), true) as $t ){
            $evt = new StdClass;
            $evt->table = $t;
            $this->afterTableInit($evt);
        }
        $app->registerEventListener("afterTableInit", array($this, 'afterTableInit'));
        $app->registerEventListener("Dataface_Record__htmlValue", array($this, 'Dataface_Record__htmlValue'));

    }
    
    
    function registerPaths(){
        if ( !$this->pathsRegistered ){
            $mod = $this;
            $jt = Dataface_JavascriptTool::getInstance();
            $jt->addPath(dirname(__FILE__).'/js', $mod->getBaseURL().'/js');

            $ct = Dataface_CSSTool::getInstance();
            $ct->addPath(dirname(__FILE__).'/css', $mod->getBaseURL().'/css');
        }
    }
	

    public function Dataface_Record__htmlValue($event){
        $fieldname = $event->fieldname;
        $record = $event->record;
        $field =& $record->table()->getField($fieldname);
        if ( $field['widget']['type'] === 'map' ){
            $this->registerPaths();
            Dataface_JavascriptTool::getInstance()
                    ->import('xataface/modules/google_maps/widgets/map.js');
            $val = $record->val($fieldname);
            if ( !trim($val) ){
                $event->out = '';
            } else {
                $event->out = '<input type="text" value="'.df_escape($val).'" class="xf-map" data-map-read-only="1"/>';
            }
        }
        
    }
    
    public function afterTableInit($event){
        $fields =& $event->table->fields();
        
        foreach ( $fields as $k=>$f ){
            if ( @$fields[$k]['widget']['type'] === 'map' ){
                $fields[$k]['struct'] = 1;
                $fields[$k]['visibility']['list'] = 'hidden';
                
            }
        }
    }
    
    /**
     * @brief Returns the base URL to this module's directory.  Useful for including
     * Javascripts and CSS.
     *
     */
    public function getBaseURL(){
        if ( !isset($this->baseURL) ){
            $this->baseURL = Dataface_ModuleTool::getInstance()->getModuleURL(__FILE__);
        }
        return $this->baseURL;
    }
}
