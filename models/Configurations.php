<?php namespace components\custom_field\models; if(!defined('TX')) die('No direct access.');

class Configurations extends \dependencies\BaseModel
{
  
  protected static
    
    $table_name = 'custom_field_configurations';
  
  #TODO
  public static function find($component_name, $model_name)
  {
    
    return mk('Sql')->table('custom_field', 'Configurations')
      ->where('component_name', "'{$component_name}'")
      ->where('model_name', "'{$model_name}'")
      ->execute_single()
      
      ->is('empty', function($cfg)use($component_name, $model_name){
        
        //When creating a new one, validate this model exists.
        //Note: we don't do this when retrieving. For archiving purposes when components update.
        try{
          mk('Sql')->model($component_name, $model_name);
        }
        catch(\exception\NotFound $nfe){
          throw new \exception\Programmer('Could not find for model "'.$component_name.'.'.$model_name.'" it does not exist.');
        }
        
        return $cfg->set(
          mk('Sql')->model('custom_field', 'Configurations')
            ->merge(array(
              'component_name' => $component_name,
              'model_name' => $model_name
            ))
            ->save()
        );
        
      });
    
  }
  
  #TODO
  public function configure($definition)
  {
    
    //Try decode the supplied JSON.
    $definition = json_decode($definition);
    
    //Detect JSON parsing errors.
    if($definition === null)
      throw new \exception\Validation('Invalid JSON provided: "'.json_last_error_msg().'"');
    
    //Check if things are properly formatted.
    if(!is_array($definition))
      throw new \exception\Validation('The root element must be an array.');
    
    $keys = array();
    $types = array('line', 'text', 'checkbox');
    
    //Validate the individual field definitions.
    for($i=0; $i<count($definition); $i++){
      
      //Filter out things that are not relevant.
      $field = (array)$definition[$i];
      $data = Data($field)
        ->having('key', 'type', 'label', 'required')
        
        //Validate the fields.
        ->key->validate('Key field at array index '.$i, array('required', 'string', 'not_empty'))->back()
        ->type->validate('Type field at array index '.$i, array('required', 'string', 'in'=>$types))->back()
        ->required->validate('Required field at array index '.$i, array('boolean'))->back();
      
      //Validate the label.
      if(!array_key_exists('label', $field))
        $data->label->validate('Label field at array index '.$i, array('required', 'string', 'not_empty'));
      
      //Check for types.
      if(!is_string($field['label']) && !is_object($field['label']))
        throw new \exception\Validation('Label field at array index '.$i.' is invalid. Must be a string or an object with strings per language code.');
      
      //Check required en-GB locale, when using an object.
      if(is_object($field['label']) && !isset($field['label']->{"en-GB"}))
        throw new \exception\Validation('Label field at array index '.$i.' is invalid. When using localized strings, the en-GB locale is required.');
      
      //Validate each entry in the labels.
      if(is_object($field['label']))
      {
        
        foreach($field['label'] as $locale => $label){
          
          //Validate locale.
          if(!preg_match('~^[a-z]{2}-[A-Z]{2}$~', $locale))
            throw new \exception\Validation('Label field at array index '.$i.' is invalid. Invalid locale "'.$locale.'", use a format like "en-GB".');
          
          //Validate string.
          if(!is_string($label) || empty($label))
            throw new \exception\Validation('Label field at array index '.$i.' is invalid. Invalid locale "'.$locale.'", the value can not be empty.');
          
        }
        
      }
      
      //Check for duplicate keys.
      if(in_array($field['key'], $keys))
        throw new \exception\Validation('Duplicated key "'.$field['key'].'" at array index '.$i.'.');
      
      //Make type lowercase.
      $data->type->set(strtolower($data->type->get('string')));
      
      //Store this key.
      $keys[] = $field['key'];
      
      //Store the sanitized version.
      $definition[$i] = $data->as_array();
      
    }
    
    $this->field_definition->set(json_encode($definition));
    $this->fields->set($this->get_fields()); //Set this so that caches don't confuse you.
    
    return $this->save();
    
  }
  
  #TODO
  public function get_target_model()
  {
    return mk('Sql')->model($this->component_name, $this->model_name);
  }
  
  #TODO
  public function get_fields()
  {
    
    $fields = array();
    $definition = json_decode($this->field_definition->get('string'), true);
    if($definition)
    {
      foreach($definition as $field){
        $fields[$field['key']] = $field;
      }
    }
    return Data($fields);
    
  }
  
  #TODO
  public function get_values($pks=null)
  {
    
  }
  
  #TODO
  public function set_values($pks)
  {
    
  }
  
  #TODO
  public function render_fields($form_id)
  {
    
    $fields = array();
    $classes = array(
      'line' => '\\dependencies\\forms\\TextField',
      'text' => '\\dependencies\\forms\\TextAreaField',
      'checkbox' => '\\dependencies\\forms\\CheckboxField'
    );
    
    foreach($this->fields as $field){
      
      //Build options.
      $options = array();
      $options['form_id'] = $form_id;
      if($field->type->get('string') == 'checkbox')
        $options['options'] = array(1);
      
      //Get label.
      $label = $field->label->is_leafnode() ?
        $field->label->get('string') :
        $field->label->{mk('Language')->code}
          ->otherwise($field->label->{'en-GB'})
          ->get('string');
      
      //Create the field.
      $field_obj = new $classes[$field->type->get('string')](
        $field->key->get('string'),
        $label,
        $this->target_model,
        $options
      );
      
      //Render it.
      $field_obj->render();
      
    }
    
    return $this;
    
  }
  
  #TODO
  public function render_form(&$id, $action, array $options=array(), $pks=null)
  {
    
    mk('Component')->load('custom_field', 'classes\\CustomFieldFormBuilder', false);
    
    $builder = new \components\custom_field\classes\CustomFieldFormBuilder($this, array(
      'fields' => isset($options['fields']) ? $options['fields'] : null,
      'relations' => isset($options['relations']) ? $options['relations'] : null,
    ));
    
    $id = $builder->id();
    
    $options = array_merge($options, array(
      'action' => $action
    ));
    
    $builder->render($options);
    
  }
  
  #TODO
  public function render_values($pks=null)
  {
    
  }
  
}
