<?php

use \Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "cache")]
class Cache {

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(type: "string")]
    protected $url;

    #[ORM\Column(type: "string")]
    protected $file_id;

    public function getFile_id()
    {
        return $this->file_id;
    }

    public function setFile_id($file_id)
    {
        $this->file_id = $file_id;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}