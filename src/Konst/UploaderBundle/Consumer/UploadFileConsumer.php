<?php

namespace Konst\UploaderBundle\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use \Doctrine\ORM\EntityManager;
use Konst\UploaderBundle\Models\UploadConsumer;

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

        $msgBody = unserialize($msg->body);

        $fileOnServer = $this->em
            ->getRepository('KonstUploaderBundle:FilesOnServers')
            ->find($msgBody["fileOnServerId"]);

        //nothing to upload
        if ($fileOnServer) {
            $uploadStatus = UploadConsumer::fileUpload($msgBody, $fileOnServer, $this->em);
        }
        else
            $uploadStatus = false;

        if (!$uploadStatus) {
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