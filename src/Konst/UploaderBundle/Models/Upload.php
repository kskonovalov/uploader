<?php
/**
 * Created by PhpStorm.
 * User: konstantin
 * Date: 24.5.16
 * Time: 11.40
 */

namespace Konst\UploaderBundle\Models;

use Konst\UploaderBundle\Entity\UserFile;
use Konst\UploaderBundle\Entity\FilesOnServers;
use \Doctrine\ORM\EntityManager;

class Upload
{
    /**
     * function ask for file validation and if it passes adds it to upload queue
     * @param UserFile                    $file
     * @param \Doctrine\ORM\EntityManager $em
     * @param                             $rabbitMqProducer
     * @param                             $fileUploadRules
     * @param                             $serversToUpload
     * @return array
     */
    public static function processFile(UserFile $file, EntityManager $em, 
        $rabbitMqProducer, $fileUploadRules, $serversToUpload)
    {
        $notifications = [];
        $filesIDsOnServers = [];
        
        //file validation
        $fileValidation = self::fileValidation($file, $fileUploadRules);
        $fileIsValid = $fileValidation["fileValid"];
        $notifications = array_merge($notifications, $fileValidation["notifications"]);

        if($fileIsValid) {
            $em->persist($file);
            $em->flush();
            $notifications[] = "File was uploaded successfully";

            //sending upload task to ftp

            foreach($serversToUpload as $server) {
                $fileMessageToQueue = self::generateMessageToQueue($em, $file, $server);

                //adding message to rabbitmq queue
                $rabbitMqProducer->publish(serialize($fileMessageToQueue["messageToQueue"]));
                
                $filesIDsOnServers[] = $fileMessageToQueue["fileIdOnServer"];
            }
        }
        else {
            $notifications[] = "File didn't uploaded because of validation reasons";
        }
        return [
            "fileIsValid" => $fileIsValid,
            "filesIDsOnServers" => $filesIDsOnServers,
            "notifications" => $notifications
        ];
    }

    /**
     * Function generates message to add in rabbitmq queue
     * @param EntityManager $em
     * @param UserFile      $file
     * @param               $server
     * @return array
     */
    public static function generateMessageToQueue(EntityManager $em, UserFile $file, $server)
    {
        //insert file upload info
        $fileOnServer = new FilesOnServers();
        $fileOnServer->setFilename($file->getSavedName());
        $fileOnServer->setServer(serialize($server));
        $fileOnServer->setStatus("NEW_FILE");
        $fileOnServer->setDateUpdated(new \DateTime('now'));
        $em->persist($fileOnServer);
        $em->flush();

        $fileIdOnServer = $fileOnServer->getId();

        //generate message to rabbitmq
        $msg = array(
            'savedName' => $file->getSavedName(),
            'path' => $file->getFile()->getPath(),
            'server' => $server,
            'fileOnServerId' => $fileIdOnServer);

        return [
            "messageToQueue" => $msg,
            "fileIdOnServer" => $fileIdOnServer
        ];
    }
    
    /**
     * function validates file entity with rules
     * @param UserFile $file
     * @param          $fileUploadRules
     * @return bool
     */
    public static function fileValidation(UserFile $file, $fileUploadRules)
    {
        $fileValid = false;
        $notifications = [];

        foreach($fileUploadRules as $rule) {
            //$rule["format"]
            //$rule["max"]
            //$rule["stopwords"]

            //if all rules passes, file is valid
            //check file extension first
            if(self::validateFormat($file, $rule["format"])) {
                //checking "max" rule
                if(isset($rule["max"])) {
                    $validateFileSize = self::validateSize($file, $rule["max"]);
                    if(!$validateFileSize["fileValid"]) {
                        continue;
                    }
                }

                //checking "stopwords" rule
                if(isset($rule["stopwords"])) {
                    $validateStopWords = self::validateStopWords($file, $rule["stopwords"]);

                    $fileValid = $validateStopWords["fileValid"];
                    $notifications = array_merge($notifications, $validateStopWords["notifications"]);
                }
                else { //file format good, max size didn't set
                    $fileValid = true;
                }

                //file passes tests for this rule group
                if($fileValid)
                    break;
            }

        }
        return Array("fileValid" => $fileValid, "notifications" => $notifications);
    }

    /**
     * @param UserFile $file
     * @param          $format
     * @return bool
     */
    public static function validateFormat(UserFile $file, $format)
    {
        if($format == pathinfo($file->getOriginalName(), PATHINFO_EXTENSION)) {
            $fileValid = true;
        }
        else {
            $fileValid = false;
        }
        return $fileValid;
    }

    /**
     * @param UserFile $file
     * @param          $maxSize
     * @return bool
     */
    public static function validateSize(UserFile $file, $maxSize)
    {
        $notifications = [];
        if($maxSize >= $file->getFile()->getSize()) {
            $fileValid = true;
        }
        else {
            $fileValid = false;
            $notifications[] = "Problem with file size";
        }
        return Array("fileValid" => $fileValid, "notifications" => $notifications);
    }

    /**
     * @param UserFile $file
     * @param          $stopWordsFileName
     * @return array
     */
    public static function validateStopWords(UserFile $file, $stopWordsFileName)
    {
        $fileValid = true;
        $notifications = [];
        $pathToStopWords = __DIR__ . "/../Resources/config/stopwords/" . $stopWordsFileName;
        $stopWordsFile = fopen($pathToStopWords, "r");
        if ($stopWordsFile) {
            //get stop words array
            $stopWordsArray = [];
            while (($line = fgets($stopWordsFile)) !== false) {
                // process the line read.
                $stopWordsArray[] = trim($line);
            }
            fclose($stopWordsFile);

            //check for forbidden lines
            $splFile = $file->getFile()->openFile();
            while (!$splFile->eof()) {
                $currentFileString = $splFile->fgets();
                foreach($stopWordsArray as $stopWord) {
                    if(stristr($currentFileString, $stopWord)) {
                        $fileValid = false;
                        $notifications[] = "File upload cancelled because stop word \"{$stopWord}\" found";
                        //to break all checks, change to "break 3";
                        break;
                    }
                }
            }
        } else {
            // error opening the file.
            $fileValid = false;
            $notifications[] = "Can't open file with stop words";
        }
        return Array("fileValid" => $fileValid, "notifications" => $notifications);
    }
}