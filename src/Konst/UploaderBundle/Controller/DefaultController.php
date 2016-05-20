<?php

namespace Konst\UploaderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Konst\UploaderBundle\Form\Type\UserFileType;
use Konst\UploaderBundle\Entity\UserFile;
use Symfony\Component\HttpKernel\Log\LoggerInterface;


class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('KonstUploaderBundle:Default:index.html.twig');
    }

    public function uploadAction(Request $request) {

        $messages = [];
        //create entity
        $file = new UserFile();

        $form = $this->createForm(UserFileType::class, $file);
        
        $form->handleRequest($request);

        //standart validation
        if ($form->isValid()) {

            //additional file validation
            //TODO: logic must be in the model, i think
            $fileUploadRules = $this->container->getParameter( 'konst_uploader_bundle.file_upload_rules' );
            $fileValid = false;
            foreach($fileUploadRules as $rule) {
                //$rule["format"]
                //$rule["max"]
                //$rule["stopwords"]

                //if all rules passes, file is valid
                //check file extension first
                if($rule["format"] == pathinfo($file->getOriginalName(), PATHINFO_EXTENSION)) {

                    //checking "max" rule
                    if(isset($rule["max"])) {
                        if($rule["max"] >= $file->getFile()->getSize()) {
                            $fileValid = true;
                        }
                        else {
                            $fileValid = false;
                            continue;
                        }
                    }
                    else { //file format good, max size didn't set
                        $fileValid = true;
                    }

                    //checking "stopwords" rule
                    if(isset($rule["stopwords"])) {
                        //$kernel =  $container->getService('kernel');
                        $pathToStopWords = $this->get('kernel')->locateResource('@KonstUploaderBundle/Resources/config/stopwords/'.$rule["stopwords"]);
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
                                        //to break all checks, change to break 3;
                                        break;
                                    }
                                }
                            }
                        } else {
                            // error opening the file.
                            $fileValid = false;
                            $messages[] = "Can't open file with stop words";
                        }
                    }
                    else { //file format good, max size didn't set
                        $fileValid = true;
                    }

                }

                //file passes tests for this rule group
                if($fileValid)
                    break;
            }

            if($fileValid) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($file);
                $em->flush();
                $messages[] = "File was uploaded successfully";

                //sending upload task to ftp
                $serversToUpload = $this->container->getParameter( 'konst_uploader_bundle.servers_list' );
                
                foreach($serversToUpload as $server) {
                    //send to rabbitmq
                    $msg = array(
                        'savedName' => $file->getSavedName(), 
                        'path' => $file->getFile()->getPath(), 
                        'server' => $server);
                    $this->get('old_sound_rabbit_mq.upload_file_producer')->publish(serialize($msg));
                }
            }
            else {
                $messages[] = "File didn't uploaded because of validation reasons";
            }
        }
        else {
            //some errors with form validation
        }

        return $this->render('KonstUploaderBundle:Uploader:form.html.twig', array(
            'form' => $form->createView(),
            'messages' => $messages,
        ));
    }

}
