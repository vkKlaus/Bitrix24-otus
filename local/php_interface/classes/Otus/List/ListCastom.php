<?php
namespace Otus\List;

//use Bitrix\Main;
//use Bitrix\Main\Loader;
//use Bitrix\Iblock;
//use Bitrix\Iblock\IblockTable;
//use Bitrix\Main\ModuleManager;
//use Bitrix\Main\ModuleTable;
use Bitrix\Iblock\ElementTable;

class ListCastom
{
  //  public $idBlock;
    public  function getList($idBlock, $id){
      
      $res = ElementTable::getList(array(
       
          'order' => array('SORT' => 'ASC'),
    
          'select' => array('ID', 'NAME', 'IBLOCK_ID','SEARCHABLE_CONTENT'),
      
          'filter' => array('IBLOCK_ID' => ($idBlock),'ID' => ($id) ),
       
          'data_doubling' => false,
      ));

return $res;
    
}}