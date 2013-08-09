<?php namespace components\custom_field\models; if(!defined('TX')) die('No direct access.');

class Configurations extends \dependencies\BaseModel
{
  
  protected static
    $table_name = 'custom_field_configurations';
  
  protected
    $is_mixed = false;
  
  #TODO
  public static function find($component_name, $model_name, $alternative=null)
  {
    
    return mk('Sql')->table('custom_field', 'Configurations')
      ->where('component_name', "'{$component_name}'")
      ->where('model_name', "'{$model_name}'")
      ->where('alternative', $alternative === null ? 'NULL' : "'$alternative'")
      ->execute_single()
      
      ->is('empty', function($cfg)use($component_name, $model_name, $alternative){
        
        //When creating a new one, validate this model exists.
        //Note: we don't do this when retrieving. For archiving purposes when components update.
        try{
          mk('Sql')->model($component_name, $model_name);
        }
        catch(\exception\NotFound $nfe){
          throw new \exception\Programmer('Could not find for model "'.$component_name.'.'.$model_name.'" it does not exist.');
        }
        
        return mk('Sql')
          ->model('custom_field', 'Configurations')
          ->merge(array(
            'component_name' => $component_name,
            'model_name' => $model_name,
            'alternative' => $alternative === null ? 'NULL' : $alternative
          ))
          ->save();
        
      });
    
  }
  
  #TODO
  public function mix_alternative($alternative)
  {
    
    $alt = $this::find($this->component_name, $this->model_name, $alternative);
    
    //Get the current fields.
    $fields = $this->fields;
    
    //Every field that isn't already defined is now inserted.
    foreach($alt->fields as $key => $field)
      if(!$fields->{$key}->is_set())
        $fields->{$key}->set($field);
    
    //Set the merged fields.
    $this->fields->set($fields);
    
    //Mark this config as mixed.
    $this->is_mixed = true;
    return $this;
    
  }
  
  #TODO
  public function configure($definition, $validate_only=false)
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
    
    $this->field_definition->set(json_encode($definition, JSON_PRETTY_PRINT));
    $this->fields->set($this->get_fields()); //Set this so that caches don't confuse you.
    
    if($validate_only === true)
      return $this;
    else
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
    
    $fields = Data();
    $definition = json_decode($this->field_definition->get('string'), true);
    
    if($definition)
    {
      foreach($definition as $field){
        $field = Data($field);
        $field->preferred_label->set(
          $field->label->is_leafnode() ?
            $field->label->get('string') :
            $field->label->{mk('Language')->code}
              ->otherwise($field->label->{'en-GB'})
              ->get('string')
        );
        $fields->merge(array($field->key->get() => $field));
      }
    }
    
    return $fields;
    
  }
  
  #TODO
  public function get_values($pks=null)
  {
    
    //Prepare PK string.
    raw($pks);
    if(is_array($pks)) {
      ksort($pks);
      $pks_string = json_encode($pks);
    } else {
      $pks_string = (string)$pks;
    }
    
    return mk('Sql')
      ->table('custom_field', 'Values')
      ->where('target_pk', "'$pks_string'")
      ->where('configuration_id', $this->id)
      ->execute()
      ->map(function($value){
        return array($value->key->get() => $value->value->get());
      })
      ->as_array();
    
  }
  
  #TODO
  public function set_values($pks, $values)
  {
    
    $values = Data($values);
    
    //Prepare PK string.
    raw($pks);
    if(is_array($pks)) {
      ksort($pks);
      $pks_string = json_encode($pks);
    } else {
      $pks_string = (string)$pks;
    }
    
    //Loop the fields.
    $value_models = array();
    foreach($this->fields as $key => $field)
    {
      
      //Validation.
      $value = $values[$field->key->get()];
      switch ($field->type->get()) {
        case 'line':
        case 'text':
          $value->validate($field->preferred_label->get(), $field->required->get('boolean') ?
            array('required', 'string', 'not_empty') :
            array('string')
          );
          break;
        
        case 'checkbox':
          $value->validate($field->preferred_label->get(), $field->required->get('boolean') ?
            array('required', 'boolean') :
            array('boolean')
          );
          break;
      }
      
      //Store the value.
      $config_id = $this->id;
      $value_models[] = mk('Sql')
        ->table('custom_field', 'Values')
        ->where('target_pk', "'$pks_string'")
        ->where('configuration_id', $config_id)
        ->where('key', "'$key'")
        ->execute_single()
        
        ->is('empty', function()use($pks_string, $config_id, $key){
          return mk('Sql')
            ->model('custom_field', 'Values')
            ->merge(array(
              'configuration_id' => $config_id,
              'target_pk' => $pks_string,
              'key' => $key
            ));
        })
        
        ->merge(array(
          'value' => $value
        ));
      
    }
    
    //All validation is done, now just save everything.
    foreach($value_models as $model)
      $model->save();
    
    return $this;
    
  }
  
  #TODO
  public function render_fields($form_id, $defaults=array())
  {
    
    $fields = array();
    $classes = array(
      'line' => '\\dependencies\\forms\\TextField',
      'text' => '\\dependencies\\forms\\TextAreaField',
      'checkbox' => '\\dependencies\\forms\\CheckboxField'
    );
    
    $model = $this->target_model;
    $model->merge($defaults);
    
    foreach($this->fields as $field){
      
      //Build options.
      $options = array();
      $options['form_id'] = $form_id;
      if($field->type->get('string') == 'checkbox')
        $options['options'] = array(1);
      
      //Create the field.
      $field_obj = new $classes[$field->type->get('string')](
        $field->key->get('string'),
        $field->preferred_label->get('string'),
        $model,
        $options
      );
      
      //Render it.
      $field_obj->render();
      
    }
    
    return $this;
    
  }
  
  #TODO
  public function render_values($pks=null)
  {
    
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
    
    return $this;
    
  }
  
}
