<?php
header("Content-type: text/json");
$action_json=$_REQUEST["a"];
//$action_json='{"action": ["summary","history"]}';
$action=json_decode($action_json,true);
$user="bharath";
//var_dump($action["action"]);
$return_object="{";
$i=1;
try{
  $m = new MongoClient("mongodb://localhost/");
  $mongo_connected=true;
}catch(MongoConnectException $e){
  $mongo_connected=false;
  $return_object.='"operation":-1';
}
if ($mongo_connected){
  foreach ($action["action"] as $act){
    $return_object.='"'.$act.'":{';
    switch($act){
      case 'summary':
        $db = $m->selectDB("carLog");
        $collection=$db->selectCollection('bharath_clog');
        $cursor=$collection->find([])->sort(["date"=>-1])->limit(10);
        $cursor_iterator=iterator_to_array($cursor);
        $keys = array_keys($cursor_iterator);
        //var_dump($cursor_iterator[$keys[0]]["odometer"]);
        $data='"odometer":'.$cursor_iterator[$keys[0]]['odometer'];
        $odoF=$cursor_iterator[$keys[0]]['odometer'];
        $fuelVolume=0;
        //for ($j=0;$j<10;$j++){
        foreach($cursor_iterator as $doc){
          $fuelVolume+=$doc['gallons'];
          $j++;
        }
        $fuelVolume-=$doc['gallons'];
        $odoI=$cursor_iterator[$keys[$j-1]]['odometer'];
        $economy=round(($odoF-$odoI)/$fuelVolume,2);
        $data.=',"economy":'.$economy;
        $return_object.=$data.',"operation":0';
      break;
      case 'history':
        $db = $m->selectDB("carLog");
        $collection=$db->selectCollection('bharath_clog');
        $cursor=$collection->find([])->sort(["date"=>-1])->limit(20);
        $cursor_array=iterator_to_array($cursor);
        $data='[';
        $j=0;
        foreach($cursor as $doc){
          $data.='{"date":"new Date(\"'.date('Y-M-d',$doc["date"]->sec).'\")",'.
            '"eTyp":'.$doc["etype"].','.
            '"odometer":'.$doc["odometer"].','.
            '"gallons":'.$doc["gallons"].','.
            '"price":'.$doc["fuel_price"].','.
            '"place":"'.$doc["location"].'",'.
            '"eta":'.$doc["economy"].'}';
          if ($cursor->hasNext()){
            $data.=',';
          }
          $j++;
        }
        $data.=']';
        $return_object.='"events":'.$data.',"operation":0';
      break;
      case 'add':
        $db = $m->selectDB("carLog");
        $collection=$db->selectCollection('bharath_clog');
        $rV=$collection->save(array(
          "date"=>new MongoDate(strtotime($_REQUEST["fillDate"]." 00:00:00")),
          "etype"=>1,
          "car"=>1,
          "gallons"=>$_REQUEST["gallons"],
          "fuel_price"=>$_REQUEST["fuel_price"],
          "economy"=>$_REQUEST["economy"],
          "odometer"=>$_REQUEST["odometer"],
          "location"=>$_REQUEST["location"]
        ));
        $return_object.='"operation":0';
      break;
      default:
        $return_object.="'operation':-1";
    }
    $return_object.="}";
    if ($i<count($action["action"])) $return_object.=",";
    $i++;
  }
  $m->close();
}
$return_object.="}";
echo $return_object;
/*echo "\n------------------------------\n";
var_dump(json_decode($return_object,true));
*/
?>
