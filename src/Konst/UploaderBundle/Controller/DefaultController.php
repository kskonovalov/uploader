<?php

namespace Konst\UploaderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Konst\UploaderBundle\Form\Type\UserFileType;
use Konst\UploaderBundle\Entity\UserFile;
use Konst\UploaderBundle\Entity\FilesOnServers;
use Konst\UploaderBundle\Models\Upload;


class DefaultController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        return $this->render('KonstUploaderBundle:Default:index.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function uploadAction(Request $request)
    {
        $messages = [];
        $filesOnServers = [];

        //create entity
        $file = new UserFile();

        $form = $this->createForm(UserFileType::class, $file);
        $form->handleRequest($request);

        //standart validation
        if ($form->isValid()) {

            //get rules for file validation
            $fileUploadRules = $this->container->getParameter( 'konst_uploader_bundle.file_upload_rules' );

            //file validation
            $fileValidation = Upload::fileValidation($file, $fileUploadRules);
            $fileIsValid = $fileValidation["fileValid"];
            $messages = array_merge($messages, $fileValidation["messages"]);
            if($fileIsValid) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($file);
                $em->flush();
                $messages[] = "File was uploaded successfully";

                //sending upload task to ftp
                $serversToUpload = $this->container->getParameter( 'konst_uploader_bundle.servers_list' );

                foreach($serversToUpload as $server) {
                    //insert file upload info
                    $fileOnServer = new FilesOnServers();
                    $fileOnServer->setFilename($file->getSavedName());
                    $fileOnServer->setServer(serialize($server));
                    $fileOnServer->setStatus("NEW_FILE");
                    $fileOnServer->setDateUpdated(new \DateTime('now'));
                    $em->persist($fileOnServer);
                    $em->flush();
                    
                    $fileIdOnServer = $fileOnServer->getId();

                    //send to rabbitmq
                    $msg = array(
                        'savedName' => $file->getSavedName(), 
                        'path' => $file->getFile()->getPath(), 
                        'server' => $server,
                        'fileOnServerId' => $fileIdOnServer);

                    $filesOnServers[] = $fileIdOnServer;
                    
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
            'filesOnServers' => $filesOnServers,
        ));
    }

    /**
     * return json-encoded uploaded file status from DB
     * @param Request $request
     * @return Response
     */
    public function showStatusAction (Request $request)
    {
        $em = $this->getDoctrine()->getRepository('KonstUploaderBundle:FilesOnServers')
            ->find($request->get('fileId'));

        $result = [];
        $result["server"] = $em->getServer();
        $result["status"] = $em->getStatus();

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->setStatusCode(Response::HTTP_OK);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
