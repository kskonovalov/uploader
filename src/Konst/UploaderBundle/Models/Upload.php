<?php
/**
 * Created by PhpStorm.
 * User: konstantin
 * Date: 24.5.16
 * Time: 11.40
 */

namespace Konst\UploaderBundle\Models;

use Konst\UploaderBundle\Entity\UserFile;

class Upload
{
    /**
     * function validates file entity with rules
     * @param UserFile $file
     * @param          $fileUploadRules
     * @return bool
     */
    public static function fileValidation(UserFile $file, $fileUploadRules)
    {
        $fileValid = false;
        $messages = [];

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
                    $messages = array_merge($messages, $validateStopWords["messages"]);
                }
                else { //file format good, max size didn't set
                    $fileValid = true;
                }

                //file passes tests for this rule group
                if($fileValid)
                    break;
            }

        }
        return Array("fileValid" => $fileValid, "messages" => $messages);
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
        $messages = [];
        if($maxSize >= $file->getFile()->getSize()) {
            $fileValid = true;
        }
        else {
            $fileValid = false;
            $messages[] = "Problem with file size";
        }
        return Array("fileValid" => $fileValid, "messages" => $messages);
    }

    /**
     * @param UserFile $file
     * @param          $stopWordsFileName
     * @return array
     */
    public static function validateStopWords(UserFile $file, $stopWordsFileName)
    {
        $fileValid = true;
        $messages = [];
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
                        $messages[] = "File upload cancelled because stop word \"{$stopWord}\" found";
                        //to break all checks, change to "break 3";
                        break;
                    }
                }
            }
        } else {
            // error opening the file.
            $fileValid = false;
            $messages[] = "Can't open file with stop words";
        }
        return Array("fileValid" => $fileValid, "messages" => $messages);
    }
}