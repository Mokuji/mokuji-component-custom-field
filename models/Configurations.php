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
        
        return $cfg->become(
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
    
  }
  
  #TODO
  public function get_fields()
  {
    
  }
  
  #TODO
  public function get_values()
  {
    
  }
  
  #TODO
  public function render_fields()
  {
    
  }
  
  #TODO
  public function render_values()
  {
    
  }
  
}
