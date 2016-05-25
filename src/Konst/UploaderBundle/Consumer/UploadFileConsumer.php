<?php

namespace Konst\UploaderBundle\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use \Doctrine\ORM\EntityManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Adapter\Ftp as FtpAdapter;

class UploadFileConsumer
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * UploadFileConsumer constructor.
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     */
    public function execute(AMQPMessage $msg)
    {
        //hope all will work good
        $currentSuccess = true;
        $filesystem = false;

        $msgBody = unserialize($msg->body);

        $server = $msgBody["server"];
        $server["access"] = explode("/", $server["access"]);

        $fileOnServer = $this->em
            ->getRepository('KonstUploaderBundle:FilesOnServers')
            ->find($msgBody["fileOnServerId"]);

        if (!$fileOnServer) {
            //nothing to upload
            return true;
        }
        
        //not a new file.. we tried to upload it but with no result
        if ($fileOnServer->getStatus() != "NEW_FILE") {
            $fileOnServer->setStatus("OTHER_ERROR");
            $fileOnServer->setDateUpdated(new \DateTime('now'));
            $this->em->persist($fileOnServer);
            $this->em->flush();
        }

        $fileOnServer->setStatus("UPLOADING");
        $fileOnServer->setDateUpdated(new \DateTime('now'));
        $this->em->persist($fileOnServer);
        $this->em->flush();

        //common settings for both FTP and SFTP
        $serverConfig = [
            'host' => $server["host"],
            'username' => $server["access"][0],
            'password' => $server["access"][1],
            'port' => $server["port"],
            'root' => $server["path"],
            'timeout' => 10,
        ];
        
        if($server["type"] == "FTP") {
            $filesystem = new Filesystem(new FtpAdapter([
                array_merge($serverConfig, [
                    'passive' => true,
                    'ssl' => true,
                ])
            ]));
        }
        else if($server["type"] == "SFTP") {
            $filesystem = new Filesystem(new SftpAdapter([
                array_merge($serverConfig, [
                    'directoryPerm' => 0755,
                ])
            ]));
        }
        else {
            $fileOnServer->setStatus("CONNECT_ERROR");
            $currentSuccess = false;
        }

        if($currentSuccess) {
            //if file doesn't already exists
            if (!$filesystem->has($msgBody["savedName"])) {
                $stream = fopen($msgBody["path"] . "/" . $msgBody["savedName"], 'r+');
                $filesystem->writeStream($msgBody["savedName"], $stream);
                fclose($stream);
                $fileOnServer->setStatus("FILE_UPLOADED");
            } else {
                $fileOnServer->setStatus("UPLOAD_ERROR");
                $currentSuccess = false;
            }
        }

        $fileOnServer->setDateUpdated(new \DateTime('now'));
        $this->em->persist($fileOnServer);
        $this->em->flush();

        if (!$currentSuccess) {
            // If your file upload failed due to a temporary error you can return false
            // from your callback so the message will be rejected by the consumer and
            // requeued by RabbitMQ.
            // Any other value not equal to false will acknowledge the message and remove it
            // from the queue
            return false;
        }
        return true;
    }
}