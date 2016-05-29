<?php
/**
 * Created by PhpStorm.
 * User: konstantin
 * Date: 25.5.16
 * Time: 17.02
 */

namespace Konst\UploaderBundle\Models;

use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use \Doctrine\ORM\EntityManager;
use Konst\UploaderBundle\Entity\FilesOnServers;

/**
 * Class UploadConsumer
 *
 * @package Konst\UploaderBundle\Models
 */
class UploadConsumer
{
    /**
     * @param               $msgBody
     * @param               $fileOnServer
     * @param EntityManager $em
     * @return bool
     */
    public static function fileUpload($msgBody, FilesOnServers $fileOnServer, EntityManager $em)
    {
        $server = $msgBody["server"];
        $server["access"] = explode("/", $server["access"]);
        
        //not a new file.. we tried to upload it earlier but with no result
        if ($fileOnServer->getStatus() != "NEW_FILE") {
            $fileOnServer->setStatus("OTHER_ERROR");
            $fileOnServer->setDateUpdated(new \DateTime('now'));
            $em->persist($fileOnServer);
            $em->flush();
        }

        $fileOnServer->setStatus("UPLOADING");
        $fileOnServer->setDateUpdated(new \DateTime('now'));
        $em->persist($fileOnServer);
        $em->flush();
        
        $fileSystem = self::getFileSystem($server);

        if($fileSystem) {
            //if file doesn't already exists
            if (!$fileSystem->has($msgBody["savedName"])) {
                $stream = fopen($msgBody["path"] . "/" . $msgBody["savedName"], 'r+');
                $fileSystem->writeStream($msgBody["savedName"], $stream);
                fclose($stream);
                $fileOnServer->setStatus("FILE_UPLOADED");
                $uploadStatus = true;
            } else {
                $fileOnServer->setStatus("UPLOAD_ERROR");
                $uploadStatus = false;
            }
        } else {
            $fileOnServer->setStatus("CONNECT_ERROR");
            $uploadStatus = false;
        }

        $fileOnServer->setDateUpdated(new \DateTime('now'));
        $em->persist($fileOnServer);
        $em->flush();
        return $uploadStatus;
    }

    /**
     * function returns file system object
     * @param $server
     * @return bool|Filesystem
     */
    public static function getFileSystem($server)
    {
        //common settings for both FTP and SFTP
        $serverConfig = [
            'host' => $server["host"],
            'username' => $server["access"][0],
            'password' => $server["access"][1],
            'port' => isset($server["port"]) ? $server["port"] : 21,
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
            $filesystem = false;
        }
        return $filesystem;
    }
}