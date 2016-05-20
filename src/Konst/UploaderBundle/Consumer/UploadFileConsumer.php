<?php

namespace Konst\UploaderBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Adapter\Ftp as Adapter;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class UploadFileConsumer
{

    private $em;

    public function __construct(\Doctrine\ORM\EntityManager $entityManager){

        $this->em = $entityManager;

    }

    public function execute(AMQPMessage $msg)
    {
        //we hope all will work good
        $isUploadSuccess = true;

        $msgBody = unserialize($msg->body);

        $server = $msgBody["server"];
        $server["access"] = explode("/", $server["access"]);

        $fileOnServer = $this->em
            ->getRepository('KonstUploaderBundle:FilesOnServers')
            ->find($msgBody["fileOnServerId"]);

        if (!$fileOnServer) {

            throw $this->createNotFoundException(
                'No info for file found in uploading status table '
            );

        }
        
        //not a new file.. we tried to upload it but with no result
        if($fileOnServer->getStatus() != "NEW_FILE") {
            
            $fileOnServer->setStatus("OTHER_ERROR");
            $fileOnServer->setDateUpdated(new \DateTime('now'));
            $this->em->persist($fileOnServer);
            $this->em->flush();
            
            return false;
            
        }
            

        $fileOnServer->setStatus("UPLOADING");
        $fileOnServer->setDateUpdated(new \DateTime('now'));
        $this->em->persist($fileOnServer);
        $this->em->flush();
        
        if($server["type"] == "FTP") {

            $filesystem = new Filesystem(new Adapter([
                'host' => $server["host"],
                'username' => $server["access"][0],
                'password' => $server["access"][1],

                /** optional config settings */
                'port' => $server["port"],
                'root' => $server["path"],
                'passive' => true,
                'ssl' => true,
                'timeout' => 30,
            ]));

        }
        else if($server["type"] == "SFTP") {

            $filesystem = new Filesystem(new SftpAdapter([
                'host' => $server["host"],
                'port' => $server["port"],
                'username' => $server["access"][0],
                'password' => $server["access"][1],
//                'privateKey' => null,
                'root' => $server["path"],
                'timeout' => 10,
                'directoryPerm' => 0755
            ]));

        }
        else {

            $fileOnServer->setStatus("CONNECT_ERROR");
            $isUploadSuccess = false;

        }

        if($isUploadSuccess) {
            //if file doesn't already exists!
            if (!$filesystem->has($msgBody["savedName"])) {

                $stream = fopen($msgBody["path"] . "/" . $msgBody["savedName"], 'r+');
                $filesystem->writeStream($msgBody["savedName"], $stream);
                fclose($stream);

                $fileOnServer->setStatus("FILE_UPLOADED");

            } else {

                $fileOnServer->setStatus("UPLOAD_ERROR");

                $isUploadSuccess = false;

            }
        }

        $fileOnServer->setDateUpdated(new \DateTime('now'));
        $this->em->persist($fileOnServer);
        $this->em->flush();

        if (!$isUploadSuccess) {
            // If your file upload failed due to a temporary error you can return false
            // from your callback so the message will be rejected by the consumer and
            // requeued by RabbitMQ.
            // Any other value not equal to false will acknowledge the message and remove it
            // from the queue
            return false;
        }
    }
}