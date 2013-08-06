<?php namespace components\custom_field\classes; if(!defined('TX')) die('No direct access.');

use components\custom_field\models\Configurations;

class CustomFieldFormBuilder extends \dependencies\forms\FormBuilder
{
  
  protected $custom_fields_configuration;
  
  public function __construct(Configurations $config, array $options=array())
  {
    
    $this->custom_fields_configuration = $config;
    parent::__construct($config->target_model, $options);
    
  }
  
  protected function generate_fields()
  {
    
    parent::generate_fields();
    
    $classes = array(
      'line' => '\\dependencies\\forms\\TextField',
      'text' => '\\dependencies\\forms\\TextAreaField',
      'checkbox' => '\\dependencies\\forms\\CheckboxField'
    );
    
    foreach($this->custom_fields_configuration->fields as $field){
      
      //Build options.
      $options = array();
      $options['form_id'] = $this->id();
      if($field->type->get('string') == 'checkbox')
        $options['options'] = array(1);
      
      //Get label.
      $label = $field->label->is_leafnode() ?
        $field->label->get('string') :
        $field->label->{mk('Language')->code}
          ->otherwise($field->label->{'en-GB'})
          ->get('string');
      
      //Create the field.
      $this->fields[] = new $classes[$field->type->get('string')](
        $field->key->get('string'),
        $label,
        $this->custom_fields_configuration->target_model,
        $options
      );
      
    }
    
  }
  
}