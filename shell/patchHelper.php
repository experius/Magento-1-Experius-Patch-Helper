<?php

require_once 'abstract.php';

class Mage_Shell_PatchHelper extends Mage_Shell_Abstract{
    
    private $appCodeLocalPath = false;
    
    private $rewrites = false;
    
    private $rewritesFlat = false;
    
    public function run(){
        
        if($this->getArg('patch')){

            $patchFilePath = Mage::getBaseDir() . DS . $this->getArg('patch');
        
            if(file_exists($patchFilePath)){
                $fp = @fopen($patchFilePath, 'r'); 
                if ($fp) {
                   $lines = explode("\n", fread($fp, filesize($patchFilePath)));
                   
                   foreach($lines as $line){
                        if(preg_match("/\+{3} (.*)/",$line, $matches)){
                            echo $matches[1] . "\n";
                            
                            $patchedFiles[$matches[1]] = $matches[1];
                        }
                   }
                }
            } else {
                echo "Patch file not found \n";
                return;
            }
            
            echo "\n\n";
            echo "Check Local Overwrites \n";
            foreach($patchedFiles as $patchedFile){
                if(preg_match('/app\/code\/core\/Mage/',$patchedFile)){
                    $this->checkLocalOverwrite($patchedFile);
                }
            }
    
            echo "\n\n";    
            echo "Check Rewrites \n";
            foreach($patchedFiles as $patchedFile){
                if(preg_match('/.php/',$patchedFile) && preg_match('/app\/code\/core\/Mage/',$patchedFile)){
                    $this->checkRewrites($patchedFile);
                }
            }
            echo "\n\n";
            
		} else {
            echo "Add Patch Filename. php patchHelper.php --patch PATCH_SUPEE-8788_CE_1.9.0.1_v1-2016-10-11-06-57-03.sh \n";
        }
        
    }
    
    protected function getAppCodeLocalFolderPath(){
        if(!$this->appCodeLocalPath){
            $this->appCodeLocalPath = Mage::getBaseDir('app');
        }
        return $this->appCodeLocalPath;
    }
    
    protected function checkLocalOverwrite($filename){
            
        $localOverwriteFilename = Mage::getBaseDir('app') . str_replace('app/code/core/Mage','/code/local/Mage',$filename);

        if(file_exists($localOverwriteFilename)){
            echo $localOverwriteFilename . "\n";
        }
        
        $this->getAppCodeLocalFolderPath();
    }
    
    protected function getClassNameFromFile($filename){

        $className  = str_replace('/','_',str_replace(array('app/code/core/','.php'),'',$filename));
        
        if(preg_match('/_controllers_/',$className)){
            $className = str_replace('_controllers','',$className);
        }
        
        return $className;

    }
    
    protected function checkRewrites($filename){
        
        $className = $this->getClassNameFromFile($filename);
        
        $rewrites = $this->getRewrites();
        
        if(isset($rewrites[$className])){
            foreach($rewrites[$className] as $rewriteClass){
                echo $rewriteClass['module_name'] . ' -> ' . $rewriteClass['rewrite_class'] . "\n";
            }
        }
        
    }
    
    protected function getRewritesArray(){
        if(!$this->rewrites){
            $config = Mage::getModel('toolbox/mage_core_config')->init();
            $this->rewrites = $config->getRewrites();
        }
        return $this->rewrites;
    }
    
    protected function getRewrites(){

        if(!$this->rewritesFlat){
            
            $rewrites = $this->getRewritesArray();
            
            $rewritesFlatList = array();
            
            foreach($rewrites as $rewriteType=>$rewritesForType){
                foreach($rewritesForType as $coreClass=>$rewritesForCoreClass){
                    
                    if($rewriteType=='helpers'){
                        $type = 'Helper';
                    }
                    
                    if($rewriteType=='models'){
                        $type = 'Model';
                    }
                    
                    if($rewriteType=='blocks'){
                        $type = 'Block';
                    }
                    
                    $coreClassArray = explode('_',$coreClass);
                    $coreClassUpperCasedArray = array();
                    
                    $partCount = 1;
                    
                    foreach($coreClassArray as $coreClassPart){
                        $coreClassUpperCasedArray[] = ucfirst($coreClassPart);
                        
                        if($partCount==1){
                            $coreClassUpperCasedArray[] = $type;
                        }
                        
                        $partCount++;
                    }
                    
                    $coreClassUpperCased = implode('_',$coreClassUpperCasedArray);           
                    $rewritesFlatList['Mage_'.$coreClassUpperCased] = $rewritesForCoreClass;
                }
            }
        
            $this->rewritesFlat = $rewritesFlatList;    
        }

        return $this->rewritesFlat;
    }
    
}

$shell = new Mage_Shell_PatchHelper();
$shell->run();    