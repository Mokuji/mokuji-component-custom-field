<?php namespace components\custom_field; if(!defined('TX')) die('No direct access.');

//Make sure we have the things we need for this class.
mk('Component')->check('update');
mk('Component')->load('update', 'classes\\BaseDBUpdates', false);

class DBUpdates extends \components\update\classes\BaseDBUpdates
{
  
  protected
    $component = 'custom_field',
    $updates = array(
      '0.0.1-alpha' => '0.0.2-alpha',
      '0.0.2-alpha' => '0.0.3-alpha'
    );
  
  public function update_to_0_0_3_alpha($current_version, $forced)
  {
    
    try{
      
      mk('Sql')->query("
        ALTER TABLE `#__custom_field_configurations`
          ADD COLUMN `alternative` varchar(255) NULL after `model_name`,
          DROP INDEX `model_name`,
          ADD INDEX `alternative` (`component_name`(100), `model_name`(130), `alternative`(20))
      ");
      
    }catch(\exception\Sql $ex){
      //When it's not forced, this is a problem.
      //But when forcing, ignore this.
      if(!$forced) throw $ex;
    }
    
  }
  
  public function update_to_0_0_2_alpha($current_version, $forced)
  {
    
    try{
      
      mk('Sql')->query("
        ALTER TABLE `#__custom_field_values`
          ADD COLUMN `target_pk` varchar(255) NOT NULL after `configuration_id`,
          ADD INDEX `target_pk` (`configuration_id`, `target_pk`),
          DROP INDEX `key`,
          ADD UNIQUE INDEX `key` (`configuration_id`, `target_pk`(150), `key`(100))
      ");
      
    }catch(\exception\Sql $ex){
      //When it's not forced, this is a problem.
      //But when forcing, ignore this.
      if(!$forced) throw $ex;
    }
    
  }
  
  public function install_0_0_1_alpha($dummydata, $forced)
  {
    
    if($forced === true){
      mk('Sql')->query('DROP TABLE IF EXISTS `#__custom_field_configurations`');
      mk('Sql')->query('DROP TABLE IF EXISTS `#__custom_field_values`');
    }
    
    mk('Sql')->query('
      CREATE TABLE `#__custom_field_configurations` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `component_name` varchar(255) NOT NULL,
        `model_name` varchar(255) NOT NULL,
        `field_definition` TEXT NOT NULL,
        PRIMARY KEY (`id`),
        INDEX `model_name` (`component_name`(100), `model_name`(150))
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ');
    
    mk('Sql')->query('
      CREATE TABLE `#__custom_field_values` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `configuration_id` int(10) UNSIGNED NOT NULL,
        `key` varchar(255) NOT NULL,
        `value` TEXT NOT NULL,
        PRIMARY KEY (`id`),
        INDEX `configuration_id` (`configuration_id`),
        UNIQUE INDEX `key` (`configuration_id`, `key`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8
    ');
    
  }
  
}
