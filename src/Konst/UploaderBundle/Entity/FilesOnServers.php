<?php

namespace Konst\UploaderBundle\Entity;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * FilesOnServers
 */
class FilesOnServers
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $server;

    /**
     * @var \DateTime
     */
    private $dateUpdated;

    /**
     * status list
     * @var array
     */
    private $statusArray = [
        "NEW_FILE" => 0,
        "UPLOADING" => 1,
        "FILE_UPLOADED" => 2,
        "CONNECT_ERROR" => 3,
        "UPLOAD_ERROR" => 4,
        "OTHER_ERROR" => 5
    ];
    
    /**
     * @var string
     */
    private $status;
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return FilesOnServers
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set server
     *
     * @param string $server
     *
     * @return FilesOnServers
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server
     *
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set dateUpdated
     *
     * @param \DateTime $dateUpdated
     *
     * @return FilesOnServers
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get dateUpdated
     *
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return FilesOnServers
     */
    public function setStatus($status)
    {
        if(array_key_exists($status, $this->statusArray))
            $this->status = $this->statusArray[$status];
        else throw new Exception(
            "Status {$status} not found in statusArray " .
            http_build_query($this->statusArray, "", ", ")
        );

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        if(in_array($this->status, $this->statusArray))
            return array_search($this->status, $this->statusArray);
        else throw new Exception(
            "Status {$this->status} not found in statusArray " .
            http_build_query($this->statusArray, "", ", ")
        );
    }
}

