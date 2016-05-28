<?php

namespace Konst\UploaderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Konst\UploaderBundle\Form\Type\UserFileType;
use Konst\UploaderBundle\Entity\UserFile;
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
        $notifications = [];
        $filesIDsOnServers = [];

        //create entity
        $file = new UserFile();

        $form = $this->createForm(UserFileType::class, $file);
        $form->handleRequest($request);

        //standart validation
        if ($form->isValid()) {
            //get rules for file validation
            $fileUploadRules = $this->container->getParameter('konst_uploader_bundle.file_upload_rules');
            //get rules for ftp servers to upload on
            $serversToUpload = $this->container->getParameter('konst_uploader_bundle.servers_list');
            //get rabbitMQ file producer
            $rabbitMqProducer = $this->get('old_sound_rabbit_mq.upload_file_producer');
            //get entity manager
            $em = $this->getDoctrine()->getManager();

            //process file
            $fileResult = Upload::processFile($file, $em, $rabbitMqProducer, $fileUploadRules,
                $serversToUpload);

            //notifications for users if something worked wrong
            $notifications = array_merge($notifications, $fileResult["notifications"]);
            //id of a file on each server
            $filesIDsOnServers = array_merge($filesIDsOnServers, $fileResult["filesIDsOnServers"]);
        }
        else {
            //some errors with form validation
        }

        return $this->render('KonstUploaderBundle:Uploader:form.html.twig', array(
            'form' => $form->createView(),
            'notifications' => $notifications,
            'filesOnServers' => $filesIDsOnServers,
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
