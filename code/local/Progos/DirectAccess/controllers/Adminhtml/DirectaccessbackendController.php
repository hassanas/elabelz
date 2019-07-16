<?php
class Progos_DirectAccess_Adminhtml_DirectAccessbackendController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__("Direct Access"));
        $this->renderLayout();
    }
    public function generateInvoiceAction(){
       $result = array();
        if( !empty( $this->getRequest()->getParam("order_id")) ){
            $order_id = $this->getRequest()->getParam("order_id");
            $model = Mage::getModel('directaccess/directAccess');
            $result['msg'] = $model->generate( $order_id );
            $result['status'] = true; 
        }else{
           $result['status'] = false;
           $result['msg']    = "Invalid Method.";
        }
        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }
    public function generateDestinationInvoiceAction(){
        $result = array();
        if( !empty( $this->getRequest()->getParam("order_increment_id")) ){
          $order_id = $this->getRequest()->getParam("order_increment_id");
          $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
            if ($order->getId()) {
              $folder = $this->getRequest()->getParam("folder");
              $model = Mage::getModel('directaccess/directAccess');
              $result['msg'] = $model->generateDestinationInvoice( $order_id , $folder );
              $result['status'] = true; 
            }else{
              $result['status'] = true;
              $result['msg']    = "Invalid Order No.";
            }
        }else{
           $result['status'] = false;
           $result['msg']    = "Invalid Method.";
        }
        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    public function generateZipFileAction(){
      $result = array();
      $folder = $this->getRequest()->getParam("folder");
      $mainDirectory = Mage::getBaseDir().DS.'progos'.DS.'destinationInvoice'.DS.$folder;
      $zip = Mage::getBaseDir().DS.'progos'.DS.'destinationInvoice'.DS.$folder.'.zip';
      if( file_exists( $mainDirectory ) ){
        $source       = $mainDirectory;
        $destination  = $mainDirectory; 
        $filesArray =array();
        if( $files = scandir( $mainDirectory ) ){
          if( count($files) > 2 ){
            for( $i = 2 ; $i < count( $files ) ; $i++ ){
                $filesArray[$i]['name'] = $files[$i];
                $filesArray[$i]['path'] = $mainDirectory.DS.$files[$i];
            }
          }
        }
        if( $this->create_zip($filesArray,$zip)){
          $result['status'] = true;
          $this->delete_files( $mainDirectory.DS );
          $result['msg'] =  'Zip file created.';
          $result['filepath'] =  Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB).'progos'.DS.'destinationInvoice'.DS.$folder.'.zip';
          $result['folder'] = $folder.'.zip';
        }else{
           $result['status'] = false;
           $result['msg'] =  'Some Error Occour during zip file process.';
        }
       
      }else{
        $result['status'] = false;
        $result['msg'] =  'Some Error Occour during zip file process.';
      }
      $jsonData = json_encode($result);
      $this->getResponse()->setHeader('Content-type', 'application/json');
      $this->getResponse()->setBody($jsonData);
    }

  public function delete_files($target) {
    if(is_dir($target)){
        $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned
        
        foreach( $files as $file ){
            $this->delete_files( $file );      
        }
        rmdir( $target );
    } elseif(is_file($target)) {
        unlink( $target );  
    }
    return true;
  }

  function create_zip($files = array(),$destination = '',$overwrite = false ){
    if(file_exists($destination) && !$overwrite)
      return false;
    $valid_files = array();
    if(is_array($files))
      $i = 0;
      foreach($files as $file){
        if(file_exists($file['path'])){
          $valid_files[$i]['path'] = $file['path'];
          $valid_files[$i]['name'] = $file['name'];
          $i++;
        }
      }
    if(empty($valid_files))
      return false;
    $zip = new ZipArchive();
    if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true)
      return false;
    foreach($valid_files as $file){
      $zip->addFile($file['path'],$file['name']);
    }

    if( $zip->close() ){
      if( file_exists($destination) )
        return true;
      else
        return false;
    }
    return false;
  }
  public function removeZipFileAction(){
    $result = array();
    $folder = $this->getRequest()->getParam("url");
    $mainDirectory = Mage::getBaseDir().DS.'progos'.DS.'destinationInvoice'.DS.$folder;
    if (unlink( $mainDirectory )){
      $result['status'] = true;
    }else{
      $result['status'] = false;
    } 
    $jsonData = json_encode($result);
    $this->getResponse()->setHeader('Content-type', 'application/json');
    $this->getResponse()->setBody($jsonData);
  }
}