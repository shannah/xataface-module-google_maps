<?php
class Dataface_FormTool_map {

    /**
     * Defines how a tagger widget should be built.
     *
     * @param Dataface_Record $record The Dataface_Record that is being edited.
     * @param array &$field The field configuration data structure that the widget is being generated for.
     * @param HTML_QuickForm The form to which the field is to be added.
     * @param string $formFieldName The name of the field in the form.
     * @param boolean $new Whether this widget is being built for a new record form.
     * @return HTML_QuickForm_element The element that can be added to a form.
     *
     */
    function &buildWidget($record, &$field, $form, $formFieldName, $new=false){
        
        
        
        $factory = Dataface_FormTool::factory();
        $mt = Dataface_ModuleTool::getInstance();
        $mod = $mt->loadModule('modules_google_maps');
        //$atts = $el->getAttributes();
        $widget =& $field['widget'];
        $atts = array();
        if ( !@$atts['class'] ) $atts['class'] = '';
        $atts['class'] .= ' xf-map';
        
        $atts['df:cloneable'] = 1;
        $perms = $record->getPermissions(array('field'=>$field['name']));
        $noEdit = ($new and !@$perms['new']) or (!$new and !@$perms['edit']);
        if ( !@$perms['google_maps_addMarker'] or $noEdit ){
            
            $atts['data-xf-override-data-map-features-addmarkers'] = "0";
        }
        if ( !@$perms['google_maps_removeMarker'] or $noEdit ){
            $atts['data-xf-override-data-map-features-removemarkers'] = "0";
        }
        
        if ( $noEdit ){
            $atts['data-map-read-only'] = "1";
        }
              
        $mod->registerPaths();
        
        // Add our javascript
        Dataface_JavascriptTool::getInstance()->import('xataface/modules/google_maps/widgets/map.js');
        
        //$el->setAttributes($atts);
        $el = $factory->addElement('text', $formFieldName, $widget['label'], $atts);
        if ( PEAR::isError($el) ) throw new Exception($el->getMessage(), $el->getCode());
    
        return $el;
    }
    

}
