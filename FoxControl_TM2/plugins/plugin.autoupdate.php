<?php
//* plugin.autoupdate.php - FoxControl Autoupdate
//* Version:   1.2
//* Coded by:  matrix142
//* Copyright: FoxRace, http://www.fox-control.de

/************************************************************
********************FOXCONTROL AUTOUPDATE********************
*****************DO NOT CHANGE ANYTHING HERE*****************
*****************OR YOUR DATA COULD GET LOST*****************
************************************************************/


class plugin_autoupdate extends FoxControlPlugin {
	public $filename;
	public $dirName;

	public function onStartUp() {
		$this->name = 'Autoupdate';
		$this->author = 'matrix142';
		$this->version = '1.2';
	}
	
	/*
		START UPDATE
	*/
	public function startUpdate() {
		//Check FoxControl Version
		$content = file_get_contents('http://fox.global-rebels.de/autoupdate/checkVersionTM2.php?version='.FOXC_VERSION);
		
		console('Checking if update file is available...');
		
		if($content != '0' && $content != "") {					
			$this->filename = $content;
		
			//Check if download is successful
			if($zipFile = file_get_contents('http://fox.global-rebels.de/autoupdate/files/'.$this->filename, 'r')) {
				file_put_contents(getcwd().'/FoxControl_Update_Newest.zip', $zipFile);
				console('Update file downloaded!');
				
				$this->createBackup();
				
			//End Update if not successful
			} else {
				console('Can\'t download Update file. Ending Update!');
				$this->chat('$fff[$06fA$fffuto$06fU$fffpdater] Can\'t download Update file. Ending Update!');
			}
			
		//End Update if can't find update file
		} else {
			console('No Update file available!');
			$this->chat('$fff[$06fA$fffuto$06fU$fffpdater] No Update file available. Ending Update!');
		}
	}
	
	/*
		CREATE BACKUP
	*/
	private function createBackup() {
		//Backup FoxControl Directory
		console('Starting Backup...');

		$this->dirName = getcwd().'/'.'_Backup_'.date('d.m.Y', time());
		if($this->copyDirectory(getcwd(), $this->dirName)) {
			console('Backup complete!');
			
			$this->unpackArchive();
		} else {
			console('Backup failed!');
			$this->chat('$fff[$06fA$fffuto$06fU$fffpdater] Backup failed. Ending update!');
		}
	}
	
	/*
		UNPACK ARCHIVE
	*/
	private function unpackArchive() {
		console('Unpacking new FoxControl version...');

		//Unpacking .zip Archive
		$file = 'FoxControl_Update_Newest.zip';
		$zip = new ZipArchive; 
		if($zip->open(getcwd().'/'.$file)) {
			$zip->extractTo(getcwd()); 
			
			if($zip->close()) {
				console("Unpacking complete!");
				$this->rebuildConfig();
			} else {
				console("Unable to unpack $file!"); 
				$this->chat('$fff[$06fA$fffuto$06fU$fffpdater] Can\'t unpack Update file. Ending Update!');
			}
		} else { 
			console("Unable to unpack $file!"); 
			$this->chat('$fff[$06fA$fffuto$06fU$fffpdater] Can\'t unpack Update file. Ending Update!');
		}
	}
	
	/*
		REBUILD CONFIG
	*/
	private function rebuildConfig() {
		//Rebuild Main Config file
		$configData = $this->readXML_File($this->dirName.'/config.xml');
		$newXML = simplexml_load_file('config.xml');
		foreach($configData as $key => $value) {
			$oldXML = simplexml_load_file($this->dirName.'/config.xml');			
			$oldValue = $oldXML->$key;
	
			$xmlobj = $newXML->xpath('/FoxControl_config');
			$xmlobj[0]->{$key} = htmlspecialchars($oldValue);
		}

		$newXMLFile = $newXML->asXML();
		file_put_contents('config.xml', $newXMLFile);

		console('Update successful!');
		$this->chat('$fff[$06fA$fffuto$06fU$fffpdater] Update successful. Rebooting FoxControl...');
		
		$this->instance()->FoxControl_reboot();
		unlink('FoxControl_Update_Newest.zip');
	}
	
	/*
		COPY DIRECTORY
	*/
	private function copyDirectory($srcdir, $dstdir, $verbose = false) {
		$num = 0;
		if(!is_dir($dstdir)) mkdir($dstdir);
  
		if($curdir = opendir($srcdir)) {
			while($file = readdir($curdir)) {
				if($file != '.' && $file != '..') {
					$srcfile = $srcdir . '/' . $file;
					$dstfile = $dstdir . '/' . $file;
			
					if(is_file($srcfile)) {
						if(is_file($dstfile)) $ow = filemtime($srcfile) - filemtime($dstfile); else $ow = 1;
				
						if($ow > 0) {
							if($verbose) console("Copying '$srcfile' to '$dstfile'...");
					
							if(copy($srcfile, $dstfile)) {
								touch($dstfile, filemtime($srcfile)); $num++;
								if($verbose) echo "OK\n";
							}
							else {
								console("Error: File '$srcfile' could not be copied!");
								return false;
							}
						}                 
					}
					else if(is_dir($srcfile)) {
						if($file != (str_replace(getcwd().'/', '', $dstdir))) {
							$num += $this->copyDirectory($srcfile, $dstfile, $verbose);
						}
					}
				}
			}
		
			closedir($curdir);
		}
	
		return true;
	}

	/*
		READ XML FILE
	*/
	private function readXML_File($filename) {
		$dom = new DomDocument ();
		$array = array ();
    
		if (!@$dom->load ($filename)) {
			console('Error: Can\'t load '.$filename);
		}

		$firstLevelNodes = $dom->getElementsByTagName ("FoxControl_config")->item (0)->childNodes;
    
		foreach ($firstLevelNodes as $categories) {
			if ($categories->nodeType !== 1) continue;
			$array [$categories->nodeName] = array ();
			$settings = $categories->childNodes;
        
			foreach ($settings as $setting) {
				if ($setting->nodeType !== 1) continue;
				$array[$categories->nodeName][$setting->nodeName] = $setting->nodeValue;
			}
		} 
		
		return $array;
	}
}
?>