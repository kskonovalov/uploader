<?php

namespace Konst\UploaderBundle\Entity;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

error_reporting(E_ALL ^E_NOTICE ^E_DEPRECATED );
ini_set('display_errors',1);
error_reporting(E_ALL);

/**
 * File
 */
class UserFile
{
    /**
     * @var int
     */
    private $id;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @var File
     */
    private $file;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var string
     */
    private $originalName;

    /**
     * @var string
     */
    private $savedName;

    /**
     * @var string
     */
    private $path;


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
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return File
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;
        if ($file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
            /*
             * чую что тут какой-то костыль, но как ..?
             * почему setFile вызывается два раза, один раз для $file как instanceof UploadedFile
             * и второй для $file как instanceof File?
             */
            if($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) 
            {
                $originalName = $file->getClientOriginalName();
                $this->setOriginalName($originalName);
                //TODO: и как получить папку из конфига?
                $path = "uploads";
                $this->setPath($path);
            }
            
        }

        return $this;
    }


    /**
     * @param ExecutionContextInterface $context
     */
    public function validate(ExecutionContextInterface $context)
    {
        //TODO: here validation rules must looking good
        //but i don't know how to access config.yml file_upload_rules variable from here =(
        
       // $recipient = $this->container->getParameter( 'konst_uploader_bundle.file_upload_rules' );
        /*
        if (! in_array($this->file->getMimeType(), array(
            'image/jpeg',
            'image/gif',
            'image/png',
            'video/mp4',
            'video/quicktime',
            'video/avi',
        ))) {
            $context
                ->buildViolation('Wrong file type (jpg,gif,png,mp4,mov,avi)')
                ->atPath('fileName')
                ->addViolation()
            ;
        }*/
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set originalName
     *
     * @param string $originalName
     *
     * @return File
     */
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Get originalName
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Set savedName
     *
     * @param string $savedName
     *
     * @return File
     */
    public function setSavedName($savedName)
    {
        $this->savedName = $savedName;

        return $this;
    }

    /**
     * Get savedName
     *
     * @return string
     */
    public function getSavedName()
    {
        return $this->savedName;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return File
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}

