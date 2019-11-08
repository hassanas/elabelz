<?php                            
session_start();  
require_once(__DIR__ . '/../lib/magebird/popup/customizer.php');
require_once(__DIR__ . '/../lib/magebird/popup/popup_model.php');
require_once(__DIR__ . '/../lib/magebird/popup/popup_helper.php');
require_once(__DIR__ . '/../lib/magebird/popup/popup_view.php');

$class  = new magebird_popup();
header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");    
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'show'; 
switch($action){
  case "show": 
    require_once(__DIR__ . '/../lib/magebird/popup/Mobile_Detect.php');
    require_once(__DIR__ . '/../lib/magebird/popup/MaxMind/Db/Reader2.php');
    require_once(__DIR__ . '/../lib/magebird/popup/MaxMind/Db/Reader/Decoder2.php');
    require_once(__DIR__ . '/../lib/magebird/popup/MaxMind/Db/Reader/InvalidDatabaseException2.php');
    require_once(__DIR__ . '/../lib/magebird/popup/MaxMind/Db/Reader/Metadata2.php');
    require_once(__DIR__ . '/../lib/magebird/popup/MaxMind/Db/Reader/Util2.php');
    $class->model->addNewView();
    $class->model->setNVisitor();
    $class->showAction();    
    break;
  case "stats":
    $class->statsAction();    
    break;
  case "parsePopup":
    $class->parsePopupAction();
    break; 
  case "popupCartsCount":
    $class->model->uniqueViewStats($class->helper->getParam('popupId'));
    break;   
  default:
    $class->statsAction();
}

class magebird_popup{
  var $model;
  var $view;
  var $helper;
  
  public function __construct(){    
    $this->helper = new popup_helper();
    $this->model = new popup_model($this->helper);
    $this->view = new popup_view();     
  }
  
  public function showAction(){                  
    $product = $this->model->getCurrentProduct($this->helper);
    $cartProduct = $this->model->getCartProduct($this->helper);
    if($templateId = $this->helper->getParam('templateId')){
      $popups = $this->model->getPopupTemplate($templateId);
    }elseif($popupId = $this->helper->getParam('previewId')){    
      $popups = $this->model->getPopup($popupId);
    }else{
      $popups = $this->model->getPopups($this->helper);
    }
    
    echo $this->view->toHtml($popups,$this->helper,$product,$cartProduct);
  }  
  
  public function statsAction(){
      if($this->helper->getIsCrawler()) return;
      if(isset($_POST['popupId']) || isset($_POST['popupIds'])){
        $data = $_POST;
      }else{
        $data = $_GET;
      }
      if($this->helper->getParam('mousetracking')){
        $this->model->handleMousetracking();
      }
      $popupIds = array();
      if(isset($data['popupId'])){
        $popupId = $data['popupId'];
        $popupIds[$popupId] = $data['time'];
      }
      //multi popups on windowunload
      if(isset($data['popupIds'])){
        $popupIds2 = $data['popupIds'];
        $popupIds2 = json_decode($popupIds2);
        foreach($popupIds2 as $id => $time){
          $popupIds[$id] = $time;
        }        
      }              
       
      foreach($popupIds as $popupId => $time){
        $popup = current($this->model->getPopup($popupId));           
        if(isset($popup['popup_id'])){
          $views = $popup['views'];
          //for popups without background overlay (background_color=3,4) we set new view inside block
          if(
            ($popup['background_color']!=3 && $popup['background_color']!=4) 
            ||  
            (($popup['background_color']==3 || $popup['background_color']==4) && $popup['show_when']!=1)
          ){  
            $this->model->setPopupData($popupId,'views',$views+1);
            $this->model->uniqueViewStats($popupId);                       
          }
          $totalViews = $views;
          $totalTime = $popup['total_time'];
          $currentViewSpent = $time;          
          if($currentViewSpent>($popup['max_count_time']*1000)){
            $currentViewSpent = $popup['max_count_time']*1000;
          }
          $this->model->setPopupData($popupId,'total_time',$totalTime+$currentViewSpent);   
          if(isset($data['closed']) && $data['closed']==1){      
            $this->model->setPopupData($popupId,'popup_closed',$popup['popup_closed']+1);
          }elseif(isset($data['windowClosed']) && $data['windowClosed']==1){       
            if($popup['background_color']!=3 && $popup['background_color']!=4){
              //prever če ni to kaj fore s tem ker uporabm getter znotraj setterja
              $this->model->setPopupData($popupId,'window_closed',$popup['window_closed']+1);
              $this->model->setPopupData($popupId,'last_rand_id',$data['lastPageviewId']);
            } 
          }elseif(isset($data['clickInside']) && $data['clickInside']==1){                    
            $this->model->setPopupData($popupId,'click_inside',$popup['click_inside']+1);
          }         
        }
      }
  }
       
}